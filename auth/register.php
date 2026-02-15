<?php
/**
 * Student Registration Page
 * Campus Room Allocation System
 */

require_once '../config/database.php';
require_once 'session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /csc433/pages/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $student_id = sanitize($_POST['student_id'] ?? '');
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $year_level = sanitize($_POST['year_level'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $program = sanitize($_POST['program'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($student_id) || empty($first_name) || empty($last_name) || 
        empty($email) || empty($year_level) || empty($gender) || 
        empty($program) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if student ID already exists
            $checkSql = "SELECT user_id FROM users WHERE student_id = ? OR email = ?";
            $existing = fetchOne($checkSql, [$student_id, $email]);
            
            if ($existing) {
                $error = 'Student ID or email already registered.';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insertSql = "INSERT INTO users (student_id, first_name, last_name, email, 
                             password_hash, phone, year_level, gender, program, user_role) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')";
                
                executeQuery($insertSql, [
                    $student_id, $first_name, $last_name, $email,
                    $password_hash, $phone, $year_level, $gender, $program
                ]);
                
                // Get the new user ID
                $user_id = getLastInsertId();
                
                // Log activity
                $logSql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                          VALUES (?, 'registration', 'New user registered', ?)";
                executeQuery($logSql, [$user_id, $_SERVER['REMOTE_ADDR']]);
                
                // Redirect to login with success message
                header('Location: login.php?success=registered');
                exit();
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred during registration. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Campus Room Allocation</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        
        .register-container {
            max-width: 600px;
            width: 100%;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .register-body {
            padding: 40px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-register:hover {
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
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h2 class="mb-0">Student Registration</h2>
                <p class="mb-0">Create your account</p>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <!-- Student ID -->
                        <div class="col-md-12 mb-3">
                            <label for="student_id" class="form-label required-field">
                                <i class="fas fa-id-card"></i> Student ID
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="student_id" 
                                name="student_id" 
                                placeholder="e.g., STU2024001"
                                value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>"
                                required
                            >
                        </div>
                        
                        <!-- First Name -->
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label required-field">
                                <i class="fas fa-user"></i> First Name
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                required
                            >
                        </div>
                        
                        <!-- Last Name -->
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label required-field">
                                <i class="fas fa-user"></i> Last Name
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                required
                            >
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-12 mb-3">
                            <label for="email" class="form-label required-field">
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
                        
                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="phone" 
                                name="phone" 
                                placeholder="+268 1234 5678"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            >
                        </div>
                        
                        <!-- Year Level -->
                        <div class="col-md-6 mb-3">
                            <label for="year_level" class="form-label required-field">
                                <i class="fas fa-graduation-cap"></i> Year Level
                            </label>
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="">Select Year</option>
                                <option value="1" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '1') ? 'selected' : ''; ?>>Year 1</option>
                                <option value="2" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '2') ? 'selected' : ''; ?>>Year 2</option>
                                <option value="3" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '3') ? 'selected' : ''; ?>>Year 3</option>
                                <option value="4" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '4') ? 'selected' : ''; ?>>Year 4</option>
                                <option value="5" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '5') ? 'selected' : ''; ?>>Year 5</option>
                            </select>
                        </div>
                        
                        <!-- Gender -->
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label required-field">
                                <i class="fas fa-venus-mars"></i> Gender
                            </label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <!-- Program -->
                        <div class="col-md-6 mb-3">
                            <label for="program" class="form-label required-field">
                                <i class="fas fa-book"></i> Program
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="program" 
                                name="program" 
                                placeholder="e.g., Computer Science"
                                value="<?php echo isset($_POST['program']) ? htmlspecialchars($_POST['program']) : ''; ?>"
                                required
                            >
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label required-field">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <div class="input-group-custom">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Min. 6 characters"
                                    required
                                >
                                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label required-field">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <div class="input-group-custom">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    placeholder="Repeat password"
                                    required
                                >
                                <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the Terms and Conditions
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-register w-100">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0">Already have an account? 
                        <a href="login.php" class="text-decoration-none fw-bold">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password toggle for password
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Password toggle for confirm password
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Password strength indicator
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '';
            } else if (password.length < 6) {
                strengthBar.style.width = '33%';
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (password.length < 10) {
                strengthBar.style.width = '66%';
                strengthBar.style.backgroundColor = '#ffc107';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#28a745';
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            const terms = document.getElementById('terms').checked;
            if (!terms) {
                e.preventDefault();
                alert('Please accept the Terms and Conditions.');
                return false;
            }
        });
    </script>
</body>
</html>