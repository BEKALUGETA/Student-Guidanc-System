<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Department Guidance System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">
                <i class="fas fa-graduation-cap"></i>
                SDGS
            </a>
            
            <div class="nav-toggle" id="navToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="departments.php"><i class="fas fa-building"></i> Departments</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    
                    <?php if($_SESSION['role'] == 'student'): ?>
                        <li><a href="recommendation.php"><i class="fas fa-lightbulb"></i> Get Recommendation</a></li>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a></li>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == 'department_admin'): ?>
                        <li><a href="admin/department_dashboard.php"><i class="fas fa-cog"></i> Dept Admin</a></li>
                    <?php endif; ?>
                    
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout 
                        (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a></li>
                        
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <div class="container">