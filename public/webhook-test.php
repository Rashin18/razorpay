<?php
// public/webhook-test.php
file_put_contents(__DIR__.'/webhook-debug.log', 
    "=== REQUEST RECEIVED ===\n".
    "Time: ".date('Y-m-d H:i:s')."\n".
    "Headers:\n".print_r(getallheaders(), true)."\n".
    "POST Data:\n".file_get_contents('php://input')."\n\n",
    FILE_APPEND
);

echo "WEBHOOK TEST WORKED - CHECK webhook-debug.log";