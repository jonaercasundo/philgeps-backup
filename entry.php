<?php

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$delivery_id = filter_input(INPUT_GET, 'delivery_id', FILTER_VALIDATE_INT);

if (!$id || !$delivery_id) {
    http_response_code(400);
    exit('Invalid QR Code');
}

header("Location: http://127.0.0.1:8000/receive-delivery/" . $id . "?delivery_id=" . $delivery_id);
exit;