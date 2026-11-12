<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rid    = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'req-' . uniqid();

// 1. Endpoint GET /health (Wajib untuk setiap service)
if ($path === '/health' && $method === 'GET') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => ['service' => 'gateway', 'status' => 'healthy'],
        'meta'    => ['request_id' => $rid]
    ]);
    exit;
}

// Baca konfigurasi service registry
$servicesFile = _DIR_ . '/services.json';
if (!file_exists($servicesFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => ['code' => 'config_error', 'message' => 'services.json tidak ditemukan'],
        'meta'    => ['request_id' => $rid]
    ]);
    exit;
}
$services = json_decode(file_get_contents($servicesFile), true);

// 2. Endpoint GET /api/services (Menampilkan daftar service)
if ($path === '/api/services' && $method === 'GET') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => $services,
        'meta'    => ['request_id' => $rid]
    ]);
    exit;
}

// 3. Forwarding Dynamic ANY /api/{service}/{path}
$pattern = '#^/api/([^/]+)(/.*)?$#';
if (preg_match($pattern, $path, $matches)) {
    // Nama pada URL (resource) -> nama service di services.json
    $aliases = [
        'courses'       => 'course',
        'assignments'   => 'assignment',
        'submissions'   => 'submission',
        'notifications' => 'notification',
    ];
    $targetService = $aliases[$matches[1]] ?? $matches[1];
    $subPath       = $matches[2] ?? '';

    // Cek keberadaan service di services.json
    if (!isset($services[$targetService])) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'service_not_found', 'message' => "Service '$targetService' tidak terdaftar"],
            'meta'    => ['request_id' => $rid]
        ]);
        exit;
    }

    // Susun URL tujuan ke target service
    // Teruskan path ASLI dari client (mis. /api/assignments), jangan disusun ulang dari nama service
    $targetUrl = rtrim($services[$targetService], '/') . $path;
    if (!empty($_SERVER['QUERY_STRING'])) {
        $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
    }

    // Teruskan Header Penting (Authorization & X-Request-ID)
    $headers = ['Content-Type: application/json', 'X-Request-ID: ' . $rid];
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
    }

    // Eksekusi forwarding dengan cURL
    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout 5 detik

    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $body = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Penanganan timeout (504)
    if ($errno === CURLE_OPERATION_TIMEDOUT) {
        http_response_code(504);
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'gateway_timeout', 'message' => 'Service tujuan melewati batas waktu (timeout)'],
            'meta'    => ['request_id' => $rid]
        ]);
        exit;
    }

    // Penanganan service mati (503)
    if ($errno !== 0 || !$response) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'service_unavailable', 'message' => 'Service tujuan tidak dapat dihubungi'],
            'meta'    => ['request_id' => $rid]
        ]);
        exit;
    }

    // Kembalikan respons asli dari service tujuan
    http_response_code($httpCode);
    echo $response;
    exit;
}

// Route tidak ditemukan
http_response_code(404);
echo json_encode([
    'success' => false,
    'error'   => ['code' => 'not_found', 'message' => 'Endpoint tidak ditemukan'],
    'meta'    => ['request_id' => $rid]
]);