<?php

// Create a bcrypt hash of the password
$plainPassword = 'admin123456';
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 4]);

echo "Password hash: " . $hashedPassword . "\n";

// Get database connection
$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'mentis';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert admin user
$sql = "INSERT INTO `user` (firstname, lastname, phone, dateofbirth, type, email, password, created_at)
        VALUES ('Admin', 'User', '0000000000', '2000-01-01', 'Admin', 'admin@mentis.local', ?, NOW())
        ON DUPLICATE KEY UPDATE password = VALUES(password), type = VALUES(type)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $hashedPassword);

if ($stmt->execute()) {
    echo "✅ Admin user created successfully!\n";
    echo "📧 Email: admin@mentis.local\n";
    echo "🔑 Password: admin123456\n";
    echo "\nYou can now login and test the AI Summary feature!\n";
} else {
    echo "❌ Error: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
