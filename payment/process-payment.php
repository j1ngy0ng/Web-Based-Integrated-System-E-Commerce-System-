<?php
require_once '../vendor/autoload.php';
include '../_base.php';

// ----------------------------------------------------------------------------

// (1) Authorization (member)
auth('Member');

if (is_post()) {
    // (2) Get shopping cart (reject if empty)
    $cart = get_cart();
    if (!$cart) redirect('cart.php');

    $payment_method_id = req('payment_method_id');
    $amount_cents = req('amount');
    $order_total = req('order_total');

    if (!$payment_method_id) {
        temp('error', 'Payment method is required');
        redirect('payment.php');
    }

    // Set your Stripe secret key
    \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

    try {
        // ------------------------------------------
        // DB transaction
        // ------------------------------------------
        $_db->beginTransaction();

        // (A) Create Stripe Payment Intent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount_cents,
            'currency' => 'myr',
            'payment_method' => $payment_method_id,
            'confirm' => true,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'return_url' => base('order/success.php'),
        ]);

        // Check if payment was successful
        if ($paymentIntent->status !== 'succeeded') {
            throw new Exception('Payment failed: ' . $paymentIntent->status);
        }

        // (B) Insert order
        $stm = $_db->prepare('
            INSERT INTO `order` (userID, order_date, status, total_amount)
            VALUES (?, NOW(), "Pending", ?)
        ');
        $stm->execute([$_user->id, $order_total]);
        $orderID = $_db->lastInsertId();

        // (C) Insert order items and update stock
        $stm = $_db->prepare('
            INSERT INTO order_item (orderID, productID, quantity, price)
            VALUES (?, ?, ?, (SELECT price FROM product WHERE productID = ?))
        ');
        
        foreach ($cart as $productID => $quantity) {
            $price_stm = $_db->prepare('SELECT price FROM product WHERE productID = ?');
            $price_stm->execute([$productID]);
            $price = $price_stm->fetchColumn();
            
            $stm->execute([$orderID, $productID, $quantity, $productID]);
            
            // Update product stock
            $update_stock = $_db->prepare('UPDATE product SET stock = stock - ? WHERE productID = ?');
            $update_stock->execute([$quantity, $productID]);
        }

        // (D) Insert payment record with Stripe details
        $stm = $_db->prepare('
            INSERT INTO payment (orderID, payment_method, payment_amount, payment_status, 
                                stripe_payment_intent_id, stripe_client_secret, payment_date)
            VALUES (?, "Credit Card", ?, "Completed", ?, ?, NOW())
        ');
        $stm->execute([
            $orderID, 
            $order_total, 
            $paymentIntent->id,
            $paymentIntent->client_secret
        ]);

        // (E) Update order status to Paid
        $stm = $_db->prepare('UPDATE `order` SET status = "Paid" WHERE orderID = ?');
        $stm->execute([$orderID]);

        // (F) Commit transaction
        $_db->commit();

        // (G) Clear shopping cart
        set_cart();

        // (H) Redirect to success page
        temp('success', 'Payment completed successfully!');
        redirect("success.php?id=$orderID");

    } catch (\Stripe\Exception\CardException $e) {
        $_db->rollBack();
        temp('error', 'Card error: ' . $e->getError()->message);
        redirect('payment.php');
    } catch (\Stripe\Exception\RateLimitException $e) {
        $_db->rollBack();
        temp('error', 'Too many requests. Please try again later.');
        redirect('payment.php');
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        $_db->rollBack();
        temp('error', 'Invalid request: ' . $e->getError()->message);
        redirect('payment.php');
    } catch (\Stripe\Exception\AuthenticationException $e) {
        $_db->rollBack();
        temp('error', 'Authentication error. Please contact support.');
        redirect('payment.php');
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        $_db->rollBack();
        temp('error', 'Network error. Please try again.');
        redirect('payment.php');
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $_db->rollBack();
        temp('error', 'Stripe API error: ' . $e->getError()->message);
        redirect('payment.php');
    } catch (Exception $e) {
        $_db->rollBack();
        temp('error', 'Payment failed: ' . $e->getMessage());
        redirect('payment.php');
    }
}

redirect('cart.php');