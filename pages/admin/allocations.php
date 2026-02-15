<?php
/**
 * Admin Room Allocation Management
 * Campus Room Allocation System
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

requireAdmin();

$user = getCurrentUser();
$error = '';
$success = '';

// Handle new allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    $student_id = (int)$_POST['student_id'];
    $room_id = (int)$_POST['room_id'];
    $academic_year = sanitize($_POST['academic_year']);
    $semester = sanitize($_POST['semester']);
    
    try {
        // Check if student already has allocation
        $checkSql = "SELECT allocation_id FROM allocations 
                     WHERE user_id = ? AND academic_year = ? AND semester = ? 
                     AND status IN ('pending', 'confirmed', 'checked_in')";
        $existing = fetchOne($checkSql, [$student_id, $academic_year, $semester]);
        
        if ($existing) {
            $error = 'Student already has an allocation for this period.';
        } else {
            // Check room availability
            $roomSql = "SELECT r.*, rt.capacity FROM rooms r 
                       INNER JOIN room_types rt ON r.type_id = rt.type_id 
                       WHERE r.room_id = ?";
            $room = fetchOne($roomSql, [$room_id]);
            
            if (!$room) {
                $error = 'Room not found.';
            } elseif ($room['current_occupancy'] >= $room['capacity']) {
                $error = 'Room is full.';
            } else {
                // Create allocation
                beginTransaction();
                
                $insertSql = "INSERT INTO allocations (user_id, room_id, allocation_date, 
                             academic_year, semester, status, allocated_by) 
                             VALUES (?, ?, CURDATE(), ?, ?, 'confirmed', ?)";
                executeQuery($insertSql, [$student_id, $room_id, $academic_year, $semester, $user['user_id']]);
                
                // Update room occupancy
                executeQuery("UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE room_id = ?", [$room_id]);
                
                // Update room status if full
                if (($room['current_occupancy'] + 1) >= $room['capacity']) {
                    executeQuery("UPDATE rooms SET status = 'full' WHERE room_id = ?", [$room_id]);
                }
                
                commit();
                
                $success = 'Room allocated successfully!';
                
                // Log activity
                $logSql = "INSERT INTO activity_logs (user_id, action, table_name, description) 
                          VALUES (?, 'allocate', 'allocations', 'Allocated room to student')";
                executeQuery($logSql, [$user['user_id']]);
            }
        }
    } catch (Exception $e) {
        rollback();
        $error = 'Error creating allocation: ' . $e->getMessage();
    }
}

// Handle check-in
if (isset($_GET['checkin']) && is_numeric($_GET['checkin'])) {
    $allocation_id = (int)$_GET['checkin'];
    try {
        executeQuery("UPDATE allocations SET status = 'checked_in', check_in_date = CURDATE() WHERE allocation_id = ?", [$allocation_id]);
        $success = 'Student checked in successfully!';
    } catch (Exception $e) {
        $error = 'Error checking in: ' . $e->getMessage();
    }
}

// Handle check-out
if (isset($_GET['checkout']) && is_numeric($_GET['checkout'])) {
    $allocation_id = (int)$_GET['checkout'];
    try {
        // Get allocation details
        $alloc = fetchOne("SELECT room_id FROM allocations WHERE allocation_id = ?", [$allocation_id]);
        
        if ($alloc) {
            beginTransaction();
            
            // Update allocation
            executeQuery("UPDATE allocations SET status = 'checked_out', check_out_date = CURDATE() WHERE allocation_id = ?", [$allocation_id]);
            
            // Decrease room occupancy
            executeQuery("UPDATE rooms SET current_occupancy = GREATEST(current_occupancy - 1, 0) WHERE room_id = ?", [$alloc['room_id']]);
            
            // Update room status to available if not full
            executeQuery("UPDATE rooms r 
                         INNER JOIN room_types rt ON r.type_id = rt.type_id 
                         SET r.status = 'available' 
                         WHERE r.room_id = ? AND r.current_occupancy < rt.capacity", [$alloc['room_id']]);
            
            commit();
            $success = 'Student checked out successfully!';
        }
    } catch (Exception $e) {
        rollback();
        $error = 'Error checking out: ' . $e->getMessage();
    }
}

// Handle cancel allocation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $allocation_id = (int)$_GET['cancel'];
    try {
        $alloc = fetchOne("SELECT room_id FROM allocations WHERE allocation_id = ?", [$allocation_id]);
        
        if ($alloc) {
            beginTransaction();
            
            executeQuery("UPDATE allocations SET status = 'cancelled' WHERE allocation_id = ?", [$allocation_id]);
            executeQuery("UPDATE rooms SET current_occupancy = GREATEST(current_occupancy - 1, 0) WHERE room_id = ?", [$alloc['room_id']]);
            executeQuery("UPDATE rooms r 
                         INNER JOIN room_types rt ON r.type_id = rt.type_id 
                         SET r.status = 'available' 
                         WHERE r.room_id = ? AND r.current_occupancy < rt.capacity", [$alloc['room_id']]);
            
            commit();
            $success = 'Allocation cancelled successfully!';
        }
    } catch (Exception $e) {
        rollback();
        $error = 'Error cancelling: ' . $e->getMessage();
    }
}

// Get all allocations with details
$allocationsSql = "SELECT a.*, 
                   u.student_id, CONCAT(u.first_name, ' ', u.last_name) as student_name, u.year_level,
                   r.room_number, r.building, r.floor, rt.type_name
                   FROM allocations a
                   INNER JOIN users u ON a.user_id = u.user_id
                   INNER JOIN rooms r ON a.room_id = r.room_id
                   INNER JOIN room_types rt ON r.type_id = rt.type_id
                   ORDER BY a.created_at DESC";
$allocations = fetchAll($allocationsSql);

// Get students without allocations
$unallocatedSql = "SELECT u.* FROM users u
                   WHERE u.user_role = 'student' AND u.is_active = 1
                   AND NOT EXISTS (
                       SELECT 1 FROM allocations a 
                       WHERE a.user_id = u.user_id 
                       AND a.status IN ('confirmed', 'checked_in')
                   )
                   ORDER BY u.year_level, u.last_name";
$unallocatedStudents = fetchAll($unallocatedSql);

// Get available rooms
$availableRoomsSql = "SELECT r.*, rt.type_name, rt.capacity, 
                      (rt.capacity - r.current_occupancy) as spaces_available
                      FROM rooms r
                      INNER JOIN room_types rt ON r.type_id = rt.type_id
                      WHERE r.status = 'available' AND r.current_occupancy < rt.capacity
                      ORDER BY r.building, r.floor";
$availableRooms = fetchAll($availableRoomsSql);

// Statistics
$totalAllocations = count($allocations);
$activeAllocations = count(array_filter($allocations, fn($a) => in_array($a['status'], ['confirmed', 'checked_in'])));
$pendingCheckout = count(array_filter($allocations, fn($a) => $a['status'] === 'checked_in'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation Management - Admin</title>
    
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
        .stats-mini {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .modal-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-shield"></i> Admin Panel
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
                        <a class="nav-link" href="rooms.php">
                            <i class="fas fa-door-open"></i> Rooms
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-users"></i> Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="allocations.php">
                            <i class="fas fa-clipboard-list"></i> Allocations
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
    
    <div class="container-fluid mt-4">
        <h2 class="mb-3"><i class="fas fa-clipboard-list"></i> Room Allocation Management</h2>
        
        <!-- Stats -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Total Allocations</h6>
                    <h3 class="mb-0"><?php echo $totalAllocations; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Active</h6>
                    <h3 class="mb-0 text-success"><?php echo $activeAllocations; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Unallocated Students</h6>
                    <h3 class="mb-0 text-warning"><?php echo count($unallocatedStudents); ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                <i class="fas fa-plus"></i> New Allocation
            </button>
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#unallocatedModal">
                <i class="fas fa-users"></i> View Unallocated Students (<?php echo count($unallocatedStudents); ?>)
            </button>
        </div>
        
        <!-- Allocations Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Year</th>
                                <th>Room</th>
                                <th>Building</th>
                                <th>Type</th>
                                <th>Academic Year</th>
                                <th>Allocation Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allocations)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No allocations found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allocations as $alloc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alloc['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($alloc['student_name']); ?></td>
                                    <td><span class="badge bg-info">Year <?php echo $alloc['year_level']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($alloc['room_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($alloc['building']); ?> - Floor <?php echo $alloc['floor']; ?></td>
                                    <td><?php echo htmlspecialchars($alloc['type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($alloc['academic_year']); ?> (Sem <?php echo $alloc['semester']; ?>)</td>
                                    <td><?php echo date('M d, Y', strtotime($alloc['allocation_date'])); ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'checked_in' => 'success',
                                            'checked_out' => 'secondary',
                                            'cancelled' => 'danger'
                                        ];
                                        $color = $statusColors[$alloc['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $alloc['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($alloc['status'] === 'confirmed'): ?>
                                            <a href="?checkin=<?php echo $alloc['allocation_id']; ?>" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('Check in student?')">
                                                <i class="fas fa-sign-in-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($alloc['status'] === 'checked_in'): ?>
                                            <a href="?checkout=<?php echo $alloc['allocation_id']; ?>" 
                                               class="btn btn-sm btn-warning"
                                               onclick="return confirm('Check out student?')">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($alloc['status'], ['confirmed', 'checked_in'])): ?>
                                            <a href="?cancel=<?php echo $alloc['allocation_id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Cancel this allocation?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Allocation Modal -->
    <div class="modal fade" id="allocateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Allocation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student *</label>
                            <select class="form-select" name="student_id" id="studentSelect" required onchange="updateRecommendedRooms()">
                                <option value="">Select Student</option>
                                <?php foreach ($unallocatedStudents as $student): ?>
                                    <option value="<?php echo $student['user_id']; ?>" data-year="<?php echo $student['year_level']; ?>">
                                        <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . ' (Year ' . $student['year_level'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Available Room *</label>
                            <select class="form-select" name="room_id" id="roomSelect" required>
                                <option value="">Select Room</option>
                                <?php foreach ($availableRooms as $room): ?>
                                    <option value="<?php echo $room['room_id']; ?>" data-type="<?php echo $room['type_name']; ?>">
                                        <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['building'] . ' - ' . $room['type_name'] . ' (' . $room['spaces_available'] . ' spaces)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Academic Year *</label>
                                <input type="text" class="form-control" name="academic_year" value="2024/2025" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Semester *</label>
                                <select class="form-select" name="semester" required>
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="allocate" class="btn btn-primary">
                            <i class="fas fa-check"></i> Allocate Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Unallocated Students Modal -->
    <div class="modal fade" id="unallocatedModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Unallocated Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Year</th>
                                    <th>Program</th>
                                    <th>Recommended Room Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unallocatedStudents as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td>Year <?php echo $student['year_level']; ?></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                    <td>
                                        <?php
                                        if ($student['year_level'] == 1) echo 'Common Room';
                                        elseif (in_array($student['year_level'], [2, 3])) echo 'Double Room';
                                        else echo 'Single Room';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateRecommendedRooms() {
            const studentSelect = document.getElementById('studentSelect');
            const roomSelect = document.getElementById('roomSelect');
            const selectedOption = studentSelect.options[studentSelect.selectedIndex];
            const yearLevel = selectedOption.getAttribute('data-year');
            
            // Filter rooms based on year level
            for (let option of roomSelect.options) {
                if (option.value === '') continue;
                
                const roomType = option.getAttribute('data-type');
                let isRecommended = false;
                
                if (yearLevel == 1 && roomType.includes('Common')) isRecommended = true;
                else if ((yearLevel == 2 || yearLevel == 3) && roomType.includes('Double')) isRecommended = true;
                else if ((yearLevel == 4 || yearLevel == 5) && roomType.includes('Single')) isRecommended = true;
                
                if (isRecommended) {
                    option.style.fontWeight = 'bold';
                    option.style.backgroundColor = '#d1e7dd';
                } else {
                    option.style.fontWeight = 'normal';
                    option.style.backgroundColor = '';
                }
            }
        }
    </script>
</body>
</html>