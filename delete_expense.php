<?php
/**
 * Delete an expense by ID. Cascade removes related expense_shares.
 * Redirect to dashboard so Member-wise Settlement recalculates from updated data.
 */

require_once 'config.php';

require_admin();

ensure_session_started();

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        // Deleting the expense automatically deletes its expense_shares (ON DELETE CASCADE).
        // Dashboard recalculates Total Paid from expenses and Total Share from expense_shares,
        // so Member-wise Settlement will reflect the deletion after redirect.
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['delete_success'] = true;
        }
    } catch (PDOException $e) {
        $_SESSION['delete_error'] = 'Could not delete expense.';
    }
}

header('Location: index.php#settlement');
exit;
