<?php
/**
 * Add new expense: divide total_amount equally among selected members (or all).
 * Saves one row in expenses + N rows in expense_shares.
 */

require_once 'config.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

ensure_session_started();

$paidByMemberId = (int) ($_POST['paid_by_member_id'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$totalAmount = (float) ($_POST['total_amount'] ?? 0);
$description = trim($_POST['description'] ?? '');
$date = trim($_POST['date'] ?? date('Y-m-d'));
$expenseForAll = isset($_POST['expense_for_all']);
$memberIds = isset($_POST['member_ids']) && is_array($_POST['member_ids'])
    ? array_map('intval', array_filter($_POST['member_ids'])) : [];

$errors = [];
if ($paidByMemberId <= 0) {
    $errors[] = 'Please select who paid.';
}
if ($categoryId <= 0) {
    $errors[] = 'Please select a category.';
}
if ($totalAmount <= 0) {
    $errors[] = 'Total amount must be greater than 0.';
}
if ($description === '') {
    $errors[] = 'Description is required.';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// Resolve member list: all members or selected
if ($expenseForAll) {
    $stmt = $pdo->query("SELECT id FROM members ORDER BY name");
    $memberIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
}
if (empty($memberIds)) {
    $errors[] = 'Please select "All Members" or at least one member for the expense.';
}

if (!empty($errors)) {
    $_SESSION['expense_errors'] = $errors;
    $_SESSION['expense_form'] = $_POST;
    header('Location: index.php');
    exit;
}

$shareCount = count($memberIds);
$shareAmount = round($totalAmount / $shareCount, 2);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO expenses (paid_by_member_id, category_id, total_amount, description, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$paidByMemberId, $categoryId, $totalAmount, $description, $date]);
    $expenseId = (int) $pdo->lastInsertId();

    $stmtShare = $pdo->prepare("INSERT INTO expense_shares (expense_id, member_id, share_amount) VALUES (?, ?, ?)");
    foreach ($memberIds as $mid) {
        $stmtShare->execute([$expenseId, $mid, $shareAmount]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['expense_errors'] = ['Could not save expense. Please try again.'];
    $_SESSION['expense_form'] = $_POST;
}

header('Location: index.php');
exit;
