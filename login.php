<?php
require_once '_base.php';

$email = post('email');
$password = post('password');

if (is_post()) {

    if ($email == '') {
        $_err['email'] = 'Email is required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    }

    if ($password == '') {
        $_err['password'] = 'Password is required';
    }

    if (!$_err) {
        global $_db;

        $stm = $_db->prepare("SELECT * FROM user WHERE email = ?");
        $stm->execute([$email]);
        $user = $stm->fetch();

        if ($user) {
            if ($user->password === sha1($password)) {

                temp('info', 'Login successfully');
                login($user);
            } else {
                $_err['password'] = 'Incorrect password';
            }
        } else {
            $_err['email'] = 'Email not registered';
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial;
            background: #01A7C2;
            margin: 0;
            padding: 0;
        }

        .box {
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

        button {
            width: 90%;
            background: #4285f4;
            border: none;
            padding: 12px;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 0 auto;
        }

        button:hover {
            background: #2f6bd9;
        }

        a {
            text-decoration: none;
            color: #4285f4;
        }

        .error {
            color: #d9534f;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <div class="box">
        <h2 style="text-align:center;">Login</h2>

        <form method="post">

            <label for="email">Email</label>
            <?php html_text('email'); ?>
            <?php err('email'); ?>
            <br><br>

            <label for="password">Password</label>
            <?php html_password('password'); ?>
            <?php err('password'); ?>
            <br><br>

            <button type="submit">Login</button>

            <p style="text-align:center; margin-top:15px;">
                <a href="reset.php">Forgot Password?</a>
                
            <p style="text-align:center; margin-top:15px;">
                <a href="register.php">New User? Register Here</a>
            </p>
        </form>
    </div>

</body>

</html>