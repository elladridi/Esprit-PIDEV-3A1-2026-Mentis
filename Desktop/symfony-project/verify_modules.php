<?php
echo "======== MOOD TRACKER & GOAL MODULE VERIFICATION ========\n\n";

$files = [
    'Entity' => ['src/Entity/Mood.php', 'src/Entity/Goal.php'],
    'Repository' => ['src/Repository/MoodRepository.php', 'src/Repository/GoalRepository.php'],
    'Form' => ['src/Form/MoodType.php', 'src/Form/GoalType.php'],
    'Controller' => ['src/Controller/MoodController.php', 'src/Controller/GoalController.php'],
    'Templates/Mood' => ['templates/mood/index.html.twig', 'templates/mood/new.html.twig', 'templates/mood/edit.html.twig', 'templates/mood/show.html.twig'],
    'Templates/Goal' => ['templates/goal/index.html.twig', 'templates/goal/new.html.twig', 'templates/goal/edit.html.twig', 'templates/goal/show.html.twig'],
];

foreach ($files as $category => $fileList) {
    echo "[$category]\n";
    foreach ($fileList as $file) {
        if (file_exists($file)) {
            echo "  ✓ " . $file . "\n";
        } else {
            echo "  ✗ " . $file . " (MISSING)\n";
        }
    }
    echo "\n";
}

// Check database tables
echo "[Database Tables]\n";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=mentis;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = ['mood', 'goal'];
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'mentis' AND TABLE_NAME = '$table'");
        if ($result->fetch()) {
            echo "  ✓ Table: $table\n";
            
            // Get table structure
            $columns = $pdo->query("DESCRIBE $table");
            $cols = $columns->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                echo "      - " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
        } else {
            echo "  ✗ Table: $table (NOT FOUND)\n";
        }
    }
} catch (PDOException $e) {
    echo "  ✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n======== VERIFICATION COMPLETE ========\n";
?>
