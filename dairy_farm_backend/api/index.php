<?php
// ============================================================
// api/index.php  —  Deprecation notice for unversioned API root
//
// All endpoints have moved to /api/v1/.
// Any request to /api/ (without a version prefix) lands here.
// ============================================================

http_response_code(410);
header('Content-Type: application/json');
header('X-API-Deprecated: true');

echo json_encode([
    'success' => false,
    'message' => 'This API version is deprecated. Please use /api/v1/',
]);
exit;
