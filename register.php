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
    
    if(empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required.';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email]);
        
        if($stmt->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                $success = 'Registration successful!';
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 2rem;">
        <i class="fas fa-user-plus"></i> Create Account
    </h2>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required>
        </div>
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-user-plus"></i> Register
        </button>
    </form>
    
    <p style="text-align: center; margin-top: 1rem;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>