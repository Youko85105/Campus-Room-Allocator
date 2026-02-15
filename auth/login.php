<?php
/**
 * Login Page
 * Campus Room Allocation System
 */

require_once '../config/database.php';
require_once 'session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: /Campus-Room-Allocator-main/pages/admin/dashboard.php');
    } else {
        header('Location: /Campus-Room-Allocator-main/pages/student/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Get user from database
            $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
            $user = fetchOne($sql, [$email]);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                setUserSession($user);
                
                // Log activity
                $logSql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                          VALUES (?, 'login', 'User logged in', ?)";
                executeQuery($logSql, [$user['user_id'], $_SERVER['REMOTE_ADDR']]);
                
                // Redirect based on role
                $redirect = $_GET['redirect'] ?? '';
                if (!empty($redirect)) {
                    header('Location: ' . $redirect);
                } elseif ($user['user_role'] === 'admin') {
                    header('Location: /Campus-Room-Allocator-main/pages/admin/dashboard.php');
                } else {
                    header('Location: /Campus-Room-Allocator-main/pages/student/dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Check for error messages in URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_expired':
            $error = 'Your session has expired. Please login again.';
            break;
        case 'access_denied':
            $error = 'Access denied. Please login with appropriate credentials.';
            break;
    }
}

// Check for success messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registered':
            $success = 'Registration successful! Please login with your credentials.';
            break;
        case 'logout':
            $success = 'You have been logged out successfully.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Room Allocation</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Enhanced UI CSS -->
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .input-group-custom {
            position: relative;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .demo-credentials strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-building"></i>
                <h2 class="mb-0">Campus Room Allocation</h2>
                <p class="mb-0">Login to your account</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="your.email@student.edu"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-group-custom">
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                            >
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0">Don't have an account? 
                        <a href="register.php" class="text-decoration-none fw-bold">Register here</a>
                    </p>
                </div>
                
                <!-- Demo Credentials Box -->
                <div class="demo-credentials">
                    <strong><i class="fas fa-info-circle"></i> Demo Credentials:</strong>
                    <div class="mt-2">
                        <strong>Admin:</strong> admin@campus.edu / admin123
                    </div>
                    <div class="mt-1">
                        <strong>Student:</strong> john.doe@student.edu / student123
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced UI JavaScript -->
    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Enhanced form validation with loading
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                toast.error('Please fill in all fields.');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                toast.error('Please enter a valid email address.');
                document.getElementById('email').classList.add('shake');
                setTimeout(() => {
                    document.getElementById('email').classList.remove('shake');
                }, 500);
                return false;
            }
            
            // Show loading animation
            showLoading('Logging you in...');
        });
        
        // Convert PHP alerts to toast notifications
        document.addEventListener('DOMContentLoaded', function() {
            // Add entrance animation
            document.querySelector('.login-card').classList.add('scale-in');
            
            <?php if ($error): ?>
                toast.error('<?php echo addslashes($error); ?>');
                // Hide Bootstrap alert
                const alert = document.querySelector('.alert-danger');
                if (alert) alert.style.display = 'none';
            <?php endif; ?>
            
            <?php if ($success): ?>
                toast.success('<?php echo addslashes($success); ?>');
                // Hide Bootstrap alert
                const alert = document.querySelector('.alert-success');
                if (alert) alert.style.display = 'none';
            <?php endif; ?>
        });
    </script>
</body>
</html>