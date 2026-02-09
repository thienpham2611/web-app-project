<?php
// config/response.php

function response($status, $message, $data = null, $code = 200) {
    http_response_code($code);

    echo json_encode([
        "status"  => $status,
        "message" => $message,
        "data"    => $data
    ]);

    exit();
}
