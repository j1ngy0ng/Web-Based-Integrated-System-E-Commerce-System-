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
$subtotal = 0;
foreach ($cart as $productID => $quantity) {
    $stm = $_db->prepare('SELECT price FROM product WHERE productID = ?');
    $stm->execute([$productID]);
    $price = $stm->fetchColumn();
    $subtotal += $price * $quantity;
}

// ----------------------------------------------------------------------------
// 2. APPLY VOUCHER DISCOUNT (Check Session)
// ----------------------------------------------------------------------------
$discount_amount = 0;
$voucher_code = null;

if (isset($_SESSION['voucher'])) {
    $v = $_SESSION['voucher'];
    
    // Re-validate minimum spend to be safe
    if ($subtotal >= $v->min_spend) {
        $voucher_code = $v->voucher_code;
        
        if ($v->discount_type == 'fixed') {
            $discount_amount = $v->discount_value;
        } else {
            // Percent
            $discount_amount = $subtotal * ($v->discount_value / 100);
        }
        
        // Cap discount at subtotal (cannot be negative)
        if ($discount_amount > $subtotal) {
            $discount_amount = $subtotal;
        }
    }
}

// Final Total to Charge
$TotalAmount = $subtotal - $discount_amount;

// Convert to cents for Stripe
$amount_cents = round($TotalAmount * 100); // Round ensures integer

// ----------------------------------------------------------------------------
// Create PaymentIntent
// ----------------------------------------------------------------------------
require_once '../vendor/autoload.php';

try {
    \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
    
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount_cents,
        'currency' => 'myr',
        'automatic_payment_methods' => ['enabled' => true],
        'metadata' => [
            'user_id' => $_user->id,
            'order_total' => $TotalAmount,
            'voucher_code' => $voucher_code // Store code in metadata for reference
        ]
    ]);
    
    $clientSecret = $paymentIntent->client_secret;
} catch (Exception $e) {
    error_log("Failed to create PaymentIntent: " . $e->getMessage());
    temp('error', 'Failed to initialize payment. Please try again.');
    redirect('../order/cart.php');
}

$_title = 'Payment | Checkout';
include '../navbar.php';
?>

<div class="container">
    <div class="payment-container">
        <h2>Payment Details</h2>
        
        <div class="order-summary">
            <h3>Order Summary</h3>
            
            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                <span>Subtotal:</span>
                <span>RM <?= number_format($subtotal, 2) ?></span>
            </div>

            <?php if ($discount_amount > 0): ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:5px; color:#e53e3e;">
                    <span>Discount (<?= htmlspecialchars($voucher_code) ?>):</span>
                    <span>- RM <?= number_format($discount_amount, 2) ?></span>
                </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; border-top:1px solid #ddd; padding-top:10px; margin-top:10px; font-weight:bold; font-size:1.1em;">
                <span>Total to Pay:</span>
                <span style="color:#006989;">RM <?= number_format($TotalAmount, 2) ?></span>
            </div>
        </div>

        <form id="payment-form">
            <div class="form-group">
                <label for="card-element">Credit or debit card</label>
                <div id="card-element" class="form-control">
                    <!-- Stripe Elements will create form elements here -->
                </div>
                <div id="card-errors" role="alert" class="error-message"></div>
            </div>

            <div class="payment-actions">
                <a href="../order/cart.php" class="btn btn-secondary">Back to Cart</a>
                <button type="submit" class="btn btn-primary" id="submit-button">
                    Pay RM <?= number_format($TotalAmount, 2) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Load Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
// Initialize Stripe with your publishable key
const stripe = Stripe('<?= getenv('STRIPE_PUBLISHABLE_KEY') ?>');
const elements = stripe.elements();

// Client secret from backend
const clientSecret = '<?= $clientSecret ?>';

// Create card element
const cardElement = elements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#424770',
            '::placeholder': {
                color: '#aab7c4',
            },
        },
    },
});

cardElement.mount('#card-element');

// Handle form submission
const form = document.getElementById('payment-form');
const submitButton = document.getElementById('submit-button');
const cardErrors = document.getElementById('card-errors');

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    
    console.log('Form submission started...');
    
    submitButton.disabled = true;
    submitButton.textContent = 'Processing...';
    cardErrors.textContent = '';

    try {
        // Confirm payment with card element
        const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: cardElement,
            }
        });

        console.log('Stripe confirmCardPayment result:', { paymentIntent, error });

        if (error) {
            console.log('Stripe error:', error);
            cardErrors.textContent = error.message;
            submitButton.disabled = false;
            submitButton.textContent = 'Pay RM <?= number_format($TotalAmount, 2) ?>';
        } else if (paymentIntent.status === 'succeeded') {
            console.log('Payment succeeded! PaymentIntent ID:', paymentIntent.id);
            
            // Send to backend to create order
            const formData = new FormData();
            formData.append('payment_intent_id', paymentIntent.id);
            formData.append('amount', '<?= $amount_cents ?>');
            formData.append('order_total', '<?= $TotalAmount ?>');
            
            const response = await fetch('process-payment.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Backend response:', result);
            
            if (result.success) {
                // Redirect to success page
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
        console.error('Unexpected error:', err);
        cardErrors.textContent = 'An unexpected error occurred. Please try again.';
        submitButton.disabled = false;
        submitButton.textContent = 'Pay RM <?= number_format($TotalAmount, 2) ?>';
    }
});

// Real-time validation errors
cardElement.on('change', ({ error }) => {
    if (error) {
        cardErrors.textContent = error.message;
    } else {
        cardErrors.textContent = '';
    }
});
</script>

<style>
.payment-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.order-summary {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-control {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-height: 45px;
}

.error-message {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
}

.payment-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}
</style>

<?php
include '../footer.php';
?>