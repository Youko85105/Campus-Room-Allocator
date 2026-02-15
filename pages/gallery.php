<?php
/**
 * Campus Gallery
 * View photos of rooms, buildings, and facilities
 */

require_once '../config/database.php';
require_once '../includes/image-placeholder.php';

// Get all room types
$roomTypesSql = "SELECT * FROM room_types ORDER BY type_name";
$roomTypes = fetchAll($roomTypesSql);

// Get all buildings
$buildingsSql = "SELECT DISTINCT building FROM rooms ORDER BY building";
$buildings = fetchAll($buildingsSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Gallery - Room Allocation</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    
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
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            cursor: pointer;
        }
        
        .gallery-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px;
            color: white;
        }
        
        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.9);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        /* Lightbox styles */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            align-items: center;
            justify-content: center;
        }
        
        .lightbox.active {
            display: flex;
        }
        
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 40px;
            font-size: 3rem;
            color: white;
            cursor: pointer;
            z-index: 10000;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-building"></i> Campus Room Allocation
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="gallery.php">
                            <i class="fas fa-images"></i> Gallery
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-3">
                <i class="fas fa-images"></i> Campus Gallery
            </h1>
            <p class="lead">Explore our rooms, buildings, and facilities</p>
        </div>
    </div>
    
    <div class="container mb-5">
        <!-- Filter Tabs -->
        <ul class="nav nav-pills justify-content-center mb-4" id="galleryTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="pill" href="#all">
                    <i class="fas fa-th"></i> All
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#rooms">
                    <i class="fas fa-bed"></i> Rooms
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#buildings">
                    <i class="fas fa-building"></i> Buildings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#facilities">
                    <i class="fas fa-dumbbell"></i> Facilities
                </a>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- All Tab -->
            <div class="tab-pane fade show active" id="all">
                <div class="row">
                    <!-- Room Types -->
                    <?php foreach ($roomTypes as $index => $type): ?>
                    <div class="col-md-4 fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="gallery-item" onclick="openLightbox(this)">
                            <img src="<?php echo getRoomTypeImage($type['type_name']); ?>" 
                                 alt="<?php echo htmlspecialchars($type['type_name']); ?>">
                            <span class="category-badge text-primary">Room Type</span>
                            <div class="gallery-overlay">
                                <h5><?php echo htmlspecialchars($type['type_name']); ?></h5>
                                <p class="mb-0">Capacity: <?php echo $type['capacity']; ?> person(s)</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Buildings -->
                    <?php foreach ($buildings as $index => $building): ?>
                    <div class="col-md-4 fade-in" style="animation-delay: <?php echo ($index + count($roomTypes)) * 0.1; ?>s;">
                        <div class="gallery-item" onclick="openLightbox(this)">
                            <img src="<?php echo getBuildingPlaceholder($building['building']); ?>" 
                                 alt="<?php echo htmlspecialchars($building['building']); ?>">
                            <span class="category-badge text-success">Building</span>
                            <div class="gallery-overlay">
                                <h5><?php echo htmlspecialchars($building['building']); ?></h5>
                                <p class="mb-0">Student Housing</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Facilities (Placeholders) -->
                    <?php
                    $facilities = [
                        ['name' => 'Study Lounge', 'icon' => '📚', 'color' => '#4facfe'],
                        ['name' => 'Recreation Area', 'icon' => '🎮', 'color' => '#43e97b'],
                        ['name' => 'Laundry Room', 'icon' => '🧺', 'color' => '#fa709a']
                    ];
                    foreach ($facilities as $index => $facility):
                    ?>
                    <div class="col-md-4 fade-in" style="animation-delay: <?php echo ($index + count($roomTypes) + count($buildings)) * 0.1; ?>s;">
                        <div class="gallery-item" onclick="openLightbox(this)">
                            <img src="data:image/svg+xml;base64,<?php echo base64_encode(generatePlaceholder(400, 300, $facility['icon'] . ' ' . $facility['name'], $facility['color'])); ?>" 
                                 alt="<?php echo $facility['name']; ?>">
                            <span class="category-badge text-warning">Facility</span>
                            <div class="gallery-overlay">
                                <h5><?php echo $facility['name']; ?></h5>
                                <p class="mb-0">Common Facility</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Rooms Tab -->
            <div class="tab-pane fade" id="rooms">
                <div class="row">
                    <?php foreach ($roomTypes as $type): ?>
                    <div class="col-md-4">
                        <div class="gallery-item" onclick="openLightbox(this)">
                            <img src="<?php echo getRoomTypeImage($type['type_name']); ?>" 
                                 alt="<?php echo htmlspecialchars($type['type_name']); ?>">
                            <div class="gallery-overlay">
                                <h5><?php echo htmlspecialchars($type['type_name']); ?></h5>
                                <p class="mb-0"><?php echo htmlspecialchars($type['description']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Buildings Tab -->
            <div class="tab-pane fade" id="buildings">
                <div class="row">
                    <?php foreach ($buildings as $building): ?>
                    <div class="col-md-6">
                        <div class="gallery-item" onclick="openLightbox(this)">
                            <img src="<?php echo getBuildingPlaceholder($building['building']); ?>" 
                                 alt="<?php echo htmlspecialchars($building['building']); ?>">
                            <div class="gallery-overlay">
                                <h5><?php echo htmlspecialchars($building['building']); ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Facilities Tab -->
            <div class="tab-pane fade" id="facilities">
                <div class="row">
                    <?php foreach ($facilities as $facility): ?>
                    <div class="col-md-4">
                        <div class="gallery-item" onclick="openLightbox(this)">
                            <img src="data:image/svg+xml;base64,<?php echo base64_encode(generatePlaceholder(400, 300, $facility['icon'] . ' ' . $facility['name'], $facility['color'])); ?>" 
                                 alt="<?php echo $facility['name']; ?>">
                            <div class="gallery-overlay">
                                <h5><?php echo $facility['name']; ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lightbox -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img src="" alt="" id="lightboxImage">
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function openLightbox(element) {
            const img = element.querySelector('img');
            const lightbox = document.getElementById('lightbox');
            const lightboxImage = document.getElementById('lightboxImage');
            
            lightboxImage.src = img.src;
            lightboxImage.alt = img.alt;
            lightbox.classList.add('active');
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
    </script>
</body>
</html>