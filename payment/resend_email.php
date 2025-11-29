<?php
include '../_base.php';

// Load PHPMailer
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$orderID = req('orderID');

if (!$orderID) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    $stm = $_db->prepare('
        SELECT o.*, p.stripe_payment_intent_id, u.email, u.name as customer_name
        FROM `order` o 
        LEFT JOIN payment p ON o.orderID = p.orderID 
        LEFT JOIN user u ON o.userID = u.id
        WHERE o.orderID = ?
    ');
    $stm->execute([$orderID]);
    $order = $stm->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
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
            <h1>Payment Receipt</h1>
        </div>
        <div class='content'>
            <h2>Hello " . htmlspecialchars($order->customer_name) . ",</h2>
            <p>Here is your payment receipt as requested:</p>
            
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
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);

} catch (Exception $e) {
    error_log("Email error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()]);
}
?>