<?php
// ============================================================
// config/database.php
// Singleton PDO connection.
// Credentials are loaded from the project-root .env file by
// bootstrap.php, which is always included before this file.
// ============================================================

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'esperondairyfarm');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

/**
 * Return a shared PDO connection (singleton pattern).
 * On failure: logs the real error internally, returns a generic
 * JSON error to the client, and exits — so no DB details are leaked.
 */
function getConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log full details internally — do NOT expose them to the client
        error_log('Database connection failed: ' . $e->getMessage());

        // If this is being called from a UI page (not an API endpoint),
        // throw so the caller can handle it gracefully instead of outputting JSON
        $calledFromApi = defined('API_REQUEST') && API_REQUEST === true;
        if (!$calledFromApi) {
            throw new RuntimeException('Database connection failed.');
        }

        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Unable to connect to the database. Please try again later.',
        ]);
        exit;
    }

    return $pdo;
}
