<?php
include '../_base.php';

// Must be logged in
auth();

// Only admin can save changes
if ($_user->role !== 'Admin') {
    redirect('history.php');
}

// --------------------------------------------------------
// Validate POST
$orderID = req('orderID');
$status  = req('status');
$address = req('ShippingAddress');

if (!$orderID) {
    redirect('order.php');
}

// Allowed statuses
$allowedStatus = ['Pending','Paid','Shipped','Completed','Cancelled'];

if (!in_array($status, $allowedStatus)) {
    redirect("detail.php?orderID=$orderID&err=InvalidStatus");
}

// --------------------------------------------------------
// Fetch current order (we need original status & amount)
$stm = $_db->prepare("SELECT * FROM `order` WHERE orderID = ?");
$stm->execute([$orderID]);
$order = $stm->fetch();

if (!$order) {
    redirect('order.php');
}

// --------------------------------------------------------
// Begin Transaction
$_db->beginTransaction();

try {

    // --------------------------------------------------------
    // Update order details
    $stm = $_db->prepare("
        UPDATE `order`
        SET status = ?, ShippingAddress = ?
        WHERE orderID = ?
    ");
    $stm->execute([$status, $address, $orderID]);

    // --------------------------------------------------------
    // If Admin sets order to Cancelled -> create refund
    if ($status === 'Cancelled' && $order->status === 'Paid') {

        // Check if refund already exists (prevent duplicates)
        $stm = $_db->prepare("SELECT * FROM refund WHERE orderID = ?");
        $stm->execute([$orderID]);
        $existing = $stm->fetch();

        if (!$existing) {
            $stm = $_db->prepare("
                INSERT INTO refund (orderID, userID, amount, status, reason)
                VALUES (?,?,?,?,?)
            ");
            $stm->execute([
                $orderID,
                $order->userID,
                $order->TotalAmount,
                'Pending',
                'Order cancelled by Admin'
            ]);
        }
    }

    $_db->commit();
    redirect("detail.php?orderID=$orderID&success=1");

} catch (Exception $e) {
    $_db->rollBack();
    redirect("detail.php?orderID=$orderID&error=1");
}
?>
