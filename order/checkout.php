<?php
include '../_base.php';

// ----------------------------------------------------------------------------

// (1) Authorization (member)
auth('Member'); // Only members can checkout

if (is_post()) {
    // (2) Get shopping cart (reject if empty)
    $cart = get_cart();
    if (!$cart) redirect('cart.php');

    // ------------------------------------------
    // DB transaction (insert order, items, and payment)
    // ------------------------------------------

    // (A) Begin transaction
    $_db->beginTransaction();

    try {
        // (B) Insert order, keep order id
        $stm = $_db->prepare('
            INSERT INTO `order` (userID, order_date, status, total_amount)
            VALUES (?, NOW(), "Pending", 0)
        ');
        $stm->execute([$_user->id]);
        $orderID = $_db->lastInsertId();

        // (C) Insert order items and calculate total
        $stm = $_db->prepare('
            INSERT INTO order_item (orderID, productID, quantity, price)
            VALUES (?, ?, ?, (SELECT price FROM product WHERE productID = ?))
        ');
        
        $total_amount = 0;
        foreach ($cart as $productID => $quantity) {
            // Get product price
            $price_stm = $_db->prepare('SELECT price FROM product WHERE productID = ?');
            $price_stm->execute([$productID]);
            $price = $price_stm->fetchColumn();
            
            // Insert order item
            $stm->execute([$orderID, $productID, $quantity, $productID]);
            
            // Calculate subtotal
            $subtotal = $price * $quantity;
            $total_amount += $subtotal;
            
            // Update product stock
            $update_stock = $_db->prepare('UPDATE product SET stock = stock - ? WHERE productID = ?');
            $update_stock->execute([$quantity, $productID]);
        }

        // (D) Update order total amount
        $stm = $_db->prepare('UPDATE `order` SET total_amount = ? WHERE orderID = ?');
        $stm->execute([$total_amount, $orderID]);

        // (E) Insert payment record
        $stm = $_db->prepare('
            INSERT INTO payment (orderID, payment_method, payment_amount, payment_status, payment_date)
            VALUES (?, "Credit Card", ?, "Completed", NOW())
        ');
        $stm->execute([$orderID, $total_amount]);

        // (F) Update order status to Completed
        $stm = $_db->prepare('UPDATE `order` SET status = "Completed" WHERE orderID = ?');
        $stm->execute([$orderID]);

        // (G) Commit transaction
        $_db->commit();

        // ------------------------------------------

        // (3) Clear shopping cart
        set_cart();

        // (4) Redirect to detail.php?id=XXX
        temp('info', 'Order placed successfully!');
        redirect("detail.php?id=$orderID");

    } catch (Exception $e) {
        // Rollback transaction on error
        $_db->rollBack();
        temp('error', 'Order failed: ' . $e->getMessage());
        redirect('cart.php');
    }
}

redirect('cart.php');