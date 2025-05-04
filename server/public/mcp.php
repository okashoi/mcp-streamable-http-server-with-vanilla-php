<?php

$requestBody = file_get_contents('php://input');
$parsedBody = json_decode($requestBody, true);

$data = match ($parsedBody['method']) {
    'initialize' => getInitialInfo(),
    'notifications/initialized' => http_response_code(202) && exit(),
    'tools/list' => listTools(),
    'tools/call' => getSunriseTime(
        (float)$parsedBody['params']['arguments']['latitude'],
        (float)$parsedBody['params']['arguments']['longitude'],
        $parsedBody['params']['arguments']['date'] ?? null,
    ),
};

header('Content-Type: application/json');
echo json_encode([
    'jsonrpc' => '2.0',
    'id' => $parsedBody['id'],
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
                'description' => '与えらた地点の緯度経度と日付から、その地点の日の出時刻を取得します。',
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

function getSunriseTime(float $latitude, float $longitude, ?string $date = null): array
{
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
        $text = '日の出時刻は' . date('H:i', $sunriseTime) . 'です。';
    }

    return [
        'content' => [[
            'type' => 'text',
            'text' => $text,
        ]],
        'isError' => false,
    ];
}
