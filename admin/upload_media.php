<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'department_admin'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$success = '';
$error = '';

// Get department ID
if($_SESSION['role'] == 'department_admin') {
    $dept_id = $_SESSION['department_id'];
} else {
    $dept_id = isset($_GET['dept_id']) ? $_GET['dept_id'] : (isset($_POST['dept_id']) ? $_POST['dept_id'] : null);
}

// Get department info
$department = null;
if($dept_id) {
    $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
    $stmt->execute([$dept_id]);
    $department = $stmt->fetch();
}

// Handle uploads
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Select department (for super admin)
    if($action == 'select_department') {
        $dept_id = $_POST['dept_id'];
        $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
        $stmt->execute([$dept_id]);
        $department = $stmt->fetch();
    }
    
    // Upload banner image
    if($action == 'upload_banner' && isset($_FILES['banner_image']) && $dept_id) {
        $file = $_FILES['banner_image'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if($file['error'] == 0) {
            if(in_array($ext, $allowed) && $file['size'] < 5000000) {
                $upload_dir = '../assets/images/departments/';
                if(!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $filename = $department['dept_code'] . '-banner.' . $ext;
                if(move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                    $image_url = 'assets/images/departments/' . $filename;
                    $db->prepare("UPDATE departments SET image_url = ? WHERE dept_id = ?")->execute([$image_url, $dept_id]);
                    $success = '✅ Banner image uploaded successfully!';
                    // Refresh department data
                    $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
                    $stmt->execute([$dept_id]);
                    $department = $stmt->fetch();
                } else {
                    $error = 'Failed to save image file.';
                }
            } else {
                $error = 'Invalid file. Use JPG, PNG, GIF, or WebP under 5MB.';
            }
        } else {
            $error = 'Upload error. Please try again.';
        }
    }
    
    // Update YouTube video
    if($action == 'update_video' && $dept_id) {
        $video_url = trim($_POST['video_url']);
        $video_title = trim($_POST['video_title']);
        $video_description = trim($_POST['video_description']);
        
        if(!empty($video_url)) {
            // Convert YouTube URL to embed format
            if(strpos($video_url, 'watch?v=') !== false) {
                parse_str(parse_url($video_url, PHP_URL_QUERY), $params);
                if(isset($params['v'])) $video_url = 'https://www.youtube.com/embed/' . $params['v'];
            } elseif(strpos($video_url, 'youtu.be/') !== false) {
                $video_id = substr($video_url, strrpos($video_url, '/') + 1);
                // Remove any query parameters
                if(strpos($video_id, '?') !== false) {
                    $video_id = substr($video_id, 0, strpos($video_id, '?'));
                }
                $video_url = 'https://www.youtube.com/embed/' . $video_id;
            } elseif(strpos($video_url, 'embed/') !== false) {
                // Already embed format, keep as is
            } else {
                $error = 'Invalid YouTube URL format.';
            }
            
            if(!$error) {
                $db->prepare("UPDATE departments SET video_url = ?, video_title = ?, video_description = ? WHERE dept_id = ?")
                   ->execute([$video_url, $video_title, $video_description, $dept_id]);
                $success = '✅ Video updated successfully!';
                // Refresh
                $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
                $stmt->execute([$dept_id]);
                $department = $stmt->fetch();
            }
        } else {
            $error = 'Please enter a YouTube video URL.';
        }
    }
    
    // Upload gallery images
    if($action == 'upload_gallery' && isset($_FILES['gallery_images']) && $dept_id) {
        $files = $_FILES['gallery_images'];
        $uploaded = [];
        $upload_dir = '../assets/images/departments/';
        if(!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $count = 0;
        for($i = 0; $i < count($files['name']); $i++) {
            if($files['error'][$i] == 0) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) && $files['size'][$i] < 5000000) {
                    $count++;
                    $filename = $department['dept_code'] . '-gallery-' . $count . '.' . $ext;
                    if(move_uploaded_file($files['tmp_name'][$i], $upload_dir . $filename)) {
                        $uploaded[] = 'assets/images/departments/' . $filename;
                    }
                }
            }
        }
        
        if(!empty($uploaded)) {
            // Get existing gallery
            $stmt = $db->prepare("SELECT gallery_images FROM departments WHERE dept_id = ?");
            $stmt->execute([$dept_id]);
            $existing = $stmt->fetchColumn();
            
            if($existing) {
                $all_images = array_merge(explode(',', $existing), $uploaded);
            } else {
                $all_images = $uploaded;
            }
            
            $db->prepare("UPDATE departments SET gallery_images = ? WHERE dept_id = ?")->execute([implode(',', $all_images), $dept_id]);
            $success = '✅ ' . count($uploaded) . ' gallery images uploaded!';
            // Refresh
            $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
            $stmt->execute([$dept_id]);
            $department = $stmt->fetch();
        } else {
            $error = 'No valid images were uploaded.';
        }
    }
    
    // Remove video
    if($action == 'remove_video' && $dept_id) {
        $db->prepare("UPDATE departments SET video_url = NULL, video_title = NULL, video_description = NULL WHERE dept_id = ?")->execute([$dept_id]);
        $success = 'Video removed.';
        $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
        $stmt->execute([$dept_id]);
        $department = $stmt->fetch();
    }
}

// Get all departments for super admin
$all_depts = $db->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll();

// Get current gallery images
$gallery = [];
if($department && !empty($department['gallery_images'])) {
    $gallery = explode(',', $department['gallery_images']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Media - <?php echo $department ? htmlspecialchars($department['dept_name']) : 'Select Department'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',Arial,sans-serif;background:#f3f4f6}
        
        .navbar{background:#2563eb;padding:1rem 2rem;color:white}
        .nav-container{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center}
        .nav-brand{color:white;text-decoration:none;font-size:1.3rem;font-weight:bold;display:flex;align-items:center;gap:.5rem}
        .nav-menu{display:flex;list-style:none;gap:1rem}
        .nav-menu a{color:white;text-decoration:none;padding:.5rem 1rem;border-radius:5px;transition:.3s}
        .nav-menu a:hover{background:rgba(255,255,255,.2)}
        
        .container{max-width:900px;margin:2rem auto;padding:0 1rem}
        
        .header-card{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2rem;border-radius:15px;margin-bottom:2rem}
        
        .card{background:white;padding:2rem;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:2rem}
        .card h3{margin-bottom:1.5rem;color:#1f2937;border-bottom:2px solid #e5e7eb;padding-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
        
        .alert{padding:1rem;border-radius:8px;margin-bottom:1.5rem}
        .alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
        .alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        
        .current-media{margin-bottom:1.5rem}
        .current-media img{max-width:100%;max-height:300px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
        .video-preview{position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:10px;margin:1rem 0}
        .video-preview iframe{position:absolute;top:0;left:0;width:100%;height:100%;border-radius:10px}
        
        .form-group{margin-bottom:1.25rem}
        .form-group label{display:block;margin-bottom:.5rem;font-weight:600;color:#374151}
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group select{width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;font-family:inherit}
        .form-group input[type="file"]{width:100%;padding:.75rem;border:2px dashed #d1d5db;border-radius:8px;background:#f9fafb;cursor:pointer}
        .form-group input:focus,.form-group textarea:focus,.form-group select:focus{outline:none;border-color:#3b82f6}
        .form-group textarea{resize:vertical;min-height:80px}
        .form-group small{color:#6b7280;font-size:.85rem;margin-top:.25rem;display:block}
        
        .btn{display:inline-block;padding:.75rem 1.5rem;border-radius:8px;border:none;font-weight:600;cursor:pointer;color:white;text-decoration:none;transition:.3s;font-size:1rem}
        .btn-primary{background:#3b82f6}.btn-success{background:#10b981}.btn-danger{background:#ef4444}.btn-back{background:#6b7280}
        .btn:hover{transform:translateY(-2px);opacity:.9}
        
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;margin:1rem 0}
        .gallery-grid img{width:100%;height:120px;object-fit:cover;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1)}
        .no-media{text-align:center;padding:2rem;background:#f9fafb;border-radius:10px;color:#9ca3af}
        .no-media i{font-size:2.5rem;display:block;margin-bottom:.5rem}
        
        .dept-select{text-align:center;padding:2rem}
        .dept-select select{width:100%;padding:1rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1.1rem}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo $_SESSION['role']=='admin'?'dashboard.php':'department_dashboard.php'; ?>" class="nav-brand">
                <i class="fas fa-graduation-cap"></i> 
                <?php echo $department ? htmlspecialchars($department['dept_name']).' Admin' : 'Admin Panel'; ?>
            </a>
            <ul class="nav-menu">
                <li><a href="<?php echo $_SESSION['role']=='admin'?'dashboard.php':'department_dashboard.php'; ?>">Dashboard</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="header-card">
            <h1><i class="fas fa-cloud-upload-alt"></i> Upload Department Media</h1>
            <p>Upload banner images, YouTube videos, and gallery photos</p>
        </div>
        
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error">❌ <?php echo $error; ?></div><?php endif; ?>
        
        <!-- Department Selector for Super Admin -->
        <?php if($_SESSION['role'] == 'admin' && !$dept_id): ?>
        <div class="card">
            <div class="dept-select">
                <h3>Select Department First</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="select_department">
                    <div class="form-group">
                        <select name="dept_id" required onchange="this.form.submit()">
                            <option value="">— Choose Department —</option>
                            <?php foreach($all_depts as $d): ?>
                            <option value="<?php echo $d['dept_id']; ?>"><?php echo htmlspecialchars($d['dept_name']); ?> (<?php echo $d['dept_code']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($department): ?>
        
        <!-- 1. UPLOAD BANNER IMAGE -->
        <div class="card">
            <h3><i class="fas fa-image" style="color:#3b82f6;"></i> Upload Banner Image</h3>
            
            <div class="current-media">
                <?php if(!empty($department['image_url']) && file_exists('../'.$department['image_url'])): ?>
                    <p style="margin-bottom:.5rem;font-weight:600;">Current Banner:</p>
                    <img src="../<?php echo htmlspecialchars($department['image_url']); ?>" alt="Current Banner">
                <?php else: ?>
                    <div class="no-media"><i class="fas fa-image"></i><p>No banner image uploaded yet</p></div>
                <?php endif; ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_banner">
                <div class="form-group">
                    <label>Choose Banner Image</label>
                    <input type="file" name="banner_image" accept="image/*" required>
                    <small>Recommended size: 1200×400 pixels. Max 5MB. Formats: JPG, PNG, GIF, WebP</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Banner</button>
            </form>
        </div>
        
        <!-- 2. YOUTUBE VIDEO -->
        <div class="card">
            <h3><i class="fas fa-video" style="color:#ef4444;"></i> Add/Update YouTube Video</h3>
            
            <?php if(!empty($department['video_url'])): ?>
                <p style="margin-bottom:.5rem;font-weight:600;">Current Video:</p>
                <div class="video-preview">
                    <iframe src="<?php echo htmlspecialchars($department['video_url']); ?>" allowfullscreen></iframe>
                </div>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($department['video_title'] ?? 'No title'); ?></p>
            <?php else: ?>
                <div class="no-media"><i class="fas fa-video"></i><p>No video added yet</p></div>
            <?php endif; ?>
            
            <form method="POST" style="margin-top:1rem;">
                <input type="hidden" name="action" value="update_video">
                <div class="form-group">
                    <label>YouTube Video URL</label>
                    <input type="url" name="video_url" value="<?php echo htmlspecialchars($department['video_url'] ?? ''); ?>" 
                           placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/...">
                    <small>Supports: youtube.com/watch?v=, youtu.be/, or embed URL</small>
                </div>
                <div class="form-group">
                    <label>Video Title</label>
                    <input type="text" name="video_title" value="<?php echo htmlspecialchars($department['video_title'] ?? ''); ?>" 
                           placeholder="e.g., Introduction to <?php echo htmlspecialchars($department['dept_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Video Description (Optional)</label>
                    <textarea name="video_description" rows="2" placeholder="Brief description of the video..."><?php echo htmlspecialchars($department['video_description'] ?? ''); ?></textarea>
                </div>
                <div style="display:flex;gap:1rem;">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Video</button>
                    <?php if(!empty($department['video_url'])): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="remove_video">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Remove this video?')"><i class="fas fa-trash"></i> Remove Video</button>
                    </form>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- 3. GALLERY IMAGES -->
        <div class="card">
            <h3><i class="fas fa-images" style="color:#8b5cf6;"></i> Upload Gallery Images</h3>
            
            <?php if(!empty($gallery)): ?>
                <p style="margin-bottom:.5rem;font-weight:600;">Current Gallery (<?php echo count($gallery); ?> images):</p>
                <div class="gallery-grid">
                    <?php foreach($gallery as $img): $img = trim($img); ?>
                        <?php if(file_exists('../'.$img)): ?>
                            <img src="../<?php echo htmlspecialchars($img); ?>" alt="Gallery">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-media"><i class="fas fa-images"></i><p>No gallery images yet</p></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top:1rem;">
                <input type="hidden" name="action" value="upload_gallery">
                <div class="form-group">
                    <label>Select Multiple Images</label>
                    <input type="file" name="gallery_images[]" accept="image/*" multiple>
                    <small>You can select multiple images at once. Max 5MB each.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload to Gallery</button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <div style="text-align:center;margin-top:2rem;margin-bottom:3rem;">
            <a href="<?php echo $_SESSION['role']=='admin'?'dashboard.php':'department_dashboard.php'.(isset($_GET['dept'])?'?dept='.$_GET['dept']:''); ?>" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>