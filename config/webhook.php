<?php
return [
    'rate_limit' => env('WEBHOOK_RATE_LIMIT', 2),
    'allowed_ips' => [
        '103.216.112.0/22',
        '103.216.116.0/22',
        '127.0.0.1',
        '::1'
    ]
];