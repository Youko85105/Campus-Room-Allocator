<?php
/**
 * Roommate Finder & Matching System
 * Students can create profiles and find compatible roommates
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

requireStudent();

$user = getCurrentUser();
$user_id = $user['user_id'];
$error = '';
$success = '';

// Handle profile creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $sleep_schedule = sanitize($_POST['sleep_schedule'] ?? '');
    $cleanliness = sanitize($_POST['cleanliness'] ?? '');
    $noise_level = sanitize($_POST['noise_level'] ?? '');
    $study_habits = sanitize($_POST['study_habits'] ?? '');
    $interests = sanitize($_POST['interests'] ?? '');
    $about_me = sanitize($_POST['about_me'] ?? '');
    $looking_for = sanitize($_POST['looking_for'] ?? '');
    
    try {
        // Check if profile exists
        $checkSql = "SELECT profile_id FROM roommate_profiles WHERE user_id = ?";
        $existing = fetchOne($checkSql, [$user_id]);
        
        if ($existing) {
            // Update
            $updateSql = "UPDATE roommate_profiles 
                         SET sleep_schedule = ?, cleanliness = ?, noise_level = ?, 
                             study_habits = ?, interests = ?, about_me = ?, looking_for = ?
                         WHERE user_id = ?";
            executeQuery($updateSql, [$sleep_schedule, $cleanliness, $noise_level, 
                                     $study_habits, $interests, $about_me, $looking_for, $user_id]);
            $success = 'Profile updated successfully!';
        } else {
            // Insert
            $insertSql = "INSERT INTO roommate_profiles 
                         (user_id, sleep_schedule, cleanliness, noise_level, study_habits, 
                          interests, about_me, looking_for) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            executeQuery($insertSql, [$user_id, $sleep_schedule, $cleanliness, $noise_level, 
                                     $study_habits, $interests, $about_me, $looking_for]);
            $success = 'Profile created successfully! You can now find roommates.';
        }
    } catch (Exception $e) {
        error_log("Roommate profile error: " . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
}

// Handle roommate request
if (isset($_POST['send_request'])) {
    $to_user_id = (int)$_POST['to_user_id'];
    
    try {
        // Check if request already exists
        $checkSql = "SELECT * FROM roommate_requests 
                     WHERE (from_user_id = ? AND to_user_id = ?) 
                     OR (from_user_id = ? AND to_user_id = ?)";
        $existing = fetchOne($checkSql, [$user_id, $to_user_id, $to_user_id, $user_id]);
        
        if ($existing) {
            $error = 'Request already sent or received from this user.';
        } else {
            $insertSql = "INSERT INTO roommate_requests (from_user_id, to_user_id, status) 
                         VALUES (?, ?, 'pending')";
            executeQuery($insertSql, [$user_id, $to_user_id]);
            $success = 'Roommate request sent!';
        }
    } catch (Exception $e) {
        $error = 'Error sending request.';
    }
}

// Get user's profile
$profileSql = "SELECT * FROM roommate_profiles WHERE user_id = ?";
$myProfile = fetchOne($profileSql, [$user_id]);

// Get potential roommates (same year level, has profile, not already requested)
$matchesSql = "SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.program, u.gender,
                      rp.sleep_schedule, rp.cleanliness, rp.noise_level, rp.study_habits, 
                      rp.interests, rp.about_me
               FROM users u
               INNER JOIN roommate_profiles rp ON u.user_id = rp.user_id
               WHERE u.year_level = ? 
               AND u.user_id != ?
               AND u.user_role = 'student'
               AND u.is_active = 1
               AND NOT EXISTS (
                   SELECT 1 FROM roommate_requests rr 
                   WHERE (rr.from_user_id = ? AND rr.to_user_id = u.user_id)
                   OR (rr.from_user_id = u.user_id AND rr.to_user_id = ?)
               )
               ORDER BY u.created_at DESC";
$matches = fetchAll($matchesSql, [$user['year_level'], $user_id, $user_id, $user_id]);

// Get pending requests
$requestsSql = "SELECT rr.*, u.student_id, u.first_name, u.last_name, u.program,
                       rp.about_me, rp.interests
                FROM roommate_requests rr
                INNER JOIN users u ON rr.from_user_id = u.user_id
                LEFT JOIN roommate_profiles rp ON u.user_id = rp.user_id
                WHERE rr.to_user_id = ? AND rr.status = 'pending'
                ORDER BY rr.created_at DESC";
$pendingRequests = fetchAll($requestsSql, [$user_id]);

// Get sent requests
$sentRequestsSql = "SELECT rr.*, u.student_id, u.first_name, u.last_name
                    FROM roommate_requests rr
                    INNER JOIN users u ON rr.to_user_id = u.user_id
                    WHERE rr.from_user_id = ? AND rr.status = 'pending'
                    ORDER BY rr.created_at DESC";
$sentRequests = fetchAll($sentRequestsSql, [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Roommate - Campus Room Allocation</title>
    
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
        .roommate-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        .roommate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        .trait-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 0.85rem;
            margin: 3px;
        }
        .compatibility-score {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
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
                        <a class="nav-link active" href="find-roommate.php">
                            <i class="fas fa-user-friends"></i> Find Roommate
                            <?php if (count($pendingRequests) > 0): ?>
                                <span class="badge bg-danger"><?php echo count($pendingRequests); ?></span>
                            <?php endif; ?>
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
        <h2 class="mb-4">
            <i class="fas fa-user-friends"></i> Find Your Perfect Roommate
        </h2>
        
        <?php if (!$myProfile): ?>
            <!-- Create Profile First -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>First time here?</strong> Create your roommate profile to start matching with others!
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card"></i> Create Your Roommate Profile</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="display:none;"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="profileForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-moon"></i> Sleep Schedule</label>
                                <select class="form-select" name="sleep_schedule" required>
                                    <option value="">Select...</option>
                                    <option value="Early Bird">🌅 Early Bird (Sleep before 10 PM)</option>
                                    <option value="Normal">😴 Normal (10 PM - 12 AM)</option>
                                    <option value="Night Owl">🦉 Night Owl (After 12 AM)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-broom"></i> Cleanliness Level</label>
                                <select class="form-select" name="cleanliness" required>
                                    <option value="">Select...</option>
                                    <option value="Very Clean">✨ Very Clean (Organized always)</option>
                                    <option value="Clean">🧹 Clean (Tidy most times)</option>
                                    <option value="Average">👌 Average (Clean when needed)</option>
                                    <option value="Relaxed">😌 Relaxed (Comfortable with mess)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-volume-up"></i> Noise Preference</label>
                                <select class="form-select" name="noise_level" required>
                                    <option value="">Select...</option>
                                    <option value="Quiet">🤫 Quiet (Silence preferred)</option>
                                    <option value="Moderate">🎵 Moderate (Some noise ok)</option>
                                    <option value="Social">🎉 Social (Likes conversation/music)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-book"></i> Study Habits</label>
                                <select class="form-select" name="study_habits" required>
                                    <option value="">Select...</option>
                                    <option value="In Room">📚 Study in room often</option>
                                    <option value="Library">🏛️ Prefer library/outside</option>
                                    <option value="Group Study">👥 Like group study</option>
                                    <option value="Flexible">🔄 Flexible/varies</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-heart"></i> Interests & Hobbies</label>
                            <input type="text" class="form-control" name="interests" 
                                   placeholder="e.g., Sports, Music, Gaming, Reading, Cooking..." required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> About Me</label>
                            <textarea class="form-control" name="about_me" rows="3" 
                                      placeholder="Tell potential roommates about yourself..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-search"></i> Looking For</label>
                            <textarea class="form-control" name="looking_for" rows="2" 
                                      placeholder="What are you looking for in a roommate?"></textarea>
                        </div>
                        
                        <button type="submit" name="save_profile" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-save"></i> Create Profile
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Tabs for different sections -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#find">
                        <i class="fas fa-search"></i> Find Roommates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#requests">
                        <i class="fas fa-inbox"></i> Requests
                        <?php if (count($pendingRequests) > 0): ?>
                            <span class="badge bg-danger"><?php echo count($pendingRequests); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#myprofile">
                        <i class="fas fa-id-card"></i> My Profile
                    </a>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Find Roommates Tab -->
                <div class="tab-pane fade show active" id="find">
                    <?php if (empty($matches)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            No matches found at the moment. Check back later or try updating your profile!
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($matches as $match): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="roommate-card scale-in">
                                    <div class="profile-avatar">
                                        <?php echo strtoupper(substr($match['first_name'], 0, 1) . substr($match['last_name'], 0, 1)); ?>
                                    </div>
                                    
                                    <h5 class="text-center mb-1">
                                        <?php echo htmlspecialchars($match['first_name'] . ' ' . $match['last_name']); ?>
                                    </h5>
                                    <p class="text-center text-muted small mb-3">
                                        <?php echo htmlspecialchars($match['student_id']); ?> • 
                                        <?php echo htmlspecialchars($match['program']); ?>
                                    </p>
                                    
                                    <div class="mb-3">
                                        <span class="trait-badge">😴 <?php echo htmlspecialchars($match['sleep_schedule']); ?></span>
                                        <span class="trait-badge">🧹 <?php echo htmlspecialchars($match['cleanliness']); ?></span>
                                        <span class="trait-badge">🔊 <?php echo htmlspecialchars($match['noise_level']); ?></span>
                                    </div>
                                    
                                    <?php if ($match['interests']): ?>
                                        <p class="mb-2"><strong>Interests:</strong> <?php echo htmlspecialchars($match['interests']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($match['about_me']): ?>
                                        <p class="text-muted small mb-3">
                                            "<?php echo htmlspecialchars(substr($match['about_me'], 0, 100)); ?>..."
                                        </p>
                                    <?php endif; ?>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="to_user_id" value="<?php echo $match['user_id']; ?>">
                                        <button type="submit" name="send_request" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane"></i> Send Roommate Request
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Requests Tab -->
                <div class="tab-pane fade" id="requests">
                    <h4 class="mb-3">Received Requests</h4>
                    <?php if (empty($pendingRequests)): ?>
                        <p class="text-muted">No pending requests</p>
                    <?php else: ?>
                        <?php foreach ($pendingRequests as $req): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($req['program']); ?></p>
                                <?php if ($req['about_me']): ?>
                                    <p><?php echo htmlspecialchars($req['about_me']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    Sent <?php echo date('M d, Y', strtotime($req['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <h4 class="mb-3 mt-4">Sent Requests</h4>
                    <?php if (empty($sentRequests)): ?>
                        <p class="text-muted">No sent requests</p>
                    <?php else: ?>
                        <?php foreach ($sentRequests as $req): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></h5>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- My Profile Tab -->
                <div class="tab-pane fade" id="myprofile">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Your Profile</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <!-- Same form as create, but pre-filled -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sleep Schedule</label>
                                        <select class="form-select" name="sleep_schedule" required>
                                            <option value="Early Bird" <?php echo $myProfile['sleep_schedule'] == 'Early Bird' ? 'selected' : ''; ?>>🌅 Early Bird</option>
                                            <option value="Normal" <?php echo $myProfile['sleep_schedule'] == 'Normal' ? 'selected' : ''; ?>>😴 Normal</option>
                                            <option value="Night Owl" <?php echo $myProfile['sleep_schedule'] == 'Night Owl' ? 'selected' : ''; ?>>🦉 Night Owl</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Cleanliness Level</label>
                                        <select class="form-select" name="cleanliness" required>
                                            <option value="Very Clean" <?php echo $myProfile['cleanliness'] == 'Very Clean' ? 'selected' : ''; ?>>✨ Very Clean</option>
                                            <option value="Clean" <?php echo $myProfile['cleanliness'] == 'Clean' ? 'selected' : ''; ?>>🧹 Clean</option>
                                            <option value="Average" <?php echo $myProfile['cleanliness'] == 'Average' ? 'selected' : ''; ?>>👌 Average</option>
                                            <option value="Relaxed" <?php echo $myProfile['cleanliness'] == 'Relaxed' ? 'selected' : ''; ?>>😌 Relaxed</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Noise Preference</label>
                                        <select class="form-select" name="noise_level" required>
                                            <option value="Quiet" <?php echo $myProfile['noise_level'] == 'Quiet' ? 'selected' : ''; ?>>🤫 Quiet</option>
                                            <option value="Moderate" <?php echo $myProfile['noise_level'] == 'Moderate' ? 'selected' : ''; ?>>🎵 Moderate</option>
                                            <option value="Social" <?php echo $myProfile['noise_level'] == 'Social' ? 'selected' : ''; ?>>🎉 Social</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Study Habits</label>
                                        <select class="form-select" name="study_habits" required>
                                            <option value="In Room" <?php echo $myProfile['study_habits'] == 'In Room' ? 'selected' : ''; ?>>📚 In Room</option>
                                            <option value="Library" <?php echo $myProfile['study_habits'] == 'Library' ? 'selected' : ''; ?>>🏛️ Library</option>
                                            <option value="Group Study" <?php echo $myProfile['study_habits'] == 'Group Study' ? 'selected' : ''; ?>>👥 Group Study</option>
                                            <option value="Flexible" <?php echo $myProfile['study_habits'] == 'Flexible' ? 'selected' : ''; ?>>🔄 Flexible</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Interests & Hobbies</label>
                                    <input type="text" class="form-control" name="interests" 
                                           value="<?php echo htmlspecialchars($myProfile['interests']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">About Me</label>
                                    <textarea class="form-control" name="about_me" rows="3" required><?php echo htmlspecialchars($myProfile['about_me']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Looking For</label>
                                    <textarea class="form-control" name="looking_for" rows="2"><?php echo htmlspecialchars($myProfile['looking_for']); ?></textarea>
                                </div>
                                
                                <button type="submit" name="save_profile" class="btn btn-success w-100">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
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
            
            // Add staggered animation
            const cards = document.querySelectorAll('.roommate-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>