<?php
// ============================================================
// config/bootstrap.php
// Loaded at the top of every API endpoint.
// Sets CORS headers, starts the session, and defines shared helpers.
// ============================================================

define('API_REQUEST', true);

// Conditionally load Composer autoload if it exists
$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Load environment variables manually if Dotenv is not available
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    } else {
        // Manual .env loading
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

header('Content-Type: application/json');

// ── CORS ──────────────────────────────────────────────────
// Allow any localhost origin (handles subdirectories and different ports).
// Restrict to your actual frontend origin in production.
$allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
$requestOrigin  = $_SERVER['HTTP_ORIGIN'] ?? '';
// Accept any localhost/127.0.0.1 origin (with or without port)
if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $requestOrigin)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

// Respond immediately to preflight (browser OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/response.php';

// Configure session cookie — secure flags tighten in production
$isProduction = ($_ENV['APP_ENV'] ?? 'local') === 'production';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => false,
    'secure'   => $isProduction,
    'httponly' => true,
    'samesite' => $isProduction ? 'Strict' : 'Lax',
]);

// Start session before any output (headers already sent above, which is fine
// because Content-Type is not a Set-Cookie header and sessions use cookies)
session_start();

// ── Auth helpers ──────────────────────────────────────────

/**
 * Abort with 401 if there is no active session.
 */
function requireAuth(): void {
    if (!isset($_SESSION['user'])) {
        sendError('Authentication required', 401);
    }
}

/**
 * Abort with 403 if the current user's role is not in the allowed list.
 * Call after requireAuth().
 *
 * @param string[] $roles  Allowed roles, e.g. ['Admin']
 */
function requireRole(array $roles): void {
    $userRole = $_SESSION['user']['role'] ?? '';
    if (!in_array($userRole, $roles, true)) {
        sendError('Access denied. Insufficient permissions.', 403);
    }
}

// ── CSRF helpers ──────────────────────────────────────────

/**
 * Generate (or return existing) CSRF token stored in the session.
 */
function generateCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Compare the supplied token against the session token using
 * a constant-time comparison to prevent timing attacks.
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Abort with 403 on state-changing requests that lack a valid CSRF token.
 *
 * Token lookup order:
 *   1. X-CSRF-Token request header  (used by fetch/XHR calls)
 *   2. csrf_token field in the JSON request body (fallback for multipart/form-data)
 */
function requireCsrf(): void {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    // 1. Check the X-CSRF-Token header first (preferred — no body parse needed)
    $headers = getallheaders();
    $token   = $headers['X-CSRF-Token'] ?? '';

    // 2. Fall back to the csrf_token field in the JSON body
    if ($token === '') {
        $body  = json_decode(file_get_contents('php://input'), true);
        $token = (is_array($body) ? ($body['csrf_token'] ?? '') : '');
    }

    if ($token === '' || !validateCsrfToken($token)) {
        sendError('Invalid or missing CSRF token', 403);
    }
}

// ── Input validation helpers ──────────────────────────────

/**
 * Abort with 400 if any of the required keys are missing or empty.
 *
 * @param array  $data     Associative array of input values.
 * @param array  $required List of keys that must be present and non-empty.
 */
function validateRequired(array $data, array $required): void {
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            sendError("Missing required field: {$field}");
        }
    }
}

// ── reCAPTCHA verification ──────────────────────────────────

/**
 * Verify a reCAPTCHA v2 token with Google's API.
 *
 * On localhost / XAMPP, curl cannot reach Google's HTTPS endpoint because
 * XAMPP ships without CA certificates. We detect this and bypass the
 * server-side check — the widget still has to be completed in the browser.
 *
 * Detection order:
 *  1. HTTP_HOST contains localhost or 127.0.0.1
 *  2. HTTPS is not set (plain HTTP = local dev)
 *  3. curl is not available
 *  4. The curl call itself fails (network unreachable)
 */
function verifyRecaptcha(string $token): bool {
    // Empty token always fails regardless of environment
    if ($token === '') {
        return false;
    }

    // ── Localhost / non-HTTPS bypass ──────────────────────
    $host    = strtolower($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    $isLocal = !$isHttps                                    // plain HTTP = local
            || $host === ''                                 // CLI / no host header
            || str_contains($host, 'localhost')
            || str_contains($host, '127.0.0.1')
            || str_contains($host, '::1');

    if ($isLocal) {
        // Widget was completed (token non-empty) — good enough for local dev
        return true;
    }

    // ── Production: verify with Google ───────────────────
    if (!function_exists('curl_init')) {
        error_log('reCAPTCHA: curl not available, skipping verification');
        return true; // fail-open if curl missing in production
    }

    $secretKey = '6LdTbcssAAAAAB89F9NK3vQZHI_C6unG9SI6zwk7';

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret'   => $secretKey,
        'response' => $token,
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $curlErr  = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If curl itself failed (network error, SSL error, timeout) fail-open
    // so a server-side network issue doesn't lock everyone out
    if ($curlErr || $httpCode !== 200 || !$response) {
        error_log('reCAPTCHA curl error ' . $curlErr . ' HTTP ' . $httpCode . ' — failing open');
        return true;
    }

    $result = json_decode($response, true);

    if (!isset($result['success']) || !$result['success']) {
        error_log('reCAPTCHA failed: ' . implode(', ', $result['error-codes'] ?? ['unknown']));
        return false;
    }

    return true;
}

/**
 * Validate and cast a value to a positive integer.
 * Aborts with 400 on invalid input.
 */
function validateInteger($value, string $field): int {
    if (!is_numeric($value) || (int)$value != $value) {
        sendError("Field '{$field}' must be a valid integer");
    }
    $intVal = (int)$value;
    if ($intVal <= 0) {
        sendError("Field '{$field}' must be a positive integer");
    }
    return $intVal;
}

/**
 * Validate and trim a string value.
 * Aborts with 400 if the value is not a string or exceeds the max length.
 */
function validateString($value, string $field, int $maxLength = 255): string {
    if (!is_string($value)) {
        sendError("Field '{$field}' must be a string");
    }
    $str = trim($value);
    if (strlen($str) > $maxLength) {
        sendError("Field '{$field}' exceeds maximum length of {$maxLength} characters");
    }
    return $str;
}

/**
 * Validate a date string in YYYY-MM-DD format.
 * Uses DateTime::createFromFormat for strict validation instead of
 * strtotime (which silently accepts invalid dates like 2026-13-01).
 * Aborts with 400 on invalid input.
 */
function validateDate($value, string $field): string {
    if (!is_string($value)) {
        sendError("Field '{$field}' must be a date string");
    }

    $dt = DateTime::createFromFormat('Y-m-d', $value);

    // createFromFormat can return a DateTime with "overflow" corrections;
    // comparing the formatted output to the input detects those cases.
    if (!$dt || $dt->format('Y-m-d') !== $value) {
        sendError("Field '{$field}' must be a valid date in YYYY-MM-DD format");
    }

    return $value;
}

// ── Request body helper ───────────────────────────────────

/**
 * Decode the raw JSON request body into an associative array.
 * Returns an empty array on parse failure (no exceptions thrown).
 */
function getRequestBody(): array {
    $body = file_get_contents('php://input');
    if ($body === false || $body === '') {
        return [];
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
}

// ── Shared domain validators ──────────────────────────────

/**
 * Validate password strength.
 * Returns an error string on failure, null on success.
 * Rules: 8+ chars, at least one uppercase, at least one digit.
 */
function validatePasswordStrength(string $password): ?string {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    return null;
}

/**
 * Validate a contact/phone number.
 * Returns an error string on failure, null on success.
 */
function validateContactNumber(string $contact): ?string {
    if ($contact === '') {
        return 'Contact number is required.';
    }
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $contact)) {
        return 'Please enter a valid contact number (7–20 digits).';
    }
    return null;
}
