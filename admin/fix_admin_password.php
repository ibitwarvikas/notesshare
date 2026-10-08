<?php
require_once __DIR__ . '/../config.php';

$username = 'admin';
$password = 'admin123';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin' WHERE username = ?");
$stmt->execute([$hash, $username]);

echo "<div style='font-family:Arial;padding:30px;background:#f0f0f0'>";
echo "<div style='max-width:600px;margin:0 auto;background:#fff;padding:30px;border-radius:12px'>";

if ($stmt->rowCount() > 0) {
    echo "<h1 style='color:green'>✅ Password Updated!</h1>";
    echo "<p style='font-size:18px'><b>Username:</b> admin</p>";
    echo "<p style='font-size:18px'><b>Password:</b> admin123</p>";
    echo "<hr>";
    echo "<a href='login.php' style='display:inline-block;background:#f59e0b;color:#fff;padding:14px 30px;text-decoration:none;border-radius:8px;font-weight:bold'>→ Admin Login</a>";
    echo "<div style='background:#fff3cd;padding:15px;border-radius:8px;margin-top:20px'><b>⚠️ Delete this file after login!</b></div>";
} else {
    echo "<h1 style='color:red'>❌ User 'admin' not found!</h1>";
    echo "<p>Pehle admin user banao.</p>";
}

echo "</div></div>";