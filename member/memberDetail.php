<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();

// --------------------------------------------------------------------
// Get member ID from URL (edit mode) or null (add mode)
$memberID = req('id'); // matches your member list edit links

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
}

// --------------------------------------------------------------------
// Handle POST (both Add and Edit)
if (is_post()) {
    $email = req('email');
    $name  = req('name');
    $role  = req('role');
    $password = req('password'); // optional on edit
    $phone_number = req('phone_number');
    $is_active = req('is_active') === '1' ? 1 : 0;



    // Validation
    if ($email == '') {
        $_err['email'] = 'Email is required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email format';
    }

    if ($name == '') {
        $_err['name'] = 'Name is required';
    }

    if ($role == '') {
        $_err['role'] = 'Role is required';
    }

    // Photo upload handling
    if (isset($_FILES['photo']) && $_FILES['photo']['tmp_name']) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoName = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], "../photos/$photoName");
    } else {
        $photoName = $member->photo ?? '';
    }

    // DB operation if no errors
    if (!$_err) {
        if ($memberID) {
            // Edit mode
            if ($password) {
                $pwdHash = password_hash($password, PASSWORD_DEFAULT);
            $stm = $_db->prepare('
                UPDATE user 
                SET email=?, name=?, role=?, password=?, photo=?, phone_number=?, is_active=? 
                WHERE id=?
            ');
            $stm->execute([$email, $name, $role, $pwdHash, $photoName, $phone_number, $is_active, $memberID]);

            } else {
            $stm = $_db->prepare('
                UPDATE user 
                SET email=?, name=?, role=?, photo=?, phone_number=?, is_active=?, reward_points=?
                WHERE id=?
            ');
            $stm->execute([$email, $name, $role, $photoName, $phone_number, $is_active, $reward_points, $memberID]);

            }
            temp('info', 'Member updated');
        } else {
            // Add mode
            if ($password == '') {
                $_err['password'] = 'Password is required';
            } else {
                $pwdHash = password_hash($password, PASSWORD_DEFAULT);
                $stm = $_db->prepare(
                    'INSERT INTO user 
                    (email, name, role, password, photo, phone_number, is_active, failed_attempts, lock_until, phone_verified, reward_points)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                    ');
                $stm->execute([
                    $email,
                    $name,
                    $role,
                    $pwdHash,
                    $photoName,
                    $phone_number,
                    $is_active,
                    0,       // failed_attempts
                    null,    // lock_until
                    0,        // phone_verified
                    0   // reward_points default
                ]);

                temp('info', 'Member added');
            }
        }

        if (!$_err) {
            redirect('memberList.php');
        }
    }
}

// --------------------------------------------------------------------
// Prepare $GLOBALS for html_text / html_textarea helpers
$GLOBALS['email'] = $_POST['email'] ?? $member->email;
$GLOBALS['name']  = $_POST['name'] ?? $member->name;
$GLOBALS['role']  = $_POST['role'] ?? $member->role;
$GLOBALS['password'] = '';
$GLOBALS['phone_number'] = $_POST['phone_number'] ?? $member->phone_number;
$GLOBALS['is_active'] = $_POST['is_active'] ?? $member->is_active;

?>

<?php
$_title = $memberID ? 'Edit Member' : 'Add Member';
include '../navbar.php';
?>

<style>
form.form {
    max-width: 500px;
    margin: 50px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
form label { display:block; margin-top:20px; font-weight:600; }
form input[type="text"], form input[type="email"], form input[type="password"], form select {
    width:95%; padding:10px 14px; border:1px solid #ccc; border-radius:8px; font-size:1rem;
}
form .err { color:#e74c3c; font-size:0.85rem; margin-top:4px; }
form section { margin-top:30px; display:flex; gap:10px; justify-content:flex-end; }
form button { padding:10px 20px; border:none; background-color:#007BFF; color:white; font-size:1rem; font-weight:600; border-radius:8px; cursor:pointer; }
form button[type="reset"] { background-color:#6c757d; }
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
        <option value="Admin" <?= $GLOBALS['role']=='Admin'?'selected':'' ?>>Admin</option>
        <option value="Member" <?= $GLOBALS['role']=='Member'?'selected':'' ?>>Member</option>
    </select>
    <?= err('role') ?>

    <label for="phone_number">Phone Number</label>
    <?= html_text('phone_number', 'maxlength="20"') ?>

    <label for="reward_points">Reward Points</label>
    <input type="number" name="reward_points" value="<?= $member->reward_points ?>" min="0">

    <label for="is_active">Status</label>
    <select name="is_active">
        <option value="1" <?= $member->is_active ? 'selected':'' ?>>Active</option>
        <option value="0" <?= !$member->is_active ? 'selected':'' ?>>Blocked</option>
    </select>




    <label for="password"><?= $memberID ? 'New Password (leave blank to keep)' : 'Password' ?></label>
    <?= html_text('password', 'type="password" maxlength="100"') ?>
    <?= err('password') ?>

<label for="photo">Photo</label>

<style>
#drop-area {
    border: 2px dashed #aaa;
    padding: 30px;
    text-align: center;
    border-radius: 10px;
    cursor: pointer;
    margin-bottom: 10px;
    transition: 0.2s;
}
#drop-area.highlight {
    border-color: #007bff;
    background: #eef5ff;
}
#preview-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin-top: 10px;
    display: none;
}
</style>

<div id="drop-area">
    <p>Drag & Drop photo here OR click to choose</p>

    <!-- Existing photo preview (edit mode) -->
    <img 
        id="preview-img"
        src="<?= $member->photo ? '../photos/'.$member->photo : '' ?>" 
        style="<?= $member->photo ? '' : 'display:none;' ?>"
    >
</div>

<input type="file" name="photo" id="fileElem" accept="image/*" style="display:none;">

 
    <?php if($member->photo): ?>
        <div style="margin-top:10px;">
            <img src="../photos/<?= htmlspecialchars($member->photo) ?>" alt="Photo" style="width:80px;height:80px;border-radius:50%;">
        </div>
    <?php endif; ?>

    <section>
        <button><?= $memberID ? 'Update' : 'Add' ?></button>
        <button type="reset">Reset</button>
    </section>

<script>
const dropArea = document.getElementById("drop-area");
const fileInput = document.getElementById("fileElem");
const previewImg = document.getElementById("preview-img");

// --- highlight on drag ---
["dragenter", "dragover"].forEach(evt => {
    dropArea.addEventListener(evt, e => {
        e.preventDefault();
        dropArea.classList.add("highlight");
    });
});

// --- remove highlight ---
["dragleave", "drop"].forEach(evt => {
    dropArea.addEventListener(evt, e => {
        e.preventDefault();
        dropArea.classList.remove("highlight");
    });
});

// --- drop file ---
dropArea.addEventListener("drop", e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (!file) return;

    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;

    showPreview(file);
});

// --- click to choose ---
dropArea.addEventListener("click", () => fileInput.click());

// --- choose file normally ---
fileInput.addEventListener("change", () => {
    if (fileInput.files.length > 0) {
        showPreview(fileInput.files[0]);
    }
});

// --- show preview ---
function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        previewImg.style.display = "block";
    };
    reader.readAsDataURL(file);
}
</script>

</form>


<?php include '../footer.php'; ?>
