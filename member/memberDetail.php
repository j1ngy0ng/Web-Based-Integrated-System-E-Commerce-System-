<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth("Admin", "Superadmin");
$currentUserRole = $_SESSION['user']->role ?? '';

// --------------------------------------------------------------------
// Get member ID from URL (edit mode) or null (add mode)
$memberID = req('id');

// Default empty member (Add mode)
$member = new stdClass();
$member->email = '';
$member->name = '';
$member->role = '';
$member->photo = '';
$member->password = '';
$member->failed_attempts = 0;
$member->lock_until = null;
$member->is_active = 1;
$member->phone_number = '';
$member->phone_verified = 0;
$member->reward_points = 0;

// --------------------------------------------------------------------
// If edit mode, fetch member from DB
if ($memberID) {
    $stm = $_db->prepare('SELECT * FROM user WHERE id = ?');
    $stm->execute([$memberID]);
    $m = $stm->fetch();

    if (!$m) {
        temp('err', 'Member not found');
        redirect('memberList.php');
    }

    $member = $m;

    // ----------------------------------------------------------------
    // Restrict editing Superadmin
    if ($member->role === 'Superadmin' && $currentUserRole !== 'Superadmin') {
        temp('err', 'Access denied. Only Superadmin can edit Superadmin accounts.');
        redirect('memberList.php');
    }
}

// --------------------------------------------------------------------
// Handle POST (Add/Edit)
if (is_post()) {
    $email         = req('email');
    $name          = req('name');
    $role          = req('role');
    $password      = req('password'); // optional on edit
    $phone_number  = req('phone_number');
    $is_active     = req('is_active') === '1' ? 1 : 0;
    $reward_points = (int) req('reward_points');

    $_err = [];

    // ------------------ Validation ------------------
    if ($email == '') {
        $_err['email'] = 'Email is required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    }

    if ($name == '') $_err['name'] = 'Name is required';
    if ($role == '') $_err['role'] = 'Role is required';

    // Phone validation (Malaysia)
    if ($phone_number == '') {
        $_err['phone_number'] = 'Phone number is required';
    } else if (!is_phone($phone_number)) {
        $_err['phone_number'] = 'Invalid Malaysian phone number';
    }

    // ------------------ Enforce role restrictions ------------------
    // Admin cannot assign Admin or Superadmin roles
    if ($currentUserRole === 'Admin' && ($role === 'Admin' || $role === 'Superadmin')) {
        $_err['role'] = 'You are not allowed to assign this role';
    }

    // Editing a Superadmin: only Superadmin allowed
    if ($memberID && $member->role === 'Superadmin' && $currentUserRole !== 'Superadmin') {
        $_err['role'] = 'You are not allowed to edit a Superadmin account';
    }

    // ------------------ Photo upload ------------------
    if (isset($_FILES['photo']) && $_FILES['photo']['tmp_name']) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoName = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], "../photos/$photoName");
    } else {
        $photoName = $member->photo ?? '';
    }

    // ------------------ Database operation ------------------
    if (!$_err) {
        // Reset phone_verified if phone changed
        $phone_verified = ($memberID && $member->phone_number === $phone_number)
            ? $member->phone_verified
            : 0;

        if ($memberID) {
            // ----- Edit mode -----
            if ($password) {
                $pwdHash = sha1($password);
                $stm = $_db->prepare('
                    UPDATE user 
                    SET email=?, name=?, role=?, password=?, photo=?, phone_number=?, phone_verified=?, is_active=?, reward_points=?
                    WHERE id=?');
                $stm->execute([$email, $name, $role, $pwdHash, $photoName, $phone_number, $phone_verified, $is_active, $reward_points, $memberID]);
            } else {
                $stm = $_db->prepare('
                    UPDATE user 
                    SET email=?, name=?, role=?, photo=?, phone_number=?, phone_verified=?, is_active=?, reward_points=?
                    WHERE id=?');
                $stm->execute([$email, $name, $role, $photoName, $phone_number, $phone_verified, $is_active, $reward_points, $memberID]);
            }
            temp('info', 'Member updated');
        } else {
            // ----- Add mode -----
            if ($password == '') {
                $_err['password'] = 'Password is required';
            } else {
                $pwdHash = sha1($password);
                $stm = $_db->prepare('
                    INSERT INTO user 
                    (email, name, role, password, photo, phone_number, phone_verified, is_active, failed_attempts, lock_until, reward_points)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stm->execute([
                    $email,
                    $name,
                    $role,
                    $pwdHash,
                    $photoName,
                    $phone_number,
                    0,
                    $is_active,
                    0,
                    null,
                    $reward_points
                ]);
                temp('info', 'Member added');
            }
        }

        if (!$_err) redirect('memberList.php');
    }
}

// --------------------------------------------------------------------
// Prepare $GLOBALS for form helpers
$GLOBALS['email']        = $_POST['email'] ?? $member->email;
$GLOBALS['name']         = $_POST['name'] ?? $member->name;
$GLOBALS['role']         = $_POST['role'] ?? $member->role;
$GLOBALS['password']     = '';
$GLOBALS['phone_number'] = $_POST['phone_number'] ?? $member->phone_number;
$GLOBALS['is_active']    = $_POST['is_active'] ?? $member->is_active;
$GLOBALS['reward_points'] = $_POST['reward_points'] ?? $member->reward_points;

$_title = $memberID ? 'Edit Member' : 'Add Member';
include '../navbar.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script src="/js/photo-editor.js"></script>

<style>
    form.form {
        max-width: 500px;
        margin: 50px auto;
        background: #fff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    form label {
        display: block;
        margin-top: 20px;
        font-weight: 600;
    }

    form input[type="text"],
    form input[type="email"],
    form input[type="password"],
    form select {
        width: 95%;
        padding: 10px 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 1rem;
    }

    form .err {
        color: #e74c3c;
        font-size: 0.85rem;
        margin-top: 4px;
    }

    form section {
        margin-top: 30px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    form button {
        padding: 10px 20px;
        border: none;
        background-color: #007BFF;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
    }

    form button[type="reset"] {
        background-color: #6c757d;
    }

    #drop-area {
        border: 2px dashed #aaa;
        padding: 20px;
        text-align: center;
        border-radius: 12px;
        cursor: pointer;
        background: #fafafa;
    }

    #drop-area.highlight {
        border-color: #007bff;
        background: #eef5ff;
    }

    #preview-img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 10px;
        margin-top: 10px;
        display: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
    }
</style>

<form method="post" class="form" enctype="multipart/form-data">

    <label for="email">Email</label>
    <?= html_text('email', 'type="email" maxlength="100"') ?>
    <?= err('email') ?>

    <label for="name">Name</label>
    <?= html_text('name', 'maxlength="100"') ?>
    <?= err('name') ?>

    <label for="role">Role</label>
    <select name="role">
        <option value="">Select role</option>
        <?php if ($currentUserRole === 'Superadmin'): ?>
            <option value="Admin" <?= $GLOBALS['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
        <?php endif; ?>
        <option value="Member" <?= $GLOBALS['role'] == 'Member' ? 'selected' : '' ?>>Member</option>
    </select>
    <?= err('role') ?>

    <label for="phone_number">Phone Number</label>
    <?= html_text('phone_number', 'maxlength="20"') ?>
    <?= err('phone_number') ?>

    <label for="reward_points">Reward Points</label>
    <input type="number" name="reward_points" value="<?= $GLOBALS['reward_points'] ?>" min="0">

    <label for="is_active">Status</label>
    <select name="is_active">
        <option value="1" <?= $GLOBALS['is_active'] ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= !$GLOBALS['is_active'] ? 'selected' : '' ?>>Blocked</option>
    </select>

    <label for="password"><?= $memberID ? 'New Password (leave blank to keep)' : 'Password' ?></label>
    <?= html_text('password', 'type="password" maxlength="100"') ?>
    <?= err('password') ?>

    <label for="photo">Photo</label>
    <div id="drop-area">
        <p>Drag & Drop photo here OR click to choose OR take a photo</p>
        <img id="preview-img" src="<?= $member->photo ? '../photos/' . $member->photo : '' ?>" style="<?= $member->photo ? '' : 'display:none;' ?>">
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

    <input type="file" name="photo" id="fileElem" accept="image/*" capture="environment" style="display:none;">

    <?php if ($member->photo): ?>
        <div id="saved-photo" style="margin-top:15px; text-align:center;">
            <p style="font-size:0.9rem; color:#666;">Current Photo</p>
            <img id="current-photo" src="../photos/<?= htmlspecialchars($member->photo) ?>" alt="Photo" style="max-width:100%; max-height:300px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.15);">
        </div>
    <?php endif; ?>

    <section>
        <button type="submit"><?= $memberID ? 'Update' : 'Add' ?></button>
        <button type="reset">Reset</button>
    </section>

</form>

<?php include '../footer.php'; ?>