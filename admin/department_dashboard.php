<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'department_admin'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$dept_id = null; 
$dept_name = ''; 
$dept_code = '';

// Super admin viewing specific department
if($_SESSION['role'] == 'admin' && isset($_GET['dept'])) {
    $stmt = $db->prepare("SELECT * FROM departments WHERE dept_code = ?");
    $stmt->execute([$_GET['dept']]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    if($department) { 
        $dept_id = $department['dept_id']; 
        $dept_name = $department['dept_name']; 
        $dept_code = $department['dept_code']; 
    }
}
// Department admin viewing own department
elseif($_SESSION['role'] == 'department_admin') {
    $dept_id = $_SESSION['department_id'];
    if($dept_id) {
        $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
        $stmt->execute([$dept_id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        if($department) { 
            $dept_name = $department['dept_name']; 
            $dept_code = $department['dept_code']; 
        }
    }
}
// Super admin without department - show selector
elseif($_SESSION['role'] == 'admin' && !isset($_GET['dept'])) {
    $all_depts = $db->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Select Department - Super Admin</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{font-family:Arial,sans-serif;background:#f3f4f6;display:flex;align-items:center;justify-content:center;min-height:100vh}
            .container{background:white;padding:3rem;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.1);max-width:550px;width:90%;text-align:center}
            h2{color:#1f2937;margin-bottom:1rem;font-size:1.5rem}
            p{color:#6b7280;margin-bottom:2rem}
            .dept-list{display:grid;gap:1rem}
            .dept-btn{display:block;padding:1.2rem;background:#f9fafb;border:2px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#1f2937;font-weight:600;transition:all .3s;font-size:1.1rem}
            .dept-btn:hover{border-color:#3b82f6;background:#eff6ff;transform:translateY(-3px);box-shadow:0 5px 20px rgba(0,0,0,0.1)}
            .dept-btn i{margin-right:.5rem}
            .btn-back{display:inline-block;margin-top:2rem;padding:.75rem 1.5rem;background:#6b7280;color:white;text-decoration:none;border-radius:8px;transition:.3s}
            .btn-back:hover{background:#4b5563}
        </style>
    </head>
    <body>
        <div class="container">
            <h2><i class="fas fa-building"></i> Select Department</h2>
            <p>Choose a department to view its admin dashboard</p>
            <div class="dept-list">
                <?php foreach($all_depts as $dept): ?>
                <a href="department_dashboard.php?dept=<?php echo $dept['dept_code']; ?>" class="dept-btn">
                    <i class="fas fa-<?php 
                        echo $dept['dept_code']=='EE'?'microchip':($dept['dept_code']=='CS'?'laptop-code':($dept['dept_code']=='IS'?'chart-line':'cogs')); 
                    ?>" style="color:<?php echo $dept['color_code']??'#3b82f6'; ?>;"></i>
                    <?php echo htmlspecialchars($dept['dept_name']); ?> 
                    <span style="color:#6b7280;font-size:0.9rem;">(<?php echo $dept['dept_code']; ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Main Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If no department found
if(!$dept_id) {
    die("<h2 style='text-align:center;margin-top:50px;color:red;font-family:Arial;'>Error: No department assigned or department not found.<br><a href='../index.php' style='color:#3b82f6;'>Go to Home</a></h2>");
}

// Get statistics
$course_count = $db->prepare("SELECT COUNT(*) FROM courses WHERE dept_id = ?"); 
$course_count->execute([$dept_id]); 
$course_count = $course_count->fetchColumn();

$career_count = $db->prepare("SELECT COUNT(*) FROM careers WHERE dept_id = ?"); 
$career_count->execute([$dept_id]); 
$career_count = $career_count->fetchColumn();

$model_count = $db->prepare("SELECT COUNT(*) FROM role_models WHERE dept_id = ?"); 
$model_count->execute([$dept_id]); 
$model_count = $model_count->fetchColumn();

$rec_count = $db->prepare("SELECT COUNT(*) FROM recommendations WHERE dept_id = ?"); 
$rec_count->execute([$dept_id]); 
$rec_count = $rec_count->fetchColumn();

// Get pending admin count for super admin
$pending_count = 0;
if($_SESSION['role'] == 'admin') {
    $pending_count = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending' AND role IN ('admin', 'department_admin')")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dept_name); ?> - Department Admin | SDGS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins','Segoe UI',Arial,sans-serif;background:#f3f4f6}
        
        /* Navbar */
        .navbar{background:#2563eb;padding:1rem 2rem;color:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);position:sticky;top:0;z-index:1000}
        .nav-container{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center}
        .nav-brand{color:white;text-decoration:none;font-size:1.3rem;font-weight:bold;display:flex;align-items:center;gap:.5rem}
        .nav-brand i{font-size:1.5rem}
        .nav-menu{display:flex;list-style:none;gap:1rem;align-items:center}
        .nav-menu a{color:white;text-decoration:none;padding:.5rem 1rem;border-radius:5px;transition:.3s;font-size:.9rem;font-weight:500}
        .nav-menu a:hover{background:rgba(255,255,255,.2)}
        .nav-menu a.active{background:rgba(255,255,255,.3)}
        
        /* Container */
        .container{max-width:1200px;margin:2rem auto;padding:0 1.5rem}
        
        /* Header Card */
        .header-card{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2.5rem;border-radius:15px;margin-bottom:2rem;box-shadow:0 5px 20px rgba(0,0,0,0.15)}
        .header-card h1{font-size:2.2rem;margin-bottom:.5rem;font-weight:700}
        .header-card p{opacity:.9;font-size:1.1rem}
        .badge{display:inline-block;background:rgba(255,255,255,.2);padding:.3rem 1rem;border-radius:20px;font-size:.85rem;margin-bottom:.75rem;font-weight:500}
        
        /* Notices */
        .notice{padding:1rem 1.5rem;border-radius:10px;margin-bottom:2rem;font-weight:500}
        .notice-info{background:#dbeafe;border:1px solid #93c5fd;color:#1e40af}
        .notice-warning{background:#fef3c7;border:1px solid #f59e0b;color:#92400e}
        .notice i{margin-right:.5rem}
        
        /* Stats Grid */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem}
        .stat-card{background:white;padding:1.75rem;border-radius:12px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.08);transition:transform .3s,box-shadow .3s}
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 10px 25px rgba(0,0,0,0.12)}
        .stat-card .icon{font-size:2.5rem;margin-bottom:.75rem}
        .stat-card h3{font-size:2.2rem;color:#1f2937;font-weight:700;margin-bottom:.25rem}
        .stat-card p{color:#6b7280;font-size:.9rem;font-weight:500}
        
        /* Section Title */
        .section-title{font-size:1.4rem;color:#1f2937;margin-bottom:1.5rem;padding-bottom:.5rem;border-bottom:3px solid #3b82f6;display:flex;align-items:center;gap:.5rem;font-weight:600}
        
        /* Actions Grid */
        .actions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;margin-bottom:2rem}
        .action-card{background:white;padding:2rem;border-radius:12px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.08);transition:all .3s;border:2px solid transparent}
        .action-card:hover{transform:translateY(-8px);box-shadow:0 15px 35px rgba(0,0,0,0.15);border-color:#3b82f6}
        .action-card .icon{font-size:3rem;margin-bottom:1rem}
        .action-card h3{margin-bottom:.75rem;color:#1f2937;font-size:1.2rem;font-weight:600}
        .action-card p{color:#6b7280;margin-bottom:1.5rem;font-size:.9rem;line-height:1.5}
        
        /* Buttons */
        .btn{display:inline-block;padding:.75rem 1.5rem;border-radius:8px;border:none;font-weight:600;text-decoration:none;cursor:pointer;color:white;transition:all .3s;font-size:.95rem}
        .btn:hover{transform:translateY(-3px);opacity:.9}
        .btn-course{background:#f59e0b}
        .btn-career{background:#06b6d4}
        .btn-model{background:#8b5cf6}
        .btn-media{background:#ec4899}
        .btn-back{background:#6b7280}
        .btn-view{background:#10b981}
        
        /* Department Info */
        .dept-info{background:white;padding:2rem;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:2rem}
        .dept-info h3{color:#1f2937;margin-bottom:1rem;border-bottom:2px solid #e5e7eb;padding-bottom:.75rem;font-size:1.2rem;display:flex;align-items:center;gap:.5rem}
        .dept-info p{color:#6b7280;line-height:1.7;margin-bottom:.5rem}
        .dept-info strong{color:#374151}
        
        /* Media Preview Section */
        .media-preview{background:white;padding:2rem;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:2rem}
        .media-preview h3{margin-bottom:1.5rem;border-bottom:2px solid #e5e7eb;padding-bottom:.75rem;display:flex;align-items:center;gap:.5rem;font-size:1.2rem}
        .media-grid{display:grid;grid-template-columns:1fr 1fr;gap:2rem}
        .media-item{text-align:center}
        .media-item h4{margin-bottom:.75rem;color:#374151;font-size:1rem}
        .media-item img{max-width:100%;max-height:250px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
        .media-item .video-preview{position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:10px}
        .media-item .video-preview iframe{position:absolute;top:0;left:0;width:100%;height:100%;border-radius:10px}
        .no-media{text-align:center;padding:2rem;background:#f9fafb;border-radius:10px;color:#9ca3af}
        .no-media i{font-size:2.5rem;display:block;margin-bottom:.5rem}
        
        /* Responsive */
        @media(max-width:768px){
            .media-grid{grid-template-columns:1fr}
            .header-card h1{font-size:1.6rem}
            .actions-grid{grid-template-columns:1fr}
            .nav-menu{display:none}
        }
        
        /* Footer */
        .footer{text-align:center;padding:2rem;color:#6b7280;font-size:.9rem}
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo $_SESSION['role']=='admin'?'dashboard.php':'../index.php'; ?>" class="nav-brand">
                <i class="fas fa-graduation-cap"></i> 
                <?php echo htmlspecialchars($dept_name); ?> 
                <?php if($_SESSION['role']=='admin'): ?><span style="font-size:.8rem;opacity:.7;">(Super Admin)</span><?php endif; ?>
            </a>
            <ul class="nav-menu">
                <li><a href="department_dashboard.php<?php echo isset($_GET['dept'])?'?dept='.$_GET['dept']:''; ?>" class="active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="manage_department.php?type=courses<?php echo isset($_GET['dept'])?'&dept='.$_GET['dept']:''; ?>">
                    <i class="fas fa-book"></i> Courses
                </a></li>
                <li><a href="manage_department.php?type=careers<?php echo isset($_GET['dept'])?'&dept='.$_GET['dept']:''; ?>">
                    <i class="fas fa-briefcase"></i> Careers
                </a></li>
                <li><a href="manage_department.php?type=models<?php echo isset($_GET['dept'])?'&dept='.$_GET['dept']:''; ?>">
                    <i class="fas fa-star"></i> Models
                </a></li>
                <li><a href="upload_media.php<?php echo isset($_GET['dept'])?'?dept='.$_GET['dept']:''; ?>">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Media
                </a></li>
                <li><a href="../departments.php?dept=<?php echo $dept_code; ?>">
                    <i class="fas fa-eye"></i> View Page
                </a></li>
                <?php if($_SESSION['role'] == 'admin'): ?>
                <li><a href="dashboard.php"><i class="fas fa-arrow-left"></i> Main Dashboard</a></li>
                <?php endif; ?>
                <li><a href="../logout.php" style="background:rgba(255,255,255,.15);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <span class="badge"><i class="fas fa-building"></i> <?php echo htmlspecialchars($dept_code); ?></span>
            <h1><?php echo htmlspecialchars($dept_name); ?> Administration</h1>
            <p>
                <?php if($_SESSION['role']=='admin'): ?>
                <i class="fas fa-eye"></i> Viewing as Super Admin | 
                <?php endif; ?>
                Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
            </p>
        </div>
        
        <!-- Notice -->
        <?php if($_SESSION['role']=='admin'): ?>
        <div class="notice notice-info">
            <i class="fas fa-info-circle"></i>
            <strong>Super Admin View:</strong> You are viewing the <strong><?php echo htmlspecialchars($dept_name); ?></strong> department dashboard. You have full access to manage this department's content.
        </div>
        <?php else: ?>
        <div class="notice notice-warning">
            <i class="fas fa-info-circle"></i>
            <strong>Department Admin:</strong> You can only manage content for the <strong><?php echo htmlspecialchars($dept_name); ?></strong> department.
        </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📚</div>
                <h3><?php echo $course_count; ?></h3>
                <p>Total Courses</p>
            </div>
            <div class="stat-card">
                <div class="icon">💼</div>
                <h3><?php echo $career_count; ?></h3>
                <p>Career Paths</p>
            </div>
            <div class="stat-card">
                <div class="icon">⭐</div>
                <h3><?php echo $model_count; ?></h3>
                <p>Role Models</p>
            </div>
            <div class="stat-card">
                <div class="icon">💡</div>
                <h3><?php echo $rec_count; ?></h3>
                <p>Recommendations</p>
            </div>
        </div>
        
        <!-- Current Media Preview -->
        <div class="media-preview">
            <h3><i class="fas fa-image" style="color:#3b82f6;"></i> Current Department Media</h3>
            <div class="media-grid">
                <!-- Banner Image -->
                <div class="media-item">
                    <h4><i class="fas fa-image"></i> Banner Image</h4>
                    <?php if(!empty($department['image_url']) && file_exists('../'.$department['image_url'])): ?>
                        <img src="../<?php echo htmlspecialchars($department['image_url']); ?>" alt="Department Banner">
                    <?php else: ?>
                        <div class="no-media">
                            <i class="fas fa-image"></i>
                            <p>No banner uploaded</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Video -->
                <div class="media-item">
                    <h4><i class="fas fa-video"></i> Introduction Video</h4>
                    <?php if(!empty($department['video_url'])): ?>
                        <div class="video-preview">
                            <iframe src="<?php echo htmlspecialchars($department['video_url']); ?>" allowfullscreen></iframe>
                        </div>
                        <p style="margin-top:.5rem;font-weight:600;"><?php echo htmlspecialchars($department['video_title'] ?? 'No title'); ?></p>
                    <?php else: ?>
                        <div class="no-media">
                            <i class="fas fa-video"></i>
                            <p>No video added</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="text-align:center;margin-top:1.5rem;">
                <a href="upload_media.php<?php echo isset($_GET['dept'])?'?dept='.$_GET['dept']:''; ?>" class="btn btn-media">
                    <i class="fas fa-cloud-upload-alt"></i> Upload / Update Media
                </a>
            </div>
        </div>
        
        <!-- Department Information -->
        <div class="dept-info">
            <h3><i class="fas fa-info-circle" style="color:#3b82f6;"></i> Department Information</h3>
            <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($department['description'])); ?></p>
            <p style="margin-top:1rem;"><strong>Required Skills:</strong><br><?php echo nl2br(htmlspecialchars($department['required_skills'])); ?></p>
            <p style="margin-top:1rem;"><strong>Natural Requirements:</strong><br><?php echo nl2br(htmlspecialchars($department['natural_requirements'])); ?></p>
        </div>
        
        <!-- Management Actions -->
        <h2 class="section-title"><i class="fas fa-cogs"></i> Management Actions</h2>
        <div class="actions-grid">
            <!-- Manage Courses -->
            <div class="action-card">
                <div class="icon">📚</div>
                <h3>Manage Courses</h3>
                <p>Add, edit, or delete course titles and descriptions for the <?php echo htmlspecialchars($dept_name); ?> department.</p>
                <a href="manage_department.php?type=courses<?php echo isset($_GET['dept'])?'&dept='.$_GET['dept']:''; ?>" class="btn btn-course">
                    <i class="fas fa-edit"></i> Manage Courses
                </a>
            </div>
            
            <!-- Manage Careers -->
            <div class="action-card">
                <div class="icon">💼</div>
                <h3>Manage Careers</h3>
                <p>Update career paths and job opportunities for <?php echo htmlspecialchars($dept_name); ?> graduates.</p>
                <a href="manage_department.php?type=careers<?php echo isset($_GET['dept'])?'&dept='.$_GET['dept']:''; ?>" class="btn btn-career">
                    <i class="fas fa-edit"></i> Manage Careers
                </a>
            </div>
            
            <!-- Manage Role Models -->
            <div class="action-card">
                <div class="icon">⭐</div>
                <h3>Manage Role Models</h3>
                <p>Add or update inspirational role models in the <?php echo htmlspecialchars($dept_name); ?> field.</p>
                <a href="manage_department.php?type=models<?php echo isset($_GET['dept'])?'&dept='.$_GET['dept']:''; ?>" class="btn btn-model">
                    <i class="fas fa-edit"></i> Manage Role Models
                </a>
            </div>
            
            <!-- Upload Media -->
            <div class="action-card">
                <div class="icon">📷</div>
                <h3>Upload Photos & Videos</h3>
                <p>Upload department banner image, introduction video (YouTube), and gallery photos to showcase the department.</p>
                <a href="upload_media.php<?php echo isset($_GET['dept'])?'?dept='.$_GET['dept']:''; ?>" class="btn btn-media">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Media
                </a>
            </div>
        </div>
        
        <!-- Bottom Navigation -->
        <div style="text-align:center;margin-top:2rem;margin-bottom:3rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="../departments.php?dept=<?php echo $dept_code; ?>" class="btn btn-view">
                <i class="fas fa-eye"></i> View Department Page
            </a>
            <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Main Dashboard
            </a>
            <?php else: ?>
            <a href="../index.php" class="btn btn-back">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Student Department Guidance System</p>
    </div>
</body>
</html>