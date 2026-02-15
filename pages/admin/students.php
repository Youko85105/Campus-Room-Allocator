<?php
/**
 * Admin Student Management
 * Campus Room Allocation System
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

requireAdmin();

$user = getCurrentUser();
$error = '';
$success = '';

// Handle student status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $student_id = (int)$_GET['toggle'];
    try {
        executeQuery("UPDATE users SET is_active = NOT is_active WHERE user_id = ? AND user_role = 'student'", [$student_id]);
        $success = 'Student status updated successfully!';
    } catch (Exception $e) {
        $error = 'Error updating status: ' . $e->getMessage();
    }
}

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : 0;

// Build query
$whereClauses = ["user_role = 'student'"];
$params = [];

if ($search) {
    $whereClauses[] = "(student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR program LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($year_filter > 0) {
    $whereClauses[] = "year_level = ?";
    $params[] = $year_filter;
}

$whereSQL = implode(" AND ", $whereClauses);

$studentsSql = "SELECT u.*, 
                (SELECT COUNT(*) FROM allocations a WHERE a.user_id = u.user_id AND a.status IN ('confirmed', 'checked_in')) as has_room
                FROM users u
                WHERE $whereSQL
                ORDER BY u.created_at DESC";
$students = fetchAll($studentsSql, $params);

// Statistics
$totalStudents = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_role = 'student'")['count'];
$activeStudents = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_role = 'student' AND is_active = 1")['count'];
$studentsWithRooms = fetchOne("SELECT COUNT(DISTINCT user_id) as count FROM allocations WHERE status IN ('confirmed', 'checked_in')")['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Admin</title>
    
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
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                        <a class="nav-link active" href="students.php">
                            <i class="fas fa-users"></i> Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="allocations.php">
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
        <h2 class="mb-3"><i class="fas fa-users"></i> Student Management</h2>
        
        <!-- Stats -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Total Students</h6>
                    <h3 class="mb-0"><?php echo $totalStudents; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Active Students</h6>
                    <h3 class="mb-0 text-success"><?php echo $activeStudents; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">With Rooms</h6>
                    <h3 class="mb-0 text-primary"><?php echo $studentsWithRooms; ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Search & Filter -->
        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Student ID, Name, Email, Program..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year Level</label>
                    <select class="form-select" name="year">
                        <option value="0">All Years</option>
                        <option value="1" <?php echo $year_filter == 1 ? 'selected' : ''; ?>>Year 1</option>
                        <option value="2" <?php echo $year_filter == 2 ? 'selected' : ''; ?>>Year 2</option>
                        <option value="3" <?php echo $year_filter == 3 ? 'selected' : ''; ?>>Year 3</option>
                        <option value="4" <?php echo $year_filter == 4 ? 'selected' : ''; ?>>Year 4</option>
                        <option value="5" <?php echo $year_filter == 5 ? 'selected' : ''; ?>>Year 5</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Students Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Year</th>
                                <th>Program</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Room Status</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No students found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><span class="badge bg-info">Year <?php echo $student['year_level']; ?></span></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($student['phone'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($student['has_room'] > 0): ?>
                                            <span class="badge bg-success">Allocated</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">No Room</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?toggle=<?php echo $student['user_id']; ?>" 
                                           class="btn btn-sm btn-<?php echo $student['is_active'] ? 'warning' : 'success'; ?>"
                                           onclick="return confirm('Toggle student status?')">
                                            <i class="fas fa-<?php echo $student['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <button class="btn btn-sm btn-info" onclick="viewStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
    
    <!-- View Student Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-user"></i> Student Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentDetails">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewStudent(student) {
            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Student ID:</strong> ${student.student_id}</p>
                        <p><strong>Full Name:</strong> ${student.first_name} ${student.last_name}</p>
                        <p><strong>Email:</strong> ${student.email}</p>
                        <p><strong>Phone:</strong> ${student.phone || 'Not provided'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Year Level:</strong> Year ${student.year_level}</p>
                        <p><strong>Program:</strong> ${student.program}</p>
                        <p><strong>Gender:</strong> ${student.gender}</p>
                        <p><strong>Registration Date:</strong> ${new Date(student.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
                <hr>
                <p><strong>Account Status:</strong> 
                    <span class="badge bg-${student.is_active ? 'success' : 'danger'}">
                        ${student.is_active ? 'Active' : 'Inactive'}
                    </span>
                </p>
                <p><strong>Room Status:</strong> 
                    <span class="badge bg-${student.has_room > 0 ? 'success' : 'warning'}">
                        ${student.has_room > 0 ? 'Room Allocated' : 'No Room Assigned'}
                    </span>
                </p>
            `;
            
            document.getElementById('studentDetails').innerHTML = details;
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        }
    </script>
</body>
</html>