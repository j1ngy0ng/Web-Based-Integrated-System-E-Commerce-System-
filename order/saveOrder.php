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
// Update order
$stm = $_db->prepare("
    UPDATE `order`
    SET status = ?, ShippingAddress = ?
    WHERE orderID = ?
");
$ok = $stm->execute([$status, $address, $orderID]);

// --------------------------------------------------------
// Redirect with result
if ($ok) {
    redirect("detail.php?orderID=$orderID&success=1");
} else {
    redirect("detail.php?orderID=$orderID&error=1");
}
