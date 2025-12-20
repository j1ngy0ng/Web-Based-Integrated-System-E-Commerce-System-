<?php
include '../_base.php';

// Load PHPMailer
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$id = req('id');
if (!$id) redirect('cart.php');

$stm = $_db->prepare('
    SELECT o.*, o.redeemed_points, p.stripe_payment_intent_id, u.email, u.name as customer_name, 
           sa.recipient_name, sa.phone_number, sa.address_line1, sa.address_line2, 
           sa.city, sa.state, sa.postal_code
    FROM `order` o 
    LEFT JOIN payment p ON o.orderID = p.orderID 
    LEFT JOIN user u ON o.userID = u.id
    LEFT JOIN shipping_addresses sa ON o.address_id = sa.address_id
    WHERE o.orderID = ?
');
$stm->execute([$id]);
$order = $stm->fetch();

if (!$order) redirect('cart.php');

// Fetch products ONCE
$product_stm = $_db->prepare('
    SELECT p.name, oi.quantity, oi.price 
    FROM order_item oi
    JOIN product p ON oi.productID = p.productID
    WHERE oi.orderID = ?
');
$product_stm->execute([$order->orderID]);
$products = $product_stm->fetchAll(PDO::FETCH_OBJ);

$redeem_discount = $order->redeemed_points > 0 ? $order->redeemed_points / 100 : 0;

// Calculate subtotal ONCE
$subtotal = 0;
foreach ($products as $product) {
    $subtotal += $product->price * $product->quantity;
}


// Send email receipt (pass subtotal as parameter)
sendReceiptEmail($order, $products, $subtotal, $_db);

$_title = 'Payment Successful';
include '../navbar.php';

// Function to send email receipt using PHPMailer
function sendReceiptEmail($order, $products, $subtotal, $_db) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'leejx-wm23@student.tarc.edu.my'; // Your email
        $mail->Password = 'tekh saar rnxs thgi'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('leejx-wm23@student.tarc.edu.my', 'Chillax');
        $mail->addAddress($order->email, $order->customer_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Payment Receipt - Order #" . $order->orderID;
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #006989; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .order-details, .shipping-details, .product-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #006989; color: white; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Payment Successful!</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($order->customer_name) . ",</h2>
                <p>Thank you for your purchase! Here's your order summary:</p>
                
                <!-- Order Summary -->
                <div class='order-details'>
                    <h3>Order Summary</h3>
                    <p><strong>Order ID:</strong> #" . $order->orderID . "</p>
                    <p><strong>Amount Paid:</strong> RM " . number_format($order->TotalAmount, 2) . "</p>
                    <p><strong>Payment Date:</strong> " . date('F j, Y g:i A', strtotime($order->OrderDate)) . "</p>
                    " . ($order->stripe_payment_intent_id ? "<p><strong>Transaction ID:</strong> " . $order->stripe_payment_intent_id . "</p>" : "") . "
                </div>

                <!-- Shipping Summary -->
                <div class='shipping-details'>
                    <h3>Shipping Summary</h3>
                    <p><strong>Recipient:</strong> " . htmlspecialchars($order->recipient_name) . "</p>
                    <p><strong>Phone Number:</strong> " . htmlspecialchars($order->phone_number) . "</p>
                    <p><strong>Address:</strong><br>
                        " . htmlspecialchars($order->address_line1) . 
                        ($order->address_line2 ? ", " . htmlspecialchars($order->address_line2) : "") . "<br>
                        " . htmlspecialchars($order->postal_code) . " " . htmlspecialchars($order->city) . ", " . htmlspecialchars($order->state) . "
                    </p>
                </div>

                <!-- Product Details -->
                <div class='product-details'>
                    <h3>Product Details</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Price (RM)</th>
                                <th>Total (RM)</th>
                            </tr>
                        </thead>
                        <tbody>";

                        foreach ($products as $product) {
                            $mail->Body .= "
                            <tr>
                                <td>" . htmlspecialchars($product->name) . "</td>
                                <td>" . $product->quantity . "</td>
                                <td>" . number_format($product->price, 2) . "</td>
                                <td>" . number_format($product->quantity * $product->price, 2) . "</td>
                            </tr>";
                        }

                        $mail->Body .= "
                        </tbody>
                    </table>
                    <table style='width: 100%; margin-top: 15px; border-top: 2px solid #ddd;'>
                        <tr>
                            <td colspan='3' style='text-align: right; padding: 8px; font-size: 0.95em;'>Subtotal:</td>
                            <td style='text-align: left; padding: 8px; font-weight: bold;'>RM " . number_format($subtotal, 2) . "</td>
                        </tr>";
                        
                        if ($order->voucher_code && $order->discount_amount > 0) {
                            $mail->Body .= "
                        <tr style='color: #28a745;'>
                            <td colspan='3' style='text-align: right; padding: 8px; font-size: 0.95em;'>
                                Discount Applied (<strong>" . htmlspecialchars($order->voucher_code) . "</strong>):
                            </td>
                            <td style='text-align: left; padding: 8px; font-weight: bold;'>- RM " . number_format($order->discount_amount, 2) . "</td>
                        </tr>";
                        }

                        if ($order->redeemed_points > 0) {
                            $redeem_discount = $order->redeemed_points / 100;
                            $mail->Body .= "
                            <tr style='color: #28a745;'>
                                <td colspan='3' style='text-align: right; padding: 8px; font-size: 0.95em;'>
                                    Reward Points Redeemed (<strong>" . number_format($order->redeemed_points) . " points</strong>):
                                </td>
                                <td style='text-align: left; padding: 8px; font-weight: bold;'>- RM " . number_format($redeem_discount, 2) . "</td>
                            </tr>";
                        }
                        
                        $mail->Body .= "
                        <tr style='background: #f0f8ff; border-top: 2px solid #006989;'>
                            <td colspan='3' style='text-align: right; padding: 12px; font-size: 1.1em;'><strong>Total Paid:</strong></td>
                            <td style='text-align: left; padding: 12px; font-size: 1.1em;'><strong style='color: #006989;'>RM " . number_format($order->TotalAmount, 2) . "</strong></td>
                        </tr>
                    </table>
                </div>

                <p>You can view your order details anytime in your account.</p>
            </div>
            <div class='footer'>
                <p>If you have any questions, please contact our support team.</p>
            </div>
        </body>
        </html>
        ";
        
        $mail->send();
        error_log("Email sent successfully to: " . $order->email);
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>

<div class="container">
    <div class="success-container text-center">
        <div class="success-icon">🎉</div> <h2>Payment Successful!</h2>
        <p class="lead-text">Thank you for your order! A receipt has been sent to **<?= $order->email ?>**.</p>
        
        <div class="order-details-card"> <h3 class="card-title">Order Summary</h3>
            <div class="detail-group">
                <p><strong>Order ID:</strong> <span>#<?= $order->orderID ?></span></p>
                <p><strong>Customer:</strong> <span><?= htmlspecialchars($order->customer_name) ?></span></p>
                <p><strong>Email:</strong> <span><?= $order->email ?></span></p>
            </div>
            <div class="detail-group highlight">
                <p><strong>Amount Paid:</strong> <span class="amount">RM <?= number_format($order->TotalAmount, 2) ?></span></p>
                <p><strong>Payment Date:</strong> <span><?= date('F j, Y g:i A', strtotime($order->OrderDate)) ?></span></p>
                <?php if ($order->stripe_payment_intent_id): ?>
                    <p><strong>Transaction ID:</strong> <span><?= $order->stripe_payment_intent_id ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="success-actions">
            <button onclick="printReceipt()" class="btn btn-outline">🖨️ Print Receipt</button>
            <a href="../order/detail.php?orderID=<?= $order->orderID ?>" class="btn btn-secondary">📋 View Order Details</a>
            <a href="../product/list.php" class="btn btn-primary">🛒 Continue Shopping</a>
        </div>
    </div>
</div>

<div id="printable-receipt" style="display: none;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
        <div style="text-align: center; border-bottom: 2px solid #006989; padding-bottom: 15px; margin-bottom: 20px;">
            <h1 style="color: #006989; margin: 0;">Chillax Sdn Bhd</h1>
            <p style="margin: 5px 0;">Payment Receipt</p>
        </div>
        
        <!-- Order Details -->
        <div style="margin-bottom: 20px;">
            <h3>Order Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Order ID:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">#<?= $order->orderID ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Customer:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= htmlspecialchars($order->customer_name) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Email:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $order->email ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Amount Paid:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">RM <?= number_format($order->TotalAmount, 2) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Payment Date:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= date('F j, Y g:i A', strtotime($order->OrderDate)) ?></td>
                </tr>
                <?php if ($order->stripe_payment_intent_id): ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Transaction ID:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $order->stripe_payment_intent_id ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Shipping Address -->
        <div style="margin-bottom: 20px;">
            <h3>Shipping Address</h3>
            <p><strong>Recipient:</strong> <?= htmlspecialchars($order->recipient_name) ?></p>
            <p><strong>Phone Number:</strong> <?= htmlspecialchars($order->phone_number) ?></p>
            <p><strong>Address:</strong><br>
                <?= htmlspecialchars($order->address_line1) ?><?= $order->address_line2 ? ', ' . htmlspecialchars($order->address_line2) : '' ?><br>
                <?= htmlspecialchars($order->postal_code) ?> <?= htmlspecialchars($order->city) ?>, <?= htmlspecialchars($order->state) ?>
            </p>
        </div>

        <!-- Product Details -->
        <div style="margin-bottom: 20px;">
            <h3>Product Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 8px; border-bottom: 2px solid #006989; text-align: left;">Product Name</th>
                        <th style="padding: 8px; border-bottom: 2px solid #006989; text-align: left;">Quantity</th>
                        <th style="padding: 8px; border-bottom: 2px solid #006989; text-align: left;">Price (RM)</th>
                        <th style="padding: 8px; border-bottom: 2px solid #006989; text-align: left;">Total (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= htmlspecialchars($product->name) ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $product->quantity ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">RM <?= number_format($product->price, 2) ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">RM <?= number_format($product->quantity * $product->price, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; padding: 10px; border-top: 2px solid #ddd;"><strong>Subtotal:</strong></td>
                        <td style="text-align: right; padding: 10px; border-top: 2px solid #ddd;"><strong>RM <?= number_format($subtotal, 2) ?></strong></td>
                    </tr>
                    <?php if ($order->voucher_code && $order->discount_amount > 0): ?>
                    <tr style="color: #28a745;">
                        <td colspan="3" style="text-align: right; padding: 10px;">
                            <strong>Discount (<?= htmlspecialchars($order->voucher_code) ?>):</strong>
                        </td>
                        <td style="text-align: right; padding: 10px;"><strong>- RM <?= number_format($order->discount_amount, 2) ?></strong></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($order->redeemed_points > 0): 
                        $redeem_discount = $order->redeemed_points / 100;
                    ?>
                    <tr style="color: #28a745;">
                        <td colspan="3" style="text-align: right; padding: 10px;">
                            <strong>Reward Points (<?= number_format($order->redeemed_points) ?> points):</strong>
                        </td>
                        <td style="text-align: right; padding: 10px;"><strong>- RM <?= number_format($redeem_discount, 2) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr style="background: #f0f8ff; font-size: 1.15em;">
                        <td colspan="3" style="text-align: right; padding: 12px; border-top: 2px solid #006989;"><strong>Total Paid:</strong></td>
                        <td style="text-align: right; padding: 12px; border-top: 2px solid #006989;"><strong>RM <?= number_format($order->TotalAmount, 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="font-size: 12px; color: #666;">Thank you for your purchase!</p>
            <p style="font-size: 12px; color: #666;">Generated on: <?= date('F j, Y g:i A') ?></p>
        </div>
    </div>
</div>

<script>
function printReceipt() {
    const printContent = document.getElementById('printable-receipt').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload(); // Reload to restore functionality
}
</script>

<style>
/* --- Core Styles (Unchanged or Minor Tweak) --- */
.container {
    max-width: 900px; /* Increased max width slightly for better layout */
    margin: 40px auto;
    padding: 0 15px;
}

.text-center {
    text-align: center;
}

/* --- SUCCESS CONTAINER: Added Shadow & Background --- */
.success-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 40px; /* Increased padding */
    border-radius: 15px; /* Softer edges */
    background: #ffffff; /* White background for contrast */
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Subtle, modern shadow for depth */
}

/* --- ICON & HEADINGS: Enhanced Visuals --- */
.success-icon {
    font-size: 5em; /* Bigger icon */
    margin-bottom: 10px;
}

.success-container h2 {
    color: #005a73; /* Slightly darker blue for contrast */
    font-size: 2.2em;
    margin-top: 0;
    margin-bottom: 5px;
}

.lead-text {
    font-size: 1.1em;
    color: #555;
    margin-bottom: 30px;
}

/* --- ORDER DETAILS CARD: Refined Structure & Design --- */
.order-details-card { /* New class name */
    background: #f8faff; /* Very light blue background */
    padding: 25px;
    border-radius: 10px;
    margin: 25px 0;
    text-align: left;
    border: 1px solid #e0eaff; /* Subtle border */
}

.order-details-card .card-title {
    color: #006989;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 10px;
    margin-top: 0;
    margin-bottom: 20px;
}

.order-details-card .card-subtitle {
    color: #005a73;
    margin-top: 20px;
    margin-bottom: 5px;
    font-size: 1.1em;
}

.detail-group p {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px dotted #eee; /* Dotted line for separation */
    margin: 0;
    line-height: 1.4;
}

.detail-group p:last-child {
    border-bottom: none;
}

.detail-group.highlight {
    background: #e6f7ff; /* Slightly more distinct background for financial info */
    padding: 10px 15px;
    border-radius: 5px;
    margin: 15px 0;
}

.amount {
    font-weight: bold;
    color: #006989; /* Highlight the total amount */
    font-size: 1.1em;
}

/* --- SHIPPING ADDRESS --- */
.shipping-address-box {
    margin-top: 25px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.shipping-address-box address {
    font-style: normal;
    line-height: 1.5;
    color: #666;
    margin-top: 5px;
}

/* --- ACTION BUTTONS: Better Alignment and Hover --- */
.success-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* Two columns for actions */
    gap: 15px; /* Increased gap */
    margin-top: 30px;
}

/* General button styling */
.btn {
    padding: 12px 20px; /* Slightly larger buttons */
    border-radius: 8px; /* Softer button edges */
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9em;
    letter-spacing: 0.5px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.btn-primary {
    background: #006989;
    color: white;
    border: 1px solid #006989;
    box-shadow: 0 4px 10px rgba(0, 105, 137, 0.2);
    text-decoration: none;
    width: 190%;
}

.btn-primary:hover {
    background: #005a73;
    border-color: #005a73;
    transform: translateY(-2px); /* Lift effect on hover */
    box-shadow: 0 6px 15px rgba(0, 105, 137, 0.3);
}

.btn-outline, .btn-secondary { /* Added secondary style for "View Order Details" */
    background: white;
    color: #006989;
    border: 1px solid #006989;
    text-decoration: none;
}

.btn-secondary {
    background: #e6f7ff; /* Light blue background for secondary action */
    border-color: #006989;
}

.btn-outline:hover:not(:disabled), .btn-secondary:hover:not(:disabled) {
    background: #006989;
    color: white;
    transform: translateY(-2px); /* Lift effect on hover */
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Responsive adjustments for buttons */
@media (max-width: 480px) {
    .success-actions {
        grid-template-columns: 1fr; /* Stack buttons on smaller screens */
    }
}

/* --- Print Styles (Unchanged) --- */
@media print {
    body * {
        visibility: hidden;
    }
    #printable-receipt, #printable-receipt * {
        visibility: visible;
    }
    #printable-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}

.shipping-section, .products-section {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.section-subtitle {
    color: #005a73;
    font-size: 1.15em;
    margin-bottom: 12px;
    font-weight: 600;
}

.shipping-section address {
    font-style: normal;
    line-height: 1.6;
    color: #555;
    background: #f9f9f9;
    padding: 12px;
    border-radius: 5px;
    border-left: 3px solid #006989;
}

/* Products Table */
.products-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: white;
}

.products-table th {
    background: #006989;
    color: white;
    padding: 12px 10px;
    text-align: left;
    font-weight: 600;
}

.products-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #f0f0f0;
}

.products-table tbody tr:hover {
    background: #f8f9fa;
}

.products-table tfoot td {
    padding: 10px;
    border-bottom: none;
    font-size: 0.95em;
}

.products-table .subtotal-row td {
    padding-top: 15px;
    border-top: 2px solid #ddd;
}

.products-table .discount-row {
    color: #28a745;
    font-weight: 600;
}

.products-table .total-row {
    background: #e6f7ff;
    font-size: 1.1em;
    color: #006989;
}

.products-table .total-row td {
    padding: 15px 10px;
    border-top: 2px solid #006989;
}
</style>

<?php
include '../footer.php';
?>