<?php
/**
 * Delete a category (admin only). Fails if expenses reference it (RESTRICT).
 */

require_once 'config.php';

require_admin();

ensure_session_started();

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['category_delete_success'] = true;
        }
    } catch (PDOException $e) {
        $_SESSION['category_delete_error'] = 'Cannot delete: category is used by one or more expenses.';
    }
}

header('Location: add_category.php');
exit;
