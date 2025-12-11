<?php
include '../_base.php';

// ----------------------------------------------------------------------------
// (1) Authorization (member)
auth('Member');

// (2) Get shopping cart (reject if empty)
$cart = get_cart();
if (!$cart) redirect('../order/cart.php');

// ----------------------------------------------------------------------------
// 1. CALCULATE SUBTOTAL (Sum of items)
// ----------------------------------------------------------------------------
$cartItems = [];
$subtotal = 0;

// Get product IDs from cart
$productIDs = array_keys($cart);
$placeholders = str_repeat('?,', count($productIDs) - 1) . '?';

// Fetch all products in cart in one query
$stm = $_db->prepare("
    SELECT p.productID, p.name, p.price, p.stock,
           pp.photoURL as image
    FROM product p
    LEFT JOIN productPhoto pp ON p.productID = pp.productID AND pp.is_main = 1
    WHERE p.productID IN ($placeholders)
");
$stm->execute($productIDs);
$products = $stm->fetchAll(PDO::FETCH_OBJ);

// Build cart items array and calculate subtotal
foreach ($products as $product) {
    $quantity = $cart[$product->productID];
    $itemTotal = $product->price * $quantity;
    $subtotal += $itemTotal;

    $cartItems[] = [
        'id' => $product->productID,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => $quantity,
        'total' => $itemTotal,
        'image' => $product->image
    ];
}

$_SESSION['cart_items'] = $cartItems;

// ----------------------------------------------------------------------------
// 2. APPLY VOUCHER DISCOUNT
// ----------------------------------------------------------------------------
$discount_amount = 0;
$voucher_code = null;

if (isset($_SESSION['voucher'])) {
    $v = $_SESSION['voucher'];

    if ($subtotal >= $v->min_spend) {
        $voucher_code = $v->voucher_code;

        if ($v->discount_type == 'fixed') {
            $discount_amount = $v->discount_value;
        } else {
            $discount_amount = $subtotal * ($v->discount_value / 100);
        }

        if ($discount_amount > $subtotal) $discount_amount = $subtotal;
    } else {
        // Voucher no longer valid for this subtotal
        unset($_SESSION['voucher']);
    }
}

// ----------------------------------------------------------------------------
// 3. APPLY REWARD POINTS
// ----------------------------------------------------------------------------
$redeem_discount = 0;
$redeem_points_used = 0;
$TotalAmount = $subtotal - $discount_amount;

if ($_user) {
    $user_points = $_user->reward_points ?? 0;

    if (isset($_SESSION['redeem_points'])) {
        $redeem_points_requested = max(0, $_SESSION['redeem_points']);

        // Max redeemable points based on total after voucher
        $max_points_based_on_total = floor($TotalAmount * 100); // 100 points = RM1
        $redeem_points_used = min($redeem_points_requested, $user_points, $max_points_based_on_total);

        $redeem_discount = $redeem_points_used / 100; // RM
        $TotalAmount = max($TotalAmount - $redeem_discount, 0);
    }
}

// ----------------------------------------------------------------------------
// 4. STRIPE PAYMENT
// ----------------------------------------------------------------------------
require_once '../vendor/autoload.php';

$amount_cents = round($TotalAmount * 100);

try {
    \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount_cents,
        'currency' => 'myr',
        'automatic_payment_methods' => ['enabled' => true],
        'metadata' => [
            'user_id' => $_user->id,
            'order_total' => $TotalAmount,
            'voucher_code' => $voucher_code ?? '',
            'redeem_points_used' => $redeem_points_used,
            'item_count' => count($cartItems)
        ]
    ]);

    $clientSecret = $paymentIntent->client_secret;
} catch (Exception $e) {
    error_log("Failed to create PaymentIntent: " . $e->getMessage());
    temp('error', 'Failed to initialize payment. Please try again.');
    redirect('../order/cart.php');
}

// ----------------------------------------------------------------------------
$_title = 'Payment | Checkout';
include '../navbar.php';
?>

<style>
    .payment-container {
        max-width: 800px;
        margin: 30px auto;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .shipping-info, .order-summary {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .cart-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .item-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 5px;
        margin-right: 15px;
    }
    
    .item-details {
        flex: 1;
    }
    
    .item-price {
        text-align: right;
        min-width: 150px;
    }
    
    .amount-row {
        border-top: 2px solid #dee2e6;
        padding-top: 10px;
        margin-top: 10px;
        text-align: right;
    }
    
    .total-row {
        font-size: 1.2em;
        font-weight: bold;
        color: #28a745;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    #card-element {
        padding: 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background: white;
    }
    
    .error-message {
        color: #dc3545;
        margin-top: 5px;
        font-size: 0.9em;
    }
    
    .payment-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
        border: none;
    }
    
    .btn-primary:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
</style>

<div class="container">
    <div class="payment-container">
        <h2>Payment Details</h2>

        <!-- Shipping info -->
        <div class="shipping-info">
        <?php if (isset($_SESSION['selected_address'])): 
            $address_id = $_SESSION['selected_address'];
            $stm = $_db->prepare('SELECT recipient_name, phone_number, address_line1, address_line2, city, state, postal_code FROM shipping_addresses WHERE address_id = ?');
            $stm->execute([$address_id]);
            $address = $stm->fetch();
        ?>
            <h3>Shipping Information</h3>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Recipient:</strong> <?= htmlspecialchars($address->recipient_name) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($address->phone_number) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Address:</strong></p>
                    <p>
                        <?= htmlspecialchars($address->address_line1) ?>
                        <?= $address->address_line2 ? ', ' . htmlspecialchars($address->address_line2) : '' ?><br>
                        <?= htmlspecialchars($address->postal_code) ?> <?= htmlspecialchars($address->city) ?><br>
                        <?= htmlspecialchars($address->state) ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <p class="text-danger">No shipping address selected. Please go back and select an address.</p>
        <?php endif; ?>
        </div>

        <!-- Order summary -->
        <div class="order-summary">
            <h3>Order Summary</h3>
            <?php if (!empty($cartItems)): ?>
                <div class="mb-3">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <?php if ($item['image']): ?>
                        <img src="../products/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                        <?php else: ?>
                        <div class="item-image bg-light d-flex align-items-center justify-content-center">
                            <span class="text-muted">No Image</span>
                        </div>
                        <?php endif; ?>
                        <div class="item-details">
                            <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                            <small class="text-muted">Quantity: <?= $item['quantity'] ?></small>
                        </div>
                        <div class="item-price">
                            <div>RM <?= number_format($item['price'], 2) ?> each</div>
                            <div><strong>RM <?= number_format($item['total'], 2) ?></strong></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <!-- Totals -->
                <div class="amount-row">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>RM <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                    <div class="d-flex justify-content-between text-success">
                        <span>Voucher Discount:</span>
                        <span>- RM <?= number_format($discount_amount, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($redeem_discount > 0): ?>
                    <div class="d-flex justify-content-between text-success">
                        <span>Reward Points:</span>
                        <span>- RM <?= number_format($redeem_discount, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between total-row">
                        <span>Total:</span>
                        <span>RM <?= number_format($TotalAmount, 2) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">No items in cart</p>
            <?php endif; ?>
        </div>

        <!-- Stripe Payment Form -->
        <form id="payment-form">
            <div class="form-group">
                <label for="card-element">Credit or debit card</label>
                <div id="card-element" class="form-control"></div>
                <div id="card-errors" role="alert" class="error-message"></div>
            </div>

            <div class="g-recaptcha mb-3"
                data-sitekey="6Lc34ycsAAAAALOkGTvBCkWr8JtUbwpsOWk-k0UH"></div>
            <div id="captcha-error" class="text-danger mb-2"></div>

            <div class="payment-actions">
                <a href="../order/cart.php" class="btn btn-secondary">Back to Cart</a>
                <button type="submit" class="btn btn-primary" id="submit-button">
                    Pay RM <?= number_format($TotalAmount, 2) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Stripe.js and reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="https://js.stripe.com/v3/"></script>
<script>
// --- Stripe JS remains same as your original ---
const stripe = Stripe('<?= getenv('STRIPE_PUBLISHABLE_KEY') ?>');
const elements = stripe.elements();
const clientSecret = '<?= $clientSecret ?>';
const cardElement = elements.create('card', {style: {base: {fontSize: '16px', color: '#424770', '::placeholder': {color: '#aab7c4'}}}});
cardElement.mount('#card-element');

const form = document.getElementById('payment-form');
const submitButton = document.getElementById('submit-button');
const cardErrors = document.getElementById('card-errors');

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const captcha = grecaptcha.getResponse();
    const captchaError = document.getElementById('captcha-error');
    if (!captcha) { captchaError.textContent = "Please verify that you're not a robot."; return; }
    else captchaError.textContent = "";

    submitButton.disabled = true;
    submitButton.textContent = 'Processing...';
    cardErrors.textContent = '';

    try {
        const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, { payment_method: { card: cardElement } });
        if (error) {
            cardErrors.textContent = error.message;
            submitButton.disabled = false;
            submitButton.textContent = 'Pay RM <?= number_format($TotalAmount, 2) ?>';
        } else if (paymentIntent.status === 'succeeded') {
            const formData = new FormData();
            formData.append('payment_intent_id', paymentIntent.id);
            formData.append('amount', '<?= $amount_cents ?>');
            formData.append('order_total', '<?= $TotalAmount ?>');
            formData.append('redeem_points_used', '<?= $redeem_points_used ?>');
            formData.append('g-recaptcha-response', captcha);

            const response = await fetch('process-payment.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                window.location.href = 'success.php?id=' + result.orderID;
            } else {
                cardErrors.textContent = result.error || 'Failed to process order. Please contact support.';
                submitButton.disabled = false;
                submitButton.textContent = 'Pay RM <?= number_format($TotalAmount, 2) ?>';
            }
        } else {
            cardErrors.textContent = 'Payment status: ' + paymentIntent.status;
            submitButton.disabled = false;
            submitButton.textContent = 'Pay RM <?= number_format($TotalAmount, 2) ?>';
        }
    } catch (err) {
        cardErrors.textContent = 'An unexpected error occurred. Please try again.';
        submitButton.disabled = false;
        submitButton.textContent = 'Pay RM <?= number_format($TotalAmount, 2) ?>';
    }
});

cardElement.on('change', ({ error }) => {
    cardErrors.textContent = error ? error.message : '';
});
</script>

<?php include '../footer.php'; ?>
