<?php
include '../_base.php';

// ----------------------------------------------------------------------------

// (1) Authorization (member)
auth('Member');

// (2) Get shopping cart (reject if empty)
$cart = get_cart();
if (!$cart) redirect('cart.php');

// Calculate total amount
$total_amount = 0;
foreach ($cart as $productID => $quantity) {
    $stm = $_db->prepare('SELECT price FROM product WHERE productID = ?');
    $stm->execute([$productID]);
    $price = $stm->fetchColumn();
    $total_amount += $price * $quantity;
}

// Convert to cents for Stripe (Stripe uses smallest currency unit)
$amount_cents = $total_amount * 100;

// ----------------------------------------------------------------------------

$_title = 'Payment | Checkout';
include '../navbar.php';
?>

<div class="container">
    <div class="payment-container">
        <h2>Payment Details</h2>
        
        <div class="order-summary">
            <h3>Order Summary</h3>
            <p><strong>Total Amount:</strong> RM <?= number_format($total_amount, 2) ?></p>
        </div>

        <form id="payment-form" method="post" action="process-payment.php">
            <?= html_hidden('amount', "value='$amount_cents'") ?>
            <?= html_hidden('order_total', "value='$total_amount'") ?>
            
            <div class="form-group">
                <label for="card-element">Credit or debit card</label>
                <div id="card-element" class="form-control">
                    <!-- Stripe Elements will create form elements here -->
                </div>
                <div id="card-errors" role="alert" class="error-message"></div>
            </div>

            <div class="payment-actions">
                <button type="button" class="btn btn-secondary" onclick="history.back()">Back to Cart</button>
                <button type="submit" class="btn btn-primary" id="submit-button">
                    Pay RM <?= number_format($total_amount, 2) ?>
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
    
    submitButton.disabled = true;
    submitButton.textContent = 'Processing...';

    // Create payment method
    const { paymentMethod, error } = await stripe.createPaymentMethod({
        type: 'card',
        card: cardElement,
    });

    if (error) {
        cardErrors.textContent = error.message;
        submitButton.disabled = false;
        submitButton.textContent = `Pay RM <?= number_format($total_amount, 2) ?>`;
    } else {
        // Add payment method ID to form and submit
        const hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'payment_method_id');
        hiddenInput.setAttribute('value', paymentMethod.id);
        form.appendChild(hiddenInput);
        
        form.submit();
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
</style>

<?php
include '../footer.php';