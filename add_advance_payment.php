<?php
/**
 * Add advance payment: amount credited to selected members in settlement
 */

require_once 'config.php';

session_start();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float) ($_POST['amount'] ?? 0);
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $description = trim($_POST['description'] ?? '');
    $applyToAll = isset($_POST['apply_to_all']);
    $memberIds = isset($_POST['member_ids']) && is_array($_POST['member_ids'])
        ? array_map('intval', array_filter($_POST['member_ids'])) : [];

    $errors = [];
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    if ($applyToAll) {
        $stmt = $pdo->query("SELECT id FROM members ORDER BY name");
        $memberIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }
    if (empty($memberIds)) {
        $errors[] = 'Please select "All Members" or at least one member.';
    }

    if (!empty($errors)) {
        $error = implode(' ', $errors);
        $_SESSION['advance_form'] = $_POST;
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO advance_payments (member_id, amount, date, description) VALUES (?, ?, ?, ?)");
            foreach ($memberIds as $mid) {
                $stmt->execute([$mid, $amount, $date, $description ?: null]);
            }
            $pdo->commit();
            $count = count($memberIds);
            $message = "Advance payment of " . number_format($amount, 2) . " credited to {$count} member(s). Settlement updated.";
            $_SESSION['advance_success'] = true;
            header('Location: index.php#settlement');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Could not save advance payment: ' . $e->getMessage();
            $_SESSION['advance_form'] = $_POST;
        }
    }
}

$members = $pdo->query("SELECT id, name FROM members ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$formData = $_SESSION['advance_form'] ?? null;
if (isset($_SESSION['advance_form'])) {
    unset($_SESSION['advance_form']);
}

$pageTitle = 'Advance Payment - Asia WordCamp 2026';
require_once 'includes/header.php';
?>

<div class="container">
    <header class="page-header">
        <h1>Asia WordCamp 2026 – Group Expenses</h1>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="add_member.php">Add Member</a>
            <a href="add_category.php">Add Category</a>
            <a href="add_advance_payment.php" class="btn btn-advance nav-active">Advance Payment</a>
        </nav>
    </header>

    <section class="card form-section">
        <h2>Add Advance Payment</h2>
        <p class="help">Advance payment is credited to each selected member in Member-wise Settlement (increases Total Paid).</p>
        <?php if ($message): ?>
            <p class="message success"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="add_advance_payment.php" method="POST" class="expense-form">
            <div class="form-row">
                <label for="amount">Amount *</label>
                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required placeholder="e.g. 2000" value="<?= htmlspecialchars($formData['amount'] ?? '') ?>">
            </div>
            <div class="form-row">
                <label for="date">Date *</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($formData['date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-row">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" placeholder="e.g. Prepaid for hotel" value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
            </div>
            <div class="form-row expense-for">
                <label>Apply To *</label>
                <div class="expense-for-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="apply_to_all" id="apply_to_all" value="1"<?= !empty($formData['apply_to_all']) ? ' checked' : '' ?>>
                        All Members
                    </label>
                    <p class="help">Or select specific members below (deselect "All Members" first):</p>
                    <div class="member-checkboxes">
                        <?php
                        $checkedIds = isset($formData['member_ids']) && is_array($formData['member_ids']) ? array_map('intval', $formData['member_ids']) : [];
                        foreach ($members as $m):
                            $mid = (int) $m['id'];
                            $checked = in_array($mid, $checkedIds, true) ? ' checked' : '';
                        ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="member_ids[]" value="<?= $mid ?>" class="advance-member-check"<?= $checked ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Advance Payment</button>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </section>
</div>

<script>
document.getElementById('apply_to_all').addEventListener('change', function() {
    var checks = document.querySelectorAll('.advance-member-check');
    checks.forEach(function(c) { c.disabled = this.checked; }.bind(this));
});
(function() {
    var all = document.getElementById('apply_to_all');
    if (all && all.checked) {
        document.querySelectorAll('.advance-member-check').forEach(function(c) { c.disabled = true; });
    }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
