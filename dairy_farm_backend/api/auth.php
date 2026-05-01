<?php


// ============================================================
// api/auth.php
// Authentication endpoints
//
// POST /api/auth.php?action=login   → verify credentials, start session
// POST /api/auth.php?action=logout  → destroy session
// GET  /api/auth.php?action=status  → check auth + return current user
// GET  /api/auth.php?action=google_login → initiate Google OAuth
// GET  /api/auth.php?action=google_callback → handle Google OAuth callback
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';

// ── Google OAuth Handler (without external dependencies) ──
class SimpleGoogleOAuth {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    
    public function __construct($clientId, $clientSecret, $redirectUri) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }
    
    /**
     * Get the authorization URL for redirecting user to Google
     */
    public function getAuthorizationUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'access_type' => 'offline',
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code) {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ];
        
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL issues
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get access token: HTTP ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get user info from Google using access token
     */
    public function getUserInfo($accessToken) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL issues
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get user info: HTTP ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
}

// ── Helper function to generate unique username ──
function generateUniqueUsername($name, $email) {
    // Start with the name, clean it up
    $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $name);
    $baseUsername = strtolower(substr($baseUsername, 0, 20)); // Limit length
    
    if (empty($baseUsername)) {
        // Fallback to email prefix if name is empty
        $baseUsername = strtolower(explode('@', $email)[0]);
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
    }
    
    $username = $baseUsername;
    $counter = 1;
    
    // Check if username exists and increment counter if needed
    while (true) {
        $stmt = getConnection()->prepare('SELECT COUNT(*) FROM Worker WHERE Worker = ?');
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            break; // Username is available
        }
        
        $username = $baseUsername . $counter;
        $counter++;
        
        // Prevent infinite loop
        if ($counter > 100) {
            $username = $baseUsername . '_' . time();
            break;
        }
    }
    
    return $username;
}

// Initialize Google OAuth if available
$googleOAuth = null;
if (!empty($_ENV['GOOGLE_CLIENT_ID']) && !empty($_ENV['GOOGLE_CLIENT_SECRET']) && 
    $_ENV['GOOGLE_CLIENT_ID'] !== 'your_google_client_id_here' && 
    $_ENV['GOOGLE_CLIENT_SECRET'] !== 'your_google_client_secret_here') {
    try {
        $googleOAuth = new SimpleGoogleOAuth(
            $_ENV['GOOGLE_CLIENT_ID'],
            $_ENV['GOOGLE_CLIENT_SECRET'],
            'http://localhost/esperon_final/dairy_farm_backend/api/auth.php?action=google_callback'
        );
    } catch (Exception $e) {
        error_log('Google OAuth initialization failed: ' . $e->getMessage());
        $googleOAuth = null;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {

        // ── Login ──────────────────────────────────────────
        case 'POST':
            if ($action === 'login') {
                // Login is the unauthenticated entry point — no CSRF token exists yet.
                // The token is issued to the client AFTER a successful login response.
                // All other state-changing endpoints call requireCsrf() via bootstrap.

                $data     = getRequestBody();
                $username = trim($data['username'] ?? '');
                $password = $data['password'] ?? '';
                $recaptchaToken = $data['g-recaptcha-response'] ?? '';

                if ($username === '' || $password === '') {
                    sendError('Username and password are required');
                }

                // Verify reCAPTCHA token
                if (!$recaptchaToken || !verifyRecaptcha($recaptchaToken)) {
                    sendError('reCAPTCHA verification failed. Please try again.', 400);
                }

                $dbConn = getConnection();

                // Check if approval_status column exists (migration may not have run yet)
                $cols       = $dbConn->query("SHOW COLUMNS FROM Worker")->fetchAll(PDO::FETCH_COLUMN);
                $hasApproval = in_array('approval_status', $cols);
                $selectCols  = 'Worker_ID, Worker, Worker_Role, Email, Avatar, Password'
                             . ($hasApproval ? ', approval_status' : '');

                // Fetch the worker record — use LOWER() on both sides for
                // case-insensitive match regardless of DB collation.
                $stmt = $dbConn->prepare(
                    "SELECT {$selectCols} FROM Worker WHERE LOWER(Worker) = LOWER(?) LIMIT 1"
                );
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                // password_verify does a constant-time comparison — safe
                if (!$user || !password_verify($password, $user['Password'])) {
                    // Log the specific failure reason internally (never expose to client)
                    if (!$user) {
                        error_log('[Auth] Login failed: username not found — ' . $username);
                    } else {
                        error_log('[Auth] Login failed: wrong password for — ' . $username);
                    }
                    sendError('Invalid username or password', 401);
                }

                // Check approval status only if column exists
                if ($hasApproval) {
                    $approvalStatus = $user['approval_status'] ?? 'approved';
                    // Only block if explicitly pending or rejected (null/empty = approved)
                    if ($approvalStatus === 'pending') {
                        sendError('Your account is awaiting admin approval.', 403);
                    }
                    if ($approvalStatus === 'rejected') {
                        sendError('Your account registration was rejected.', 403);
                    }
                }
                // Regenerate session ID on privilege escalation (login)
                // to prevent session fixation attacks
                session_regenerate_id(true);

                // Store minimal user info in the server-side session
                $_SESSION['user'] = [
                    'id'     => $user['Worker_ID'],
                    'name'   => $user['Worker'],
                    'role'   => $user['Worker_Role'],
                    'email'  => $user['Email'] ?? '',
                    'avatar' => $user['Avatar'] ?? '',
                ];

                // Issue a fresh CSRF token for this session
                $csrfToken = generateCsrfToken();

                sendSuccess('Login successful', [
                    'user'       => $_SESSION['user'],
                    'csrf_token' => $csrfToken,
                ]);

            } elseif ($action === 'logout') {

                // Wipe the session completely
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(
                        session_name(), '', time() - 42000,
                        $params['path'], $params['domain'],
                        $params['secure'], $params['httponly']
                    );
                }
                session_destroy();
                sendSuccess('Logged out successfully');

            } else {
                sendError('Invalid action', 400);
            }
            break;

        // ── Auth status check ──────────────────────────────
        case 'GET':
            if ($action === 'status') {
                if (isset($_SESSION['user'])) {
                    sendSuccess('Authenticated', [
                        'user'       => $_SESSION['user'],
                        'csrf_token' => generateCsrfToken(),
                    ]);
                } else {
                    sendError('Not authenticated', 401);
                }
            } elseif ($action === 'google_login') {
                if (!$googleOAuth) {
                    sendError('Google OAuth is not configured', 503);
                }
                // Redirect to Google OAuth
                $authUrl = $googleOAuth->getAuthorizationUrl();
                header('Location: ' . $authUrl);
                exit;
            } elseif ($action === 'google_callback') {
                if (!$googleOAuth) {
                    sendError('Google OAuth is not configured', 503);
                }
                // Handle Google OAuth callback
                if (!isset($_GET['code'])) {
                    sendError('Authorization code not received', 400);
                }

                try {
                    $tokenData = $googleOAuth->getAccessToken($_GET['code']);
                    if (!isset($tokenData['access_token'])) {
                        sendError('Failed to get access token', 400);
                    }

                    $userInfo = $googleOAuth->getUserInfo($tokenData['access_token']);
                    
                    $email = $userInfo['email'] ?? null;
                    $name = $userInfo['name'] ?? null;
                    
                    if (!$email) {
                        sendError('Could not retrieve email from Google account', 400);
                    }

                    // Check if user exists in database
                    $stmt = getConnection()->prepare(
                        'SELECT Worker_ID, Worker, Worker_Role, Email
                         FROM Worker
                         WHERE Email = ?
                         LIMIT 1'
                    );
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if (!$user) {
                        // User not found - auto-create account with Staff role
                        $username = generateUniqueUsername($name, $email);
                        
                        // Insert new worker
                        $insertStmt = getConnection()->prepare(
                            'INSERT INTO Worker (Worker, Worker_Role, Email, Password) 
                             VALUES (?, ?, ?, ?)'
                        );
                        $insertStmt->execute([$username, 'Staff', $email, '']);
                        
                        // Get the newly created user
                        $userId = getConnection()->lastInsertId();
                        $user = [
                            'Worker_ID' => $userId,
                            'Worker' => $username,
                            'Worker_Role' => 'Staff',
                            'Email' => $email
                        ];
                    }

                    // Regenerate session ID
                    session_regenerate_id(true);

                    // Store user info in session
                    $_SESSION['user'] = [
                        'id'    => $user['Worker_ID'],
                        'name'  => $user['Worker'],
                        'role'  => $user['Worker_Role'],
                        'email' => $user['Email'],
                    ];

                    // Redirect to dashboard
                    header('Location: ../../UI/index.php');
                    exit;
                } catch (Exception $e) {
                    error_log('Google OAuth callback error: ' . $e->getMessage());
                    sendError('Google OAuth error: ' . $e->getMessage(), 400);
                }
            } else {
                sendError('Invalid action', 400);
            }
            break;

        default:
            sendError('Method not allowed', 405);
    }

} catch (PDOException $e) {
    error_log('Auth PDOException: ' . $e->getMessage());
    sendError('A database error occurred. Please try again later.', 500);
}
