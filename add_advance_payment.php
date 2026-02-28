<?php
/**
 * Add advance payment: all members give same amount, credited to admin
 */

require_once 'config.php';

session_start();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float) ($_POST['amount'] ?? 0);
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $description = trim($_POST['description'] ?? '');

    $errors = [];
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    $admin = $pdo->query("SELECT id FROM members WHERE is_admin = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        $errors[] = 'No admin member found. Add a member and mark as Admin first.';
    }

    $members = $pdo->query("SELECT id FROM members ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($members)) {
        $errors[] = 'No members found. Add members first.';
    }

    if (!empty($errors)) {
        $error = implode(' ', $errors);
        $_SESSION['advance_form'] = $_POST;
    } else {
        try {
            $pdo->beginTransaction();
            $adminId = (int) $admin['id'];
            $memberCount = count($members);
            $totalAmount = $amount * $memberCount;

            $stmt = $pdo->prepare("INSERT INTO advance_payments (member_id, amount, date, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$adminId, $totalAmount, $date, $description ?: null]);
            foreach ($members as $m) {
                $mid = (int) $m['id'];
                if ($mid !== $adminId) {
                    $stmt->execute([$mid, $amount, $date, $description ?: null]);
                }
            }
            $pdo->commit();
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
        <p class="help">Each member gives the same amount. Total is credited to admin. All members get the amount credited to reduce their share.</p>
        <?php if ($error): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" id="toggleAdvanceForm">Add Advance Payment</button>
        <form action="add_advance_payment.php" method="POST" class="expense-form" id="advanceForm" style="<?= $error ? '' : 'display:none;' ?>">
            <div class="form-row">
                <label for="amount">Amount per member *</label>
                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required placeholder="e.g. 500" value="<?= htmlspecialchars($formData['amount'] ?? '') ?>">
            </div>
            <div class="form-row">
                <label for="date">Date *</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($formData['date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-row">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" placeholder="e.g. Prepaid for hotel" value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Advance Payment</button>
                <button type="button" class="btn btn-secondary" id="cancelAdvanceForm">Cancel</button>
            </div>
        </form>
    </section>
</div>

<script>
document.getElementById('toggleAdvanceForm').addEventListener('click', function() {
    var form = document.getElementById('advanceForm');
    form.style.display = form.style.display === 'none' ? 'grid' : 'none';
});
document.getElementById('cancelAdvanceForm').addEventListener('click', function() {
    document.getElementById('advanceForm').style.display = 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>
