<?php
// IMPORTANT: Return JSON responses only
header('Content-Type: application/json');

// Error handling for JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

require_once '../vendor/autoload.php';
include '../_base.php';

// Clear any unexpected output
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output: " . $output);
}

// ----------------------------------------------------------------------------

error_log("=== PROCESS-PAYMENT STARTED (NEW VERSION) ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// (1) Authorization check
if (!isset($_user) || $_user->role !== 'Member') {
    error_log("Unauthorized access");
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// (2) Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Not a POST request");
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// (3) Get shopping cart
$cart = get_cart();
if (!$cart) {
    error_log("Cart is empty");
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// (4) Get POST parameters
$payment_intent_id = $_POST['payment_intent_id'] ?? '';
$order_total = $_POST['order_total'] ?? 0;

error_log("Payment Intent ID: " . $payment_intent_id);
error_log("Order Total: " . $order_total);

if (empty($payment_intent_id)) {
    error_log("No payment intent ID provided");
    echo json_encode(['success' => false, 'error' => 'Payment intent ID is required']);
    exit;
}

// Set Stripe API key
try {
    \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
    error_log("Stripe API key set successfully");
} catch (Exception $e) {
    error_log("Stripe API key error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Payment configuration error']);
    exit;
}

try {
    // Start DB transaction
    error_log("Starting database transaction");
    $_db->beginTransaction();

    // (A) Verify the PaymentIntent from Stripe
    error_log("Retrieving PaymentIntent from Stripe...");
    $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    
    error_log("Payment Intent status: " . $paymentIntent->status);
    error_log("Payment Intent amount: " . $paymentIntent->amount);

    // Check if payment was successful
    if ($paymentIntent->status !== 'succeeded') {
        throw new Exception('Payment has not succeeded. Status: ' . $paymentIntent->status);
    }

    // Check if this payment has already been processed
    $check_stm = $_db->prepare('SELECT orderID FROM payment WHERE stripe_payment_intent_id = ?');
    $check_stm->execute([$payment_intent_id]);
    $existing = $check_stm->fetch();
    
    if ($existing) {
        error_log("Payment already processed. Order ID: " . $existing->orderID);
        // Return the existing order ID instead of error
        echo json_encode([
            'success' => true,
            'orderID' => $existing->orderID,
            'message' => 'Order already processed'
        ]);
        $_db->commit();
        exit;
    }

    // (B) Insert order
    error_log("Inserting order into database...");
    $stm = $_db->prepare('
        INSERT INTO `order` (userID, OrderDate, status, TotalAmount)
        VALUES (?, NOW(), "Paid", ?)
    ');
    $stm->execute([$_user->id, $order_total]);
    $orderID = $_db->lastInsertId();
    error_log("Order created with ID: " . $orderID);

    // (C) Insert order items and update stock
    $item_stm = $_db->prepare('
        INSERT INTO order_item (orderID, productID, quantity, price)
        VALUES (?, ?, ?, ?)
    ');
    
    foreach ($cart as $productID => $quantity) {
        // Get product details
        $product_stm = $_db->prepare('SELECT price, stock FROM product WHERE productID = ?');
        $product_stm->execute([$productID]);
        $product = $product_stm->fetch();
        
        if (!$product) {
            throw new Exception("Product $productID not found");
        }
        
        if ($product->stock < $quantity) {
            throw new Exception("Insufficient stock for product $productID");
        }
        
        // Insert order item
        $item_stm->execute([$orderID, $productID, $quantity, $product->price]);
        
        // Update product stock
        $update_stock = $_db->prepare('UPDATE product SET stock = stock - ? WHERE productID = ?');
        $update_stock->execute([$quantity, $productID]);
        
        error_log("Added product $productID, quantity $quantity, price {$product->price}");
    }

    // (D) Insert payment record
    error_log("Inserting payment record...");
    $payment_stm = $_db->prepare('
        INSERT INTO payment (orderID, amount, stripe_payment_intent_id, stripe_client_secret, 
                            status, method, currency, payment_date)
        VALUES (?, ?, ?, ?, "Completed", "card", "MYR", NOW())
    ');
    $payment_stm->execute([
        $orderID, 
        $order_total, 
        $paymentIntent->id,
        $paymentIntent->client_secret
    ]);

    // (E) Commit transaction
    $_db->commit();
    error_log("Database transaction committed successfully");

    // (F) Clear shopping cart
    set_cart();
    error_log("Shopping cart cleared");

    // (G) Return success response
    error_log("Payment processing completed. Order ID: " . $orderID);
    echo json_encode([
        'success' => true,
        'orderID' => $orderID,
        'message' => 'Payment completed successfully!'
    ]);
    exit;

} catch (\Stripe\Exception\CardException $e) {
    error_log("Stripe CardException: " . $e->getMessage());
    $_db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Card error: ' . $e->getError()->message]);
    exit;
    
} catch (\Stripe\Exception\InvalidRequestException $e) {
    error_log("Stripe InvalidRequestException: " . $e->getMessage());
    $_db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Invalid request: ' . $e->getError()->message]);
    exit;
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Stripe ApiErrorException: " . $e->getMessage());
    $_db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Payment service error: ' . $e->getError()->message]);
    exit;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    exit;
    
} catch (Exception $e) {
    error_log("General Exception: " . $e->getMessage());
    $_db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>