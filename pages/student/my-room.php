<?php
/**
 * Student My Room Page
 * Campus Room Allocation System
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

requireStudent();

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get current allocation with full details
$allocationSql = "SELECT a.*, r.room_number, r.building, r.floor, r.amenities, r.current_occupancy,
                  rt.type_name, rt.capacity, rt.description,
                  allocator.first_name as allocator_fname, allocator.last_name as allocator_lname
                  FROM allocations a
                  INNER JOIN rooms r ON a.room_id = r.room_id
                  INNER JOIN room_types rt ON r.type_id = rt.type_id
                  LEFT JOIN users allocator ON a.allocated_by = allocator.user_id
                  WHERE a.user_id = ? AND a.status IN ('confirmed', 'checked_in')
                  ORDER BY a.created_at DESC LIMIT 1";
$allocation = fetchOne($allocationSql, [$user_id]);

// Get roommates if in shared room
$roommates = [];
if ($allocation && $allocation['capacity'] > 1) {
    $roommatesSql = "SELECT u.student_id, CONCAT(u.first_name, ' ', u.last_name) as name, 
                     u.program, u.year_level, u.email
                     FROM allocations a
                     INNER JOIN users u ON a.user_id = u.user_id
                     WHERE a.room_id = ? AND a.user_id != ? 
                     AND a.status IN ('confirmed', 'checked_in')";
    $roommates = fetchAll($roommatesSql, [$allocation['room_id'], $user_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Room - Campus Room Allocation</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        .room-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .room-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .room-icon {
            font-size: 5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        .info-section {
            padding: 30px;
        }
        .info-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-icon {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #667eea;
            font-size: 1.3rem;
        }
        .amenity-badge {
            display: inline-block;
            background: #e7f3ff;
            color: #0066cc;
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
            font-size: 0.9rem;
        }
        .roommate-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .no-room-container {
            text-align: center;
            padding: 60px 20px;
        }
        .no-room-icon {
            font-size: 5rem;
            color: #6c757d;
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
                        <a class="nav-link active" href="my-room.php">
                            <i class="fas fa-door-open"></i> My Room
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request-room.php">
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
        <?php if ($allocation): ?>
            <!-- Room Details -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="room-card">
                        <div class="room-header">
                            <div class="room-icon">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <h1><?php echo htmlspecialchars($allocation['room_number']); ?></h1>
                            <p class="mb-0"><?php echo htmlspecialchars($allocation['building']); ?> - Floor <?php echo $allocation['floor']; ?></p>
                        </div>
                        
                        <!-- Building Image -->
                        <div style="padding: 20px; background: #f8f9fa;">
                            <?php
                            require_once '../../includes/image-placeholder.php';
                            $buildingImage = getBuildingPlaceholder($allocation['building']);
                            ?>
                            <img src="<?php echo $buildingImage; ?>" 
                                 alt="<?php echo htmlspecialchars($allocation['building']); ?>"
                                 style="width: 100%; height: 300px; object-fit: cover; border-radius: 10px;">
                        </div>
                        
                        <div class="info-section">
                            <h4 class="mb-4"><i class="fas fa-info-circle"></i> Room Information</h4>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div>
                                    <strong>Room Type</strong>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($allocation['type_name']); ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <strong>Capacity & Occupancy</strong>
                                    <p class="mb-0 text-muted">
                                        <?php echo $allocation['current_occupancy']; ?> / <?php echo $allocation['capacity']; ?> occupied
                                    </p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <strong>Allocation Date</strong>
                                    <p class="mb-0 text-muted">
                                        <?php echo date('F d, Y', strtotime($allocation['allocation_date'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div>
                                    <strong>Academic Period</strong>
                                    <p class="mb-0 text-muted">
                                        <?php echo htmlspecialchars($allocation['academic_year']); ?> - Semester <?php echo $allocation['semester']; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <strong>Status</strong>
                                    <p class="mb-0">
                                        <?php
                                        $statusColors = [
                                            'confirmed' => 'info',
                                            'checked_in' => 'success'
                                        ];
                                        $color = $statusColors[$allocation['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $allocation['status'])); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($allocation['check_in_date']): ?>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div>
                                    <strong>Check-in Date</strong>
                                    <p class="mb-0 text-muted">
                                        <?php echo date('F d, Y', strtotime($allocation['check_in_date'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($allocation['amenities']): ?>
                            <div class="mt-4">
                                <h5><i class="fas fa-star"></i> Amenities</h5>
                                <div class="mt-3">
                                    <?php 
                                    $amenities = explode(',', $allocation['amenities']);
                                    foreach ($amenities as $amenity): 
                                    ?>
                                        <span class="amenity-badge">
                                            <i class="fas fa-check"></i> <?php echo htmlspecialchars(trim($amenity)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($allocation['status'] === 'confirmed'): ?>
                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle"></i> 
                                Your room has been confirmed. Please visit the housing office to complete check-in.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Roommates Section -->
                    <?php if (!empty($roommates)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Your Roommates</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($roommates as $roommate): ?>
                            <div class="roommate-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($roommate['name']); ?></h6>
                                        <p class="mb-0 text-muted">
                                            <small>
                                                <?php echo htmlspecialchars($roommate['student_id']); ?> | 
                                                Year <?php echo $roommate['year_level']; ?> | 
                                                <?php echo htmlspecialchars($roommate['program']); ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div>
                                        <a href="mailto:<?php echo htmlspecialchars($roommate['email']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- No Room Allocated -->
            <div class="row">
                <div class="col-lg-6 mx-auto">
                    <div class="room-card">
                        <div class="no-room-container">
                            <div class="no-room-icon">
                                <i class="fas fa-bed"></i>
                            </div>
                            <h2>No Room Allocated</h2>
                            <p class="text-muted">You haven't been assigned a room yet.</p>
                            <p>Please check your dashboard for recommended rooms or contact the housing office.</p>
                            <a href="dashboard.php" class="btn btn-primary mt-3">
                                <i class="fas fa-home"></i> Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>