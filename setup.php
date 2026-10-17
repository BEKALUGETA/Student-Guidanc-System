<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if admin exists
$stmt = $db->query("SELECT * FROM users WHERE username = 'superadmin'");

if($stmt->rowCount() > 0) {
    $db->exec("DELETE FROM users WHERE username = 'superadmin'");
}

$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$query = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
$stmt = $db->prepare($query);
$stmt->execute(['superadmin', 'superadmin@guidance.com', $hashed_password, 'Super Administrator', 'admin']);

echo '<div style="max-width: 600px; margin: 50px auto; padding: 20px; background: #f0f0f0; border-radius: 10px; font-family: Arial;">';
echo '<h2 style="color: green;">✓ Setup Complete!</h2>';
echo '<p><strong>Super Admin created successfully!</strong></p>';
echo '<p>Username: <strong>superadmin</strong></p>';
echo '<p>Password: <strong>admin123</strong></p>';
echo '<p style="color: red;"><strong>Important:</strong> Delete this file (setup.php) after use!</p>';
echo '<p><a href="login.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Go to Login Page</a></p>';
echo '</div>';
?>