<?php

// ============================================================================
// PHP Setups
// ============================================================================

date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

// ============================================================================
// General Page Functions
// ============================================================================

// Is GET request?
function is_get() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

// Is POST request?
function is_post() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// Obtain GET parameter
function get($key, $value = null) {
    $value = $_GET[$key] ?? $value;
    if (is_array($value)) {
        return array_map('trim', $value);
    }
    return $value !== null ? trim($value) : null;
}

// Obtain POST parameter
function post($key, $value = null) {
    $value = $_POST[$key] ?? $value;
    if (is_array($value)) {
        return array_map('trim', $value);
    }
    return $value !== null ? trim($value) : null;
}

// Obtain REQUEST (GET and POST) parameter
function req($key, $value = null) {
    $value = $_REQUEST[$key] ?? $value;
    if (is_array($value)) {
        return array_map('trim', $value);
    }
    return $value !== null ? trim($value) : null;
}

// Redirect to URL
function redirect($url = null) {
    $url ??= $_SERVER['REQUEST_URI'];
    header("Location: $url");
    exit();
}

// Set or get temporary session variable
function temp($key, $value = null) {
    if ($value !== null) {
        $_SESSION["temp_$key"] = $value;
    }
    else {
        $value = $_SESSION["temp_$key"] ?? null;
        unset($_SESSION["temp_$key"]);
        return $value;
    }
}

// Obtain uploaded file --> cast to object
function get_file($key) {
    $f = $_FILES[$key] ?? null;
    
    if ($f && $f['error'] == 0) {
        return (object)$f;
    }

    return null;
}

// Crop, resize and save photo
function save_photo($f, $folder = 'photos', $width = 200, $height = 200) {
    // Generate unique filename
    $photo = uniqid() . '.jpg';

    // Require the SimpleImage library
    require_once __DIR__ . '/lib/SimpleImage.php';
    $img = new SimpleImage();

    // Make folder path absolute based on project directory
    $dir = __DIR__ . '/' . $folder;

    // Create folder if it doesn't exist
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    // Save the image
    $img->fromFile($f->tmp_name)
        ->thumbnail($width, $height)
        ->toFile($dir . '/' . $photo, 'image/jpeg');

    return $photo;
}

// Is money?
function is_money($value) {
    return preg_match('/^\-?\d+(\.\d{1,2})?$/', $value);
}

// Is email?
function is_email($value) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

// Is date?
function is_date($value, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $value);
    return $d && $d->format($format) == $value;
}

// Is time?
function is_time($value, $format = 'H:i') {
    $d = DateTime::createFromFormat($format, $value);
    return $d && $d->format($format) == $value;
}

// Return year list items
function get_years($min, $max, $reverse = false) {
    $arr = range($min, $max);

    if ($reverse) {
        $arr = array_reverse($arr);
    }

    return array_combine($arr, $arr);
}

// Return month list items
function get_months() {
    return [
        1  => 'January',
        2  => 'February',
        3  => 'March',
        4  => 'April',
        5  => 'May',
        6  => 'June',
        7  => 'July',
        8  => 'August',
        9  => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];
}

// Return local root path
function root($path = '') {
    return "$_SERVER[DOCUMENT_ROOT]/$path";
}

// Return base url (host + port)
function base($path = '') {
    return "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/$path";
}

// Return TRUE if ALL array elements meet the condition given
function array_all($arr, $fn) {
    foreach ($arr as $k => $v) {
        if (!$fn($v, $k)) {
            return false;
        }
    }
    return true;
}

// ============================================================================
// HTML Helpers
// ============================================================================

// Placeholder for TODO
function TODO() {
    echo '<span>TODO</span>';
}

// Encode HTML special characters
function encode($value) {
    return htmlentities($value);
}

// Generate <input type='hidden'>
function html_hidden($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='hidden' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='text'>
function html_text($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='password'>
function html_password($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='password' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='number'>
function html_number($key, $min = '', $max = '', $step = '', $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='number' id='$key' name='$key' value='$value'
                 min='$min' max='$max' step='$step' $attr>";
}

// Generate <input type='search'>
function html_search($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='search' id='$key' name='$key' value='$value' $attr>";
}

//Generate highlight search
function highlight($text, $keyword) {
    if (!$keyword) return htmlspecialchars($text);

    return preg_replace(
        "/(" . preg_quote($keyword, '/') . ")/i",
        '<mark>$1</mark>',
        htmlspecialchars($text)
    );
}

// Generate <input type='date'>
function html_date($key, $min= '', $max = '', $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='date' id='$key' name='$key' value='$value'
                 min='$min' max='$max' $attr>";
}

// Generate <input type='time'>
function html_time($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='time' id='$key' name='$key' value='$value' $attr>";
}

// Generate <textarea>
function html_textarea($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<textarea type='textarea' id='$key' name='$key' $attr>$value</textarea>";
}

// Generate SINGLE <input type='checkbox'>
function html_checkbox($key, $label = '', $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    $status = $value == 1 ? 'checked' : '';
    echo "<label><input type='checkbox' id='$key' name='$key' value='1' $status $attr>$label</label>";
}

// Generate <input type='checkbox'> list
function html_checkboxes($key, $items, $br = false) {
    $values = $GLOBALS[$key] ?? [];
    if (!is_array($values)) $values = [];

    echo '<div>';
    foreach ($items as $id => $text) {
        $state = in_array($id, $values) ? 'checked' : '';
        echo "<label><input type='checkbox' id='{$key}_$id' name='{$key}[]' value='$id' $state>$text</label>";
        if ($br) {
            echo '<br>';
        }
    }
    echo '</div>';
}

// Generate <input type='radio'> list
function html_radios($key, $items, $br = false) {
    $value = encode($GLOBALS[$key] ?? '');
    echo '<div>';
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'checked' : '';
        echo "<label><input type='radio' id='{$key}_$id' name='$key' value='$id' $state>$text</label>";
        if ($br) {
            echo '<br>';
        }
    }
    echo '</div>';
}

// Generate <select>
function html_select($key, $items, $default = '- Select One -', $selected = '', $attr = '') {
    $value = $selected !== '' ? encode($selected) : encode($GLOBALS[$key] ?? '');
    echo "<select id='$key' name='$key' $attr>";
    if ($default !== null) {
        echo "<option value=''>$default</option>";
    }
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'selected' : '';
        echo "<option value='$id' $state>$text</option>";
    }
    echo '</select>';
}

// Generate <input type='file'>
function html_file($key, $accept = '', $attr = '') {
    echo "<input type='file' id='$key' name='$key' accept='$accept' $attr>";
}

// Generate table headers <th>
function table_headers($fields, $sort, $dir, $extraParams = []) {
    // 1. Convert $extraParams array into a URL query string
    $href = '';
    if (!empty($extraParams)) {
        // Iterate through the array and build the string like 'search=keyword&'
        foreach ($extraParams as $key => $value) {
            $href .= urlencode($key) . '=' . urlencode($value) . '&';
        }
    }

    foreach ($fields as $k => $v) {
        $d = 'asc'; // Default direction
        $c = '';    // Default class
        
        if ($k == $sort) {
            $d = $dir == 'asc' ? 'desc' : 'asc';
            $c = $dir;
        }

        // 2. Use the generated $href string in the link
        echo "<th><a href='?sort=$k&dir=$d&$href' class='$c'>$v</a></th>";
    }
}

// ============================================================================
// Error Handlings
// ============================================================================

function alert($message) {
    $_SESSION['alert'] = $message;
}

// Global error array
$_err = [];

// Generate <span class='err'>
function err($key) {
    global $_err;
    if ($_err[$key] ?? false) {
        echo "<span class='err'>$_err[$key]</span>";
    }
    else {
        echo '<span></span>';
    }
}

// ============================================================================
// Security
// ============================================================================

// Global user object
$_user = $_SESSION['user'] ?? null;

// Login user
function login($user, $url = '/') {
    $_SESSION['user'] = $user;
    if($user->role == 'Admin'){
        $url = '/landing_admin.php';
    }else if($user->role == 'Member'){
        $url = '/landing_member.php';
    }
    redirect($url);
}

// Logout user
function logout($url = '/login.php') {
    global $_db;
    
    // Delete remember me token if exists
    if (isset($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me']);
        if (count($parts) === 2) {
            $selector = $parts[0];
            $stm = $_db->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stm->execute([$selector]);
        }
        // Clear cookie
        setcookie("remember_me", "", time() - 3600, "/", "", false, true);
    }
    
    unset($_SESSION['user']);
    redirect($url);
}

// Authorization
function auth(...$roles) {
    global $_user;
    if ($_user) {
        if ($roles) {
            if (in_array($_user->role, $roles)) {
                return; // OK
            }
        }
        else {
            return; // OK
        }
    }
    
    redirect('/login.php');
}

function is_logged() {
    return isset($_SESSION['user']);
}

// ============================================================================
// Email Functions
// ============================================================================

// Demo Accounts:
// --------------
// AACS3173@gmail.com           xxna ftdu plga hzxl
// BAIT2173.email@gmail.com     ncom fsil wjzk ptre
// liaw.casual@gmail.com        buvq yftx klma vezl
// liawcv1@gmail.com            pztq znli gpjg tooe

// Initialize and return mail object
function get_mail() {
    require_once 'lib/PHPMailer.php';
    require_once 'lib/SMTP.php';

    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->SMTPAuth = true;
    $m->Host = 'smtp.gmail.com';
    $m->Port = 587;
    $m->Username = 'njywbisdemo@gmail.com';
    $m->Password = 'cjoj ubfn swhp nqri';
    $m->CharSet = 'utf-8';
    $m->setFrom($m->Username, '😺 Admin');

    return $m;
}

// ============================================================================
// Product Photo Functions
// ============================================================================

// Get main product photo
function get_main_photo($productID) {
    global $_db;
    $stm = $_db->prepare('
        SELECT photoURL FROM productPhoto 
        WHERE productID = ? AND is_main = 1
    ');
    $stm->execute([$productID]);
    return $stm->fetchColumn();
}

// Get all product photos
function get_product_photos($productID) {
    global $_db;
    $stm = $_db->prepare('
        SELECT photoURL, is_main FROM productPhoto 
        WHERE productID = ? 
        ORDER BY is_main DESC, photoID
    ');
    $stm->execute([$productID]);
    return $stm->fetchAll();
}

// Get additional product photos (excluding main)
function get_additional_photos($productID) {
    global $_db;
    $stm = $_db->prepare('
        SELECT photoURL FROM productPhoto 
        WHERE productID = ? AND is_main = 0 
        ORDER BY photoID
    ');
    $stm->execute([$productID]);
    return $stm->fetchAll();
}

// ============================================================================
// Shopping Cart
// ============================================================================

function get_user_cart($userID) {
    global $_db;
    
    $stm = $_db->prepare('SELECT cartID FROM cart WHERE userID = ? ORDER BY created_at DESC LIMIT 1');
    $stm->execute([$userID]);
    $cartID = $stm->fetchColumn();
    
    // If no cart exists, create one
    if (!$cartID) {
        $stm = $_db->prepare('INSERT INTO cart (userID) VALUES (?)');
        $stm->execute([$userID]);
        $cartID = $_db->lastInsertId();
    }
    
    return $cartID;
}

function get_cart() {
    return $_SESSION['cart'] ?? [];
}

function update_cart($id, $unit) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if ($unit >= 1 && $unit <= 10 && is_exists($id, 'product', 'productID')) {
        $_SESSION['cart'][$id] = $unit;
    } else {
        // Remove item if unit is 0 or invalid
        unset($_SESSION['cart'][$id]);
    }
}

function clear_cart() {
    $_SESSION['cart'] = [];
}

function set_cart($cart = []) {
    $_SESSION['cart'] = [];
    
    foreach ($cart as $id => $unit) {
        if ($unit >= 1 && $unit <= 10 && is_exists($id, 'product', 'productID')) {
            $_SESSION['cart'][$id] = $unit;
        }
    }
}

// ============================================================================
// Stripe configuration
// ============================================================================

putenv('STRIPE_PUBLISHABLE_KEY=pk_test_51SVROKJ2qvgPlT6ge6aVlR5dpsVRnjZe1kXW385p4CYFq0GW3jmUYdoHmXwLwR0mbvAGrYJuHAgDoAOaRWLOMz8X005MK2GHNz');
putenv('STRIPE_SECRET_KEY=sk_test_51SVROKJ2qvgPlT6gqClelz0ywQkuujGvF4JW5BuZgRL4yUOZCTzzQZlMLDP7tpohSwlth389xxtk0ohn8PKjydyo00vYsvz3AK');

// ============================================================================
// Database Setups and Functions
// ============================================================================

// Global PDO object
$_db = new PDO('mysql:dbname=assignment', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
]);

// Is unique?
function is_unique($value, $table, $field) {
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() == 0;
}

// Is exists?
function is_exists($value, $table, $field) {
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() > 0;
}

// ============================================================================
// Global Constants and Variables
// ============================================================================

