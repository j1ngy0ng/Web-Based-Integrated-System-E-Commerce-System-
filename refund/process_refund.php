<?php
include '../_base.php';
auth("Admin","Superadmin");

// --------------------------------------------------------------------
// Get POST parameters
$refundID = req('refundID');
$status   = req('status');

// Validate input
if (!$refundID || !in_array($status, ['Completed','Failed'])) {
    temp('error', 'Invalid refund request.');
    redirect('refund_list.php');
}

// --------------------------------------------------------------------
// Update refund status
$stm = $_db->prepare('UPDATE refund SET status = ? WHERE refundID = ?');
try {
    $stm->execute([$status, $refundID]);
    temp('success', "Refund #$refundID marked as $status.");
} catch (Exception $e) {
    temp('error', 'Error updating refund: ' . $e->getMessage());
}

redirect('refund_list.php');
?>
