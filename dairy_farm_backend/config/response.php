<?php
// ============================================================
// config/response.php
// Standardized JSON response helpers
// ============================================================

/**
 * Core response sender.
 * Always calls exit() so no code runs after a response is sent.
 */
function sendResponse(bool $success, string $message, $data = null, int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json');

    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

/**
 * Send a JSON error response.
 *
 * @param string $message  Human-readable error description.
 * @param int    $httpCode HTTP status code (default 400).
 * @param bool   $logError Write to PHP error log (default true).
 */
function sendError(string $message, int $httpCode = 400, bool $logError = true): void {
    if ($logError) {
        error_log("API Error [{$httpCode}]: {$message}");
    }
    sendResponse(false, $message, null, $httpCode);
}

/**
 * Send a JSON success response.
 *
 * @param string     $message  Human-readable success message.
 * @param mixed|null $data     Optional payload to include under "data".
 * @param int        $httpCode HTTP status code (default 200).
 */
function sendSuccess(string $message, $data = null, int $httpCode = 200): void {
    sendResponse(true, $message, $data, $httpCode);
}
