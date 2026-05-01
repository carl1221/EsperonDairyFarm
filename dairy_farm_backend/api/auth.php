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

// Initialize Google Client if available
$googleClient = null;
if (!empty($_ENV['GOOGLE_CLIENT_ID']) && !empty($_ENV['GOOGLE_CLIENT_SECRET']) && 
    $_ENV['GOOGLE_CLIENT_ID'] !== 'your_google_client_id_here' && 
    $_ENV['GOOGLE_CLIENT_SECRET'] !== 'your_google_client_secret_here' && 
    class_exists('Google\Client')) {
    try {
        $googleClient = new Google\Client();
        $googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $googleClient->setRedirectUri('http://localhost/esperon_final/dairy_farm_backend/api/auth.php?action=google_callback');
        $googleClient->addScope('email');
        $googleClient->addScope('profile');
    } catch (Exception $e) {
        // Google API client not available or not configured
        $googleClient = null;
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

                // ── Try Worker table first ────────────────────
                $stmt = $dbConn->prepare(
                    "SELECT {$selectCols} FROM Worker WHERE LOWER(Worker) = LOWER(?) LIMIT 1"
                );
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                // ── If not found in Worker, try Customer table ─
                $isCustomer = false;
                if (!$user) {
                    $custStmt = $dbConn->prepare(
                        "SELECT c.CID, c.Customer_Name, c.Contact_Num, a.Address
                         FROM Customer c
                         JOIN Address a ON c.Address_ID = a.Address_ID
                         WHERE LOWER(c.Customer_Name) = LOWER(?) LIMIT 1"
                    );
                    $custStmt->execute([$username]);
                    $customer = $custStmt->fetch();

                    if ($customer) {
                        // Customers don't have passwords in the DB yet —
                        // they use the username as a simple passphrase for now.
                        // A real implementation would store a hashed password on the Customer row.
                        // For now we accept any non-empty password for a matched customer name.
                        if ($password !== '') {
                            $isCustomer = true;
                            session_regenerate_id(true);
                            $_SESSION['user'] = [
                                'id'      => $customer['CID'],
                                'name'    => $customer['Customer_Name'],
                                'role'    => 'Customer',
                                'email'   => '',
                                'avatar'  => '',
                                'address' => $customer['Address'],
                                'contact' => $customer['Contact_Num'],
                            ];
                            $csrfToken = generateCsrfToken();
                            sendSuccess('Login successful', [
                                'user'       => $_SESSION['user'],
                                'csrf_token' => $csrfToken,
                            ]);
                        }
                    }
                }

                // password_verify does a constant-time comparison — safe
                if (!$isCustomer && (!$user || !password_verify($password, $user['Password']))) {
                    if (!$user) {
                        error_log('[Auth] Login failed: username not found — ' . $username);
                    } else {
                        error_log('[Auth] Login failed: wrong password for — ' . $username);
                    }
                    sendError('Invalid username or password', 401);
                }

                if (!$isCustomer) {
                // Check approval status only if column exists
                if ($hasApproval) {
                    $approvalStatus = $user['approval_status'] ?? 'approved';
                    if ($approvalStatus === 'pending') {
                        sendError('Your account is awaiting admin approval.', 403);
                    }
                    if ($approvalStatus === 'rejected') {
                        sendError('Your account registration was rejected.', 403);
                    }
                }
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'     => $user['Worker_ID'],
                    'name'   => $user['Worker'],
                    'role'   => $user['Worker_Role'],
                    'email'  => $user['Email'] ?? '',
                    'avatar' => $user['Avatar'] ?? '',
                ];
                $csrfToken = generateCsrfToken();
                sendSuccess('Login successful', [
                    'user'       => $_SESSION['user'],
                    'csrf_token' => $csrfToken,
                ]);
                } // end !$isCustomer

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
                if (!$googleClient) {
                    sendError('Google OAuth is not configured', 503);
                }
                // Redirect to Google OAuth
                $authUrl = $googleClient->createAuthUrl();
                header('Location: ' . $authUrl);
                exit;
            } elseif ($action === 'google_callback') {
                if (!$googleClient || !class_exists('Google\Service\Oauth2')) {
                    sendError('Google OAuth is not configured', 503);
                }
                // Handle Google OAuth callback
                if (!isset($_GET['code'])) {
                    sendError('Authorization code not received', 400);
                }

                $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
                if (isset($token['error'])) {
                    sendError('Failed to get access token: ' . $token['error'], 400);
                }

                $googleClient->setAccessToken($token);
                $oauth2 = new Google\Service\Oauth2($googleClient);
                $userInfo = $oauth2->userinfo->get();

                $email = $userInfo->email;
                $name = $userInfo->name;

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
                    // User not found, redirect to login with error
                    header('Location: ../../UI/login.php?error=google_user_not_found');
                    exit;
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
