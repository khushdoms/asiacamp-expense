<?php
/**
 * Add advance payment: amount per selected member(s), credited to admin (same logic as before, scoped to selection)
 */

require_once 'config.php';

require_admin();

ensure_session_started();

$message = '';
$error = '';

$membersList = $pdo->query("SELECT id, name FROM members ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float) ($_POST['amount'] ?? 0);
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $description = trim($_POST['description'] ?? '');
    $advanceForAll = isset($_POST['advance_for_all']);
    $memberIds = isset($_POST['member_ids']) && is_array($_POST['member_ids'])
        ? array_map('intval', array_filter($_POST['member_ids'])) : [];

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

    if ($advanceForAll) {
        $memberIds = array_column($membersList, 'id');
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
            $adminId = (int) $admin['id'];
            $memberCount = count($memberIds);
            $totalAmount = $amount * $memberCount;

            $stmt = $pdo->prepare("INSERT INTO advance_payments (member_id, amount, date, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$adminId, $totalAmount, $date, $description ?: null]);
            foreach ($memberIds as $mid) {
                $mid = (int) $mid;
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

$showAdvanceForm = !empty($error);

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
            <a href="manage_admins.php">Manage Admins</a>
        </nav>
    </header>

    <section class="card form-section">
        <h2>Add Advance Payment</h2>
        <p class="help">Each selected member pays the same amount. Total collected is credited to admin; each payer gets their share credited (same as before, but only for who you select).</p>
        <?php if ($error): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" id="toggleAdvanceForm">Add Advance Payment</button>
        <form action="add_advance_payment.php" method="POST" class="expense-form" id="advanceForm" style="<?= $showAdvanceForm ? '' : 'display:none;' ?>">
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
            <div class="form-row expense-for">
                <label>Advance From *</label>
                <div class="expense-for-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="advance_for_all" id="advance_for_all" value="1"<?= !empty($formData['advance_for_all']) ? ' checked' : '' ?>>
                        All Members
                    </label>
                    <p class="help">Or select specific members below (deselect &quot;All Members&quot; first):</p>
                    <div class="member-checkboxes">
                        <?php
                        $checkedIds = isset($formData['member_ids']) && is_array($formData['member_ids']) ? array_map('intval', $formData['member_ids']) : [];
                        foreach ($membersList as $m):
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
var advAll = document.getElementById('advance_for_all');
if (advAll) {
    advAll.addEventListener('change', function() {
        document.querySelectorAll('.advance-member-check').forEach(function(c) {
            c.disabled = this.checked;
        }.bind(this));
    });
    if (advAll.checked) {
        document.querySelectorAll('.advance-member-check').forEach(function(c) { c.disabled = true; });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
