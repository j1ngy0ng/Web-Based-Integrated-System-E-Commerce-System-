<?php
include '../_base.php';
// ----------------------------------------------------------------------------
// 1. PRE-CALCULATE TOTALS & FETCH DATA
// ----------------------------------------------------------------------------
$cart = get_cart();
$cart_products = [];
$subtotal = 0;
$count = 0;
$has_unavailable_items = false;
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($cart) {
    foreach ($cart as $id => $unit) {
        $stm = $_db->prepare('SELECT p.*, c.categoryName FROM product p LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID WHERE p.productID = ?');
        $stm->execute([$id]);
        $p = $stm->fetch();

        if ($p) {
            $p->is_available = ($p->is_active == 1 && $p->stock > 0);
            $p->unit = $unit;
            $p->line_total = $p->price * $unit;
            $cart_products[] = $p;

            if ($p->is_available) {
                $subtotal += $p->line_total;
                $count += $unit;
            } else {
                $has_unavailable_items = true;
            }
        }
    }
}

// --- FETCH AVAILABLE VOUCHERS ---
$stm = $_db->prepare("SELECT * FROM vouchers WHERE expiry_date >= CURDATE() ORDER BY min_spend ASC");
$stm->execute();
$available_vouchers = $stm->fetchAll();

if ($_user) {
    $stm = $_db->prepare("SELECT * FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stm->execute([$_user->id]);
    $saved_addresses = $stm->fetchAll();

    // Auto-select default address if none selected
    if (!isset($_SESSION['selected_address']) && !empty($saved_addresses)) {
        foreach ($saved_addresses as $addr) {
            if ($addr->is_default) {
                $_SESSION['selected_address'] = $addr->address_id;
                break;
            }
        }
    }
}

// ----------------------------------------------------------------------------
// 2. HANDLE POST REQUESTS
// ----------------------------------------------------------------------------
if (is_post()) {
    $btn = req('btn');
    $id = req('id');
    $unit = req('unit', 1);

    // --------- CART ACTIONS ---------
    if ($btn == 'add_to_cart') {
        $cart = get_cart();
        if (!isset($cart[$id])) $cart[$id] = 0;
        $cart[$id] += $unit;

        $stm = $_db->prepare('SELECT stock, is_active FROM product WHERE productID = ?');
        $stm->execute([$id]);
        $product = $stm->fetch();

        if ($product) {
            if ($product->is_active == 0) {
                unset($cart[$id]);
                $message = 'Product is no longer available';
                $success = false;
            } elseif ($cart[$id] > $product->stock) {
                $cart[$id] = $product->stock;
                $message = 'Limited to available stock: ' . $product->stock;
                $success = true;
            } else {
                $message = 'Added to cart successfully!';
                $success = true;
            }
        } else {
            unset($cart[$id]);
            $message = 'Product not found';
            $success = false;
        }

        $_SESSION['cart'] = $cart;
        save_cart_to_db($cart);
        $_SESSION['cart_last_updated'] = time();

        $count = array_sum($cart);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'count' => $count,
                'message' => $message
            ]);
            exit;
        } else {
            temp('info', $message);
            redirect();
        }
    }

    if ($btn == 'update') {
        update_cart($id, $unit);
        $_SESSION['cart_last_updated'] = time();
        redirect();
    }

    if ($btn == 'delete') {
        $cart = get_cart();
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        save_cart_to_db($cart);
        if (empty($cart)) unset($_SESSION['voucher']);
        $_SESSION['cart_last_updated'] = time();
        redirect();
    }

    if ($btn == 'clear') {
        clear_cart();
        unset($_SESSION['voucher'], $_SESSION['selected_address'], $_SESSION['show_new_address_form']);
        $_SESSION['cart_last_updated'] = time();
        redirect('?');
    }

    // --------- VOUCHER ---------
    if ($btn == 'apply_voucher') {
        $code = strtoupper(trim(req('voucher_code')));
        $stm = $_db->prepare("SELECT * FROM vouchers WHERE voucher_code = ? AND expiry_date >= CURDATE()");
        $stm->execute([$code]);
        $voucher = $stm->fetch();

        if (!$voucher) {
            temp('info', 'Invalid or expired voucher code.');
        } elseif ($subtotal < $voucher->min_spend) {
            temp('info', "Minimum spend of RM " . number_format($voucher->min_spend, 2) . " required.");
        } else {
            $_SESSION['voucher'] = $voucher;
            temp('info', 'Voucher applied successfully!');
        }
        redirect();
    }

    if ($btn == 'remove_voucher') {
        unset($_SESSION['voucher']);
        temp('info', 'Voucher removed.');
        redirect();
    }

    // --------- REWARD POINTS ---------
    if ($btn == 'redeem_points' && $_user) {
        $input_points = max(0, (int) req('redeem_points'));
        $_SESSION['redeem_points'] = $input_points;
        redirect();
    }

    // --------- ADDRESS HANDLERS ---------
    if ($btn == 'save_address') {
        $recipient = req('recipient_name');
        $phone = req('phone_number');
        $addr1 = req('address_line1');
        $addr2 = req('address_line2');
        $city = req('city');
        $state = req('state');
        $postal = req('postal_code');
        $lat = req('latitude');
        $lng = req('longitude');
        $set_default = req('set_default') ? 1 : 0;

        $phone = preg_replace('/\D/', '', $phone);

        if (!preg_match('/^(01[0-9]{8,9}|03[0-9]{8})$/', $phone)) {
            temp('info', 'Invalid phone number format. Use Malaysian format (e.g., 0123456789)');
            redirect();
            return;
        }

        // If set as default, unset other defaults
        if ($set_default) {
            $stm = $_db->prepare("UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ?");
            $stm->execute([$_user->id]);
        }

        $stm = $_db->prepare("
            INSERT INTO shipping_addresses 
            (user_id, recipient_name, phone_number, address_line1, address_line2, city, state, postal_code, latitude, longitude, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stm->execute([$_user->id, $recipient, $phone, $addr1, $addr2, $city, $state, $postal, $lat, $lng, $set_default]);
        
        $_SESSION['selected_address'] = $_db->lastInsertId();
            temp('info', 'Address saved successfully!');
            redirect();
    }

    if ($btn == 'select_address') {
        $address_id = req('address_id');
        $_SESSION['selected_address'] = $address_id;
        temp('info', 'Delivery address selected.');
        redirect();
    }

    if ($btn == 'delete_address') {
        $address_id = req('address_id');
        $stm = $_db->prepare("DELETE FROM shipping_addresses WHERE address_id = ? AND user_id = ?");
        $stm->execute([$address_id, $_user->id]);
        
        // If deleted address was selected, auto-select another one
        if (($_SESSION['selected_address'] ?? null) == $address_id) {
            unset($_SESSION['selected_address']);
            
            // Get remaining addresses
            $stm = $_db->prepare("SELECT address_id FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC LIMIT 1");
            $stm->execute([$_user->id]);
            $next_addr = $stm->fetch();
            
            if ($next_addr) {
                $_SESSION['selected_address'] = $next_addr->address_id;
            }
        }
        
        temp('info', 'Address deleted.');
        redirect();
    }

    if ($btn == 'use_new_address') {
        unset($_SESSION['selected_address']);
        $_SESSION['show_new_address_form'] = true;
        redirect();
    }

    if ($btn == 'update_address') {
        $address_id = req('address_id');
        $recipient = req('recipient_name');
        $phone = req('phone_number');
        $addr1 = req('address_line1');
        $addr2 = req('address_line2');
        $city = req('city');
        $state = req('state');
        $postal = req('postal_code');
        $lat = req('latitude');
        $lng = req('longitude');
        $set_default = req('set_default') ? 1 : 0;

        $phone = preg_replace('/\D/', '', $phone);

        if (!preg_match('/^(01[0-9]{8,9}|03[0-9]{8})$/', $phone)) {
            temp('info', 'Invalid phone number format. Use Malaysian format (e.g., 0123456789)');
            redirect();
            return;
        }

        // If set as default, unset other defaults
        if ($set_default) {
            $stm = $_db->prepare("UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ? AND address_id != ?");
            $stm->execute([$_user->id, $address_id]);
        }

        $stm = $_db->prepare("
            UPDATE shipping_addresses 
            SET recipient_name = ?, phone_number = ?, address_line1 = ?, address_line2 = ?,
                city = ?, state = ?, postal_code = ?, latitude = ?, longitude = ?, is_default = ?
            WHERE address_id = ? AND user_id = ?
        ");
        $stm->execute([
            $recipient, $phone, $addr1, $addr2, $city, $state, $postal, $lat, $lng, $set_default,
            $address_id, $_user->id
        ]);
        
        // If this is the address that was selected, keep it selected
        if (($_SESSION['selected_address'] ?? null) == $address_id) {
            $_SESSION['selected_address'] = $address_id;
        }
        
        temp('info', 'Address updated successfully!');
        redirect();
    }
}

// ----------------------------------------------------------------------------
// 3. CALCULATE GRAND TOTALS
// ----------------------------------------------------------------------------

// 3.1 Subtotal is already pre-calculated

// 3.2 Voucher discount
$discount_amount = 0;
$applied_voucher = $_SESSION['voucher'] ?? null;

if ($applied_voucher && $subtotal >= $applied_voucher->min_spend) {
    if ($applied_voucher->discount_type === 'fixed') {
        $discount_amount = $applied_voucher->discount_value;
    } else {
        $discount_amount = $subtotal * ($applied_voucher->discount_value / 100);
    }
    if ($discount_amount > $subtotal) $discount_amount = $subtotal;
} else {
    $applied_voucher = null;
    $discount_amount = 0;
}

// 3.3 Total after voucher
$grand_total = max($subtotal - $discount_amount, 0);

// 3.4 Reward points redemption
$redeem_points_used = 0;
$redeem_discount = 0;
$grand_total_after_points = $grand_total;

if ($_user) {
    // Refresh points from DB to ensure latest value
    $stm = $_db->prepare('SELECT reward_points FROM user WHERE id = ?');
    $stm->execute([$_user->id]);
    $_user->reward_points = (int) $stm->fetchColumn();

    $user_points = $_user->reward_points;

    // Maximum redeemable points based on total
    $max_redeemable_points = min(floor($grand_total * 100), $user_points);

    if (isset($_SESSION['redeem_points'])) {
        $redeem_points_used = min($_SESSION['redeem_points'], $max_redeemable_points);
        $redeem_discount = floor($redeem_points_used / 100); // 100 points = RM1
    }

    $grand_total_after_points = max($grand_total - $redeem_discount, 0);
}

// ----------------------------------------------------------------------------
// 4. READY TO DISPLAY
// ----------------------------------------------------------------------------
$isCartPage = true;
$_title = 'Order | Shopping Cart';
include '../navbar.php';
?>


<style>
    /* ... (Standard Layout Styles) ... */
    .cart-page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
        min-height: 70vh;
    }

    .cart-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .cart-header h1 {
        color: #2d3748;
        font-size: 2.5em;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #006989, #00a8cc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .cart-header p {
        color: #718096;
        font-size: 1.1em;
    }

    .cart-summary {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 105, 137, 0.15);
        overflow: hidden;
        border: 1px solid #e8f4f8;
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cart-table th {
        background: linear-gradient(#006989);
        color: white;
        padding: 20px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 1em;
    }

    .cart-table th:first-child {
        border-radius: 20px 0 0 0;
    }

    .cart-table th:last-child {
        border-radius: 0 20px 0 0;
    }

    .cart-table td {
        padding: 25px 15px;
        border-bottom: 1px solid #f1f8fc;
        vertical-align: middle;
        transition: background-color 0.3s ease;
    }

    .cart-table tr:hover td {
        background-color: #f8fcfd;
    }

    .cart-table tr:last-child td {
        border-bottom: none;
    }

    .cart-item-image {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        overflow: hidden;
        background: #f8fafc;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .cart-item-image:hover {
        transform: scale(1.05);
    }

    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cart-item-name {
        font-weight: 600;
        color: #2d3748;
        font-size: 1.1em;
        margin-bottom: 5px;
    }

    .cart-item-category {
        color: #718096;
        font-size: 0.9em;
    }

    .cart-item-price {
        color: #006989;
        font-weight: 700;
        font-size: 1.2em;
    }

    .quantity-form {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #f7f7fa;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 4px 6px;
        width: fit-content;
    }

    .qty-btn {
        width: 32px;
        height: 32px;
        font-size: 18px;
        border: none;
        background: #ffffff;
        border-radius: 6px;
        cursor: pointer;
        border: 1px solid #ccc;
        transition: 0.2s ease;
    }

    .qty-btn:hover {
        background: #e8f0ff;
        border-color: #7aa7ff;
    }

    .qty-display {
        padding: 0 12px;
        font-size: 16px;
        font-weight: 600;
    }

    .delete-btn {
        background: linear-gradient(135deg, #e53e3e, #c53030);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9em;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .delete-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
    }

    .cart-subtotal {
        color: #2d3748;
        font-weight: 700;
        font-size: 1.2em;
    }

    /* --- UPDATED CONSISTENT THEME STYLES --- */
    .totals-section {
        background: linear-gradient(135deg, #f8fafc, #e8f4f8);
        padding: 30px;
        border-top: 2px solid #e8f4f8;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 30px;
    }

    /* Voucher Area */
    .voucher-box {
        flex: 1;
        min-width: 300px;
    }

    .voucher-header {
        font-weight: 700;
        color: #006989;
        margin-bottom: 15px;
        display: block;
        font-size: 1.1em;
    }

    /* Manual Input */
    .voucher-form {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .voucher-input {
        flex: 1;
        padding: 12px;
        border: 2px solid #cbd5e0;
        border-radius: 10px;
        outline: none;
    }

    .voucher-input:focus {
        border-color: #006989;
        box-shadow: 0 0 0 3px rgba(0, 105, 137, 0.1);
    }

    /* Apply Button - Matches Primary Theme */
    .btn-apply {
        background: linear-gradient(135deg, #006989, #0088aa);
        color: white;
        border: none;
        padding: 15px 25px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-apply:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 105, 137, 0.2);
    }

    /* Voucher List */
    .voucher-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 5px;
    }

    .voucher-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        border: 1px solid #e2e8f0;
        padding: 15px;
        border-radius: 12px;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }

    .voucher-card:hover {
        border-color: #006989;
        box-shadow: 0 4px 12px rgba(0, 105, 137, 0.08);
    }

    /* Left border accent for visual consistency */
    .voucher-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #006989;
    }

    .voucher-info strong {
        color: #006989;
        font-size: 1.1em;
        display: block;
        margin-bottom: 2px;
    }

    .voucher-info p {
        margin: 0;
        color: #718096;
        font-size: 0.85em;
    }

    /* Claim Button - Consistent Blue Outline Style */
    .voucher-btn {
        background: transparent;
        color: #006989;
        border: 2px solid #006989;
        padding: 6px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.85em;
        transition: all 0.2s;
    }

    .voucher-btn:hover {
        background: #006989;
        color: white;
    }

    /* Disabled State */
    .voucher-card.disabled {
        opacity: 0.6;
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    .voucher-card.disabled::before {
        background: #cbd5e0;
    }

    .voucher-card.disabled .voucher-btn {
        border-color: #cbd5e0;
        color: #a0aec0;
        cursor: not-allowed;
    }

    .voucher-card.disabled .voucher-btn:hover {
        background: transparent;
        color: #a0aec0;
    }

    /* Applied State - Theme Consistent */
    .applied-voucher-tag {
        margin-top: 10px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #e0f2f5;
        /* Light version of your teal */
        color: #006989;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 1em;
        font-weight: 600;
        border: 1px solid #bce3eb;
        width: 100%;
        box-sizing: border-box;
        justify-content: space-between;
    }

    .remove-voucher {
        background: white;
        border: 1px solid #e53e3e;
        color: #e53e3e;
        cursor: pointer;
        font-weight: bold;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1em;
        line-height: 1;
        transition: 0.2s;
    }

    .remove-voucher:hover {
        background: #e53e3e;
        color: white;
    }

    /* Address Box */
    .address-box {
        flex: 1;
        min-width: 300px;
        border-right: 1px solid #e2e8f0;
        padding-right: 30px;
    }

    .address-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 15px;
    }

    .address-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: white;
        border: 2px solid #e2e8f0;
        padding: 15px;
        border-radius: 12px;
        transition: all 0.2s;
        position: relative;
        cursor: pointer;
    }

    .address-card:hover {
        border-color: #006989;
        box-shadow: 0 4px 12px rgba(0, 105, 137, 0.08);
    }

    .address-card.selected {
        border-color: #006989;
        background: #f0f9fc;
    }

    .address-card input[type="radio"] {
        margin-top: 3px;
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #006989;
    }

    .address-info {
        flex: 1;
        cursor: pointer;
    }

    .address-info strong {
        color: #2d3748;
        font-size: 1.05em;
        display: inline-block;
        margin-bottom: 5px;
    }

    .address-info p {
        margin: 2px 0;
        color: #4a5568;
        font-size: 0.9em;
        line-height: 1.4;
    }

    .default-badge {
        background: #006989;
        color: white;
        font-size: 0.7em;
        padding: 2px 8px;
        border-radius: 6px;
        margin-left: 8px;
        font-weight: 600;
    }

    .edit-address-btn {
        background: transparent;
        border: none;
        color: #e53e3e;
        cursor: pointer;
        font-size: 1.1em;
        padding: 5px;
        transition: 0.2s;
    }

    .edit-address-btn:hover {
        transform: scale(1.2);
    }

    .delete-address-btn {
        background: transparent;
        border: none;
        color: #e53e3e;
        cursor: pointer;
        font-size: 1.2em;
        padding: 5px;
        transition: 0.2s;
    }

    .delete-address-btn:hover {
        transform: scale(1.2);
    }

    .btn-use-new {
        width: 100%;
        background: transparent;
        border: 2px dashed #006989;
        color: #006989;
        padding: 12px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }

    .btn-use-new:hover {
        background: #f0f9fc;
        border-style: solid;
    }

    .new-address-form {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }

    .address-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 10px;
        font-size: 0.95em;
        transition: 0.2s;
        box-sizing: border-box;
    }

    .address-input:focus {
        border-color: #006989;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 105, 137, 0.1);
    }

    @media (max-width: 768px) {
        .address-box {
            border-right: none;
            padding-right: 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
    }

    .unavailable-item {
        background-color: #f7fafc !important;
        opacity: 0.7;
        border-left: 4px solid #cbd5e0 !important;
    }

    .unavailable-item td {
        color: #a0aec0 !important;
    }

    .grayscale {
        filter: grayscale(100%);
        opacity: 0.6;
    }

    .cart-table tr.unavailable-item:hover {
        background-color: #f1f5f9 !important;
    }

    .unavailable-message {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 0.7;
        }

        50% {
            opacity: 1;
        }

        100% {
            opacity: 0.7;
        }
    }

    /* Style for the delete button on unavailable items */
    .unavailable-item .delete-btn {
        background-color: #fed7d7 !important;
        border-color: #fc8181 !important;
        color: #c53030 !important;
    }

    .unavailable-item .delete-btn:hover {
        background-color: #feb2b2 !important;
        transform: scale(1.1) !important;
    }

    /* Totals Area */
    .totals-box {
        flex: 1;
        min-width: 250px;
        text-align: right;
    }

    .totals-row {
        display: flex;
        justify-content: flex-end;
        gap: 30px;
        margin-bottom: 10px;
        color: #4a5568;
        font-size: 1.1em;
    }

    .totals-row.final {
        font-size: 1.5em;
        font-weight: 800;
        color: #006989;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #cbd5e0;
    }

    .discount-text {
        color: #e53e3e;
    }

    .cart-actions {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-top: 40px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 15px 35px;
        border: none;
        border-radius: 12px;
        font-size: 1.1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-align: center;
    }

    .btn-primary {
        background: linear-gradient(135deg, #006989, #0088aa);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 105, 137, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #005672, #006989);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 105, 137, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #e53e3e, #c53030);
        color: white;
        box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
    }

    .btn-secondary:hover {
        background: linear-gradient(135deg, #c53030, #a31818);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
    }

    .btn-outline {
        background: transparent;
        color: #006989;
        border: 2px solid #006989;
    }

    .btn-outline:hover {
        background: #006989;
        color: white;
        transform: translateY(-3px);
    }

    .empty-cart {
        text-align: center;
        padding: 80px 20px;
        color: #718096;
    }

    .empty-cart-icon {
        font-size: 4em;
        margin-bottom: 20px;
        opacity: 0.7;
    }

    .empty-cart h3 {
        font-size: 1.8em;
        margin-bottom: 15px;
        color: #2d3748;
    }

    .empty-cart p {
        font-size: 1.1em;
        margin-bottom: 30px;
        color: #718096;
    }

    .stock-warning {
        color: #e67e22;
        font-size: 0.85em;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .low-stock {
        color: #e74c3c;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .totals-section {
            flex-direction: column-reverse;
        }

        .totals-box,
        .voucher-box {
            width: 100%;
            text-align: left;
        }

        .totals-row {
            justify-content: space-between;
        }

        .cart-table {
            display: block;
            overflow-x: auto;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px 10px;
        }

        .btn {
            padding: 12px 25px;
            font-size: 1em;
        }

        .quantity-form {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>

<div class="cart-page-container">
    <div class="cart-header">
        <h1>🛒 Shopping Cart</h1>
        <p>Review your items and proceed to checkout</p>
    </div>

    <?php if ($cart_products): ?>
        <div class="cart-summary">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_products as $p):
                        $units_options = ['' => 'Select quantity'];
                        $max_units = min($p->stock, 10);
                        for ($i = 1; $i <= $max_units; $i++) {
                            $units_options[$i] = $i . ' unit' . ($i > 1 ? 's' : '');
                        }
                        $main_photo = get_main_photo($p->productID);

                        // Determine if item is unavailable
                        $is_inactive = ($p->is_active == 0);
                        $is_out_of_stock = ($p->stock == 0);
                        $is_unavailable = $is_inactive || $is_out_of_stock;
                        $row_class = $is_unavailable ? 'unavailable-item' : '';
                        $disabled_message = '';

                        if ($is_inactive) {
                            $disabled_message = 'Product no longer available';
                        } elseif ($is_out_of_stock) {
                            $disabled_message = 'Out of stock';
                        }
                    ?>
                        <tr class="<?= $row_class ?>" data-product-id="<?= $p->productID ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div class="cart-item-image <?= $is_unavailable ? 'grayscale' : '' ?>">
                                        <?php if (!empty($main_photo)): ?>
                                            <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f8fafc, #e8f4f8); display: flex; align-items: center; justify-content: center; font-size: 0.8em; color: #718096;">No Image</div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="cart-item-name"><?= $p->name ?></div>
                                        <div class="cart-item-category"><?= $p->categoryName ?? 'Uncategorized' ?></div>
                                        <?php if ($is_unavailable): ?>
                                            <div class="stock-warning unavailable-message" style="color: #e53e3e; font-weight: bold;">
                                                ❌ <?= $disabled_message ?>
                                            </div>
                                        <?php elseif ($p->stock < $p->unit): ?>
                                            <div class="stock-warning low-stock">⚠️ Only <?= $p->stock ?> in stock</div>
                                        <?php elseif ($p->stock <= 5): ?>
                                            <div class="stock-warning">⚠️ Low stock (<?= $p->stock ?> left)</div>
                                        <?php endif; ?>

                                        <?php if ($is_inactive): ?>
                                            <div style="color: #a0aec0; font-size: 0.85em; margin-top: 2px;">
                                                Status: Inactive
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="cart-item-price">
                                <?php if ($is_unavailable): ?>
                                    <span style="text-decoration: line-through; color: #a0aec0;">RM <?= number_format($p->price, 2) ?></span>
                                    <br><small style="color: #e53e3e;">Not included in total</small>
                                <?php else: ?>
                                    RM <?= number_format($p->price, 2) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_unavailable): ?>
                                    <div style="color: #a0aec0; font-style: italic;">
                                        Cannot update quantity
                                    </div>
                                <?php else: ?>
                                    <div class="quantity-form">
                                        <!-- Minus button -->
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="btn" value="update">
                                            <input type="hidden" name="id" value="<?= $p->productID ?>">
                                            <input type="hidden" name="unit" value="<?= max(1, $p->unit - 1) ?>">
                                            <button type="submit" class="qty-btn" <?= $is_unavailable ? 'disabled' : '' ?>>-</button>
                                        </form>

                                        <div class="qty-display"><?= $p->unit ?></div>

                                        <!-- Plus button -->
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="btn" value="update">
                                            <input type="hidden" name="id" value="<?= $p->productID ?>">
                                            <input type="hidden" name="unit" value="<?= $p->unit + 1 ?>">
                                            <button type="submit" class="qty-btn" <?= $is_unavailable ? 'disabled' : '' ?>>+</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="cart-subtotal">
                                <?php if ($is_unavailable): ?>
                                    <span style="text-decoration: line-through; color: #a0aec0;">RM <?= number_format($p->line_total, 2) ?></span>
                                <?php else: ?>
                                    RM <?= number_format($p->line_total, 2) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="delete-cart-form" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $p->productID ?>">
                                    <input type="hidden" name="btn" value="delete">
                                    <button type="submit" class="delete-btn"
                                        data-product-name="<?= htmlspecialchars($p->name) ?>"
                                        style="<?= $is_unavailable ? 'background-color: #e53e3e;' : '' ?>">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($has_unavailable_items): ?>
                <div class="unavailable-warning-banner" style="background: linear-gradient(135deg, #fed7d7, #feb2b2); border: 1px solid #fc8181; padding: 15px; border-radius: 8px; margin: 20px 0; display: flex; align-items: center; gap: 10px;">
                    <div style="font-size: 24px;">⚠️</div>
                    <div style="flex: 1;">
                        <strong style="color: #c53030;">Some items in your cart are unavailable!</strong>
                        <p style="margin: 5px 0 0 0; color: #742a2a;">
                            You have items that are out of stock or disabled. These items are greyed out and not included in the total.
                            Please remove them before proceeding to checkout.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="totals-section">
                <div class="voucher-box">
                    <span class="voucher-header">Available Vouchers</span>

                    <?php if (!$applied_voucher): ?>

                        <form method="post" class="voucher-form">
                            <input type="text" name="voucher_code" class="voucher-input" placeholder="Enter code (e.g. SAVE10)" required>
                            <button type="submit" name="btn" value="apply_voucher" class="btn-apply">Apply</button>
                        </form>

                        <?php if ($available_vouchers): ?>
                            <div class="voucher-list">
                                <?php foreach ($available_vouchers as $v):
                                    $min_met = $subtotal >= $v->min_spend;
                                    $desc = ($v->discount_type == 'fixed')
                                        ? "RM " . number_format($v->discount_value, 0) . " OFF"
                                        : number_format($v->discount_value, 0) . "% OFF";
                                ?>
                                    <div class="voucher-card <?= $min_met ? '' : 'disabled' ?>">
                                        <div class="voucher-info">
                                            <strong><?= htmlspecialchars($v->voucher_code) ?></strong>
                                            <p><?= $desc ?> (Min spend: RM<?= $v->min_spend ?>)</p>
                                        </div>
                                        <form method="post">
                                            <input type="hidden" name="voucher_code" value="<?= $v->voucher_code ?>">
                                            <button type="submit" name="btn" value="apply_voucher" class="voucher-btn" <?= $min_met ? '' : 'disabled title="Spend more to unlock"' ?>>
                                                <?= $min_met ? 'Claim' : 'Locked' ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="applied-voucher-tag">
                            <div>
                                <span style="display:block; font-size:0.8em; opacity:0.8;">Applied Voucher:</span>
                                <span>🏷️ <b><?= htmlspecialchars($applied_voucher->voucher_code) ?></b></span>
                            </div>
                            <form method="post" style="display:inline;">
                                <button type="submit" name="btn" value="remove_voucher" class="remove-voucher" title="Remove Voucher">&times;</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="points-redemption-box" style="margin-top: 15px; padding: 15px; background: #f0fdf4; border: 1px solid #c6f6d5; border-radius: 8px;">
                    <strong>Reward Points:</strong> <?= number_format($user_points) ?> points<br>
                    <small>Every 100 points = RM1 discount</small>

                    <form method="post" style="margin-top: 10px;">
                        <input type="hidden" name="btn" value="redeem_points">
                        <label for="redeem_points">Redeem points:</label>
                        <input type="number" name="redeem_points" id="redeem_points"
                            min="0" max="<?= $max_redeemable_points ?>"
                            step="100"
                            value="<?= $redeem_points ?>"
                            style="width: 100px; margin-left: 10px;">
                        <button type="submit" class="btn-apply" style="margin-left: 10px;">Apply</button>
                    </form>

                    <?php if ($redeem_discount > 0): ?>
                        <div style="margin-top: 10px; color: #38a169;">
                            ✅ Discount applied: RM <?= number_format($redeem_discount, 2) ?>
                        </div>
                    <?php endif; ?>
                </div>


                <!-- Address Selection Box -->
                <div class="address-box">
                    <span class="voucher-header">Delivery Address</span>

                    <?php if ($saved_addresses): ?>
                        <div class="address-list">
                            <?php foreach ($saved_addresses as $addr): ?>
                                <form method="post" class="address-card <?= ($_SESSION['selected_address'] ?? ($addr->is_default ? $addr->address_id : null)) == $addr->address_id ? 'selected' : '' ?>">
                                    <input type="radio" name="address_id" value="<?= $addr->address_id ?>"
                                        id="addr_<?= $addr->address_id ?>"
                                        <?= ($_SESSION['selected_address'] ?? ($addr->is_default ? $addr->address_id : null)) == $addr->address_id ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                    <input type="hidden" name="btn" value="select_address">
                                    <label for="addr_<?= $addr->address_id ?>" class="address-info">
                                        <strong><?= htmlspecialchars($addr->recipient_name) ?></strong>
                                        <?php if ($addr->is_default): ?><span class="default-badge">Default</span><?php endif; ?>
                                        <p><?= htmlspecialchars($addr->phone_number) ?></p>
                                        <p><?= htmlspecialchars($addr->address_line1) ?><?= $addr->address_line2 ? ', ' . htmlspecialchars($addr->address_line2) : '' ?></p>
                                        <p><?= htmlspecialchars($addr->postal_code) ?> <?= htmlspecialchars($addr->city) ?>, <?= htmlspecialchars($addr->state) ?></p>
                                    </label>
                                    <div class="address-actions">
                                        <button type="button" class="edit-address-btn"
                                            onclick="editAddress(
                                                <?= $addr->address_id ?>, 
                                                {
                                                    recipient_name: '<?= addslashes($addr->recipient_name) ?>',
                                                    phone_number: '<?= addslashes($addr->phone_number) ?>',
                                                    address_line1: '<?= addslashes($addr->address_line1) ?>',
                                                    address_line2: '<?= addslashes($addr->address_line2) ?>',
                                                    city: '<?= addslashes($addr->city) ?>',
                                                    state: '<?= addslashes($addr->state) ?>',
                                                    postal_code: '<?= addslashes($addr->postal_code) ?>',
                                                    latitude: <?= $addr->latitude ? "'" . addslashes($addr->latitude) . "'" : 'null' ?>,
                                                    longitude: <?= $addr->longitude ? "'" . addslashes($addr->longitude) . "'" : 'null' ?>,
                                                    is_default: <?= $addr->is_default ?>
                                                }
                                            )">
                                            ✏️
                                        </button>
                                        <button type="submit" name="btn" value="delete_address" class="delete-address-btn"
                                            onclick="return confirmDeleteAddress(event)">
                                            🗑️
                                        </button>
                                    </div>
                                </form>

                            <?php endforeach; ?>
                        </div>

                        <button class="btn-use-new" onclick="toggleNewAddressForm(); return false;">+ Add New Address</button>
                    <?php endif; ?>

                    <!-- New Address Form -->
                    <div class="new-address-form" style="<?= !$saved_addresses || isset($_SESSION['show_new_address_form']) ? 'display:block;' : 'display:none;' ?>">
                        <form method="post" id="address-form">
                            <input type="hidden" name="btn" value="save_address">
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">

                            <input type="text" id="address-search" class="address-input" placeholder="🔍 Search address or enter manually..." autocomplete="off">
                            <div id="map" style="display:none; height: 250px; border-radius: 12px; margin: 15px 0; border: 2px solid #e2e8f0;"></div>

                            <input type="text" name="recipient_name" id="recipient_name" placeholder="Recipient Name *" class="address-input" required>
                            <input type="tel"
                                name="phone_number"
                                id="phone_number"
                                placeholder="Phone Number * (e.g., 0123456789)"
                                class="address-input"
                                pattern="^(01[0-9]{8,9}|03[0-9]{8})$"
                                maxlength="11"
                                required>
                            <input type="text" name="address_line1" id="address_line1" placeholder="Address Line 1 *" class="address-input" required>
                            <input type="text" name="address_line2" id="address_line2" placeholder="Address Line 2 (Optional)" class="address-input">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="text" name="city" id="city" placeholder="City *" class="address-input" required>
                                <input type="text" name="state" id="state" placeholder="State *" class="address-input" required>
                            </div>

                            <input type="text" name="postal_code" id="postal_code" placeholder="Postal Code *" class="address-input" required>

                            <label style="display: flex; align-items: center; gap: 8px; margin: 10px 0; color: #4a5568;">
                                <input type="checkbox" name="set_default" value="1">
                                Set as default address
                            </label>

                            <button type="submit" class="btn-apply" style="width: 100%; margin-top: 10px;">💾 Save Address</button>
                        </form>
                    </div>
                </div>

                <div class="totals-box">
                    <div class="totals-row">
                        <span>Subtotal:</span>
                        <span>RM <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                        <div class="totals-row discount-text">
                            <span>Voucher Discount:</span>
                            <span>- RM <?= number_format($discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($redeem_discount > 0): ?>
                        <div class="totals-row discount-text">
                            <span>Points Redeemed (<?= $redeem_points_used ?> pts):</span>
                            <span>- RM <?= number_format($redeem_discount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="totals-row final">
                        <span>Grand Total:</span>
                        <span>RM <?= number_format($grand_total_after_points, 2) ?></span>
                    </div>
                </div>

                <?php
                $can_checkout = true;
                $block_checkout_reasons = [];

                // Check each product in cart
                foreach ($cart_products as $p) {
                    if ($p->is_active == 0) {
                        $can_checkout = false;
                        $block_checkout_reasons[] = "Product '{$p->name}' is inactive";
                    } elseif ($p->stock == 0) {
                        $can_checkout = false;
                        $block_checkout_reasons[] = "Product '{$p->name}' is out of stock";
                    } elseif ($p->stock < $p->unit) {
                        $can_checkout = false;
                        $block_checkout_reasons[] = "Product '{$p->name}' has insufficient stock (only {$p->stock} available)";
                    }
                }
                ?>
            </div>
        </div>

        <div class="cart-actions">
            <button type="button" class="btn btn-secondary" onclick="clearCart()">🗑️ Clear Cart</button>
            <a href="../product/list.php" class="btn btn-outline">← Continue Shopping</a>
            <?php if ($_user?->role == 'Member'): ?>
                <?php if (isset($_SESSION['selected_address']) || !empty($saved_addresses)): ?>
                    <?php if (!empty($cart_products)): ?>
                        <div class="checkout-section">
                            <button id="checkout-btn"
                                class="btn btn-primary"
                                onclick="return validateCheckout()"
                                <?= !$can_checkout ? 'disabled' : '' ?>
                                style="<?= !$can_checkout ? 'opacity: 0.5; cursor: not-allowed; background-color: #cbd5e0 !important;' : '' ?>">
                                <?php if ($can_checkout): ?>
                                    Proceed to Checkout (RM <?= number_format($grand_total_after_points, 2) ?>)
                                <?php else: ?>
                                    ❌ Cannot Checkout - Fix Issues Above
                                <?php endif; ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn btn-primary" onclick="alert('Please select or add a delivery address first.'); return false;">🛒 Proceed to Checkout</button>
                <?php endif; ?>
            <?php else: ?>
                <a href="/login.php" class="btn btn-outline">🔐 Login to Checkout</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-cart">
            <h3>Your cart is empty</h3>
            <a href="../product/list.php" class="btn btn-primary">🛍️ Start Shopping</a>
        </div>
    <?php endif; ?>
</div>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCnmt907PtCJ_i5Yd3tIf2_w6CR3Y7KL84&libraries=places&callback=initAutocomplete" async defer></script>
<script>
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('Forms found:', $('form').length);
    // Google Maps Integration
    let map, marker, autocomplete;

    function initAutocomplete() {
        console.log('initAutocomplete called');
        const input = document.getElementById('address-search');
        if (!input) {
            console.log('Address search input not found, skipping autocomplete');
            return;
        }

        try {
            autocomplete = new google.maps.places.Autocomplete(input, {
                componentRestrictions: {
                    country: 'my'
                },
                fields: ['address_components', 'geometry', 'formatted_address', 'name']
            });

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();

                if (!place.geometry) {
                    alert('No details available for this address');
                    return;
                }

                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();

                $('#latitude').val(lat);
                $('#longitude').val(lng);

                let streetNumber = '',
                    route = '',
                    premise = '',
                    subpremise = '';
                let city = '',
                    state = '',
                    postal = '';

                for (let component of place.address_components) {
                    const types = component.types;

                    if (types.includes('street_number')) streetNumber = component.long_name;
                    if (types.includes('route')) route = component.long_name;
                    if (types.includes('premise')) premise = component.long_name;
                    if (types.includes('subpremise')) subpremise = component.long_name;
                    if (types.includes('locality')) city = component.long_name;
                    if (types.includes('sublocality') && !city) city = component.long_name;
                    if (types.includes('administrative_area_level_1')) state = component.long_name;
                    if (types.includes('postal_code')) postal = component.long_name;
                }

                let addressLine1 = '';
                if (place.name && !place.name.includes(route) && !place.name.match(/^\d+$/)) {
                    addressLine1 = place.name;
                }
                if (premise && premise !== place.name) {
                    addressLine1 += (addressLine1 ? ', ' : '') + premise;
                }
                if (subpremise) {
                    addressLine1 += (addressLine1 ? ', ' : '') + 'Unit ' + subpremise;
                }

                let addressLine2 = '';
                if (streetNumber) addressLine2 = streetNumber;
                if (route) addressLine2 += (addressLine2 ? ' ' : '') + route;

                if (!addressLine1 && place.formatted_address) {
                    addressLine1 = place.formatted_address.split(',')[0];
                }

                $('#address_line1').val(addressLine1.trim());
                $('#address_line2').val(addressLine2.trim());
                $('#city').val(city);
                $('#state').val(state);
                $('#postal_code').val(postal);

                initMap(lat, lng);
            });
        } catch (error) {
            console.error('Google Maps autocomplete error:', error);
        }
    }

    function clearCart() {
        if (!confirm('Clear entire cart?')) {
            return false;
        }

        const form = $('<form>', {
            method: 'POST',
            action: window.location.href
        });

        form.append($('<input>', {
            type: 'hidden',
            name: 'btn',
            value: 'clear'
        }));

        form.appendTo('body').submit();
    }

    function updateCartCount() {
        const itemCount = <?= $count ?>;
        $('.cart-count').text(itemCount);
    }
    updateCartCount();

    function initMap(lat, lng) {
        const mapDiv = document.getElementById('map');
        if (!mapDiv) {
            console.error('Map div not found!');
            return;
        }
        mapDiv.style.display = 'block';

        try {
            const location = {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            };

            map = new google.maps.Map(mapDiv, {
                center: location,
                zoom: 15,
                disableDefaultUI: false,
                zoomControl: true,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            if (marker) marker.setMap(null);

            marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP
            });

            marker.addListener('dragend', function() {
                const pos = marker.getPosition();
                $('#latitude').val(pos.lat());
                $('#longitude').val(pos.lng());
            });
        } catch (error) {
            console.error('Error initializing map:', error);
            mapDiv.innerHTML = '<p style="color: red; padding: 20px;">Map could not be loaded. Please check your API key.</p>';
        }
    }

    function toggleNewAddressForm() {
        $('.new-address-form').slideToggle(300);
    }

    function editAddress(addressId, addressData) {
        $('.new-address-form').slideDown(300);
        $('#address-form input[name="address_id"]').remove();
        $('#address-form input[name="btn"]').val('update_address');
        $('#address-form').append('<input type="hidden" name="address_id" value="' + addressId + '">');

        $('#address-search').val(addressData.address_line1 + (addressData.address_line2 ? ', ' + addressData.address_line2 : ''));
        $('#recipient_name').val(addressData.recipient_name);
        $('#phone_number').val(addressData.phone_number);
        $('#address_line1').val(addressData.address_line1);
        $('#address_line2').val(addressData.address_line2 || '');
        $('#city').val(addressData.city);
        $('#state').val(addressData.state);
        $('#postal_code').val(addressData.postal_code);
        $('input[name="set_default"]').prop('checked', addressData.is_default == 1);

        if (addressData.latitude && addressData.longitude) {
            $('#latitude').val(addressData.latitude);
            $('#longitude').val(addressData.longitude);
            setTimeout(() => {
                initMap(addressData.latitude, addressData.longitude);
            }, 500);
        } else {
            $('#map').hide();
        }

        if (!$('#cancel-edit-btn').length) {
            $('#address-form').append('<button type="button" id="cancel-edit-btn" class="btn-cancel" style="width: 100%; margin-top: 10px; background: #f7fafc; color: #4a5568; border: 1px solid #e2e8f0; padding: 10px; border-radius: 6px;">Cancel Edit</button>');
        }

        $('#cancel-edit-btn').off('click').on('click', function() {
            resetAddressForm();
        });

        $('html, body').animate({
            scrollTop: $('.new-address-form').offset().top - 100
        }, 500);
    }

    function resetAddressForm() {
        $('#address-form')[0].reset();
        $('#address-form input[name="btn"]').val('save_address');
        $('#address-form input[name="address_id"]').remove();
        $('#cancel-edit-btn').remove();
        $('#map').hide();
    }

    function confirmDeleteAddress(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!confirm('Delete this address?')) {
            return false;
        }

        // Create a new form specifically for deletion
        const button = $(event.target).closest('button');
        const addressId = button.closest('form').find('input[name="address_id"]').val();

        const deleteForm = $('<form>', {
            'method': 'post',
            'action': ''
        });

        deleteForm.append($('<input>', {
            'type': 'hidden',
            'name': 'btn',
            'value': 'delete_address'
        }));

        deleteForm.append($('<input>', {
            'type': 'hidden',
            'name': 'address_id',
            'value': addressId
        }));

        deleteForm.appendTo('body').submit();

        return false;
    }

    $(document).ready(function() {
        function checkUnavailableItems() {
            const unavailableItems = $('.unavailable-item');
            const checkoutBtn = $('#checkout-btn'); // Assuming you have a checkout button with this ID

            if (unavailableItems.length > 0) {
                // Disable checkout button if it exists
                if (checkoutBtn.length) {
                    checkoutBtn.prop('disabled', true);
                    checkoutBtn.css({
                        'opacity': '0.5',
                        'cursor': 'not-allowed',
                        'background-color': '#cbd5e0'
                    });

                    // Add tooltip
                    checkoutBtn.attr('title', 'Remove unavailable items before checkout');
                }

                // Show persistent warning
                showCheckoutWarning();
            } else {
                // Enable checkout button
                if (checkoutBtn.length) {
                    checkoutBtn.prop('disabled', false);
                    checkoutBtn.css({
                        'opacity': '1',
                        'cursor': 'pointer',
                        'background-color': '' // Reset to default
                    });
                    checkoutBtn.removeAttr('title');
                }

                // Hide warning
                hideCheckoutWarning();
            }
        }

        // Show checkout warning
        function showCheckoutWarning() {
            if (!$('#checkout-warning').length) {
                const warning = $(`
                        <div id="checkout-warning" style="
                            position: fixed;
                            bottom: 20px;
                            right: 20px;
                            background: linear-gradient(135deg, #fed7d7, #feb2b2);
                            border: 2px solid #fc8181;
                            padding: 15px 20px;
                            border-radius: 10px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                            z-index: 1000;
                            max-width: 300px;
                            animation: slideUp 0.3s ease-out;
                        ">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="font-size: 24px;">⚠️</div>
                                <div>
                                    <strong style="color: #c53030;">Checkout Blocked</strong>
                                    <p style="margin: 5px 0 0 0; color: #742a2a; font-size: 14px;">
                                        Remove unavailable items to proceed with checkout.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `);
                $('body').append(warning);
            }
        }

        // Hide checkout warning
        function hideCheckoutWarning() {
            $('#checkout-warning').remove();
        }

        // Add CSS animation for the warning
        $('<style>')
            .text('@keyframes slideUp { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }')
            .appendTo('head');

        // Auto-delete unavailable items on page load (optional - if you want to force removal)
        function autoDeleteUnavailableItems() {
            const unavailableItems = $('.unavailable-item');
            if (unavailableItems.length > 0) {
                // Optional: Auto-remove after 5 seconds with confirmation
                setTimeout(() => {
                    if (confirm(`You have ${unavailableItems.length} unavailable item(s) in your cart. Remove them now?`)) {
                        // Delete all unavailable items
                        unavailableItems.each(function() {
                            const productId = $(this).data('product-id');
                            const deleteForm = $(`form.delete-cart-form input[name="id"][value="${productId}"]`).closest('form');
                            deleteForm.find('input[name="btn"]').val('delete');
                            deleteForm.submit();
                        });
                    }
                }, 5000);
            }
        }

        // Initialize checks
        checkUnavailableItems();

        // Re-check after any cart updates (you might need to trigger this after AJAX calls)
        $(document).on('cartUpdated', function() {
            checkUnavailableItems();
        });

        $('.quantity-update-form select[name="unit"]').on('change', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (this.value !== '') {
                const form = $(this).closest('.quantity-update-form');
                const productId = form.data('product-id');

                // Submit only this specific form
                form[0].submit();
            }

            return false;
        });

        // Smooth animations
        $('.delete-btn, .btn').hover(
            function() {
                $(this).css('transform', 'translateY(-2px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );

        $('.delete-btn').on('click', function(e) {
            e.preventDefault();
            const productName = $(this).data('product-name');
            if (confirm('Remove "' + productName + '" from cart?')) {
                $(this).closest('form').submit();
            }
            return false;
        });

        $('#phone_number').on('input', function(e) {
            let value = this.value.replace(/\D/g, '');

            if (value.length > 11) {
                value = value.slice(0, 11);
            }

            this.value = value;

            const input = $(this);
            const mobilePattern = /^01[0-9]{8,9}$/;
            const landlinePattern = /^03[0-9]{8}$/;

            if (value.length > 0) {
                if (mobilePattern.test(value) || landlinePattern.test(value)) {
                    input.css('border-color', '#48bb78');
                } else if (value.length >= 9) {
                    input.css('border-color', '#f56565');
                } else {
                    input.css('border-color', '#e2e8f0');
                }
            } else {
                input.css('border-color', '#e2e8f0');
            }
        });

        // Form submission validation
        $('#address-form').on('submit', function(e) {
            const phone = $('#phone_number').val();
            const mobilePattern = /^01[0-9]{8,9}$/;
            const landlinePattern = /^03[0-9]{8}$/;

            if (!mobilePattern.test(phone) && !landlinePattern.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid Malaysian phone number:\n• Mobile: 01X-XXXXXXXX (10-11 digits)\n• Landline: 03-XXXXXXXX (10 digits)');
                $('#phone_number').focus();
                return false;
            }
        });
    });

    function validateCheckout() {
        const unavailableItems = $('.unavailable-item');
        const checkoutBlocked = <?= !$can_checkout ? 'true' : 'false' ?>;

        if (unavailableItems.length > 0 || checkoutBlocked) {
            <?php
            $reasons_json = !empty($block_checkout_reasons)
                ? json_encode(implode("\n", $block_checkout_reasons))
                : json_encode("Please remove unavailable items from cart");
            ?>
            alert('❌ Please fix the following issues before checkout:\n\n' + <?= $reasons_json ?>);

            const firstUnavailable = unavailableItems.first();
            if (firstUnavailable.length) {
                $('html, body').animate({
                    scrollTop: firstUnavailable.offset().top - 100
                }, 500);

                firstUnavailable.css('background-color', '#fee');
                setTimeout(() => {
                    firstUnavailable.css('background-color', '');
                }, 2000);
            }

            return false;
        }

        window.location.href = '../payment/payment.php';
        return false;
    }
</script>

<?php
include '../footer.php';
?>