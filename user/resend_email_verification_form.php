<?php include '../_base.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Resend Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #01A7C2;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .box {
            width: 400px;
            padding: 30px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            text-align: center;
        }

        h2 {
            color: #01A7C2;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
            text-align: left;
        }

        input[type="email"] {
            width: 95%;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        button {
            width: 90%;
            padding: 12px;
            background-color: #34a853;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #2a8f45;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            color: #4285f4;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Resend Email Verification</h2>
        <form action="resend_email_verification.php" method="POST">
            <label>Enter your email:</label>
            <input type="email" name="email" required>
            <button type="submit">Resend Verification Email</button>
        </form>
        <p><a href="/login.php">Back to Login</a></p>
    </div>
</body>
</html>
