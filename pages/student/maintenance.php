<?php
/**
 * Maintenance Request System
 * Students can report room issues
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

requireStudent();

$user = getCurrentUser();
$user_id = $user['user_id'];
$error = '';
$success = '';

// Get student's current room
$roomSql = "SELECT a.*, r.room_number, r.building 
            FROM allocations a
            INNER JOIN rooms r ON a.room_id = r.room_id
            WHERE a.user_id = ? AND a.status IN ('confirmed', 'checked_in')
            ORDER BY a.created_at DESC LIMIT 1";
$currentRoom = fetchOne($roomSql, [$user_id]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentRoom) {
    $issue_type = sanitize($_POST['issue_type'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($issue_type) || empty($description)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $insertSql = "INSERT INTO maintenance_requests 
                         (user_id, room_id, issue_type, priority, description, status) 
                         VALUES (?, ?, ?, ?, ?, 'pending')";
            executeQuery($insertSql, [$user_id, $currentRoom['room_id'], $issue_type, $priority, $description]);
            
            // Log activity
            $logSql = "INSERT INTO activity_logs (user_id, action, description) 
                      VALUES (?, 'maintenance_request', 'Student submitted maintenance request')";
            executeQuery($logSql, [$user_id]);
            
            $success = 'Maintenance request submitted successfully! Our team will address it soon.';
        } catch (Exception $e) {
            error_log("Maintenance request error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Get user's maintenance requests
$requestsSql = "SELECT mr.*, r.room_number, r.building
                FROM maintenance_requests mr
                INNER JOIN rooms r ON mr.room_id = r.room_id
                WHERE mr.user_id = ?
                ORDER BY mr.created_at DESC";
$requests = fetchAll($requestsSql, [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Requests - Campus Room Allocation</title>
    
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
        .request-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .issue-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        .priority-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
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
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="maintenance.php">
                            <i class="fas fa-tools"></i> Maintenance
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
        <h2 class="mb-4"><i class="fas fa-tools"></i> Maintenance Requests</h2>
        
        <?php if (!$currentRoom): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                You need to have a room allocated before submitting maintenance requests.
                <a href="dashboard.php" class="alert-link">View available rooms</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <!-- Request Form -->
                    <div class="request-card scale-in mb-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" style="display:none;"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" style="display:none;"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <h4 class="mb-4"><i class="fas fa-plus-circle"></i> Submit New Request</h4>
                        
                        <div class="alert alert-info mb-4">
                            <strong>Your Room:</strong> <?php echo htmlspecialchars($currentRoom['room_number']); ?> 
                            (<?php echo htmlspecialchars($currentRoom['building']); ?>)
                        </div>
                        
                        <form method="POST" id="maintenanceForm">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-wrench"></i> Issue Type *</label>
                                <select class="form-select" name="issue_type" required>
                                    <option value="">Select Issue Type</option>
                                    <option value="Plumbing">🚿 Plumbing (Leaks, Drains, Toilets)</option>
                                    <option value="Electrical">⚡ Electrical (Lights, Outlets, Switches)</option>
                                    <option value="HVAC">❄️ HVAC (Heating, Cooling, Ventilation)</option>
                                    <option value="Furniture">🪑 Furniture (Bed, Desk, Chair)</option>
                                    <option value="Door/Window">🚪 Door/Window (Locks, Hinges, Glass)</option>
                                    <option value="Cleanliness">🧹 Cleanliness (Pests, Odors)</option>
                                    <option value="Safety">🚨 Safety Concern</option>
                                    <option value="Other">🔧 Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-exclamation-circle"></i> Priority Level *</label>
                                <select class="form-select" name="priority" required>
                                    <option value="low">🟢 Low - Can wait a few days</option>
                                    <option value="medium" selected>🟡 Medium - Should be fixed soon</option>
                                    <option value="high">🔴 High - Needs immediate attention</option>
                                    <option value="emergency">🚨 Emergency - Safety hazard</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-comment-alt"></i> Description *</label>
                                <textarea class="form-control" name="description" rows="4" 
                                          placeholder="Please describe the issue in detail..." required></textarea>
                                <small class="text-muted">Include location in room, what happened, when it started, etc.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Quick Tips -->
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Quick Tips</h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li class="mb-2">Be specific about the problem</li>
                                <li class="mb-2">Include exact location in room</li>
                                <li class="mb-2">Mention if issue affects daily life</li>
                                <li class="mb-0">For emergencies, call housing office directly</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Request History -->
        <?php if (!empty($requests)): ?>
        <div class="mt-4">
            <h4 class="mb-3"><i class="fas fa-history"></i> Your Maintenance Requests</h4>
            <div class="row">
                <?php foreach ($requests as $request): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0"><?php echo htmlspecialchars($request['issue_type']); ?></h5>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'secondary'
                                ];
                                $priorityColors = [
                                    'low' => 'success',
                                    'medium' => 'warning',
                                    'high' => 'danger',
                                    'emergency' => 'dark'
                                ];
                                $statusColor = $statusColors[$request['status']] ?? 'secondary';
                                $priorityColor = $priorityColors[$request['priority']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $statusColor; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-2">
                                <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($request['room_number']); ?> - 
                                <?php echo htmlspecialchars($request['building']); ?>
                            </p>
                            
                            <p class="mb-2"><?php echo htmlspecialchars($request['description']); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="priority-badge bg-<?php echo $priorityColor; ?> text-white">
                                    Priority: <?php echo ucfirst($request['priority']); ?>
                                </span>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($request['created_at'])); ?>
                                </small>
                            </div>
                            
                            <?php if ($request['admin_notes']): ?>
                                <div class="alert alert-info mt-3 mb-0">
                                    <strong>Admin Notes:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/toast.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($error): ?>
                toast.error('<?php echo addslashes($error); ?>');
            <?php endif; ?>
            
            <?php if ($success): ?>
                toast.success('<?php echo addslashes($success); ?>');
            <?php endif; ?>
            
            document.getElementById('maintenanceForm')?.addEventListener('submit', function() {
                showLoading('Submitting request...');
            });
        });
    </script>
</body>
</html>