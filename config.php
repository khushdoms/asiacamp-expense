<?php
/**
 * Database configuration - PDO connection for Asia WordCamp 2026 Expense Manager
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'asiacamp_expenses');
define('DB_USER', 'root');
define('DB_PASS', '');  // Change if your MySQL has a password
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Ensure advance_payments table exists (for existing installs)
    $pdo->exec("CREATE TABLE IF NOT EXISTS advance_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        date DATE NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        INDEX idx_member_id (member_id),
        INDEX idx_date (date)
    )");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
