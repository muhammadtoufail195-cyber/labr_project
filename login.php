<?php
require __DIR__.'/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_user = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($input_user !== '' && $password !== '') {
        $user_found = false;

        if (isset($conn) && $conn instanceof mysqli) {
            // Check matching 'name' OR 'email' column from database
            $stmt = $conn->prepare("SELECT * FROM users WHERE name = ? OR email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $input_user, $input_user);
                $stmt->execute();
                $res = $stmt->get_result();
                $user = $res ? $res->fetch_assoc() : null;

                if ($user) {
                    $db_pass = $user['password'] ?? '';
                    
                    // Verify hash password or direct text fallback
                    if (password_verify($password, $db_pass) || $password === $db_pass) {
                        $_SESSION['user_id']  = $user['id'];
                        $_SESSION['username'] = $user['name'] ?? $user['email'];
                        $user_found = true;
                        header('Location: dashboard.php');
                        exit;
                    }
                }
            }
        }

        // Direct Admin Fallback if Hash does not match
        if (!$user_found) {
            if (($input_user === 'Admin' || $input_user === 'admin@lab.com' || $input_user === 'admin') && $password === 'admin') {
                $_SESSION['user_id']  = 1;
                $_SESSION['username'] = 'Admin';
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid Username or Password!';
            }
        }
    } else {
        $error = 'Please fill all fields!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pull Lamp Login - Lab Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #0b0b0e;
            transition: background 0.5s ease;
            overflow: hidden;
        }

        body.light-active {
            background-color: #1a1c23;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            width: 100%;
            max-width: 360px;
            padding: 20px;
        }

        .lamp-box {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            z-index: 10;
        }

        .shade {
            width: 130px;
            height: 65px;
            background: linear-gradient(135deg, #2ecc71, #1e824c);
            border-top-left-radius: 70px;
            border-top-right-radius: 70px;
            position: relative;
            z-index: 3;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        .stand {
            width: 10px;
            height: 80px;
            background: linear-gradient(to bottom, #00d2ff, #0072ff);
            z-index: 2;
            border-radius: 2px;
        }

        .base {
            width: 85px;
            height: 10px;
            background: #111111;
            border-radius: 5px 5px 0 0;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
        }

        .string-container {
            position: absolute;
            top: 60px;
            right: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            z-index: 5;
            transition: transform 0.2s ease-out;
        }

        .string-line {
            width: 2px;
            height: 40px;
            background: #a0a0a0;
        }

        .yellow-tip {
            width: 14px;
            height: 14px;
            background-color: #ff9f43;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255, 159, 67, 0.9);
        }

        .light-beam {
            position: absolute;
            top: 60px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 140px solid transparent;
            border-right: 140px solid transparent;
            border-bottom: 250px solid rgba(255, 255, 255, 0.12);
            opacity: 0;
            transition: opacity 0.5s ease;
            pointer-events: none;
            z-index: 1;
        }

        body.light-active .light-beam {
            opacity: 1;
        }

        .login-form {
            background: #16171f;
            padding: 25px;
            border-radius: 12px;
            width: 100%;
            border: 1px solid #282936;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            pointer-events: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        body.light-active .login-form {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }

        .login-form h2 {
            color: #fff;
            margin-bottom: 15px;
            text-align: center;
        }

        .error-msg {
            background: #ef4444;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 12px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            color: #8a8aa0;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #282936;
            border-radius: 6px;
            background: #1f202b;
            color: #fff;
            outline: none;
        }

        .login-btn {
            width: 100%;
            padding: 11px;
            background: #0072ff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body class="<?= !empty($error) ? 'light-active' : '' ?>">

    <div class="container">
        <div class="lamp-box">
            <div class="shade"></div>
            <div class="stand"></div>
            <div class="base"></div>
            <div class="light-beam"></div>

            <div class="string-container" id="pullString">
                <div class="string-line"></div>
                <div class="yellow-tip"></div>
            </div>
        </div>

        <div class="login-form" id="loginForm">
            <h2>Welcome</h2>
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="username" placeholder="Enter name or email" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter Password" required>
                </div>
                <button type="submit" class="login-btn">Sign In</button>
            </form>
        </div>
    </div>

    <script>
        const pullString = document.getElementById('pullString');
        const body = document.body;

        let isPulled = body.classList.contains('light-active');

        pullString.addEventListener('click', () => {
            pullString.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                pullString.style.transform = 'translateY(0px)';
            }, 200);

            isPulled = !isPulled;
            if (isPulled) {
                body.classList.add('light-active');
            } else {
                body.classList.remove('light-active');
            }
        });
    </script>
</body>
</html>
