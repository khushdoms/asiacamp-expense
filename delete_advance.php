<?php
/**
 * Delete one advance batch: all rows with same date + description (admin pool + member credits).
 */

require_once 'config.php';

require_admin();

ensure_session_started();

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $admin = $pdo->query("SELECT id FROM members WHERE is_admin = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $adminId = (int) $admin['id'];
        $stmt = $pdo->prepare("SELECT date, description, member_id FROM advance_payments WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int) $row['member_id'] === $adminId) {
            try {
                $del = $pdo->prepare("DELETE FROM advance_payments WHERE date = ? AND description <=> ?");
                $del->execute([$row['date'], $row['description']]);
                $_SESSION['delete_success'] = true;
            } catch (PDOException $e) {
                $_SESSION['delete_error'] = 'Could not delete advance entry.';
            }
        }
    }
}

header('Location: index.php#settlement');
exit;
