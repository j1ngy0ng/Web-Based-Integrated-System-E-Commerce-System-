<?php
// Return JSON only
header('Content-Type: application/json');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

require_once '../vendor/autoload.php';
include '../_base.php';

// Clear any unexpected output
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output: " . $output);
}

// ----------------------------------------------------------------------------
// (1) Authorization check
// ----------------------------------------------------------------------------
if (!isset($_user) || $_user->role !== 'Member') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ----------------------------------------------------------------------------
// (2) Only POST requests allowed
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// ----------------------------------------------------------------------------
// (3) Check reCAPTCHA
// ----------------------------------------------------------------------------
$secretKey = "6Lc34ycsAAAAAOtetg4cZmZiKd6POLavp3MBadHA";
$captcha = $_POST['g-recaptcha-response'] ?? '';

$response = file_get_contents(
    "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha"
);

$responseKeys = json_decode($response, true);

if (empty($captcha) || !$responseKeys["success"]) {
    echo json_encode(['success' => false, 'error' => 'Captcha verification failed.']);
    exit;
}

// ----------------------------------------------------------------------------
// (4) Get shopping cart
// ----------------------------------------------------------------------------
$cart = get_cart();
if (!$cart) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// ----------------------------------------------------------------------------
// (5) Get POST parameters
// ----------------------------------------------------------------------------
$payment_intent_id = $_POST['payment_intent_id'] ?? '';

if (empty($payment_intent_id)) {
    echo json_encode(['success' => false, 'error' => 'Payment intent ID is required']);
    exit;
}

// ----------------------------------------------------------------------------
// (6) Calculate totals: voucher + reward points
// ----------------------------------------------------------------------------
$voucher_code = null;
$discount_amount = 0;

// 6a) Calculate raw subtotal from cart
$raw_subtotal = 0;
foreach ($cart as $pid => $qty) {
    $p_stm = $_db->prepare('SELECT price FROM product WHERE productID = ?');
    $p_stm->execute([$pid]);
    $raw_subtotal += ($p_stm->fetchColumn() * $qty);
}

// 6b) Voucher
if (isset($_SESSION['voucher'])) {
    $v = $_SESSION['voucher'];
    $voucher_code = $v->voucher_code;
    if ($v->discount_type == 'fixed') {
        $discount_amount = $v->discount_value;
    } else {
        $discount_amount = $raw_subtotal * ($v->discount_value / 100);
    }
    if ($discount_amount > $raw_subtotal) $discount_amount = $raw_subtotal;
}

// 6c) Reward points
$redeem_points_used = $_SESSION['redeem_points'] ?? 0;
$redeem_discount = $redeem_points_used / 100; // 100 points = RM1

// 6d) Final total
$final_total = max($raw_subtotal - $discount_amount - $redeem_discount, 0);

// ----------------------------------------------------------------------------
// (7) Stripe setup
// ----------------------------------------------------------------------------
try {
    \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Stripe configuration error']);
    exit;
}

try {
    $_db->beginTransaction();

    // ----------------------------------------------------------------------------
    // (A) Verify Stripe Payment
    // ----------------------------------------------------------------------------
    $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    if ($paymentIntent->status !== 'succeeded') {
        throw new Exception('Payment has not succeeded.');
    }

    // ----------------------------------------------------------------------------
    // (B) Check duplicate payment
    // ----------------------------------------------------------------------------
    $check_stm = $_db->prepare('SELECT orderID FROM payment WHERE stripe_payment_intent_id = ?');
    $check_stm->execute([$payment_intent_id]);
    $existing = $check_stm->fetch();
    if ($existing) {
        echo json_encode([
            'success' => true, 
            'orderID' => $existing['orderID'], 
            'message' => 'Order already processed'
        ]);
        $_db->commit();
        exit;
    }

    // ----------------------------------------------------------------------------
    // (C) Insert order
    // ----------------------------------------------------------------------------
    $address_id = $_SESSION['selected_address'] ?? null;

    $stm = $_db->prepare('
        INSERT INTO `order` 
        (userID, OrderDate, status, TotalAmount, voucher_code, discount_amount, redeemed_points, address_id)
        VALUES (?, NOW(), "Paid", ?, ?, ?, ?, ?)
    ');
    $stm->execute([
        $_user->id, 
        $final_total, 
        $voucher_code, 
        $discount_amount, 
        $redeem_points_used, 
        $address_id
    ]);
    $orderID = $_db->lastInsertId();

    // ----------------------------------------------------------------------------
    // (D) Insert order items & update stock
    // ----------------------------------------------------------------------------
    $item_stm = $_db->prepare('INSERT INTO order_item (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)');
    $stock_stm = $_db->prepare('UPDATE product SET stock = stock - ? WHERE productID = ?');

    foreach ($cart as $productID => $quantity) {
        $product_stm = $_db->prepare('SELECT price, stock FROM product WHERE productID = ?');
        $product_stm->execute([$productID]);
        $product = $product_stm->fetch(PDO::FETCH_OBJ);

        if (!$product || $product->stock < $quantity) {
            throw new Exception("Stock error for product ID $productID");
        }

        $item_stm->execute([$orderID, $productID, $quantity, $product->price]);
        $stock_stm->execute([$quantity, $productID]);
    }

    // ----------------------------------------------------------------------------
    // (E) Insert payment record
    // ----------------------------------------------------------------------------
    $payment_stm = $_db->prepare('
        INSERT INTO payment (orderID, amount, stripe_payment_intent_id, stripe_client_secret, status, method, currency, payment_date)
        VALUES (?, ?, ?, ?, "Completed", "card", "MYR", NOW())
    ');
    $payment_stm->execute([$orderID, $final_total, $paymentIntent->id, $paymentIntent->client_secret]);

    // ----------------------------------------------------------------------------
    // (F) Deduct reward points from user
    // ----------------------------------------------------------------------------
    if ($redeem_points_used > 0) {
        $update_points = $_db->prepare('UPDATE user SET reward_points = reward_points - ? WHERE id = ?');
        $update_points->execute([$redeem_points_used, $_user->id]);
    }

    // ----------------------------------------------------------------------------
    // (G) Commit transaction
    // ----------------------------------------------------------------------------
    $_db->commit();

    // ----------------------------------------------------------------------------
    // (H) Clear cart & sessions
    // ----------------------------------------------------------------------------
    set_cart(); // clears cart
    unset($_SESSION['voucher']);
    unset($_SESSION['redeem_points']);

    echo json_encode(['success' => true, 'orderID' => $orderID]);
    exit;

} catch (Exception $e) {
    $_db->rollBack();
    error_log("Error processing payment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>
