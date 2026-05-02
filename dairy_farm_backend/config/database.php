<?php
// ============================================================
// config/database.php
// Singleton PDO connection.
// Credentials are loaded from the project-root .env file by
// bootstrap.php, which is always included before this file.
// ============================================================

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'esperon_dairy_farm');
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
