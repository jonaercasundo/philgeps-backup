<?php

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$delivery_id = filter_input(INPUT_GET, 'delivery_id', FILTER_VALIDATE_INT);

if (!$id || !$delivery_id) {
    http_response_code(400);
    exit('Invalid QR Code');
}

header("Location: scan.php?id={$id}&delivery_id={$delivery_id}");
exit;