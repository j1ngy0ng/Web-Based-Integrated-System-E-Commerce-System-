<?php
include '_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $email = req('email');

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    } else if (!is_exists($email, 'user', 'email')) {
        $_err['email'] = 'Not exists';
    }

    // Send reset token (if valid)
    if (!$_err) {
        // TODO: (1) Select user
        $stm = $_db->prepare('SELECT * FROM user WHERE email = ?');
        $stm->execute([$email]);
        $u = $stm->fetch();

        // TODO: (2) Generate token id
        $id = sha1(uniqid() . rand());

        // TODO: (3) Delete old and insert new token
        $stm = $_db->prepare('
            DELETE FROM token WHERE user_id = ?;

            INSERT INTO token (id, expire, user_id)
            VALUES (?, ADDTIME(NOW(), "00:05"),?);
        ');
        $stm->execute([$u->id, $id, $u->id]);

        // TODO: (4) Generate token url
        $url = base("token.php?id=$id");

        // TODO: (5) Send email
        $m = get_mail();
        $m->addAddress($u->email, $u->name);
        $m->addEmbeddedImage("photos/{$u->photo}", 'photo');
        $m->isHTML(true);
        $m->Subject = 'Reset Password';
        $m->Body = "
            <img src='cid:photo'
                 style='width: 200px; height: 200px;
                        border: 1px solid #333'>
            <p>Dear $u->name,<p>
            <h1 style='color: red'>Reset Password</h1>
            <p>
                Please click <a href='$url'>here</a>
                to reset your password.
            </p>
            <p>From, 😺 Admin</p>
        ";
        $m->send();
        temp('info', 'Email sent');
        redirect('/login.php');
    }
}

// ----------------------------------------------------------------------------

$_title = 'User | Reset Password';
?>

<style>
    body {
        font-family: Arial;
        background: #01A7C2;
        margin: 0;
        padding: 0;
    }

    .form {
        width: 50%;
        padding: 25px;
        background: white;
        margin: 80px auto;
        border-radius: 40px;
        border-style: groove;
        border-width: 20px;
        box-shadow: 0 4px 20px #A3BAC3;
    }

    input {
        width: 95%;
        padding: 10px;
        border: 1px solid #bbb;
        border-radius: 5px;
        margin-top: 5px;
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

    .error {
        color: #d9534f;
        font-size: 13px;
    }
</style>

<form method="post" class="form">
    <label for="email">Email</label>
    <?= html_text('email', 'maxlength="100"') ?>
    <?= err('email') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

