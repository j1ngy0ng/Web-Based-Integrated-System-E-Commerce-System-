<?php
include '../_base.php';
auth();

// Only admin can save changes
if ($_user->role !== 'Admin') {
    redirect('history.php');
}

// --------------------------------------------------------
// Validate POST
$orderID        = req('orderID');
$status         = req('status');
$address_id     = req('address_id');
$recipient_name = req('recipient_name');
$phone_number   = req('phone_number');
$address_line1  = req('address_line1');
$address_line2  = req('address_line2');
$city           = req('city');
$state          = req('state');
$postal_code    = req('postal_code');

if (!$orderID) redirect('order.php');

// Allowed statuses
$allowedStatus = ['Pending','Paid','Shipped','Completed','Cancelled'];
if (!in_array($status, $allowedStatus)) {
    redirect("detail.php?orderID=$orderID&err=InvalidStatus");
}

// --------------------------------------------------------
// Fetch current order
$stm = $_db->prepare("SELECT * FROM `order` WHERE orderID = ?");
$stm->execute([$orderID]);
$order = $stm->fetch();
if (!$order) redirect('order.php');

// --------------------------------------------------------
// Begin Transaction
$_db->beginTransaction();

try {

    // --------------------------------------------------------
    // Update order status first
    $stm = $_db->prepare("UPDATE `order` SET status = ? WHERE orderID = ?");
    $stm->execute([$status, $orderID]);

    // --------------------------------------------------------
    // Award reward points if status changed to Completed
    if ($status === 'Completed' && $order->status !== 'Completed') {
        $points = is_numeric($order->TotalAmount) ? floor($order->TotalAmount) : 0;
        $stm = $_db->prepare("UPDATE user SET reward_points = reward_points + ? WHERE id = ?");
        $stm->execute([$points, $order->userID]);
    }

    // --------------------------------------------------------
    // Update shipping address if provided
    if ($address_id) {
        $stm = $_db->prepare("
            UPDATE shipping_addresses
            SET recipient_name = ?, phone_number = ?, address_line1 = ?, address_line2 = ?,
                city = ?, state = ?, postal_code = ?
            WHERE address_id = ?
        ");
        $stm->execute([
            $recipient_name,
            $phone_number,
            $address_line1,
            $address_line2,
            $city,
            $state,
            $postal_code,
            $address_id
        ]);
    }

    // --------------------------------------------------------
    // Handle refund if status changed to Cancelled
    if ($status === 'Cancelled' && $order->status === 'Paid') {

        // Check if refund already exists
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

    // --------------------------------------------------------
    $_db->commit();
    redirect("detail.php?orderID=$orderID&success=1");

} catch (Exception $e) {
    $_db->rollBack();
    // Debugging: uncomment temporarily to see error message
    // echo "Error: " . $e->getMessage(); exit;
    redirect("detail.php?orderID=$orderID&error=1");
}
?>
