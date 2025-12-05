<?php
include '../_base.php';
auth();

// --------------------------------------------------------------------
// Get orderID from POST
$orderID = post('orderID');
if (!$orderID) redirect('history.php');

// --------------------------------------------------------------------
// Fetch order for this member
$stm = $_db->prepare('SELECT * FROM `order` WHERE orderID = ? AND userID = ?');
$stm->execute([$orderID, $_user->id]);
$order = $stm->fetch();

if (!$order) {
    temp('msg', 'Order not found.');
    redirect('history.php');
}

// Only Pending or Paid orders can be cancelled
if (!in_array($order->status, ['Pending','Paid'])) {
    temp('msg', 'Order cannot be cancelled.');
    redirect('history.php');
}

// --------------------------------------------------------------------
// Start transaction
$_db->beginTransaction();

try {
    // Update order status to Cancelled
    $stm = $_db->prepare('UPDATE `order` SET status = ? WHERE orderID = ?');
    $stm->execute(['Cancelled', $orderID]);

    // If order was Paid, create a refund record
    if ($order->status == 'Paid') {
        $stm = $_db->prepare('INSERT INTO refund (orderID, userID, amount, status, reason) VALUES (?,?,?,?,?)');
        $stm->execute([$orderID, $_user->id, $order->TotalAmount, 'Pending', 'Order cancelled']);
    }

    $_db->commit();
    temp('msg', 'Order cancelled successfully.');
} catch (Exception $e) {
    $_db->rollBack();
    temp('msg', 'Error cancelling order: ' . $e->getMessage());
}

redirect('history.php');
