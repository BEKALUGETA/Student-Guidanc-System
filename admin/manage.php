<?php
session_start();
require_once '../config/database.php';

// Only super admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$type = isset($_GET['type']) ? $_GET['type'] : 'departments';
$success = '';
$error = '';

// Handle actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action == 'add') {
        if($type == 'departments') {
            $stmt = $db->prepare("INSERT INTO departments (dept_code, dept_name, description, required_skills, natural_requirements) VALUES (?, ?, ?, ?, ?)");
            if($stmt->execute([$_POST['dept_code'], $_POST['dept_name'], $_POST['description'], $_POST['required_skills'], $_POST['natural_requirements']])) {
                $success = 'Department added!';
            }
        } elseif($type == 'courses') {
            $stmt = $db->prepare("INSERT INTO courses (dept_id, course_title, course_description) VALUES (?, ?, ?)");
            if($stmt->execute([$_POST['dept_id'], $_POST['course_title'], $_POST['course_description']])) {
                $success = 'Course added!';
            }
        } elseif($type == 'careers') {
            $stmt = $db->prepare("INSERT INTO careers (dept_id, career_title, career_description, job_level) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$_POST['dept_id'], $_POST['career_title'], $_POST['career_description'], $_POST['job_level']])) {
                $success = 'Career added!';
            }
        } elseif($type == 'models') {
            $stmt = $db->prepare("INSERT INTO role_models (dept_id, name, achievement, description) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$_POST['dept_id'], $_POST['name'], $_POST['achievement'], $_POST['description']])) {
                $success = 'Role model added!';
            }
        }
    }
    
    if($action == 'delete') {
        $id = $_POST['id'];
        if($type == 'departments') {
            $db->prepare("DELETE FROM departments WHERE dept_id = ?")->execute([$id]);
        } elseif($type == 'courses') {
            $db->prepare("DELETE FROM courses WHERE course_id = ?")->execute([$id]);
        } elseif($type == 'careers') {
            $db->prepare("DELETE FROM careers WHERE career_id = ?")->execute([$id]);
        } elseif($type == 'models') {
            $db->prepare("DELETE FROM role_models WHERE model_id = ?")->execute([$id]);
        } elseif($type == 'users') {
            $db->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'")->execute([$id]);
        }
        $success = 'Item deleted!';
    }
}

// Get departments for dropdown
$departments = $db->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);

// Get items based on type
$items = [];
if($type == 'users') {
    $items = $db->query("SELECT u.*, d.dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.dept_id ORDER BY u.role, u.username")->fetchAll(PDO::FETCH_ASSOC);
} elseif($type == 'departments') {
    $items = $db->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll();
} else {
    $query = "";
    if($type == 'courses') $query = "SELECT c.*, d.dept_name FROM courses c JOIN departments d ON c.dept_id = d.dept_id ORDER BY d.dept_name, c.course_title";
    elseif($type == 'careers') $query = "SELECT ca.*, d.dept_name FROM careers ca JOIN departments d ON ca.dept_id = d.dept_id ORDER BY d.dept_name, ca.career_title";
    elseif($type == 'models') $query = "SELECT r.*, d.dept_name FROM role_models r JOIN departments d ON r.dept_id = d.dept_id ORDER BY d.dept_name, r.name";
    $items = $db->query($query)->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage <?php echo ucfirst($type); ?> - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f3f4f6; }
        .navbar { background: #1e3a8a; padding: 1rem 2rem; color: white; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .nav-brand { color: white; text-decoration: none; font-size: 1.3rem; font-weight: bold; }
        .nav-menu { display: flex; list-style: none; gap: 1rem; }
        .nav-menu a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px; }
        .nav-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 5px; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #3b82f6; color: white; padding: 1rem; text-align: left; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 5px; border: none; font-weight: 600; cursor: pointer; color: white; text-decoration: none; display: inline-block; }
        .btn-primary { background: #3b82f6; }
        .btn-danger { background: #ef4444; padding: 0.5rem 1rem; }
        .btn-back { background: #6b7280; }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-brand"><i class="fas fa-crown"></i> Super Admin</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="card" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white;">
            <h1>Manage <?php echo ucfirst($type); ?></h1>
        </div>
        
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        
        <?php if($type != 'users'): ?>
        <div class="card">
            <h3>Add New</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <?php if($type != 'departments'): ?>
                <div class="form-group">
                    <label>Department</label>
                    <select name="dept_id" required>
                        <option value="">Select</option>
                        <?php foreach($departments as $d): ?>
                        <option value="<?php echo $d['dept_id']; ?>"><?php echo $d['dept_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if($type == 'departments'): ?>
                <div class="form-group"><label>Code</label><input type="text" name="dept_code" required></div>
                <div class="form-group"><label>Name</label><input type="text" name="dept_name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                <div class="form-group"><label>Skills</label><textarea name="required_skills"></textarea></div>
                <div class="form-group"><label>Requirements</label><textarea name="natural_requirements"></textarea></div>
                <?php elseif($type == 'courses'): ?>
                <div class="form-group"><label>Title</label><input type="text" name="course_title" required></div>
                <div class="form-group"><label>Description</label><textarea name="course_description"></textarea></div>
                <?php elseif($type == 'careers'): ?>
                <div class="form-group"><label>Title</label><input type="text" name="career_title" required></div>
                <div class="form-group"><label>Description</label><textarea name="career_description"></textarea></div>
                <div class="form-group"><label>Level</label><select name="job_level"><option>Entry</option><option>Mid</option><option>Senior</option></select></div>
                <?php elseif($type == 'models'): ?>
                <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Achievement</label><input type="text" name="achievement" required></div>
                <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Add</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>All <?php echo ucfirst($type); ?></h3>
            <table>
                <thead><tr>
                    <?php if($type == 'users'): ?>
                    <th>Username</th><th>Name</th><th>Role</th><th>Department</th>
                    <?php elseif($type == 'departments'): ?>
                    <th>Code</th><th>Name</th><th>Description</th>
                    <?php else: ?>
                    <th>Department</th><th>Title/Name</th><th>Description</th>
                    <?php endif; ?>
                    <th>Action</th>
                </tr></thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <?php if($type == 'users'): ?>
                        <td><?php echo $item['username']; ?></td>
                        <td><?php echo $item['full_name']; ?></td>
                        <td><?php echo $item['role']; ?></td>
                        <td><?php echo $item['dept_name'] ?? '-'; ?></td>
                        <?php elseif($type == 'departments'): ?>
                        <td><?php echo $item['dept_code']; ?></td>
                        <td><?php echo $item['dept_name']; ?></td>
                        <td><?php echo substr($item['description'], 0, 100); ?></td>
                        <?php else: ?>
                        <td><?php echo $item['dept_name']; ?></td>
                        <td><?php echo $item[$type == 'courses' ? 'course_title' : ($type == 'careers' ? 'career_title' : 'name')]; ?></td>
                        <td><?php echo substr($item[$type == 'courses' ? 'course_description' : ($type == 'careers' ? 'career_description' : 'achievement')], 0, 80); ?></td>
                        <?php endif; ?>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item[$type == 'users' ? 'user_id' : ($type == 'departments' ? 'dept_id' : ($type == 'courses' ? 'course_id' : ($type == 'careers' ? 'career_id' : 'model_id')))]; ?>">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <a href="dashboard.php" class="btn btn-back">Back to Dashboard</a>
    </div>
</body>
</html>