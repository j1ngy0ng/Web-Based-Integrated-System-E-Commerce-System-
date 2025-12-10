<?php
include '../_base.php';
error_log("AJAX Cart Count called");
error_log("Session cart: " . print_r($_SESSION['cart'] ?? 'NOT SET', true));
error_log("User: " . print_r($_user->id ?? 'NOT LOGGED IN', true));

// Function to get cart count
function get_cart_count() {
    $cart = get_cart();
    $count = 0;
    
    if ($cart) {
        foreach ($cart as $id => $unit) {
            $count += $unit;
        }
    }
    
    return $count;
}

// Get the current cart count
$cart_count = get_cart_count();

echo json_encode([
    'success' => true,
    'count' => $cart_count,
    'timestamp' => time()
]);

?>