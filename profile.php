<?php
include '_base.php';

// ----------------------------------------------------------------------------

// Authenticated users
// TODO
auth();

if (is_get()) {
    // TODO
    $stm = $_db->prepare('SELECT * FROM user WHERE id = ?');
    $stm->execute([$_user->id]);
    $u = $stm->fetch();

    if (!$u) {
        redirect('/');
    }

    extract((array)$u);
    $_SESSION['photo'] = $u->photo;
}

if (is_post()) {
    $email = req('email');
    $name  = req('name');
    $photo = $_SESSION['photo'];
    $f = get_file('photo');

    // Email validation
    if ($email == '') {
        $_err['email'] = 'Required';
    }
    else if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    }
    else if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    }
    else {
        // Check duplicated (exclude current user)
        $stm = $_db->prepare('
            SELECT COUNT(*) FROM user
            WHERE email = ? AND id <> ?
        ');
        $stm->execute([$email, $_user->id]);

        if ($stm->fetchColumn() > 0) {
            $_err['email'] = 'Duplicated';
        }
    }

    // Name validation
    if ($name == '') {
        $_err['name'] = 'Required';
    }
    else if (strlen($name) > 100) {
        $_err['name'] = 'Maximum 100 characters';
    }

    // Photo validation
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['photo'] = 'Must be image';
        }
        else if ($f->size > 1 * 1024 * 1024) {
            $_err['photo'] = 'Maximum 1MB';
        }
    }

    // DB operation
    if (!$_err) {

        // Delete + replace photo
        if ($f) {
            if ($photo && file_exists(__DIR__ . $photo)) {
                unlink(__DIR__ . $photo);
            }

            $photo = save_photo($f, '/photos');
        }

        // Update user info
        $stm = $_db->prepare('
            UPDATE user
            SET email = ?, name = ?, photo = ?
            WHERE id = ?
        ');
        $stm->execute([$email, $name, $photo, $_user->id]);

        // Update global object
        $_user->email = $email;
        $_user->name  = $name;
        $_user->photo = $photo;

        temp('info', 'Record updated');
        redirect('/profile.php');
    }
}

// ----------------------------------------------------------------------------

$_title = 'User | Profile';
include 'navbar.php';
?>

<style>
/* Global */
body {
    font-family: 'Lato-Regular';
    background: #f5f6fa;
    color: #333;
    margin: 0;
    padding: 0;
}

form.form {
    max-width: 450px;
    margin: 50px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

/* Labels */
form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    margin-top: 20px;
}

/* Text inputs */
form input[type="text"],
form input[type="email"],
form input[type="password"] {
    width: 95%;
    padding: 10px 14px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    transition: border 0.3s;
}

form input[type="text"]:focus,
form input[type="email"]:focus,
form input[type="password"]:focus {
    border-color: #007BFF;
    outline: none;
}

/* Error messages */
form .err {
    color: #e74c3c;
    font-size: 0.85rem;
    margin-top: 4px;
}

/* Photo upload */
form .upload {
    display: block;
    position: relative;
    width: 120px;
    height: 120px;
    border: 2px dashed #ccc;
    border-radius: 12px;
    cursor: pointer;
    overflow: hidden;
    margin-top: 10px;
}

form .upload img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Buttons */
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
    transition: background-color 0.3s;
}

form button[type="reset"] {
    background-color: #6c757d;
}

form button:hover {
    background-color: #0056b3;
}

form button[type="reset"]:hover {
    background-color: #5a6268;
}

</style>

<form method="post" class="form" enctype="multipart/form-data">
    <label for="email">Email</label>
    <?= html_text('email', 'maxlength="100"') ?>
    <?= err('email') ?>

    <label for="name">Name</label>
    <?= html_text('name', 'maxlength="100"') ?>
    <?= err('name') ?>

    <label for="photo">Photo</label>
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
include 'footer.php';