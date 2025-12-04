<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();
$role = $_user->role;
if ($role !== 'Member') redirect('history.php');

// --------------------------------------------------------------------
// Get POST data
$orderItemID = req('order_item_id');
$rating = intval(req('rating'));
$comment = req('comment') ?: null;

if (!$orderItemID || $rating < 1 || $rating > 5) redirect('history.php');

// --------------------------------------------------------------------
// Validate order item belongs to this member and is completed
$stm = $_db->prepare('
    SELECT o.userID, o.status
    FROM `order` o
    JOIN order_item oi ON o.orderID = oi.orderID
    WHERE oi.order_item_id = ?
');
$stm->execute([$orderItemID]);
$order = $stm->fetch();
if (!$order || $order->userID != $_user->id || $order->status !== 'Completed') {
    redirect('history.php');
}

// --------------------------------------------------------------------
// Check if already rated
$stm = $_db->prepare('SELECT * FROM productRating WHERE order_item_id = ?');
$stm->execute([$orderItemID]);
if ($stm->fetch()) redirect("detail.php?orderID={$orderItemID}");

// --------------------------------------------------------------------
// Insert rating
$stm = $_db->prepare('
    INSERT INTO productRating (order_item_id, rating, comment)
    VALUES (?, ?, ?)
');
$stm->execute([$orderItemID, $rating, $comment]);

// Redirect back to order detail
$stm = $_db->prepare('SELECT orderID FROM order_item WHERE order_item_id = ?');
$stm->execute([$orderItemID]);
$orderID = $stm->fetchColumn();

redirect("detail.php?orderID={$orderID}");
