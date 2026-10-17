<?php
include 'includes/header.php';
require_once 'config/database.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $admin_type = $_POST['admin_type']; // 'department_admin' or request for specific role
    $department_code = $_POST['department_code'] ?? null;
    $reason = trim($_POST['reason'] ?? '');
    
    // Validation
    if(empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required.';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif(strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif(empty($reason)) {
        $error = 'Please provide a reason for admin access request.';
    } else {
        // Check if username or email exists
        $query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email]);
        
        if($stmt->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Get department ID if department admin
            $dept_id = null;
            if($admin_type == 'department_admin' && $department_code) {
                $dept_stmt = $db->prepare("SELECT dept_id FROM departments WHERE dept_code = ?");
                $dept_stmt->execute([$department_code]);
                $dept = $dept_stmt->fetch(PDO::FETCH_ASSOC);
                if($dept) {
                    $dept_id = $dept['dept_id'];
                }
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert as pending admin
            $role = ($admin_type == 'super_admin') ? 'admin' : 'department_admin';
            
            $query = "INSERT INTO users (username, email, password, full_name, role, department_id, status, interests) 
                      VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$username, $email, $hashed_password, $full_name, $role, $dept_id, $reason])) {
                $success = 'Your admin account request has been submitted! Please wait for superadmin approval.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

// Get departments for dropdown
$database = new Database();
$db = $database->getConnection();
$dept_stmt = $db->query("SELECT * FROM departments ORDER BY dept_name");
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .register-container {
        max-width: 600px;
        margin: 2rem auto;
        background: white;
        padding: 2.5rem;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .register-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .register-header i {
        font-size: 3rem;
        color: #3b82f6;
        margin-bottom: 1rem;
    }
    
    .register-header h2 {
        color: #1f2937;
        font-size: 1.8rem;
    }
    
    .register-header p {
        color: #6b7280;
        margin-top: 0.5rem;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
    }
    
    .form-group label i {
        width: 20px;
        color: #3b82f6;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .btn-register {
        width: 100%;
        padding: 0.875rem;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-register:hover {
        background: #2563eb;
        transform: translateY(-2px);
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }
    
    .info-box {
        background: #eff6ff;
        border: 1px solid #93c5fd;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .info-box i {
        color: #3b82f6;
        margin-right: 0.5rem;
    }
    
    .login-link {
        text-align: center;
        margin-top: 1.5rem;
    }
    
    .login-link a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
    }
    
    .login-link a:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="register-container">
    <div class="register-header">
        <i class="fas fa-user-shield"></i>
        <h2>Admin Registration</h2>
        <p>Request admin access to manage department content</p>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <div class="login-link">
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
        </div>
    <?php else: ?>
    
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <strong>Note:</strong> All admin registrations require approval from the Super Admin before access is granted.
    </div>
    
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name</label>
                <input type="text" name="full_name" required 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                       placeholder="John Doe">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-at"></i> Username</label>
                <input type="text" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="johndoe">
            </div>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Address</label>
            <input type="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                   placeholder="john@example.com">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required 
                       placeholder="Minimum 8 characters" minlength="8">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" name="confirm_password" required 
                       placeholder="Re-enter password">
            </div>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-user-tag"></i> Admin Type</label>
            <select name="admin_type" id="adminType" required onchange="toggleDepartment()">
                <option value="">Select admin type</option>
                <option value="department_admin" <?php echo (isset($_POST['admin_type']) && $_POST['admin_type'] == 'department_admin') ? 'selected' : ''; ?>>
                    Department Admin
                </option>
                <option value="super_admin" <?php echo (isset($_POST['admin_type']) && $_POST['admin_type'] == 'super_admin') ? 'selected' : ''; ?>>
                    Super Admin
                </option>
            </select>
        </div>
        
        <div class="form-group" id="departmentGroup" style="display: none;">
            <label><i class="fas fa-building"></i> Department</label>
            <select name="department_code">
                <option value="">Select Department</option>
                <?php foreach($departments as $dept): ?>
                <option value="<?php echo $dept['dept_code']; ?>"
                    <?php echo (isset($_POST['department_code']) && $_POST['department_code'] == $dept['dept_code']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept['dept_name']); ?> (<?php echo $dept['dept_code']; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-comment"></i> Reason for Admin Access</label>
            <textarea name="reason" required 
                      placeholder="Please explain why you need admin access and your qualifications..."><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
        </div>
        
        <button type="submit" class="btn-register">
            <i class="fas fa-paper-plane"></i> Submit Registration Request
        </button>
    </form>
    
    <div class="login-link">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleDepartment() {
    const adminType = document.getElementById('adminType').value;
    const deptGroup = document.getElementById('departmentGroup');
    
    if(adminType === 'department_admin') {
        deptGroup.style.display = 'block';
    } else {
        deptGroup.style.display = 'none';
    }
}

// Check on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDepartment();
});
</script>

<?php include 'includes/footer.php'; ?>