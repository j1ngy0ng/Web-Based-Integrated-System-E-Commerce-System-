<?php
include '../_base.php';

// Must be logged in
auth();

// Only admin can delete orders
if ($_user->role !== 'Admin' || $_user->role !== 'Superadmin') {
    redirect('history.php');
}

// --------------------------------------------------------
// Validate orderID
$orderID = req('orderID');

if (!$orderID) {
    redirect('maintenance.php?err=MissingOrderID');
}

// --------------------------------------------------------
// Check if order exists
$stm = $_db->prepare("SELECT * FROM `order` WHERE orderID = ?");
$stm->execute([$orderID]);
$order = $stm->fetch();

if (!$order) {
    redirect("maintenance.php?err=OrderNotFound");
}

// --------------------------------------------------------
// Delete order items first
$stm = $_db->prepare("DELETE FROM order_item WHERE orderID = ?");
$stm->execute([$orderID]);

// --------------------------------------------------------
// Delete the order itself
$stm = $_db->prepare("DELETE FROM `order` WHERE orderID = ?");
$ok = $stm->execute([$orderID]);

// --------------------------------------------------------
// Redirect based on result
if ($ok) {
    redirect("/order/maintenance.php?success=OrderDeleted");
} else {
    redirect("/order/maintenance.php?error=DeleteFailed");
}