<?php
require_once '../_base.php';

if (is_post()) {

    $email    = req('email');
    $password = req('password');
    $confirm  = req('confirm');
    $name     = req('name');
    $phone    = req('phone'); // NEW: phone number
    $f        = get_file('photo');

    // --- Validation ---
    if ($name == '') $_err['name'] = 'Name is required';

    if ($email == '') $_err['email'] = 'Email is required';
    else if (!is_email($email)) $_err['email'] = 'Invalid email format';
    else if (!is_unique($email, 'user', 'email')) $_err['email'] = 'Email already exists';

    if ($password == '') $_err['password'] = 'Password is required';
    else if (strlen($password) < 4) $_err['password'] = 'Password must be at least 4 characters';

    if ($confirm != $password) $_err['confirm'] = 'Passwords do not match';

    if (!$f) $_err['photo'] = 'Required';
    else if (!str_starts_with($f->type, 'image/')) $_err['photo'] = 'Must be image';
    else if ($f->size > 1 * 1024 * 1024) $_err['photo'] = 'Maximum 1MB';

    if (!$_err) {
        // --- Save photo ---
        $photo = save_photo($f, '/photos');

        // --- Insert user ---
        $stm = $_db->prepare('
            INSERT INTO user (email, password, name, photo, role, failed_attempts, lock_until, is_active, phone_number, phone_verified)
            VALUES (?, SHA1(?), ?, ?, "Member", 0, NULL, 1, ?, 0)
        ');
        $stm->execute([$email, $password, $name, $photo, $phone]);

        temp('info', 'Registration successful. Please verify your phone.');
        redirect('/login.php');
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
    <style>
        body { font-family: Arial; background: #01A7C2; margin:0; padding:0; }
        .box { width:50%; padding:25px; background:white; margin:80px auto; border-radius:40px; border-style:groove; border-width:20px; box-shadow:0 4px 20px #A3BAC3; }
        input { width:95%; padding:10px; border:1px solid #bbb; border-radius:5px; margin-top:5px; }
        button { width:90%; background:#34a853; border:none; padding:12px; color:white; border-radius:5px; cursor:pointer; font-size:16px; display:block; margin:0 auto; }
        button:hover { background:#2a8f45; }
        a { text-decoration:none; color:#4285f4; }
        .success { color:#2a8f45; text-align:center; font-size:14px; margin-bottom:10px; }
        label.upload img { display:block; border:1px solid #333; width:200px; height:200px; object-fit:cover; cursor:pointer; }
    </style>
</head>

<body>
<div class="box">
    <h2 style="text-align:center;">Register</h2>

    <?php if (temp('success')): ?>
        <div class="success"><?= temp('success') ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">

        <label for="name">Name</label>
        <?php html_text('name'); ?>
        <?php err('name'); ?>
        <br><br>

        <label for="email">Email</label>
        <?php html_text('email'); ?>
        <?php err('email'); ?>
        <br><br>

        <label for="phone">Phone Number</label>
        <?php html_text('phone'); ?>
        <?php err('phone'); ?>
        <br><br>

        <label for="password">Password</label>
        <?php html_password('password'); ?>
        <?php err('password'); ?>
        <br><br>

        <label for="confirm">Confirm Password</label>
        <?php html_password('confirm'); ?>
        <?php err('confirm'); ?>
        <br><br>

        <label for="photo">Photo</label>
        <label class="upload" tabindex="0">
            <?= html_file('photo', 'image/*', 'hidden') ?>
            <img src="/images/photo.jpg">
        </label>
        <?= err('photo') ?>
        <br><br>

        <button type="submit">Register</button>

        <p style="text-align:center; margin-top:15px;">
            <a href="../login.php">Already have an account? Login</a>
        </p>
    </form>
</div>
</body>
</html>
