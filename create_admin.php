<?php
require_once 'config/database.php';

echo "<h2>Admin Account Setup</h2>";

// Check if admin already exists
$check = fetchOne("SELECT user_id FROM users WHERE email = 'admin@campus.edu'");

if ($check) {
    echo "<p>Admin already exists. Updating password...</p>";
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    executeQuery("UPDATE users SET password_hash = ? WHERE email = 'admin@campus.edu'", [$password_hash]);
    echo "<div style='color: green;'>✅ Admin password updated to: <strong>admin123</strong></div>";
} else {
    echo "<p>Creating new admin account...</p>";
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (student_id, first_name, last_name, email, password_hash, phone, year_level, gender, program, user_role) 
            VALUES ('ADMIN002', 'System', 'Admin', 'admin@campus.edu', ?, '1234567890', '1', 'Other', 'Administration', 'admin')";
    
    executeQuery($sql, [$password_hash]);
    echo "<div style='color: green;'>✅ Admin account created!</div>";
}

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "<p><strong>Email:</strong> admin@campus.edu</p>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "<br><a href='auth/login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
echo "<br><br><p style='color: red;'><strong>IMPORTANT:</strong> Delete this file after creating the admin account!</p>";
?>