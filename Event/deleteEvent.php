<?php
include_once __DIR__ . '/../_base.php';

$id = req('id');

if ($id) {
    $stm = $_db->prepare("DELETE FROM product_event WHERE id=?");
    $stm->execute([$id]);
    echo "success";
} else {
    http_response_code(400);
    echo "No event ID";
}
