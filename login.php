<?php
include 'includes/header.php';
require_once 'config/database.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $query = "SELECT u.*, d.dept_code, d.dept_name 
                  FROM users u 
                  LEFT JOIN departments d ON u.department_id = d.dept_id 
                  WHERE u.username = ? OR u.email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $username]);
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if account is approved (for admin accounts)
            if(($user['role'] == 'admin' || $user['role'] == 'department_admin') && $user['status'] == 'pending') {
                $error = 'Your admin account is pending approval. Please wait for superadmin approval.';
            } elseif(($user['role'] == 'admin' || $user['role'] == 'department_admin') && $user['status'] == 'rejected') {
                $error = 'Your admin registration has been rejected. Please contact superadmin for more information.';
            } elseif(password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['department_id'] = $user['department_id'];
                $_SESSION['department_code'] = $user['dept_code'];
                $_SESSION['department_name'] = $user['dept_name'];
                $_SESSION['status'] = $user['status'];
                
                // Redirect based on role
                if($user['role'] == 'admin') {
                    header('Location: admin/dashboard.php');
                    exit();
                } elseif($user['role'] == 'department_admin') {
                    header('Location: admin/department_dashboard.php');
                    exit();
                } else {
                    header('Location: index.php');
                    exit();
                }
            } else {
                $error = 'Invalid password. Please try again.';
            }
        } else {
            $error = 'Username not found.';
        }
    }
}
?>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 2rem;">
        <i class="fas fa-sign-in-alt"></i> Login
    </h2>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success">Registration successful! Please login with your credentials.</div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                   placeholder="Enter your username">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required 
                   placeholder="Enter your password">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <p style="text-align: center; margin-top: 0.5rem;">
    <a href="forgot_password.php" style="color: #ef4444;">
        <i class="fas fa-key"></i> Forgot Password?
    </a>
</p>
    
    <p style="text-align: center; margin-top: 1.5rem;">
        Don't have an account? <a href="register.php">Register as Student</a>
    </p>
    
    <p style="text-align: center; margin-top: 0.5rem;">
        <a href="admin_register.php" style="color: #3b82f6; text-decoration: none;">
            <i class="fas fa-user-shield"></i> Register as Admin
        </a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>