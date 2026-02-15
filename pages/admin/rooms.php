<?php
/**
 * Admin Room Management (CRUD)
 * Campus Room Allocation System
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Require admin login
requireAdmin();

$user = getCurrentUser();
$error = '';
$success = '';

// Handle DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $room_id = (int)$_GET['delete'];
    
    try {
        // Check if room has active allocations
        $checkSql = "SELECT COUNT(*) as count FROM allocations 
                     WHERE room_id = ? AND status IN ('confirmed', 'checked_in')";
        $hasAllocations = fetchOne($checkSql, [$room_id])['count'];
        
        if ($hasAllocations > 0) {
            $error = 'Cannot delete room with active allocations.';
        } else {
            executeQuery("DELETE FROM rooms WHERE room_id = ?", [$room_id]);
            $success = 'Room deleted successfully!';
            
            // Log activity
            $logSql = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) 
                      VALUES (?, 'delete', 'rooms', ?, 'Room deleted')";
            executeQuery($logSql, [$user['user_id'], $room_id]);
        }
    } catch (Exception $e) {
        $error = 'Error deleting room: ' . $e->getMessage();
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
    $room_number = sanitize($_POST['room_number'] ?? '');
    $building = sanitize($_POST['building'] ?? '');
    $floor = (int)($_POST['floor'] ?? 0);
    $type_id = (int)($_POST['type_id'] ?? 0);
    $capacity = (int)($_POST['capacity'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'available');
    $amenities = sanitize($_POST['amenities'] ?? '');
    
    if (empty($room_number) || empty($building) || $type_id === 0) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            if ($room_id > 0) {
                // UPDATE
                $sql = "UPDATE rooms 
                       SET room_number = ?, building = ?, floor = ?, type_id = ?, 
                           capacity = ?, status = ?, amenities = ?
                       WHERE room_id = ?";
                executeQuery($sql, [$room_number, $building, $floor, $type_id, $capacity, $status, $amenities, $room_id]);
                $success = 'Room updated successfully!';
                
                // Log activity
                $logSql = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) 
                          VALUES (?, 'update', 'rooms', ?, 'Room updated')";
                executeQuery($logSql, [$user['user_id'], $room_id]);
            } else {
                // INSERT
                $sql = "INSERT INTO rooms (room_number, building, floor, type_id, capacity, status, amenities) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
                executeQuery($sql, [$room_number, $building, $floor, $type_id, $capacity, $status, $amenities]);
                $new_id = getLastInsertId();
                $success = 'Room added successfully!';
                
                // Log activity
                $logSql = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description) 
                          VALUES (?, 'insert', 'rooms', ?, 'New room added')";
                executeQuery($logSql, [$user['user_id'], $new_id]);
            }
        } catch (Exception $e) {
            $error = 'Error saving room: ' . $e->getMessage();
        }
    }
}

// Get room for editing
$editRoom = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editRoom = fetchOne("SELECT * FROM rooms WHERE room_id = ?", [(int)$_GET['edit']]);
}

// Get all rooms with type info
$roomsSql = "SELECT r.*, rt.type_name, rt.capacity as type_capacity
             FROM rooms r
             INNER JOIN room_types rt ON r.type_id = rt.type_id
             ORDER BY r.building, r.floor, r.room_number";
$rooms = fetchAll($roomsSql);

// Get room types for dropdown
$roomTypes = fetchAll("SELECT * FROM room_types ORDER BY type_name");

// Get statistics
$totalRooms = count($rooms);
$availableRooms = count(array_filter($rooms, fn($r) => $r['status'] === 'available'));
$fullRooms = count(array_filter($rooms, fn($r) => $r['current_occupancy'] >= $r['capacity']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .table-actions {
            white-space: nowrap;
        }
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
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
                        <a class="nav-link active" href="rooms.php">
                            <i class="fas fa-door-open"></i> Rooms
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">
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
        <div class="row mb-3">
            <div class="col-md-12">
                <h2><i class="fas fa-door-open"></i> Room Management</h2>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Total Rooms</h6>
                    <h3 class="mb-0"><?php echo $totalRooms; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Available</h6>
                    <h3 class="mb-0 text-success"><?php echo $availableRooms; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-mini">
                    <h6 class="text-muted mb-0">Full</h6>
                    <h3 class="mb-0 text-danger"><?php echo $fullRooms; ?></h3>
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
        
        <!-- Add Room Button -->
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="clearForm()">
                <i class="fas fa-plus"></i> Add New Room
            </button>
            
            <!-- Search Box -->
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text bg-white">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" 
                       class="form-control" 
                       id="roomSearch" 
                       placeholder="Search rooms by number, building, type..."
                       onkeyup="searchRooms()">
            </div>
        </div>
        
        <!-- Rooms Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="roomsTable">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Building</th>
                                <th>Floor</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Occupancy</th>
                                <th>Status</th>
                                <th>Amenities</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="roomsTableBody">
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No rooms found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($room['building']); ?></td>
                                    <td><?php echo $room['floor']; ?></td>
                                    <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                                    <td><?php echo $room['capacity']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $room['current_occupancy'] >= $room['capacity'] ? 'danger' : 'success'; ?>">
                                            <?php echo $room['current_occupancy']; ?> / <?php echo $room['capacity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'available' => 'success',
                                            'full' => 'danger',
                                            'maintenance' => 'warning'
                                        ];
                                        $color = $statusColors[$room['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?> badge-status">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($room['amenities'], 0, 30)) . (strlen($room['amenities']) > 30 ? '...' : ''); ?></small>
                                    </td>
                                    <td class="table-actions">
                                        <button class="btn btn-sm btn-info" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $room['room_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this room?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
    
    <!-- Add/Edit Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-door-open"></i> <span id="modalTitleText">Add New Room</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="roomForm">
                    <div class="modal-body">
                        <input type="hidden" name="room_id" id="room_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Room Number *</label>
                            <input type="text" class="form-control" name="room_number" id="room_number" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Building *</label>
                                <input type="text" class="form-control" name="building" id="building" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Floor *</label>
                                <input type="number" class="form-control" name="floor" id="floor" min="0" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Type *</label>
                                <select class="form-select" name="type_id" id="type_id" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($roomTypes as $type): ?>
                                        <option value="<?php echo $type['type_id']; ?>">
                                            <?php echo htmlspecialchars($type['type_name']); ?> (Cap: <?php echo $type['capacity']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacity *</label>
                                <input type="number" class="form-control" name="capacity" id="capacity" min="1" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="available">Available</option>
                                <option value="full">Full</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amenities</label>
                            <textarea class="form-control" name="amenities" id="amenities" rows="3" 
                                      placeholder="WiFi, Private Bathroom, Study Desk..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced UI JavaScript -->
    <script src="../../assets/js/toast.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
        // Show toasts for PHP messages
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($error): ?>
                toast.error('<?php echo addslashes($error); ?>');
                document.querySelector('.alert-danger')?.remove();
            <?php endif; ?>
            
            <?php if ($success): ?>
                toast.success('<?php echo addslashes($success); ?>');
                document.querySelector('.alert-success')?.remove();
            <?php endif; ?>
            
            // Add entrance animations
            document.querySelectorAll('.stats-mini').forEach((card, index) => {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.classList.add('scale-in');
                }, index * 100);
            });
        });
        
        function clearForm() {
            document.getElementById('roomForm').reset();
            document.getElementById('room_id').value = '';
            document.getElementById('modalTitleText').textContent = 'Add New Room';
        }
        
        function editRoom(room) {
            document.getElementById('room_id').value = room.room_id;
            document.getElementById('room_number').value = room.room_number;
            document.getElementById('building').value = room.building;
            document.getElementById('floor').value = room.floor;
            document.getElementById('type_id').value = room.type_id;
            document.getElementById('capacity').value = room.capacity;
            document.getElementById('status').value = room.status;
            document.getElementById('amenities').value = room.amenities || '';
            document.getElementById('modalTitleText').textContent = 'Edit Room';
            
            new bootstrap.Modal(document.getElementById('roomModal')).show();
        }
        
        // Live Search Function
        function searchRooms() {
            const searchInput = document.getElementById('roomSearch');
            const filter = searchInput.value.toLowerCase();
            const table = document.getElementById('roomsTable');
            const rows = table.getElementsByTagName('tr');
            let visibleCount = 0;
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent || row.innerText;
                
                if (text.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = '';
                    row.classList.add('fade-in');
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Show message if no results
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) noResultsRow.remove();
            
            if (visibleCount === 0 && filter !== '') {
                const tbody = document.getElementById('roomsTableBody');
                const newRow = tbody.insertRow();
                newRow.id = 'noResultsRow';
                const cell = newRow.insertCell(0);
                cell.colSpan = 9;
                cell.className = 'text-center text-muted py-4';
                cell.innerHTML = '<i class="fas fa-search fa-2x mb-2"></i><br>No rooms found matching your search.';
            }
        }
        
        // Enhanced delete confirmation
        document.querySelectorAll('a[href*="delete="]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.href;
                
                if (confirm('⚠️ Are you sure you want to delete this room?\n\nThis action cannot be undone.')) {
                    showLoading('Deleting room...');
                    window.location.href = url;
                }
            });
        });
        
        // Form submission with loading
        document.getElementById('roomForm').addEventListener('submit', function() {
            showLoading('Saving room...');
        });
    </script>
</body>
</html>