<?php
/**
 * Notification Center
 * View and manage notifications
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

requireStudent();

$user = getCurrentUser();
$user_id = $user['user_id'];

// Mark notification as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $notif_id = (int)$_GET['read'];
    executeQuery("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?", 
                 [$notif_id, $user_id]);
    header('Location: notifications.php');
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    executeQuery("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", [$user_id]);
    header('Location: notifications.php');
    exit();
}

// Delete notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notif_id = (int)$_GET['delete'];
    executeQuery("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?", 
                 [$notif_id, $user_id]);
    header('Location: notifications.php');
    exit();
}

// Get all notifications
$notificationsSql = "SELECT * FROM notifications 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC";
$notifications = fetchAll($notificationsSql, [$user_id]);

// Count unread
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Campus Room Allocation</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
    <link rel="stylesheet" href="../../assets/css/animations.css">
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white !important;
        }
        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #e9ecef;
        }
        .notification-card.unread {
            border-left-color: #667eea;
            background: #f8f9ff;
        }
        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .icon-info { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .icon-success { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .icon-warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .icon-danger { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-icon {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-building"></i> Campus Room Allocation
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-room.php">
                            <i class="fas fa-door-open"></i> My Room
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request-room.php">
                            <i class="fas fa-paper-plane"></i> Request Room
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-bell"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-primary"><?php echo $unreadCount; ?> new</span>
                <?php endif; ?>
            </h2>
            
            <?php if ($unreadCount > 0): ?>
                <a href="?mark_all_read" class="btn btn-outline-primary">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
            <!-- Empty State -->
            <div class="card">
                <div class="card-body empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h4>No Notifications Yet</h4>
                    <p class="text-muted">You'll see important updates and messages here</p>
                    <a href="dashboard.php" class="btn btn-primary mt-3">
                        <i class="fas fa-home"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Notifications List -->
            <?php foreach ($notifications as $notification): ?>
                <?php
                $iconClass = [
                    'info' => 'icon-info fa-info-circle',
                    'success' => 'icon-success fa-check-circle',
                    'warning' => 'icon-warning fa-exclamation-triangle',
                    'error' => 'icon-danger fa-times-circle'
                ];
                $icon = $iconClass[$notification['type']] ?? 'icon-info fa-bell';
                $isUnread = !$notification['is_read'];
                ?>
                <div class="notification-card <?php echo $isUnread ? 'unread' : ''; ?> fade-in">
                    <div class="d-flex">
                        <div class="notification-icon <?php echo explode(' ', $icon)[0]; ?> me-3">
                            <i class="fas <?php echo explode(' ', $icon)[1]; ?>"></i>
                        </div>
                        
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                    <?php if ($isUnread): ?>
                                        <span class="badge bg-primary ms-2">New</span>
                                    <?php endif; ?>
                                </h5>
                                <small class="text-muted">
                                    <?php
                                    $time = strtotime($notification['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 60) echo 'Just now';
                                    elseif ($diff < 3600) echo floor($diff / 60) . ' minutes ago';
                                    elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                                    else echo date('M d, Y', $time);
                                    ?>
                                </small>
                            </div>
                            
                            <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                            
                            <div class="d-flex gap-2">
                                <?php if ($isUnread): ?>
                                    <a href="?read=<?php echo $notification['notification_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($notification['action_url']): ?>
                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-arrow-right"></i> View Details
                                    </a>
                                <?php endif; ?>
                                
                                <a href="?delete=<?php echo $notification['notification_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this notification?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/toast.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add staggered animation to cards
            const cards = document.querySelectorAll('.notification-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                }, index * 100);
            });
        });
    </script>
</body>
</html>