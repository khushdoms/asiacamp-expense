<?php
/**
 * Delete a member (admin only). Cascades related rows.
 */

require_once 'config.php';

require_admin();

ensure_session_started();

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT is_admin FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM members WHERE is_admin = 1")->fetchColumn();
        if (!empty($row['is_admin']) && $adminCount <= 1) {
            $_SESSION['member_delete_error'] = 'Cannot delete the only admin.';
        } else {
            try {
                $del = $pdo->prepare("DELETE FROM members WHERE id = ?");
                $del->execute([$id]);
                $_SESSION['member_delete_success'] = true;
            } catch (PDOException $e) {
                $_SESSION['member_delete_error'] = 'Could not delete member.';
            }
        }
    }
}

header('Location: add_member.php');
exit;
