<?php
include '../_base.php';

// Load PHPMailer
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$id = req('id');
if (!$id) redirect('cart.php');

$stm = $_db->prepare('
    SELECT o.*, p.stripe_payment_intent_id, u.email, u.name as customer_name
    FROM `order` o 
    LEFT JOIN payment p ON o.orderID = p.orderID 
    LEFT JOIN user u ON o.userID = u.id
    WHERE o.orderID = ?
');
$stm->execute([$id]);
$order = $stm->fetch();

if (!$order) redirect('cart.php');

// Send email receipt
sendReceiptEmail($order);

$_title = 'Payment Successful';
include '../navbar.php';

// Function to send email receipt using PHPMailer
function sendReceiptEmail($order) {
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
                .order-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Payment Successful!</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($order->customer_name) . ",</h2>
                <p>Thank you for your purchase! Here's your order summary:</p>
                
                <div class='order-details'>
                    <p><strong>Order ID:</strong> #" . $order->orderID . "</p>
                    <p><strong>Amount Paid:</strong> RM " . number_format($order->TotalAmount, 2) . "</p>
                    <p><strong>Payment Date:</strong> " . date('F j, Y g:i A', strtotime($order->OrderDate)) . "</p>
                    " . ($order->stripe_payment_intent_id ? "<p><strong>Transaction ID:</strong> " . $order->stripe_payment_intent_id . "</p>" : "") . "
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
        <div class="success-icon">✅</div>
        <h2>Payment Successful!</h2>
        
        <div class="order-details">
            <p><strong>Order ID:</strong> #<?= $order->orderID ?></p>
            <p><strong>Customer:</strong> <?= htmlspecialchars($order->customer_name) ?></p>
            <p><strong>Email:</strong> <?= $order->email ?></p>
            <p><strong>Amount Paid:</strong> RM <?= number_format($order->TotalAmount, 2) ?></p>
            <p><strong>Payment Date:</strong> <?= date('F j, Y g:i A', strtotime($order->OrderDate)) ?></p>
            <?php if ($order->stripe_payment_intent_id): ?>
                <p><strong>Transaction ID:</strong> <?= $order->stripe_payment_intent_id ?></p>
            <?php endif; ?>
        </div>

        <div class="success-actions">
            <div>
                <button onclick="printReceipt()" class="btn btn-outline">🖨️ Print Receipt</button>
                <button onclick="sendEmail()" class="btn btn-outline" id="resendBtn">📧 Resend Email</button>
            </div>
            <div>
                <a href="../product/list.php" class="btn btn-primary">🛒 Continue Shopping</a>
                <a href="../order/detail.php?orderID=<?= $order->orderID ?>" class="btn btn-primary">📋 View Order Details</a>
            </div>
        </div>
    </div>
</div>

<!-- Hidden receipt for printing -->
<div id="printable-receipt" style="display: none;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
        <div style="text-align: center; border-bottom: 2px solid #006989; padding-bottom: 15px; margin-bottom: 20px;">
            <h1 style="color: #006989; margin: 0;">Chillax Sdn Bhd</h1>
            <p style="margin: 5px 0;">Payment Receipt</p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h3>Order Summary</h3>
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

function sendEmail() {
    // Show loading state
    const btn = document.getElementById('resendBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Sending...';
    btn.disabled = true;
    
    // Send AJAX request to resend email
    fetch('resend_email.php?orderID=<?= $order->orderID ?>', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('✅ Email receipt has been sent to <?= $order->email ?>');
            } else {
                alert('❌ Failed to send email: ' + (data.message || 'Please try again.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error sending email. Please try again later.');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}
</script>

<style>
.success-container {
    max-width: 500px;
    margin: 20px auto;
    padding: 20px 30px;
    border-radius: 30px;
    border: 1px solid grey;
    box-shadow: inset;
}

.success-icon {
    font-size: 4em;
    margin-bottom: 20px;
}

.order-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: left;
}

.success-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 15px;
    border: 1px solid #006989;
    border-radius: 5px;
    text-decoration: none;
    color: #006989;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn:hover:not(:disabled) {
    background: #006989;
    color: white;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: #006989;
    color: white;
}

.btn-primary:hover {
    background: #005a73;
}

.btn-outline {
    background: white;
    color: #006989;
}

.btn-outline:hover:not(:disabled) {
    background: #f8f9fa;
}

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
</style>

<?php
include '../footer.php';
?>