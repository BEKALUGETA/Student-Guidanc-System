<?php
include 'includes/header.php';
require_once 'config/database.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    
    if(empty($email) && empty($username)) {
        $error = 'Please enter your email or username.';
    } else {
        // Find user
        $query = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email, $username]);
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Create password reset request
            $insert = $db->prepare("INSERT INTO password_reset_requests (user_id, requested_by, notes) VALUES (?, ?, ?)");
            $insert->execute([$user['user_id'], $user['full_name'], 'Password reset requested by user']);
            
            $success = 'Password reset request submitted! Please contact the super admin to complete the reset.';
            $success .= '<br><strong>Your Username:</strong> ' . htmlspecialchars($user['username']);
            $success .= '<br><strong>Your User ID:</strong> ' . $user['user_id'];
        } else {
            $error = 'No account found with that email or username.';
        }
    }
}
?>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 2rem;">
        <i class="fas fa-key"></i> Forgot Password
    </h2>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
    
    <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;">
        <p><i class="fas fa-info-circle" style="color: #3b82f6;"></i> 
        <strong>Forgot your password?</strong> Enter your email or username below. 
        A password reset request will be sent to the super admin who will help you reset your password.</p>
    </div>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your registered email">
        </div>
        
        <div style="text-align: center; margin: 1rem 0; color: #6b7280;">— OR —</div>
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username">
        </div>
        
        <button type="submit" class="btn btn-solid-primary" style="width: 100%;">
            <i class="fas fa-paper-plane"></i> Submit Reset Request
        </button>
    </form>
    <?php endif; ?>
    
    <p style="text-align: center; margin-top: 1.5rem;">
        <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>