<?php
/**
 * Admin Dashboard
 * Campus Room Allocation System
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Require admin login
requireAdmin();

$user = getCurrentUser();

// Get statistics
$totalStudents = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_role = 'student'")['count'];
$totalRooms = fetchOne("SELECT COUNT(*) as count FROM rooms")['count'];
$availableRooms = fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'available' AND current_occupancy < capacity")['count'];
$totalAllocations = fetchOne("SELECT COUNT(*) as count FROM allocations WHERE status IN ('confirmed', 'checked_in')")['count'];
$pendingRequests = fetchOne("SELECT COUNT(*) as count FROM allocation_requests WHERE request_status = 'pending'")['count'];

// Get recent allocations
$recentAllocations = fetchAll("
    SELECT a.*, u.student_id, CONCAT(u.first_name, ' ', u.last_name) as student_name,
           r.room_number, r.building
    FROM allocations a
    INNER JOIN users u ON a.user_id = u.user_id
    INNER JOIN rooms r ON a.room_id = r.room_id
    ORDER BY a.created_at DESC
    LIMIT 5
");

// Get room occupancy stats
$roomStats = fetchAll("
    SELECT rt.type_name, 
           COUNT(r.room_id) as total_rooms,
           SUM(r.current_occupancy) as total_occupancy,
           SUM(rt.capacity) as total_capacity
    FROM room_types rt
    LEFT JOIN rooms r ON rt.type_id = r.type_id
    GROUP BY rt.type_id, rt.type_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Campus Room Allocation</title>
    
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
        
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white !important;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .icon-blue {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        
        .icon-green {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .icon-purple {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .icon-orange {
            background: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
        }
        
        .icon-red {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-user-shield"></i> Admin Panel
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
        <!-- Welcome Section -->
        <div class="welcome-card">
            <h2><i class="fas fa-chart-line"></i> Admin Dashboard</h2>
            <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Students</h6>
                            <h2 class="mb-0"><?php echo $totalStudents; ?></h2>
                        </div>
                        <div class="stats-icon icon-blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Rooms</h6>
                            <h2 class="mb-0"><?php echo $totalRooms; ?></h2>
                        </div>
                        <div class="stats-icon icon-purple">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Available Rooms</h6>
                            <h2 class="mb-0"><?php echo $availableRooms; ?></h2>
                        </div>
                        <div class="stats-icon icon-green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Active Allocations</h6>
                            <h2 class="mb-0"><?php echo $totalAllocations; ?></h2>
                        </div>
                        <div class="stats-icon icon-orange">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Visual Insights Row -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Room Type Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="roomTypeChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Allocation Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="allocationStatusChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Students</h6>
                            <h2 class="mb-0"><?php echo $totalStudents; ?></h2>
                        </div>
                        <div class="stats-icon icon-blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Rooms</h6>
                            <h2 class="mb-0"><?php echo $totalRooms; ?></h2>
                        </div>
                        <div class="stats-icon icon-purple">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Available Rooms</h6>
                            <h2 class="mb-0"><?php echo $availableRooms; ?></h2>
                        </div>
                        <div class="stats-icon icon-green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Active Allocations</h6>
                            <h2 class="mb-0"><?php echo $totalAllocations; ?></h2>
                        </div>
                        <div class="stats-icon icon-orange">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Pending Requests Card -->
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pending Requests</h6>
                            <h2 class="mb-0"><?php echo $pendingRequests; ?></h2>
                        </div>
                        <div class="stats-icon icon-red">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Room Occupancy Overview -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Room Occupancy Overview</h5>
                    </div>
                    <div class="card-body">
                        <!-- Chart Canvas -->
                        <canvas id="occupancyChart" style="max-height: 300px;"></canvas>
                        
                        <hr class="my-4">
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Room Type</th>
                                        <th>Total Rooms</th>
                                        <th>Current Occupancy</th>
                                        <th>Total Capacity</th>
                                        <th>Occupancy Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roomStats as $stat): ?>
                                    <?php 
                                        $occupancyRate = $stat['total_capacity'] > 0 
                                            ? round(($stat['total_occupancy'] / $stat['total_capacity']) * 100, 1) 
                                            : 0;
                                        $progressClass = $occupancyRate < 50 ? 'bg-success' : ($occupancyRate < 80 ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($stat['type_name']); ?></strong></td>
                                        <td><?php echo $stat['total_rooms']; ?></td>
                                        <td><?php echo $stat['total_occupancy']; ?></td>
                                        <td><?php echo $stat['total_capacity']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?php echo $progressClass; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $occupancyRate; ?>%"
                                                     aria-valuenow="<?php echo $occupancyRate; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo $occupancyRate; ?>%
                                                </div>
                                            </div>
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
        
        <!-- Recent Allocations -->
        <div class="row mt-4 mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Allocations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentAllocations)): ?>
                            <p class="text-muted">No allocations yet.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Room</th>
                                        <th>Building</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAllocations as $allocation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($allocation['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($allocation['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($allocation['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($allocation['building']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($allocation['allocation_date'])); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'checked_in' => 'success',
                                                'checked_out' => 'secondary',
                                                'cancelled' => 'danger'
                                            ];
                                            $class = $statusClass[$allocation['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($allocation['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Enhanced UI JavaScript -->
    <script src="../../assets/js/toast.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
        // Add entrance animation
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Initialize Occupancy Chart
            const ctx = document.getElementById('occupancyChart');
            if (ctx) {
                const roomData = <?php echo json_encode($roomStats); ?>;
                
                const labels = roomData.map(stat => stat.type_name);
                const occupancy = roomData.map(stat => parseInt(stat.total_occupancy));
                const capacity = roomData.map(stat => parseInt(stat.total_capacity));
                const available = roomData.map((stat, i) => capacity[i] - occupancy[i]);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Occupied',
                                data: occupancy,
                                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                                borderColor: 'rgba(102, 126, 234, 1)',
                                borderWidth: 2,
                                borderRadius: 8
                            },
                            {
                                label: 'Available',
                                data: available,
                                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 2,
                                borderRadius: 8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y;
                                        const total = capacity[context.dataIndex];
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    },
                                    callback: function(value) {
                                        return value + ' beds';
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1500,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
            
            // Room Type Distribution Pie Chart
            const pieCtx = document.getElementById('roomTypeChart');
            if (pieCtx) {
                const roomData = <?php echo json_encode($roomStats); ?>;
                
                new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: roomData.map(stat => stat.type_name),
                        datasets: [{
                            data: roomData.map(stat => parseInt(stat.total_rooms)),
                            backgroundColor: [
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(118, 75, 162, 0.8)',
                                'rgba(255, 193, 7, 0.8)'
                            ],
                            borderColor: [
                                'rgba(102, 126, 234, 1)',
                                'rgba(118, 75, 162, 1)',
                                'rgba(255, 193, 7, 1)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: {
                                        size: 12
                                    },
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value} rooms (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true,
                            duration: 2000
                        }
                    }
                });
            }
            
            // Allocation Status Chart
            const statusCtx = document.getElementById('allocationStatusChart');
            if (statusCtx) {
                // Get allocation status counts
                <?php
                $statusCounts = [
                    'pending' => 0,
                    'confirmed' => 0,
                    'checked_in' => 0,
                    'checked_out' => 0,
                    'cancelled' => 0
                ];
                
                $allAllocations = fetchAll("SELECT status FROM allocations");
                foreach ($allAllocations as $alloc) {
                    if (isset($statusCounts[$alloc['status']])) {
                        $statusCounts[$alloc['status']]++;
                    }
                }
                ?>
                
                const statusData = <?php echo json_encode($statusCounts); ?>;
                
                new Chart(statusCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled'],
                        datasets: [{
                            label: 'Number of Allocations',
                            data: Object.values(statusData),
                            backgroundColor: [
                                'rgba(255, 193, 7, 0.8)',
                                'rgba(13, 110, 253, 0.8)',
                                'rgba(25, 135, 84, 0.8)',
                                'rgba(108, 117, 125, 0.8)',
                                'rgba(220, 53, 69, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 193, 7, 1)',
                                'rgba(13, 110, 253, 1)',
                                'rgba(25, 135, 84, 1)',
                                'rgba(108, 117, 125, 1)',
                                'rgba(220, 53, 69, 1)'
                            ],
                            borderWidth: 2,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' allocation(s)';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        animation: {
                            duration: 1500,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
            
            // Auto-refresh stats every 30 seconds (optional)
            // setInterval(() => {
            //     location.reload();
            // }, 30000);
        });
    </script>
</body>
</html>