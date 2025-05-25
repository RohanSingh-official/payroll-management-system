<?php
require_once 'includes/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit;
}

$error = '';

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    // Validate credentials
    $sql = "SELECT user_id, username, password, full_name, role FROM users WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        
        if ($stmt->execute()) {
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id, $username, $hashed_password, $full_name, $role);
                
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION["user_id"] = $user_id;
                        $_SESSION["username"] = $username;
                        $_SESSION["full_name"] = $full_name;
                        $_SESSION["role"] = $role;
                        
                        // Update last login time
                        $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            $update_stmt->bind_param("i", $user_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        
                        // Redirect to dashboard
                        header("Location: pages/dashboard.php");
                        exit;
                    } else {
                        $error = "Invalid username or password.";
                    }
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Oops! Something went wrong. Please try again later.";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management System - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 900px;
            max-width: 100%;
            display: flex;
        }
        .login-image {
            background-image: url('assets/images/payroll-bg.jpg');
            background-size: cover;
            background-position: center;
            width: 50%;
            position: relative;
        }
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
        }
        .login-form {
            padding: 40px;
            width: 50%;
        }
        .login-form h2 {
            color: #333;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .form-control {
            border-radius: 5px;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 5px;
            color: white;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 90%;
            }
            .login-image, .login-form {
                width: 100%;
            }
            .login-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-image"></div>
        <div class="login-form">
            <h2 class="text-center">Payroll Management System</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Default credentials: admin / admin123</p>
                <p>&copy; <?php echo date('Y'); ?> Payroll Management System</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>