-- Add notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    action_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample notifications for testing
INSERT INTO notifications (user_id, title, message, type, action_url) 
SELECT user_id, 
       'Welcome to Campus Room Allocation!',
       'Complete your profile and submit room preferences to get started.',
       'info',
       'profile.php'
FROM users 
WHERE user_role = 'student' 
LIMIT 5;

INSERT INTO notifications (user_id, title, message, type, action_url)
SELECT user_id,
       'Room Allocation Update',
       'New rooms have been added to the system. Check the dashboard for available options!',
       'success',
       'dashboard.php'
FROM users
WHERE user_role = 'student'
LIMIT 5;