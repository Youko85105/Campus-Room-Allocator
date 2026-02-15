<?php
require_once 'config/database.php';

// Check if student already exists
$check = fetchOne("SELECT user_id FROM users WHERE email = 'john.doe@student.edu'");

if ($check) {
    echo "Student already exists. Updating password...<br>";
    $password_hash = password_hash('student123', PASSWORD_DEFAULT);
    executeQuery("UPDATE users SET password_hash = ? WHERE email = 'john.doe@student.edu'", [$password_hash]);
    echo "✅ Student password updated to: student123<br>";
} else {
    echo "Creating new student account...<br>";
    $password_hash = password_hash('student123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (student_id, first_name, last_name, email, password_hash, phone, year_level, gender, program, user_role) 
            VALUES ('STU2024001', 'John', 'Doe', 'john.doe@student.edu', ?, '1234567891', '1', 'Male', 'Computer Science', 'student')";
    
    executeQuery($sql, [$password_hash]);
    echo "✅ Student account created!<br>";
    echo "Email: john.doe@student.edu<br>";
    echo "Password: student123<br>";
}

echo "<br><a href='auth/login.php'>Go to Login</a>";
?>