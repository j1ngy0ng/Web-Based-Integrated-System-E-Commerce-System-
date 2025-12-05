<?php
include '../_base.php';

// ----------------------------------------------------------------------------

// (1) Authorization (member)
auth('Member'); 

if (is_post()) {
    // (2) Get shopping cart (reject if empty)
    $cart = get_cart();
    if (!$cart) redirect('cart.php');

    // ------------------------------------------
    // DB transaction
    // ------------------------------------------

    // (A) Begin transaction
    $_db->beginTransaction();

    try {
        // (B) Insert order (Initialize with 0, we update later)
        // Note: Added voucher columns here, defaulting to null/0 for now
        $stm = $_db->prepare('
            INSERT INTO `order` (userID, OrderDate, status, TotalAmount, voucher_code, discount_amount)
            VALUES (?, NOW(), "Pending", 0, NULL, 0)
        ');
        $stm->execute([$_user->id]);
        $orderID = $_db->lastInsertId();

        // (C) Insert order items and calculate RAW Subtotal
        $stm_item = $_db->prepare('
            INSERT INTO order_item (orderID, productID, quantity, price)
            VALUES (?, ?, ?, ?)
        ');
        
        $rawSubtotal = 0; // This is the total BEFORE discount

        foreach ($cart as $productID => $quantity) {
            // Get product price
            $price_stm = $_db->prepare('SELECT price FROM product WHERE productID = ?');
            $price_stm->execute([$productID]);
            $price = $price_stm->fetchColumn();
            
            // Insert order item
            $stm_item->execute([$orderID, $productID, $quantity, $price]);
            
            // Calculate subtotal
            $rawSubtotal += ($price * $quantity);
            
            // Update product stock
            $update_stock = $_db->prepare('UPDATE product SET stock = stock - ? WHERE productID = ?');
            $update_stock->execute([$quantity, $productID]);
        }

        // ======================================================
        // (D) VOUCHER CALCULATION LOGIC (New Addition)
        // ======================================================
        $discount_amount = 0;
        $voucher_code = null;
        $voucher = $_SESSION['voucher'] ?? null;

        if ($voucher) {
            // Re-validate minimum spend
            if ($rawSubtotal >= $voucher->min_spend) {
                $voucher_code = $voucher->voucher_code;
                
                if ($voucher->discount_type == 'fixed') {
                    $discount_amount = $voucher->discount_value;
                } else {
                    // Percentage calculation
                    $discount_amount = $rawSubtotal * ($voucher->discount_value / 100);
                }

                // Cap discount if it exceeds total
                if ($discount_amount > $rawSubtotal) {
                    $discount_amount = $rawSubtotal;
                }
            }
        }

        // Calculate Final Total to be paid
        $finalTotal = $rawSubtotal - $discount_amount;
        // ======================================================


        // (E) Update order with Final Total and Voucher Info
        $stm = $_db->prepare('
            UPDATE `order` 
            SET TotalAmount = ?, voucher_code = ?, discount_amount = ? 
            WHERE orderID = ?
        ');
        $stm->execute([$finalTotal, $voucher_code, $discount_amount, $orderID]);

        // (F) Insert payment record (Use Final Total, not raw total)
        $stm = $_db->prepare('
            INSERT INTO payment (orderID, payment_method, payment_amount, payment_status, payment_date)
            VALUES (?, "Credit Card", ?, "Completed", NOW())
        ');
        $stm->execute([$orderID, $finalTotal]);

        // (G) Update order status
        $stm = $_db->prepare('UPDATE `order` SET status = "Completed" WHERE orderID = ?');
        $stm->execute([$orderID]);

        // (H) Commit transaction
        $_db->commit();

        // ------------------------------------------

        // (3) Clear shopping cart AND Voucher session
        set_cart();
        unset($_SESSION['voucher']); // <--- IMPORTANT: Clear voucher after use

        // (4) Redirect
        temp('info', 'Order placed successfully!');
        redirect("detail.php?id=$orderID");

    } catch (Exception $e) {
        $_db->rollBack();
        temp('error', 'Order failed: ' . $e->getMessage());
        redirect('cart.php');
    }
}

redirect('cart.php');
?>