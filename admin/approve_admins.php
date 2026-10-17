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

$success = '';
$error = '';

// Handle approval/rejection
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'];
    
    if($action == 'approve') {
        $stmt = $db->prepare("UPDATE users SET status = 'approved' WHERE user_id = ? AND status = 'pending'");
        if($stmt->execute([$user_id])) {
            // Add permissions for department admins
            $user = $db->prepare("SELECT role FROM users WHERE user_id = ?")->fetch(PDO::FETCH_ASSOC);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user && $user['role'] == 'department_admin') {
                $perm_check = $db->prepare("SELECT permission_id FROM admin_permissions WHERE user_id = ?");
                $perm_check->execute([$user_id]);
                
                if($perm_check->rowCount() == 0) {
                    $db->prepare("INSERT INTO admin_permissions (user_id) VALUES (?)")->execute([$user_id]);
                }
            }
            
            $success = 'Admin approved successfully!';
        }
    } elseif($action == 'reject') {
        $stmt = $db->prepare("UPDATE users SET status = 'rejected' WHERE user_id = ? AND status = 'pending'");
        if($stmt->execute([$user_id])) {
            $success = 'Admin request rejected.';
        }
    }
}

// Get pending admin requests
$pending = $db->query("SELECT u.*, d.dept_name, d.dept_code 
                    FROM users u 
                    LEFT JOIN departments d ON u.department_id = d.dept_id 
                    WHERE u.role IN ('admin', 'department_admin') AND u.status = 'pending' 
                    ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get approved admins
$approved = $db->query("SELECT u.*, d.dept_name, d.dept_code 
                        FROM users u 
                        LEFT JOIN departments d ON u.department_id = d.dept_id 
                        WHERE u.role IN ('admin', 'department_admin') AND u.status = 'approved' 
                        ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get rejected admins
$rejected = $db->query("SELECT u.*, d.dept_name, d.dept_code 
                        FROM users u 
                        LEFT JOIN departments d ON u.department_id = d.dept_id 
                        WHERE u.role IN ('admin', 'department_admin') AND u.status = 'rejected' 
                        ORDER BY u.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Admin Requests - Super Admin</title>
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
        
        .header-card { 
            background: linear-gradient(135deg, #1e3a8a, #3b82f6); 
            color: white; 
            padding: 2rem; 
            border-radius: 15px; 
            margin-bottom: 2rem; 
        }
        
        .card { 
            background: white; 
            padding: 2rem; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 2rem; 
        }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #3b82f6; color: white; padding: 1rem; text-align: left; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        
        .btn { 
            padding: 0.5rem 1rem; 
            border-radius: 5px; 
            border: none; 
            font-weight: 600; 
            cursor: pointer; 
            color: white; 
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-approve { background: #10b981; }
        .btn-reject { background: #ef4444; }
        .btn-back { background: #6b7280; text-decoration: none; display: inline-block; padding: 0.75rem 1.5rem; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .badge { 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.85rem; 
            font-weight: 600; 
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-super { background: #dbeafe; color: #1e40af; }
        .badge-dept { background: #e0e7ff; color: #3730a3; }
        
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e5e7eb; }
        .tab-btn { 
            padding: 0.75rem 1.5rem; 
            border: none; 
            background: none; 
            cursor: pointer; 
            font-weight: 600; 
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: 0.3s;
        }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .empty-state { text-align: center; padding: 3rem; color: #9ca3af; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-brand"><i class="fas fa-crown"></i> Super Admin</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="approve_admins.php">Approve Admins</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="header-card">
            <h1><i class="fas fa-user-check"></i> Admin Approval Management</h1>
            <p>Approve or reject admin registration requests</p>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pending')">
                <i class="fas fa-clock"></i> Pending (<?php echo count($pending); ?>)
            </button>
            <button class="tab-btn" onclick="showTab('approved')">
                <i class="fas fa-check-circle"></i> Approved (<?php echo count($approved); ?>)
            </button>
            <button class="tab-btn" onclick="showTab('rejected')">
                <i class="fas fa-times-circle"></i> Rejected
            </button>
        </div>
        
        <!-- Pending Requests -->
        <div id="pending" class="tab-content active">
            <div class="card">
                <h3>Pending Admin Requests</h3>
                <?php if(empty($pending)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No pending admin requests</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Admin Type</th>
                                <th>Department</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending as $admin): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $admin['role'] == 'admin' ? 'badge-super' : 'badge-dept'; ?>">
                                        <?php echo $admin['role'] == 'admin' ? 'Super Admin' : 'Dept Admin'; ?>
                                    </span>
                                </td>
                                <td><?php echo $admin['dept_name'] ? htmlspecialchars($admin['dept_name']) : 'All'; ?></td>
                                <td><?php echo htmlspecialchars(substr($admin['interests'] ?? '', 0, 50)); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $admin['user_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this admin?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $admin['user_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this request?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Approved Admins -->
        <div id="approved" class="tab-content">
            <div class="card">
                <h3>Approved Admins</h3>
                <?php if(empty($approved)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No approved admins yet</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Admin Type</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($approved as $admin): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $admin['role'] == 'admin' ? 'badge-super' : 'badge-dept'; ?>">
                                        <?php echo $admin['role'] == 'admin' ? 'Super Admin' : 'Dept Admin'; ?>
                                    </span>
                                </td>
                                <td><?php echo $admin['dept_name'] ? htmlspecialchars($admin['dept_name']) : 'All'; ?></td>
                                <td><span class="badge badge-approved">Approved</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Rejected Admins -->
        <div id="rejected" class="tab-content">
            <div class="card">
                <h3>Rejected Requests</h3>
                <?php if(empty($rejected)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ban"></i>
                        <p>No rejected requests</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Admin Type</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rejected as $admin): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $admin['role'] == 'admin' ? 'badge-super' : 'badge-dept'; ?>">
                                        <?php echo $admin['role'] == 'admin' ? 'Super Admin' : 'Dept Admin'; ?>
                                    </span>
                                </td>
                                <td><?php echo $admin['dept_name'] ? htmlspecialchars($admin['dept_name']) : 'All'; ?></td>
                                <td><span class="badge badge-rejected">Rejected</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    
    <script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }
    </script>
</body>
</html>