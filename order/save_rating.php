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
    SELECT o.userID, o.status, oi.productID
    FROM `order` o
    JOIN order_item oi ON o.orderID = oi.orderID
    WHERE oi.order_item_id = ?
');
$stm->execute([$orderItemID]);
$order = $stm->fetch();
if (!$order || $order->userID != $_user->id || $order->status !== 'Completed') {
    redirect('history.php');
}
$productID = $order->productID;

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

// --------------------------------------------------------------------
// Update product's average rating
$stm = $_db->prepare('
    SELECT AVG(pr.rating) 
    FROM productRating pr
    JOIN order_item oi ON pr.order_item_id = oi.order_item_id
    WHERE oi.productID = ?
');
$stm->execute([$productID]);
$avgRating = $stm->fetchColumn();

$stm = $_db->prepare('UPDATE product SET rating_avg = ? WHERE productID = ?');
$stm->execute([$avgRating, $productID]);

// --------------------------------------------------------------------
// Redirect back to order detail
$stm = $_db->prepare('SELECT orderID FROM order_item WHERE order_item_id = ?');
$stm->execute([$orderItemID]);
$orderID = $stm->fetchColumn();

redirect("detail.php?orderID={$orderID}");
