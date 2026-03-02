<?php
/**
 * Clear all data from database tables (keeps table structure)
 */

require_once 'config.php';

require_admin();

ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE expense_shares");
        $pdo->exec("TRUNCATE TABLE advance_payments");
        $pdo->exec("TRUNCATE TABLE expenses");
        $pdo->exec("TRUNCATE TABLE categories");
        $pdo->exec("TRUNCATE TABLE members");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $_SESSION['clear_success'] = true;
    } catch (PDOException $e) {
        $_SESSION['clear_error'] = $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

header('Location: index.php');
exit;
