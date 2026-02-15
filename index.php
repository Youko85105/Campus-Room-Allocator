<?php
/**
 * Landing Page
 * Campus Room Allocation System
 */

require_once 'config/database.php';
require_once 'auth/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: pages/admin/dashboard.php');
    } else {
        header('Location: pages/student/dashboard.php');
    }
    exit();
}

// Get some statistics for display
$totalRooms = fetchOne("SELECT COUNT(*) as count FROM rooms")['count'];
$availableRooms = fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")['count'];
$totalStudents = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_role = 'student'")['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Room Allocation System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .hero-icon {
            font-size: 6rem;
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
        }
        
        .btn-custom {
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            color: #667eea;
        }
        
        .stats-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-top: 50px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <div class="hero-icon">
                <i class="fas fa-building"></i>
            </div>
            <h1 class="display-3 fw-bold mb-4">Campus Room Allocation System</h1>
            <p class="lead mb-5">Streamlined room management for students and administrators</p>
            
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="pages/gallery.php" class="btn btn-custom">
                    <i class="fas fa-images"></i> View Gallery
                </a>
                <a href="auth/login.php" class="btn btn-custom">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="auth/register.php" class="btn btn-custom">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>
            
            <!-- Stats -->
            <div class="stats-section">
                <div class="row">
                    <div class="col-md-4 stat-item">
                        <div class="stat-number"><?php echo $totalRooms; ?></div>
                        <div>Total Rooms</div>
                    </div>
                    <div class="col-md-4 stat-item">
                        <div class="stat-number"><?php echo $availableRooms; ?></div>
                        <div>Available Rooms</div>
                    </div>
                    <div class="col-md-4 stat-item">
                        <div class="stat-number"><?php echo $totalStudents; ?></div>
                        <div>Registered Students</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">System Features</h2>
                <p class="lead text-muted">Everything you need for efficient room management</p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4 class="text-center mb-3">Smart Allocation</h4>
                        <p class="text-center text-muted">
                            Automatic room suggestions based on year level. First years get common rooms, 
                            seniors get single rooms.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 class="text-center mb-3">Fully Responsive</h4>
                        <p class="text-center text-muted">
                            Access from any device - desktop, tablet, or mobile. 
                            Beautiful design that works everywhere.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="text-center mb-3">Secure & Safe</h4>
                        <p class="text-center text-muted">
                            Encrypted passwords, SQL injection protection, and secure session management 
                            keep your data safe.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="text-center mb-3">Real-time Analytics</h4>
                        <p class="text-center text-muted">
                            Track occupancy rates, view allocation statistics, and monitor 
                            room availability in real-time.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="text-center mb-3">Role-Based Access</h4>
                        <p class="text-center text-muted">
                            Separate dashboards for students and administrators with 
                            appropriate permissions.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h4 class="text-center mb-3">Complete CRUD</h4>
                        <p class="text-center text-muted">
                            Full Create, Read, Update, Delete functionality for rooms, 
                            students, and allocations.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p class="mb-0">
                <i class="fas fa-building"></i> Campus Room Allocation System &copy; 2024
            </p>
            <p class="mb-0">
                <small>Built with PHP, MySQL, Bootstrap & JavaScript</small>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>