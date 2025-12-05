<?php
include '../_base.php';

// ----------------------------------------------------------------------------

// Authenticated users
auth();

if (is_get()) {
    $stm = $_db->prepare('SELECT * FROM user WHERE id = ?');
    $stm->execute([$_user->id]);
    $u = $stm->fetch();
    if (!$u) redirect('/');

    $_SESSION['photo'] = $u->photo;

    // populate form variables
    $email = $u->email;
    $name = $u->name;
    $phone = $u->phone_number;
    $phone_verified = $u->phone_verified;
    $photo = $u->photo;
}

if (is_post()) {
    $email = req('email');
    $name  = req('name');
    $phone = req('phone');
    $photo = $_SESSION['photo'];
    $f     = get_file('photo');

    $_err = [];

    // Email validation
    if ($email == '') $_err['email'] = 'Required';
    else if (!is_email($email)) $_err['email'] = 'Invalid email';

    // Name validation
    if ($name == '') $_err['name'] = 'Required';

    // Phone validation
    if ($phone && strlen($phone) > 20) $_err['phone'] = 'Max 20 chars';

    // Photo validation
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) $_err['photo'] = 'Must be image';
        else if ($f->size > 1*1024*1024) $_err['photo'] = 'Max 1MB';
    }

    if (!$_err) {
        // Replace photo
        if ($f) {
            if ($photo && file_exists(__DIR__ . $photo)) unlink(__DIR__ . $photo);
            $photo = save_photo($f, '/photos');
        }

        // Update user info
        $stm = $_db->prepare('UPDATE user SET email=?, name=?, phone_number=?, photo=?, phone_verified=? WHERE id=?');
        // reset phone_verified if phone changed
        $verified = ($_user->phone_number == $phone) ? $_user->phone_verified : 0;
        $stm->execute([$email, $name, $phone, $photo, $verified, $_user->id]);

        $_user->email = $email;
        $_user->name = $name;
        $_user->phone_number = $phone;
        $_user->photo = $photo;
        $_user->phone_verified = $verified;

        // Generate verification token if phone changed
        if (!$verified && $phone) {
            $token = random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', time() + 600); // 10 min
            $stm = $_db->prepare('INSERT INTO phone_verifications (user_id, phone_number, token, expires_at) VALUES (?, ?, ?, ?)');
            $stm->execute([$_user->id, $phone, $token, $expires]);

            // TODO: send $token via SMS
            temp('info', 'Phone verification code sent. Please verify.');
        }

        temp('info', 'Profile updated successfully');
        redirect('/user/profile.php');
    }
}

// ----------------------------------------------------------------------------

$_title = 'User | Profile';
include '../navbar.php';
?>

<style>
/* Global */
body { font-family: 'Lato-Regular'; background: #f5f6fa; color: #333; margin:0; padding:0; }
form.form { max-width: 450px; margin: 50px auto; background: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); }

/* Labels */
form label { display: block; margin-bottom: 8px; font-weight: 600; margin-top: 20px; }

/* Text inputs */
form.form input[type="text"], form.form input[type="email"], form.form input[type="password"] {
    width: 95%; 
    padding: 10px 14px; 
    border: 1px solid #ccc; 
    border-radius: 8px; 
    font-size: 1rem; 
    transition: border 0.3s;
}

form.form input[type="text"]:focus, form.form input[type="email"]:focus, form.form input[type="password"]:focus {
    border-color: #007BFF; 
    outline: none;
}

/* Error messages */
form .err { color: #e74c3c; font-size: 0.85rem; margin-top: 4px; }

/* Photo upload */
form .upload { display: block; position: relative; width: 120px; height: 120px; border: 2px dashed #ccc; border-radius: 12px; cursor: pointer; overflow: hidden; margin-top: 10px; }
form .upload img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Buttons */
form section { margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end; }
form button { padding: 10px 20px; border: none; background-color: #007BFF; color: white; font-size: 1rem; font-weight: 600; border-radius: 8px; cursor: pointer; transition: background-color 0.3s; }
form button[type="reset"] { background-color: #6c757d; }
form button:hover { background-color: #0056b3; }
form button[type="reset"]:hover { background-color: #5a6268; }

</style>

<form method="post" class="form" enctype="multipart/form-data">
    <label>Email</label>
    <?= html_text('email') ?>
    <?= err('email') ?>

    <label>Name</label>
    <?= html_text('name') ?>
    <?= err('name') ?>

    <label>Phone Number</label>
    <?= html_text('phone') ?>
    <?= err('phone') ?>
    <?php if (isset($phone_verified) && $phone_verified == 0 && $phone): ?>
        <div style="color:#e67e22; font-size:0.85rem; margin-top:4px;">
            Phone not verified. <a href="/user/resend_phone_code.php">Resend code</a>
        </div>
    <?php endif; ?>

    <label>Photo</label>
    <label class="upload" tabindex="0">
        <?= html_file('photo', 'image/*', 'hidden') ?>
        <img src="/photos/<?= $photo ?>">
    </label>
    <?= err('photo') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

<?php
include '../footer.php';
