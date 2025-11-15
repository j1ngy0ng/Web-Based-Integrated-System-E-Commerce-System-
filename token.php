<?php
require_once '_base.php';

// ----------------------------------------------------------------------------

// TODO: (1) Delete expired tokens
$_db->query('DELETE FROM token WHERE expire < NOW()');

$id = req('id');

// TODO: (2) Is token id valid?
if (!is_exists($id, 'token', 'id')) {
    temp('info', 'Invalid token. Try again');
    redirect('/');
}

if (is_post()) {
    $password = req('password');
    $confirm  = req('confirm');

    // Validate: password
    if ($password == '') {
        $_err['password'] = 'Required';
    } else if (strlen($password) < 5 || strlen($password) > 100) {
        $_err['password'] = 'Between 5-100 characters';
    }

    // Validate: confirm
    if ($confirm == '') {
        $_err['confirm'] = 'Required';
    } else if (strlen($confirm) < 5 || strlen($confirm) > 100) {
        $_err['confirm'] = 'Between 5-100 characters';
    } else if ($confirm != $password) {
        $_err['confirm'] = 'Not matched';
    }

    // DB operation
    if (!$_err) {
        // TODO: Update user (password) based on token id + delete token
        $stm = $_db->prepare('
            UPDATE user
            SET password = SHA1(?)
            WHERE id = (
                SELECT user_id FROM token WHERE id = ?
            );
            DELETE FROM token WHERE id = ?;
        ');
        $stm->execute([$password, $id, $id]);

        temp('info', 'Record updated');
        redirect('/login.php');
    }
}

// ----------------------------------------------------------------------------

$_title = 'User | Reset Password';
?>

<style>
    /* Global */
    body {
        font-family: 'Lato-Regular';
        background: #01A7C2;
        margin: 0;
        padding: 0;
    }

    form.form {
        width: 50%;
        padding: 25px;
        background: white;
        margin: 80px auto;
        border-radius: 40px;
        border-style: groove;
        border-width: 20px;
        box-shadow: 0 4px 20px #A3BAC3;
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

    <label for="confirm">Confirm</label>
    <?= html_password('confirm', 'maxlength="100"') ?>
    <?= err('confirm') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>