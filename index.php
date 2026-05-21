<?php
// Include helper functions (auth.php automatically calls session_start())
require_once 'includes/auth.php';
require_once 'includes/json_helpers.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header("Location: /home.php");
    exit();
}

$error_message = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = read_json('data/users.json');
    $action = $_POST['action'] ?? '';

    // --- LOGIN LOGIC ---
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user_found = false;
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $user_found = true;
                if (password_verify($password, $user['password_hash'])) {
                    // Password correct, set session variables
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: /home.php");
                    exit();
                }
                break; // Stop looping once we found the user
            }
        }

        // Generic error for both "user not found" and "wrong password"
        if (!$user_found || !isset($_SESSION['username'])) {
            $error_message = "Incorrect username or password.";
        }
    }

    // --- SIGNUP LOGIC ---
    elseif ($action === 'signup') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username)) {
            $error_message = "Username cannot be empty.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error_message = "Username must only contain letters, numbers, and underscores.";
        } elseif (empty($email)) {
            $error_message = "Email cannot be empty.";
        } elseif (empty($password)) {
            $error_message = "Password cannot be empty.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } else {
            // Check if username already exists
            $username_taken = false;
            foreach ($users as $user) {
                if (strcasecmp($user['username'], $username) === 0) {
                    $username_taken = true;
                    break;
                }
            }

            if ($username_taken) {
                $error_message = "Username is already taken.";
            } else {
                // Passed all validation - create the user!
                $new_user = [
                    "username" => $username,
                    "password_hash" => password_hash($password, PASSWORD_DEFAULT),
                    "email" => $email,
                    "role" => "user", // Defaults to user as per PRD
                    "created_at" => date('Y-m-d'),
                    "pfp" => "assets/img/default-pfp.png"
                ];

                // Append and save users
                $users[] = $new_user;
                write_json('data/users.json', $users);

                // Create empty score file (using empty object stdClass so it saves as {})
                write_json("data/scores/{$username}.json", new stdClass());

                // Log the new user in
                $_SESSION['username'] = $new_user['username'];
                $_SESSION['role'] = $new_user['role'];
                header("Location: /home.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - osu! Web Clone</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Modern Dark Theme Styling */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #ffffff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-container {
            background: rgba(25, 25, 35, 0.95);
            max-width: 400px;
            width: 90%;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            text-align: center;
            box-sizing: border-box;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .auth-container h1 {
            color: #ff66aa; /* Classic osu! pink */
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 2.2em;
            text-shadow: 0 2px 10px rgba(255, 102, 170, 0.3);
        }

        .auth-container h2 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.2em;
            font-weight: 400;
            color: #bbbbcc;
        }

        .error-msg {
            background: rgba(255, 77, 77, 0.1);
            color: #ff4d4d;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 77, 77, 0.3);
            font-size: 0.9em;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input {
            width: 100%;
            padding: 14px 15px;
            background: #2a2a35;
            border: 1px solid #3a3a45;
            border-radius: 8px;
            color: #ffffff;
            font-size: 1em;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        input::placeholder {
            color: #888899;
        }

        input:focus {
            outline: none;
            border-color: #ff66aa;
            box-shadow: 0 0 8px rgba(255, 102, 170, 0.4);
            background: #2f2f3d;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #ff66aa;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.1s ease;
            margin-top: 10px;
        }

        button:hover {
            background: #ff4d94;
        }

        button:active {
            transform: scale(0.98);
        }

        .toggle-link {
            color: #888899;
            cursor: pointer;
            margin-top: 25px;
            display: inline-block;
            transition: color 0.3s ease;
            font-size: 0.9em;
        }

        .toggle-link:hover {
            color: #ff66aa;
            text-decoration: underline;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <h1>osu! Web Clone</h1>
    
    <?php if ($error_message): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div id="login-section" <?php echo (isset($_POST['action']) && $_POST['action'] === 'signup' && $error_message) ? 'class="hidden"' : ''; ?>>
        <h2>Welcome back</h2>
        <form method="POST" action="/index.php">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Log In</button>
        </form>
        <span class="toggle-link" onclick="toggleForms()">Don't have an account? Sign up</span>
    </div>

    <div id="signup-section" <?php echo (isset($_POST['action']) && $_POST['action'] === 'signup' && $error_message) ? '' : 'class="hidden"'; ?>>
        <h2>Create an account</h2>
        <form method="POST" action="/index.php">
            <input type="hidden" name="action" value="signup">
            <input type="text" name="username" placeholder="Username (letters, numbers, _ only)" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Create Account</button>
        </form>
        <span class="toggle-link" onclick="toggleForms()">Already have an account? Log in</span>
    </div>
</div>

<script>
    function toggleForms() {
        const loginSec = document.getElementById('login-section');
        const signupSec = document.getElementById('signup-section');
        loginSec.classList.toggle('hidden');
        signupSec.classList.toggle('hidden');
    }
</script>

</body>
</html>