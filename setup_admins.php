<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$db->exec("DELETE FROM users WHERE role != 'student'");
$db->exec("DELETE FROM admin_permissions");

$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$admins = [
    ['superadmin', 'superadmin@guidance.com', 'Super Administrator', 'admin', null],
    ['ee_admin', 'ee_admin@guidance.com', 'EE Department Admin', 'department_admin', 'EE'],
    ['cs_admin', 'cs_admin@guidance.com', 'CS Department Admin', 'department_admin', 'CS'],
    ['is_admin', 'is_admin@guidance.com', 'IS Department Admin', 'department_admin', 'IS'],
    ['sw_admin', 'sw_admin@guidance.com', 'SW Department Admin', 'department_admin', 'SW']
];

foreach($admins as $admin) {
    $dept_id = null;
    if($admin[4]) {
        $stmt = $db->prepare("SELECT dept_id FROM departments WHERE dept_code = ?");
        $stmt->execute([$admin[4]]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        if($dept) $dept_id = $dept['dept_id'];
    }
    
    $query = "INSERT INTO users (username, email, password, full_name, role, department_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$admin[0], $admin[1], $hashed_password, $admin[2], $admin[3], $dept_id]);
    
    if($admin[3] == 'department_admin') {
        $user_id = $db->lastInsertId();
        $db->prepare("INSERT INTO admin_permissions (user_id) VALUES (?)")->execute([$user_id]);
    }
}

echo '<div style="max-width: 800px; margin: 50px auto; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); font-family: Arial;">';
echo '<h2 style="color: #10b981;">✓ All Admin Accounts Created!</h2>';
echo '<p style="margin: 20px 0;">Password for all accounts: <strong>admin123</strong></p>';
echo '<div style="background: #f9fafb; padding: 20px; border-radius: 10px;">';
echo '<p><strong>Super Admin:</strong> superadmin</p>';
echo '<p><strong>EE Admin:</strong> ee_admin</p>';
echo '<p><strong>CS Admin:</strong> cs_admin</p>';
echo '<p><strong>IS Admin:</strong> is_admin</p>';
echo '<p><strong>SW Admin:</strong> sw_admin</p>';
echo '</div>';
echo '<p style="color: red; margin-top: 20px;"><strong>Important:</strong> Delete this file after use!</p>';
echo '<p><a href="login.php" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 10px;">Go to Login</a></p>';
echo '</div>';
?>