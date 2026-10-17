<?php
session_start();
require_once '../config/database.php';

// Only allow admin (super admin) to access this page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$user_count = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$dept_count = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$course_count = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$career_count = $db->query("SELECT COUNT(*) FROM careers")->fetchColumn();
$model_count = $db->query("SELECT COUNT(*) FROM role_models")->fetchColumn();
$rec_count = $db->query("SELECT COUNT(*) FROM recommendations")->fetchColumn();
$dept_admin_count = $db->query("SELECT COUNT(*) FROM users WHERE role = 'department_admin'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - SDGS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f3f4f6; }
        
        .navbar { background: #1e3a8a; padding: 1rem 2rem; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .nav-brand { color: white; text-decoration: none; font-size: 1.3rem; font-weight: bold; }
        .nav-brand i { color: #fbbf24; }
        .nav-menu { display: flex; list-style: none; gap: 1rem; }
        .nav-menu a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px; transition: 0.3s; }
        .nav-menu a:hover { background: rgba(255,255,255,0.2); }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        
        .header-card { 
            background: linear-gradient(135deg, #1e3a8a, #3b82f6); 
            color: white; 
            padding: 2.5rem; 
            border-radius: 15px; 
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .header-card h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .header-card .crown { font-size: 3rem; color: #fbbf24; }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2rem; 
        }
        .stat-card { 
            background: white; 
            padding: 1.5rem; 
            border-radius: 10px; 
            text-align: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .stat-card h3 { font-size: 2rem; color: #1f2937; margin-bottom: 0.25rem; }
        .stat-card p { color: #6b7280; font-size: 0.9rem; }
        
        .icon-blue { color: #3b82f6; }
        .icon-green { color: #10b981; }
        .icon-yellow { color: #f59e0b; }
        .icon-red { color: #ef4444; }
        .icon-purple { color: #8b5cf6; }
        .icon-cyan { color: #06b6d4; }
        .icon-orange { color: #f97316; }
        
        .section-title { 
            font-size: 1.5rem; 
            color: #1f2937; 
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #3b82f6;
        }
        
        .actions-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2rem; 
        }
        .action-card { 
            background: white; 
            padding: 2rem; 
            border-radius: 10px; 
            text-align: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .action-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #3b82f6;
        }
        .action-card .icon { font-size: 3rem; margin-bottom: 1rem; }
        .action-card h3 { margin-bottom: 0.5rem; color: #1f2937; font-size: 1.3rem; }
        .action-card p { color: #6b7280; margin-bottom: 1.5rem; font-size: 0.9rem; }
        
        .btn { 
            display: inline-block; 
            padding: 0.75rem 1.5rem; 
            border-radius: 8px; 
            border: none; 
            font-size: 1rem; 
            font-weight: 600; 
            text-decoration: none; 
            cursor: pointer; 
            color: white;
            transition: all 0.3s; 
        }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn-dept { background: #10b981; }
        .btn-course { background: #f59e0b; }
        .btn-career { background: #06b6d4; }
        .btn-model { background: #8b5cf6; }
        .btn-user { background: #f97316; }
        .btn-back { background: #6b7280; }
        .btn-view { background: #3b82f6; }
        
        .dept-admin-list {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th { background: #3b82f6; color: white; padding: 1rem; text-align: left; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-brand">
                <i class="fas fa-crown"></i> Super Admin Panel
            </a>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> View Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <li><a href="manage_passwords.php"><i class="fas fa-key"></i> Manage Passwords</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="header-card">
            <div class="crown">👑</div>
            <h1>Super Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! You have full control over the entire system.</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon icon-blue"><i class="fas fa-users"></i></div>
                <h3><?php echo $user_count; ?></h3>
                <p>Students</p>
            </div>
            <div class="stat-card">
                <div class="icon icon-green"><i class="fas fa-building"></i></div>
                <h3><?php echo $dept_count; ?></h3>
                <p>Departments</p>
            </div>
            <div class="stat-card">
                <div class="icon icon-yellow"><i class="fas fa-book"></i></div>
                <h3><?php echo $course_count; ?></h3>
                <p>Courses</p>
            </div>
            <div class="stat-card">
                <div class="icon icon-red"><i class="fas fa-briefcase"></i></div>
                <h3><?php echo $career_count; ?></h3>
                <p>Careers</p>
            </div>
            <div class="stat-card">
                <div class="icon icon-purple"><i class="fas fa-star"></i></div>
                <h3><?php echo $model_count; ?></h3>
                <p>Role Models</p>
            </div>
            <div class="stat-card">
                <div class="icon icon-cyan"><i class="fas fa-lightbulb"></i></div>
                <h3><?php echo $rec_count; ?></h3>
                <p>Recommendations</p>
            </div>
            <div class="stat-card">
                <div class="icon icon-orange"><i class="fas fa-user-shield"></i></div>
                <h3><?php echo $dept_admin_count; ?></h3>
                <p>Dept Admins</p>
            </div>
        </div>
        
        <!-- Management Actions -->
        <h2 class="section-title"><i class="fas fa-cogs"></i> System Management</h2>
        <div class="actions-grid">
            <div class="action-card">
                <div class="icon icon-green"><i class="fas fa-building"></i></div>
                <h3>Manage Departments</h3>
                <p>Add, edit, or delete academic departments in the system.</p>
                <a href="manage.php?type=departments" class="btn btn-dept">
                    <i class="fas fa-edit"></i> Manage Departments
                </a>
            </div>

            
            
            <div class="action-card">
                <div class="icon icon-yellow"><i class="fas fa-book"></i></div>
                <h3>Manage All Courses</h3>
                <p>Manage course titles and descriptions for all departments.</p>
                <a href="manage.php?type=courses" class="btn btn-course">
                    <i class="fas fa-edit"></i> Manage Courses
                </a>
            </div>
            
            <div class="action-card">
                <div class="icon icon-cyan"><i class="fas fa-briefcase"></i></div>
                <h3>Manage All Careers</h3>
                <p>Update career paths and job opportunities system-wide.</p>
                <a href="manage.php?type=careers" class="btn btn-career">
                    <i class="fas fa-edit"></i> Manage Careers
                </a>
            </div>
            
            <div class="action-card">
                <div class="icon icon-purple"><i class="fas fa-star"></i></div>
                <h3>Manage Role Models</h3>
                <p>Add or update role models across all departments.</p>
                <a href="manage.php?type=models" class="btn btn-model">
                    <i class="fas fa-edit"></i> Manage Models
                </a>
            </div>
            
            <div class="action-card">
                <div class="icon icon-orange"><i class="fas fa-users-cog"></i></div>
                <h3>Manage Users</h3>
                <p>View and manage all user accounts and admin permissions.</p>
                <a href="manage.php?type=users" class="btn btn-user">
                    <i class="fas fa-edit"></i> Manage Users
                </a>
            </div>

            <div class="action-card">
                <div class="icon" style="color:#8b5cf6;"><i class="fas fa-key"></i></div>
                <h3>Manage Passwords</h3>
                <p>Reset passwords for users who forgot their credentials</p>
                <a href="manage_passwords.php" class="btn btn-model">
                    <i class="fas fa-key"></i> Manage Passwords
                </a>
            </div>

            <div class="action-card">
                <div class="icon icon-green"><i class="fas fa-user-check"></i></div>
                <h3>Approve Admin Requests</h3>
                <p>Review and approve new admin registration requests.</p>
                <a href="approve_admins.php" class="btn btn-dept">
                    <i class="fas fa-check-circle"></i> Manage Requests
                </a>
            </div>
        </div>
        
        <!-- Department Admins List -->
        <div class="dept-admin-list">
            <h3><i class="fas fa-user-shield"></i> Department Admins</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $admins = $db->query("SELECT u.*, d.dept_name, d.dept_code 
                                         FROM users u 
                                         LEFT JOIN departments d ON u.department_id = d.dept_id 
                                         WHERE u.role = 'department_admin' 
                                         ORDER BY d.dept_name")->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach($admins as $admin):
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                        <td>
                            <?php if($admin['dept_name']): ?>
                                <span style="color: #3b82f6;"><?php echo htmlspecialchars($admin['dept_name']); ?></span>
                                (<?php echo htmlspecialchars($admin['dept_code']); ?>)
                            <?php else: ?>
                                <span style="color: #ef4444;">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($admin['dept_code']): ?>
                            <a href="department_dashboard.php?dept=<?php echo $admin['dept_code']; ?>" 
                               style="color: #3b82f6; text-decoration: none;">
                                <i class="fas fa-eye"></i> View Dashboard
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($admins)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2rem; color: #9ca3af;">
                            No department admins found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="../index.php" class="btn btn-view" style="margin-right: 1rem;">
                <i class="fas fa-home"></i> View Main Website
            </a>
            <a href="../logout.php" class="btn btn-back">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
</body>
</html>