<?php
// IMPORTANT: Return JSON responses only
header('Content-Type: application/json');

// Error handling for JSON
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

error_log("=== PROCESS-PAYMENT STARTED (VOUCHER FIX) ===");

// (1) Authorization check
if (!isset($_user) || $_user->role !== 'Member') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// (2) Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// (3) Get shopping cart
$cart = get_cart();
if (!$cart) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// (4) Get POST parameters
$payment_intent_id = $_POST['payment_intent_id'] ?? '';
$order_total = $_POST['order_total'] ?? 0;

if (empty($payment_intent_id)) {
    echo json_encode(['success' => false, 'error' => 'Payment intent ID is required']);
    exit;
}

// ============================================================================
// (NEW) RETRIEVE VOUCHER INFO FROM SESSION
// ============================================================================
$voucher_code = null;
$discount_amount = 0;

if (isset($_SESSION['voucher'])) {
    $v = $_SESSION['voucher'];
    
    // We assume the validation (min spend) was done in payment.php
    // But we can re-check here if we want to be strict.
    // For now, we trust the session since payment succeeded.
    
    $voucher_code = $v->voucher_code;
    
    // Calculate discount again to be safe
    // 1. Calculate raw subtotal
    $raw_subtotal = 0;
    foreach ($cart as $pid => $qty) {
        $p_stm = $_db->prepare('SELECT price FROM product WHERE productID = ?');
        $p_stm->execute([$pid]);
        $raw_subtotal += ($p_stm->fetchColumn() * $qty);
    }

    if ($v->discount_type == 'fixed') {
        $discount_amount = $v->discount_value;
    } else {
        $discount_amount = $raw_subtotal * ($v->discount_value / 100);
    }
    
    if ($discount_amount > $raw_subtotal) $discount_amount = $raw_subtotal;
}
// ============================================================================


// Set Stripe API key
try {
    \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Payment configuration error']);
    exit;
}

try {
    $_db->beginTransaction();

    // (A) Verify Stripe Payment
    $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    if ($paymentIntent->status !== 'succeeded') {
        throw new Exception('Payment has not succeeded.');
    }

    // Check duplicate
    $check_stm = $_db->prepare('SELECT orderID FROM payment WHERE stripe_payment_intent_id = ?');
    $check_stm->execute([$payment_intent_id]);
    $existing = $check_stm->fetch();
    
    if ($existing) {
        echo json_encode(['success' => true, 'orderID' => $existing->orderID, 'message' => 'Order already processed']);
        $_db->commit();
        exit;
    }

    // (B) Insert order (UPDATED WITH VOUCHER COLUMNS)
    // ---------------------------------------------------------
    // Make sure your database columns match these names:
    // voucher_code (VARCHAR), discount_amount (DECIMAL)
    // ---------------------------------------------------------
    $address_id = $_SESSION['selected_address'] ?? null;
    $stm = $_db->prepare('
        INSERT INTO `order` (userID, OrderDate, status, TotalAmount, voucher_code, discount_amount, address_id)
        VALUES (?, NOW(), "Paid", ?, ?, ?, ?)
    ');
    
    // Pass the calculated variables here
    $stm->execute([$_user->id, $order_total, $voucher_code, $discount_amount, $address_id]);
    
    $orderID = $_db->lastInsertId();

    // (C) Insert items
    $item_stm = $_db->prepare('INSERT INTO order_item (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)');
    $stock_stm = $_db->prepare('UPDATE product SET stock = stock - ? WHERE productID = ?');
    
    foreach ($cart as $productID => $quantity) {
        $product_stm = $_db->prepare('SELECT price, stock FROM product WHERE productID = ?');
        $product_stm->execute([$productID]);
        $product = $product_stm->fetch();
        
        if (!$product || $product->stock < $quantity) {
            throw new Exception("Stock error for product $productID");
        }
        
        $item_stm->execute([$orderID, $productID, $quantity, $product->price]);
        $stock_stm->execute([$quantity, $productID]);
    }

    // (D) Insert payment
    $payment_stm = $_db->prepare('
        INSERT INTO payment (orderID, amount, stripe_payment_intent_id, stripe_client_secret, status, method, currency, payment_date)
        VALUES (?, ?, ?, ?, "Completed", "card", "MYR", NOW())
    ');
    $payment_stm->execute([$orderID, $order_total, $paymentIntent->id, $paymentIntent->client_secret]);

    // (E) Commit
    $_db->commit();

    // (F) Clear Cart & Voucher Session
    set_cart();
    unset($_SESSION['voucher']); // <--- Clear the voucher here

    echo json_encode(['success' => true, 'orderID' => $orderID]);
    exit;

} catch (Exception $e) {
    $_db->rollBack();
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>

<?php
$secretKey = "6Lc34ycsAAAAAOtetg4cZmZiKd6POLavp3MBadHA";
$captcha = $_POST['g-recaptcha-response'];

$response = file_get_contents(
    "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha"
);

$responseKeys = json_decode($response, true);

if(!$responseKeys["success"]) {
    echo json_encode([
        'success' => false,
        'error' => 'Captcha verification failed.'
    ]);
    exit;
}
?>
