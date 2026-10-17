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
$active_tab = 'requests'; // requests, all_users

// Handle password reset
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Reset password for specific user
    if($action == 'reset_password') {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if(empty($new_password) || strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Get user info
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                // Update password
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                
                if($update->execute([$hashed, $user_id])) {
                    // Mark reset request as completed if exists
                    $db->prepare("UPDATE password_reset_requests SET status = 'completed', completed_at = NOW() WHERE user_id = ? AND status = 'pending'")->execute([$user_id]);
                    
                    $success = 'Password reset successfully for <strong>' . htmlspecialchars($user['full_name']) . '</strong> (Username: ' . htmlspecialchars($user['username']) . ')!';
                    $success .= '<br><strong>New Password:</strong> <code>' . htmlspecialchars($new_password) . '</code>';
                } else {
                    $error = 'Failed to reset password.';
                }
            } else {
                $error = 'User not found.';
            }
        }
    }
    
    // Mark request as completed/rejected
    if($action == 'mark_request') {
        $request_id = $_POST['request_id'];
        $status = $_POST['status']; // completed or rejected
        
        $db->prepare("UPDATE password_reset_requests SET status = ?, completed_at = NOW() WHERE request_id = ?")->execute([$status, $request_id]);
        $success = 'Request marked as ' . $status . '.';
    }
    
    if(isset($_POST['tab'])) {
        $active_tab = $_POST['tab'];
    }
}

// Get pending password reset requests
$pending_requests = $db->query("SELECT pr.*, u.username, u.full_name, u.email, u.role, u.status as user_status, d.dept_name 
    FROM password_reset_requests pr 
    JOIN users u ON pr.user_id = u.user_id 
    LEFT JOIN departments d ON u.department_id = d.dept_id 
    WHERE pr.status = 'pending' 
    ORDER BY pr.created_at DESC")->fetchAll();

// Get completed requests
$completed_requests = $db->query("SELECT pr.*, u.username, u.full_name, u.email, u.role, d.dept_name 
    FROM password_reset_requests pr 
    JOIN users u ON pr.user_id = u.user_id 
    LEFT JOIN departments d ON u.department_id = d.dept_id 
    WHERE pr.status != 'pending' 
    ORDER BY pr.completed_at DESC LIMIT 20")->fetchAll();

// Get all users
$all_users = $db->query("SELECT u.*, d.dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.dept_id ORDER BY u.role, u.username")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Passwords - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', Arial, sans-serif; background: #f3f4f6; }
        
        .navbar { background: #1e3a8a; padding: 1rem 2rem; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .nav-brand { color: white; text-decoration: none; font-size: 1.3rem; font-weight: bold; display: flex; align-items: center; gap: 0.5rem; }
        .nav-brand i { color: #fbbf24; }
        .nav-menu { display: flex; list-style: none; gap: 1rem; }
        .nav-menu a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px; transition: 0.3s; }
        .nav-menu a:hover { background: rgba(255,255,255,0.2); }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        
        .header-card { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; }
        .header-card h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        
        .tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid #e5e7eb; }
        .tab-btn { padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #6b7280; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: 0.3s; }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th { background: #3b82f6; color: white; padding: 1rem; text-align: left; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 5px; border: none; font-weight: 600; cursor: pointer; color: white; text-decoration: none; transition: 0.3s; font-size: 0.9rem; }
        .btn-primary { background: #3b82f6; }
        .btn-success { background: #10b981; }
        .btn-warning { background: #f59e0b; }
        .btn-danger { background: #ef4444; }
        .btn-back { background: #6b7280; padding: 0.75rem 1.5rem; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-admin { background: #ede9fe; color: #5b21b6; }
        .badge-student { background: #dbeafe; color: #1e40af; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-content h3 { margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 5px; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #3b82f6; }
        .modal-actions { display: flex; gap: 1rem; margin-top: 1.5rem; justify-content: flex-end; }
        
        .password-strength { height: 4px; border-radius: 2px; margin-top: 0.25rem; transition: 0.3s; }
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-medium { background: #f59e0b; width: 50%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        .empty-state { text-align: center; padding: 3rem; color: #9ca3af; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-brand"><i class="fas fa-crown"></i> Super Admin Panel</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="approve_admins.php"><i class="fas fa-user-check"></i> Approve Admins</a></li>
                <li><a href="manage_passwords.php"><i class="fas fa-key"></i> Manage Passwords</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> View Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="header-card">
            <h1><i class="fas fa-key"></i> Password Management</h1>
            <p>Reset passwords for users who have forgotten their credentials</p>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('requests')">
                <i class="fas fa-clock"></i> Reset Requests 
                <?php if(count($pending_requests) > 0): ?>
                    <span style="background:#ef4444;color:white;padding:2px 8px;border-radius:10px;margin-left:5px;"><?php echo count($pending_requests); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn" onclick="switchTab('history')">
                <i class="fas fa-history"></i> Request History
            </button>
            <button class="tab-btn" onclick="switchTab('all_users')">
                <i class="fas fa-users"></i> All Users
            </button>
        </div>
        
        <!-- Pending Requests Tab -->
        <div id="requests" class="tab-content active">
            <div class="card">
                <h3><i class="fas fa-clock"></i> Pending Password Reset Requests</h3>
                
                <?php if(empty($pending_requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color:#10b981;"></i>
                        <p>No pending password reset requests</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_requests as $req): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($req['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['username']); ?></td>
                                <td><?php echo htmlspecialchars($req['email']); ?></td>
                                <td><span class="badge <?php echo $req['role']=='student'?'badge-student':'badge-admin'; ?>"><?php echo ucfirst(str_replace('_',' ',$req['role'])); ?></span></td>
                                <td><?php echo $req['dept_name'] ? htmlspecialchars($req['dept_name']) : 'N/A'; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-success" onclick="openResetModal(<?php echo $req['user_id']; ?>, '<?php echo htmlspecialchars($req['full_name']); ?>', '<?php echo htmlspecialchars($req['username']); ?>', <?php echo $req['request_id']; ?>)">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="mark_request">
                                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this request?')">
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
        
        <!-- History Tab -->
        <div id="history" class="tab-content">
            <div class="card">
                <h3><i class="fas fa-history"></i> Request History</h3>
                
                <?php if(empty($completed_requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No completed requests yet</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($completed_requests as $req): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($req['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['username']); ?></td>
                                <td><span class="badge <?php echo $req['role']=='student'?'badge-student':'badge-admin'; ?>"><?php echo ucfirst(str_replace('_',' ',$req['role'])); ?></span></td>
                                <td><span class="badge badge-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                <td><?php echo $req['completed_at'] ? date('M d, Y H:i', strtotime($req['completed_at'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- All Users Tab -->
        <div id="all_users" class="tab-content">
            <div class="card">
                <h3><i class="fas fa-users"></i> All Users - Direct Password Reset</h3>
                
                <div style="margin-bottom: 1rem;">
                    <input type="text" id="userSearch" placeholder="🔍 Search users by name or username..." 
                           style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem;"
                           onkeyup="filterUsers()">
                </div>
                
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_users as $user): ?>
                        <tr class="user-row" data-search="<?php echo strtolower($user['username'] . ' ' . $user['full_name'] . ' ' . $user['email']); ?>">
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge <?php echo $user['role']=='student'?'badge-student':'badge-admin'; ?>"><?php echo ucfirst(str_replace('_',' ',$user['role'])); ?></span></td>
                            <td><?php echo $user['dept_name'] ? htmlspecialchars($user['dept_name']) : '-'; ?></td>
                            <td><span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                            <td>
                                <button class="btn btn-warning" onclick="openResetModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo htmlspecialchars($user['username']); ?>', 0)">
                                    <i class="fas fa-key"></i> Reset
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
    
    <!-- Password Reset Modal -->
    <div class="modal" id="resetModal">
        <div class="modal-content">
            <h3><i class="fas fa-key"></i> Reset Password</h3>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                Resetting password for: <strong id="modalUserName"></strong><br>
                Username: <strong id="modalUserUsername"></strong>
            </p>
            
            <form method="POST" id="resetForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="request_id" id="modalRequestId">
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="text" name="new_password" id="newPassword" required minlength="6" 
                           placeholder="Enter new password (min 6 characters)"
                           oninput="checkPasswordStrength()">
                    <div class="password-strength" id="passwordStrength"></div>
                    <small style="color: #6b7280;">Password must be at least 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="text" name="confirm_password" id="confirmPassword" required 
                           placeholder="Re-enter new password"
                           oninput="checkPasswordMatch()">
                    <small id="passwordMatchMsg" style="color: #ef4444; display: none;">Passwords do not match!</small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-back" onclick="closeResetModal()">Cancel</button>
                    <button type="submit" class="btn btn-success" id="resetSubmitBtn">
                        <i class="fas fa-check"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }
    
    function openResetModal(userId, fullName, username, requestId) {
        document.getElementById('modalUserId').value = userId;
        document.getElementById('modalRequestId').value = requestId;
        document.getElementById('modalUserName').textContent = fullName;
        document.getElementById('modalUserUsername').textContent = username;
        document.getElementById('resetModal').classList.add('active');
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        document.getElementById('passwordStrength').className = 'password-strength';
        document.getElementById('passwordMatchMsg').style.display = 'none';
    }
    
    function closeResetModal() {
        document.getElementById('resetModal').classList.remove('active');
    }
    
    function checkPasswordStrength() {
        const pass = document.getElementById('newPassword').value;
        const strength = document.getElementById('passwordStrength');
        
        if(pass.length >= 8 && /[A-Z]/.test(pass) && /[0-9]/.test(pass)) {
            strength.className = 'password-strength strength-strong';
        } else if(pass.length >= 6) {
            strength.className = 'password-strength strength-medium';
        } else {
            strength.className = 'password-strength strength-weak';
        }
    }
    
    function checkPasswordMatch() {
        const pass = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;
        const msg = document.getElementById('passwordMatchMsg');
        
        if(confirm && pass !== confirm) {
            msg.style.display = 'block';
        } else {
            msg.style.display = 'none';
        }
    }
    
    function filterUsers() {
        const search = document.getElementById('userSearch').value.toLowerCase();
        document.querySelectorAll('.user-row').forEach(row => {
            const text = row.getAttribute('data-search');
            row.style.display = text.includes(search) ? '' : 'none';
        });
    }
    
    // Close modal when clicking outside
    document.getElementById('resetModal').addEventListener('click', function(e) {
        if(e.target === this) closeResetModal();
    });
    </script>
</body>
</html>