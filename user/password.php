<?php
include '../_base.php';

// ----------------------------------------------------------------------------

// Authenticated users
// TODO
auth();

if (is_post()) {
    $password     = req('password');
    $new_password = req('new_password');
    $confirm      = req('confirm');

    // Validate: password
    if ($password == '') {
        $_err['password'] = 'Required';
    }
    else if (strlen($password) < 5 || strlen($password) > 100) {
        $_err['password'] = 'Between 5-100 characters';
    }
    else {
        // TODO
        $stm = $_db->prepare('
            SELECT COUNT(*) FROM user
            WHERE password = SHA1(?) AND id = ?
        ');
        $stm->execute([$password, $_user->id]);
        
        if ($stm->fetchColumn() == 0) {
            $_err['password'] = 'Not matched';
        }
    }

    // Validate: new_password
    if ($new_password == '') {
        $_err['new_password'] = 'Required';
    }
    else if (strlen($new_password) < 5 || strlen($new_password) > 100) {
        $_err['new_password'] = 'Between 5-100 characters';
    }

    // Validate: confirm
    if (!$confirm) {
        $_err['confirm'] = 'Required';
    }
    else if (strlen($confirm) < 5 || strlen($confirm) > 100) {
        $_err['confirm'] = 'Between 5-100 characters';
    }
    else if ($confirm != $new_password) {
        $_err['confirm'] = 'Not matched';
    }

     // DB operation
    if (!$_err) {
        // Update user (password)
        $stm = $_db->prepare('UPDATE user SET password = SHA1(?) WHERE id = ?');
        $stm->execute([$new_password, $_user->id]);

        temp('info', 'Password updated successfully');
        redirect('/password.php');
    }
}

// ----------------------------------------------------------------------------

$_title = 'User | Password';
include '../navbar.php';
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

form input[type="password"] {
    width: 95%;
    padding: 10px 14px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    transition: border 0.3s;
}

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

<form method="post" class="form">
    <label for="password">Password</label>
    <?= html_password('password', 'maxlength="100"') ?>
    <?= err('password') ?>

    <label for="new_password">New Password</label>
    <?= html_password('new_password', 'maxlength="100"') ?>
    <?= err('new_password') ?>

    <label for="confirm">Confirm</label>
    <?= html_password('confirm', 'maxlength="100"') ?>
    <?= err('confirm') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

<?php
include '../footer.php';