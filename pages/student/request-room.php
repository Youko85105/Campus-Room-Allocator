<?php
/**
 * Room Request/Preference System
 * Students can submit room preferences and special requests
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

requireStudent();

$user = getCurrentUser();
$user_id = $user['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferred_building = sanitize($_POST['preferred_building'] ?? '');
    $preferred_floor = isset($_POST['preferred_floor']) ? (int)$_POST['preferred_floor'] : null;
    $roommate_preference = sanitize($_POST['roommate_preference'] ?? '');
    $special_needs = sanitize($_POST['special_needs'] ?? '');
    
    try {
        // Check if user already has an active request
        $checkSql = "SELECT request_id FROM allocation_requests 
                     WHERE user_id = ? AND request_status = 'pending'";
        $existing = fetchOne($checkSql, [$user_id]);
        
        if ($existing) {
            $error = 'You already have a pending request. Please wait for admin review.';
        } else {
            // Insert new request
            $insertSql = "INSERT INTO allocation_requests 
                         (user_id, preferred_building, preferred_floor, roommate_preference, special_needs, request_status) 
                         VALUES (?, ?, ?, ?, ?, 'pending')";
            executeQuery($insertSql, [$user_id, $preferred_building, $preferred_floor, $roommate_preference, $special_needs]);
            
            // Log activity
            $logSql = "INSERT INTO activity_logs (user_id, action, description) 
                      VALUES (?, 'room_request', 'Student submitted room preference request')";
            executeQuery($logSql, [$user_id]);
            
            $success = 'Your room preference request has been submitted successfully! An admin will review it soon.';
        }
    } catch (Exception $e) {
        error_log("Room request error: " . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
}

// Get user's requests history
$requestsSql = "SELECT * FROM allocation_requests 
                WHERE user_id = ? 
                ORDER BY created_at DESC";
$requests = fetchAll($requestsSql, [$user_id]);

// Get available buildings
$buildingsSql = "SELECT DISTINCT building FROM rooms ORDER BY building";
$buildings = fetchAll($buildingsSql);

// Check if user already has a room
$hasRoomSql = "SELECT COUNT(*) as count FROM allocations 
               WHERE user_id = ? AND status IN ('confirmed', 'checked_in')";
$hasRoom = fetchOne($hasRoomSql, [$user_id])['count'] > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Room - Campus Room Allocation</title>
    
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
        .request-form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .request-card {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
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
                        <a class="nav-link active" href="request-room.php">
                            <i class="fas fa-paper-plane"></i> Request Room
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
        <h2 class="mb-4"><i class="fas fa-paper-plane"></i> Room Preference Request</h2>
        
        <div class="row">
            <div class="col-lg-8">
                <?php if ($hasRoom): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You already have a room assigned. Room change requests are subject to admin approval.
                    </div>
                <?php endif; ?>
                
                <!-- Request Form -->
                <div class="request-form-card scale-in">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" style="display:none;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" style="display:none;">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h4 class="mb-4"><i class="fas fa-edit"></i> Submit Your Preferences</h4>
                    
                    <form method="POST" id="requestForm">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-building"></i> Preferred Building
                            </label>
                            <select class="form-select" name="preferred_building">
                                <option value="">No Preference</option>
                                <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                        <?php echo htmlspecialchars($building['building']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Choose your preferred building or leave blank</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-layer-group"></i> Preferred Floor
                            </label>
                            <input type="number" class="form-control" name="preferred_floor" min="0" max="10" 
                                   placeholder="e.g., 2">
                            <small class="text-muted">Optional: Specify floor preference</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-user-friends"></i> Roommate Preference
                            </label>
                            <input type="text" class="form-control" name="roommate_preference" 
                                   placeholder="Enter student ID or name of preferred roommate">
                            <small class="text-muted">Optional: Request a specific roommate (for shared rooms)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-wheelchair"></i> Special Needs / Requirements
                            </label>
                            <textarea class="form-control" name="special_needs" rows="4" 
                                      placeholder="Describe any special requirements (e.g., ground floor access, medical needs, allergies)"></textarea>
                            <small class="text-muted">Please specify any accessibility or medical requirements</small>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note:</strong> Preferences are not guaranteed. Room allocation is based on availability and year level requirements.
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Info Box -->
                <div class="info-box">
                    <h5><i class="fas fa-info-circle"></i> How It Works</h5>
                    <ol class="mb-0">
                        <li class="mb-2">Fill in your room preferences</li>
                        <li class="mb-2">Submit your request</li>
                        <li class="mb-2">Admin reviews your request</li>
                        <li class="mb-0">You'll be notified of the decision</li>
                    </ol>
                </div>
                
                <!-- Recommended Room Type -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-star"></i> Your Recommended Room Type</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $roomType = '';
                        if ($user['year_level'] == 1) {
                            $roomType = 'Common Room (6 beds)';
                        } elseif (in_array($user['year_level'], [2, 3])) {
                            $roomType = 'Double Room (2 beds)';
                        } else {
                            $roomType = 'Single Room (1 bed)';
                        }
                        ?>
                        <p class="mb-2"><strong>Year <?php echo $user['year_level']; ?>:</strong></p>
                        <h5 class="text-success"><?php echo $roomType; ?></h5>
                        <small class="text-muted">Based on your current year level</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Request History -->
        <?php if (!empty($requests)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-history"></i> Your Request History</h4>
                <?php foreach ($requests as $request): ?>
                <div class="request-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1">
                                <strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($request['created_at'])); ?>
                            </p>
                            <?php if ($request['preferred_building']): ?>
                                <p class="mb-1"><strong>Building:</strong> <?php echo htmlspecialchars($request['preferred_building']); ?></p>
                            <?php endif; ?>
                            <?php if ($request['special_needs']): ?>
                                <p class="mb-1"><strong>Special Needs:</strong> <?php echo htmlspecialchars($request['special_needs']); ?></p>
                            <?php endif; ?>
                            <?php if ($request['admin_response']): ?>
                                <p class="mb-0"><strong>Admin Response:</strong> <?php echo htmlspecialchars($request['admin_response']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger'
                            ];
                            $color = $statusColors[$request['request_status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo ucfirst($request['request_status']); ?>
                            </span>
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
            
            // Form submission
            document.getElementById('requestForm').addEventListener('submit', function() {
                showLoading('Submitting your request...');
            });
        });
    </script>
</body>
</html>