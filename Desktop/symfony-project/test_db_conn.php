<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=mentis", "root", "");
    echo "Connection successful!\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch()) {
        echo $row[0] . "\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
