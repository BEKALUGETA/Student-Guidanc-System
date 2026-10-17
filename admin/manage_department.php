<?php
session_start();
require_once '../config/database.php';

// Check access
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'department_admin'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$type = isset($_GET['type']) ? $_GET['type'] : 'courses';
$success = '';
$error = '';

// Get department ID
$dept_id = $_SESSION['department_id'] ?? null;

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action == 'add' && $dept_id) {
        if($type == 'courses') {
            $stmt = $db->prepare("INSERT INTO courses (dept_id, course_title, course_description) VALUES (?, ?, ?)");
            if($stmt->execute([$dept_id, $_POST['course_title'], $_POST['course_description']])) {
                $success = 'Course added successfully!';
            } else {
                $error = 'Failed to add course.';
            }
        } elseif($type == 'careers') {
            $stmt = $db->prepare("INSERT INTO careers (dept_id, career_title, career_description, job_level) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$dept_id, $_POST['career_title'], $_POST['career_description'], $_POST['job_level']])) {
                $success = 'Career added successfully!';
            } else {
                $error = 'Failed to add career.';
            }
        } elseif($type == 'models') {
            $stmt = $db->prepare("INSERT INTO role_models (dept_id, name, achievement, description) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$dept_id, $_POST['name'], $_POST['achievement'], $_POST['description']])) {
                $success = 'Role model added successfully!';
            } else {
                $error = 'Failed to add role model.';
            }
        }
    }
    
    if($action == 'delete' && $dept_id) {
        $id = $_POST['id'] ?? 0;
        if($type == 'courses') {
            $stmt = $db->prepare("DELETE FROM courses WHERE course_id = ? AND dept_id = ?");
        } elseif($type == 'careers') {
            $stmt = $db->prepare("DELETE FROM careers WHERE career_id = ? AND dept_id = ?");
        } elseif($type == 'models') {
            $stmt = $db->prepare("DELETE FROM role_models WHERE model_id = ? AND dept_id = ?");
        }
        if(isset($stmt) && $stmt->execute([$id, $dept_id])) {
            $success = 'Item deleted successfully!';
        } else {
            $error = 'Failed to delete item.';
        }
    }
}

// Get items for this department
$items = [];
if($dept_id) {
    if($type == 'courses') {
        $stmt = $db->prepare("SELECT * FROM courses WHERE dept_id = ? ORDER BY course_title");
    } elseif($type == 'careers') {
        $stmt = $db->prepare("SELECT * FROM careers WHERE dept_id = ? ORDER BY career_title");
    } elseif($type == 'models') {
        $stmt = $db->prepare("SELECT * FROM role_models WHERE dept_id = ? ORDER BY name");
    }
    $stmt->execute([$dept_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get department info
$dept_name = $_SESSION['department_name'] ?? 'Department';
$dept_code = $_SESSION['department_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage <?php echo ucfirst($type); ?> - <?php echo htmlspecialchars($dept_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f3f4f6; }
        
        .navbar { background: #2563eb; padding: 1rem 2rem; color: white; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .nav-brand { color: white; text-decoration: none; font-size: 1.3rem; font-weight: bold; }
        .nav-menu { display: flex; list-style: none; gap: 1rem; }
        .nav-menu a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px; transition: 0.3s; }
        .nav-menu a:hover { background: rgba(255,255,255,0.2); }
        
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        
        .card { 
            background: white; 
            padding: 2rem; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 2rem; 
        }
        .header-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 5px; font-size: 1rem; 
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            outline: none; border-color: #3b82f6; 
        }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th { background: #3b82f6; color: white; padding: 1rem; text-align: left; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        
        .btn { 
            display: inline-block; padding: 0.75rem 1.5rem; border-radius: 5px; border: none; 
            font-size: 1rem; font-weight: 600; text-decoration: none; cursor: pointer; 
            color: white; transition: 0.3s; 
        }
        .btn-primary { background: #3b82f6; }
        .btn-danger { background: #ef4444; padding: 0.5rem 1rem; font-size: 0.9rem; }
        .btn-back { background: #6b7280; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge-entry { background: #dbeafe; color: #1e40af; }
        .badge-mid { background: #fef3c7; color: #92400e; }
        .badge-senior { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="department_dashboard.php" class="nav-brand">
                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($dept_name); ?>
            </a>
            <ul class="nav-menu">
                <li><a href="department_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="../departments.php?dept=<?php echo $dept_code; ?>"><i class="fas fa-eye"></i> View Dept</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="card header-card">
            <h1><i class="fas fa-edit"></i> Manage <?php echo ucfirst($type); ?></h1>
            <p><?php echo htmlspecialchars($dept_name); ?> Department</p>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Add Form -->
        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Add New <?php echo rtrim($type, 's'); ?></h3>
            <form method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="add">
                
                <?php if($type == 'courses'): ?>
                <div class="form-group">
                    <label>Course Title</label>
                    <input type="text" name="course_title" placeholder="e.g., Advanced Programming" required>
                </div>
                <div class="form-group">
                    <label>Course Description</label>
                    <textarea name="course_description" rows="3" placeholder="Brief description of the course..." required></textarea>
                </div>
                <?php elseif($type == 'careers'): ?>
                <div class="form-group">
                    <label>Career Title</label>
                    <input type="text" name="career_title" placeholder="e.g., Software Developer" required>
                </div>
                <div class="form-group">
                    <label>Career Description</label>
                    <textarea name="career_description" rows="3" placeholder="Description of the career path..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Job Level</label>
                    <select name="job_level" required>
                        <option value="Entry">Entry Level</option>
                        <option value="Mid">Mid Level</option>
                        <option value="Senior">Senior Level</option>
                    </select>
                </div>
                <?php elseif($type == 'models'): ?>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" placeholder="e.g., Steve Jobs" required>
                </div>
                <div class="form-group">
                    <label>Achievement</label>
                    <input type="text" name="achievement" placeholder="e.g., Co-founder of Apple" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description..." required></textarea>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-plus"></i> Add <?php echo rtrim($type, 's'); ?>
                </button>
            </form>
        </div>
        
        <!-- Items List -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Existing <?php echo ucfirst($type); ?> (<?php echo count($items); ?>)</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <?php if($type == 'courses'): ?>
                            <th>Course Title</th>
                            <th>Description</th>
                            <?php elseif($type == 'careers'): ?>
                            <th>Career Title</th>
                            <th>Description</th>
                            <th>Level</th>
                            <?php else: ?>
                            <th>Name</th>
                            <th>Achievement</th>
                            <th>Description</th>
                            <?php endif; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($items)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                                No <?php echo $type; ?> found. Add your first one above!
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <?php if($type == 'courses'): ?>
                                <td><strong><?php echo htmlspecialchars($item['course_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['course_description']); ?></td>
                                <?php elseif($type == 'careers'): ?>
                                <td><strong><?php echo htmlspecialchars($item['career_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['career_description']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($item['job_level']); ?>">
                                        <?php echo $item['job_level']; ?>
                                    </span>
                                </td>
                                <?php else: ?>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['achievement']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php 
                                            echo $type == 'courses' ? $item['course_id'] : 
                                                ($type == 'careers' ? $item['career_id'] : $item['model_id']); 
                                        ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="department_dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>