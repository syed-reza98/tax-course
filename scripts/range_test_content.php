<?php
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Range: bytes=0-1023\r\n",
        'ignore_errors' => true,
    ]
];
$ctx = stream_context_create($opts);
$url = 'http://127.0.0.1:8000/videos/stream/contents/videos/6z62XNACSlt0h38VuXoOIrrjZYla6yUTVEVtEZG9.mp4';
$result = @file_get_contents($url, false, $ctx);
echo "Response headers:\n";
if (isset($http_response_header)) {
    foreach ($http_response_header as $h) {
        echo $h . "\n";
    }
} else {
    echo "No response headers\n";
}

echo "\nBytes returned: " . ($result === false ? '0' : strlen($result)) . "\n";
