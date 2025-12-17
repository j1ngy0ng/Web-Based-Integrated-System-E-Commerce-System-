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
        else if ($f->size > 1 * 1024 * 1024) $_err['photo'] = 'Max 1MB';
    }

    if (!$_err) {
        // Replace photo
        if ($f) {
            if ($photo && file_exists(__DIR__ . '/photos/' . $photo)) unlink(__DIR__ . '/photos/' . $photo);
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script src="/js/photo-editor.js"></script>

<style>
form.form { max-width: 450px; margin:50px auto; background:#fff; padding:30px 40px; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.1);}
form label { display:block; margin-top:20px; margin-bottom:8px; font-weight:600;}
form input[type="text"], form input[type="email"], form input[type="password"] { width:95%; padding:10px 14px; border:1px solid #ccc; border-radius:8px; font-size:1rem;}
form .err { color:#e74c3c; font-size:0.85rem; margin-top:4px;}
form section { margin-top:30px; display:flex; gap:10px; justify-content:flex-end;}
form button { padding:10px 20px; border:none; background-color:#007BFF; color:white; font-weight:600; border-radius:8px; cursor:pointer;}
form button[type="reset"] { background-color:#6c757d; }
form button:hover { background-color:#0056b3; }
#drop-area { border:2px dashed #aaa; padding:20px; text-align:center; border-radius:12px; cursor:pointer; background:#fafafa; }
#drop-area.highlight { border-color:#007bff; background:#eef5ff; }
#preview-img { max-width:100%; max-height:300px; border-radius:10px; margin-top:10px; display:none; box-shadow:0 4px 12px rgba(0,0,0,.15);}
#image-tools button:disabled { opacity:0.6; cursor:not-allowed;}
#start-crop.active { background-color:#0056b3;}
#saved-photo img { max-width:100%; max-height:300px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.15);}
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

    <div id="drop-area">
        <p>Drag & Drop photo here OR click to choose OR take a photo</p>
        <img id="preview-img" src="<?= $photo ? '/photos/' . $photo : '' ?>" style="<?= $photo ? '' : 'display:none;' ?>">
    </div>

    <div id="image-tools" style="margin-top:10px; display:none;">
        <button type="button" onclick="rotateImage(90)">Rotate ⟳</button>
        <button type="button" onclick="rotateImage(-90)">Rotate ⟲</button>
        <button type="button" onclick="flipImage('h')">Flip ↔</button>
        <button type="button" onclick="flipImage('v')">Flip ↕</button>
        <button type="button" id="start-crop">Crop</button>
        <button type="button" id="apply-crop" style="display:none;">Apply Crop</button>
        <button type="button" id="cancel-crop" style="display:none;">Cancel</button>
    </div>

    <button type="button" id="capture-btn">Capture Photo</button>
    <video id="camera-stream" autoplay style="display:none; width:300px; margin-top:10px;"></video>
    <canvas id="camera-canvas" style="display:none;"></canvas>
    <input type="file" name="photo" id="fileElem" accept="image/*" capture="environment" style="display:none;">

    <!-- Edit current photo button -->
    <?php if ($photo): ?>
        <div id="saved-photo" style="margin-top:15px; text-align:center;">
            <p style="font-size:0.9rem; color:#666;">Current Photo</p>
            <img id="current-photo" src="/photos/<?= htmlspecialchars($photo) ?>">
            <div style="margin-top:10px;">
                <button type="button" id="edit-current-btn">Edit Current Photo</button>
            </div>
        </div>
    <?php endif; ?>

    <?= err('photo') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

<?php
include '../footer.php';
?>
