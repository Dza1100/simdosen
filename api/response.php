<?php
// api/response.php
// Helper format response JSON & CORS untuk Web Service SIM Dosen

// Aktifkan CORS (Cross-Origin Resource Sharing)
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY');
    header('Content-Type: application/json; charset=UTF-8');
}


// Tangani preflight request (OPTIONS)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function send_json(bool $success, int $statusCode, string $message, $data = null, array $meta = []): never
{
    http_response_code($statusCode);
    
    $payload = [
        'success' => $success,
        'code' => $statusCode,
        'message' => $message,
        'data' => $data,
    ];

    if (!empty($meta)) {
        $payload['meta'] = $meta;
    }

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? array_merge($_POST, $decoded) : $_POST;
}
