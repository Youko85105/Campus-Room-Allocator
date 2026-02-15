<?php
/**
 * Student Dashboard
 * Campus Room Allocation System
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Require student login
requireStudent();

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get student's current allocation
$allocationSql = "SELECT a.*, r.room_number, r.building, r.floor, rt.type_name, rt.capacity
                  FROM allocations a
                  INNER JOIN rooms r ON a.room_id = r.room_id
                  INNER JOIN room_types rt ON r.type_id = rt.type_id
                  WHERE a.user_id = ? AND a.status IN ('confirmed', 'checked_in')
                  ORDER BY a.created_at DESC LIMIT 1";
$currentAllocation = fetchOne($allocationSql, [$user_id]);

// Get recommended rooms for student's year level
$recommendedSql = "SELECT r.*, rt.type_name, rt.capacity, 
                   (rt.capacity - r.current_occupancy) as available_spaces
                   FROM rooms r
                   INNER JOIN room_types rt ON r.type_id = rt.type_id
                   WHERE r.status = 'available' 
                   AND r.current_occupancy < rt.capacity
                   AND FIND_IN_SET(?, rt.recommended_year_levels) > 0
                   ORDER BY r.building, r.floor
                   LIMIT 6";
$recommendedRooms = fetchAll($recommendedSql, [$user['year_level']]);

// Get student's requests
$requestsSql = "SELECT * FROM allocation_requests 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5";
$requests = fetchAll($requestsSql, [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Campus Room Allocation</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Enhanced UI CSS -->
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
        
        .navbar-custom .navbar-brand {
            color: white !important;
            font-weight: 600;
        }
        
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }
        
        .navbar-custom .nav-link:hover {
            color: white !important;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .icon-purple {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .icon-green {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .icon-orange {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .room-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .badge-custom {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            color: white;
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
                        <a class="nav-link active" href="dashboard.php">
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
    
    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-hand-wave"></i> Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                    <p class="mb-0">Student ID: <?php echo htmlspecialchars($user['student_id']); ?> | Year <?php echo $user['year_level']; ?> - <?php echo htmlspecialchars($user['program']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <i class="fas fa-user-graduate" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon icon-purple">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h5>Current Room</h5>
                    <h3><?php echo $currentAllocation ? htmlspecialchars($currentAllocation['room_number']) : 'Not Assigned'; ?></h3>
                    <p class="text-muted mb-0">
                        <?php echo $currentAllocation ? htmlspecialchars($currentAllocation['building']) : 'No room allocated yet'; ?>
                    </p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon icon-green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5>Allocation Status</h5>
                    <h3><?php echo $currentAllocation ? ucfirst($currentAllocation['status']) : 'Pending'; ?></h3>
                    <p class="text-muted mb-0">
                        <?php echo $currentAllocation ? 'Active allocation' : 'Awaiting allocation'; ?>
                    </p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon icon-orange">
                        <i class="fas fa-bed"></i>
                    </div>
                    <h5>Room Type</h5>
                    <h3><?php 
                        if ($user['year_level'] == 1) {
                            echo 'Common Room';
                        } elseif (in_array($user['year_level'], [2, 3])) {
                            echo 'Double Room';
                        } else {
                            echo 'Single Room';
                        }
                    ?></h3>
                    <p class="text-muted mb-0">Based on your year level</p>
                </div>
            </div>
        </div>
        
        <!-- Current Room Details -->
        <?php if ($currentAllocation): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Your Current Room Allocation</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($currentAllocation['room_number']); ?></p>
                                <p><strong>Building:</strong> <?php echo htmlspecialchars($currentAllocation['building']); ?></p>
                                <p><strong>Floor:</strong> <?php echo $currentAllocation['floor']; ?></p>
                                <p><strong>Room Type:</strong> <?php echo htmlspecialchars($currentAllocation['type_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Capacity:</strong> <?php echo $currentAllocation['capacity']; ?> person(s)</p>
                                <p><strong>Allocation Date:</strong> <?php echo date('F d, Y', strtotime($currentAllocation['allocation_date'])); ?></p>
                                <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($currentAllocation['academic_year']); ?></p>
                                <p><strong>Semester:</strong> <?php echo $currentAllocation['semester']; ?></p>
                            </div>
                        </div>
                        <?php if ($currentAllocation['status'] === 'confirmed'): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> Your room has been confirmed. Please proceed to check-in at the housing office.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recommended Rooms -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h4 class="mb-3"><i class="fas fa-star"></i> Recommended Rooms for You</h4>
                <?php if (empty($recommendedRooms)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No available rooms at the moment. Please check back later.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($recommendedRooms as $room): ?>
                        <div class="col-md-6 mb-3">
                            <div class="room-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($room['room_number']); ?></h5>
                                    <span class="badge bg-success badge-custom">Available</span>
                                </div>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($room['building']); ?> - Floor <?php echo $room['floor']; ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Type:</strong> <?php echo htmlspecialchars($room['type_name']); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Capacity:</strong> <?php echo $room['capacity']; ?> person(s) 
                                    <span class="text-success">(<?php echo $room['available_spaces']; ?> spaces available)</span>
                                </p>
                                <?php if ($room['amenities']): ?>
                                <p class="mb-2">
                                    <strong>Amenities:</strong> <?php echo htmlspecialchars($room['amenities']); ?>
                                </p>
                                <?php endif; ?>
                                <?php if (!$currentAllocation): ?>
                                <button class="btn btn-custom btn-sm mt-2" onclick="requestRoom(<?php echo $room['room_id']; ?>)">
                                    <i class="fas fa-paper-plane"></i> Request This Room
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Requests -->
        <?php if (!empty($requests)): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <h4 class="mb-3"><i class="fas fa-history"></i> Recent Requests</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Preferred Building</th>
                                        <th>Status</th>
                                        <th>Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['preferred_building'] ?? 'Any'); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger'
                                            ];
                                            $class = $statusClass[$request['request_status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($request['request_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $request['admin_response'] ? htmlspecialchars($request['admin_response']) : '-'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Enhanced UI JavaScript -->
    <script src="../../assets/js/toast.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add entrance animations
            document.body.classList.add('fade-in');
            
            // Animate stats cards
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.classList.add('scale-in');
                }, index * 100);
            });
            
            // Animate room cards
            const roomCards = document.querySelectorAll('.room-card');
            roomCards.forEach((card, index) => {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.classList.add('slide-in-up');
                }, 300 + (index * 100));
            });
            
            // Show success message if just logged in
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('login') === 'success') {
                toast.success('Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!');
                // Remove the parameter from URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
        
        function requestRoom(roomId) {
            if (confirm('Do you want to request this room?')) {
                showLoading('Processing your request...');
                
                // Simulate API call (in production, use AJAX)
                setTimeout(() => {
                    hideLoading();
                    toast.success('Room request submitted successfully! An admin will review it soon.');
                }, 1500);
            }
        }
        
        // Add hover effect enhancement
        document.querySelectorAll('.room-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            });
        });
    </script>
</body>
</html>