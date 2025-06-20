<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed. Only POST requests are supported.']);
    exit();
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') === false) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Content-Type must be application/json']);
    exit();
}

$requestBody = file_get_contents('php://input');
$parsedBody = json_decode($requestBody, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit();
}

if (!isset($parsedBody['method'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required field: method']);
    exit();
}

$data = match ($parsedBody['method']) {
    'initialize' => getInitialInfo(),
    'notifications/initialized' => http_response_code(202) && exit(),
    'tools/list' => listTools(),
    'tools/call' => getSunriseTime($parsedBody),
    default => handleUnknownMethod($parsedBody['method'])
};

header('Content-Type: application/json');
echo json_encode([
    'jsonrpc' => '2.0',
    'id' => $parsedBody['id'] ?? null,
    'result' => $data,
]);
exit();

function getInitialInfo(): array
{
    return [
        'protocolVersion' => '2025-03-26',
        'capabilities' => [
            'tools' => [
                'listChanged' => false,
            ],
        ],
        'serverInfo' => [
            'name' => 'date sun info server',
            'version' => '1.0.0',
        ],
    ];
}

function listTools(): array
{
    return [
        'tools' => [
            [
                'name' => 'get_sunrise_time',
                'description' => '与えらた地点の緯度経度と日付から、その地点の日の出時刻をUTCで取得します。',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'latitude' => [
                            'type' => 'number',
                            'description' => '日の出時刻を知りたい地点の北緯。南緯は負の値で表します。',
                        ],
                        'longitude' => [
                            'type' => 'number',
                            'description' => '日の出時刻を知りたい地点の東経。西経は負の値で表します。',
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => '日の出時刻を知りたい日付。YYYY-MM-DD形式で指定します。デフォルトは今日の日付です。',
                        ],
                    ],
                    'required' => ['latitude', 'longitude'],
                ],
            ],
        ],
    ];
}

function getSunriseTime(array $parsedBody): array
{
    if (!isset($parsedBody['params']['arguments']['latitude']) || 
        !isset($parsedBody['params']['arguments']['longitude'])) {
        return [
            'content' => [[
                'type' => 'text',
                'text' => 'Missing required parameters: latitude and longitude',
            ]],
            'isError' => true,
        ];
    }

    $latitude = (float)$parsedBody['params']['arguments']['latitude'];
    $longitude = (float)$parsedBody['params']['arguments']['longitude'];
    $date = $parsedBody['params']['arguments']['date'] ?? null;

    $timestamp = $date ? strtotime($date) : time();
    if ($timestamp === false) {
        return [
            'content' => [[
                'type' => 'text',
                'text' => '日付の形式が不正です。',
            ]],
            'isError' => true,
        ];
    }

    $sunriseTime = date_sun_info($timestamp, $latitude, $longitude)['sunrise'];
    if ($sunriseTime === false) {
        $text = '1日中日が昇りません（極夜）。';
    } else if ($sunriseTime === true) {
        $text = '1日中日が昇ったままです（白夜）。';
    } else {
        $text = '日の出時刻は' . date('H:i', $sunriseTime) . '（UTC）です。';
    }

    return [
        'content' => [[
            'type' => 'text',
            'text' => $text,
        ]],
        'isError' => false,
    ];
}

function handleUnknownMethod(string $method): array
{
    return [
        'content' => [[
            'type' => 'text',
            'text' => "Unknown method: $method",
        ]],
        'isError' => true,
    ];
}
