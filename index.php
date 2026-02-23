<?php
/**
 * Asia WordCamp 2026 - Group Expense Management
 * Dashboard: Add expense form, All expenses table, Member-wise settlement table
 */

require_once 'config.php';

session_start();

// Fetch all expenses with paid-by name and category name
$expenses = $pdo->query("
    SELECT e.id, e.paid_by_member_id, e.category_id, e.total_amount, e.description, e.date,
           m.name AS paid_by_name, c.name AS category_name
    FROM expenses e
    JOIN members m ON e.paid_by_member_id = m.id
    JOIN categories c ON e.category_id = c.id
    ORDER BY e.date DESC, e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Members and categories for dropdowns
$members = $pdo->query("SELECT id, name FROM members ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Member-wise settlement: Total Paid, Total Share, Balance
$totalPaid = [];
$totalShare = [];
foreach ($members as $m) {
    $totalPaid[$m['id']] = 0;
    $totalShare[$m['id']] = 0;
}

$stmt = $pdo->query("SELECT paid_by_member_id, total_amount FROM expenses");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mid = (int) $row['paid_by_member_id'];
    if (isset($totalPaid[$mid])) {
        $totalPaid[$mid] += (float) $row['total_amount'];
    }
}

$stmt = $pdo->query("SELECT member_id, share_amount FROM expense_shares");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mid = (int) $row['member_id'];
    if (isset($totalShare[$mid])) {
        $totalShare[$mid] += (float) $row['share_amount'];
    }
}

$settlement = [];
foreach ($members as $m) {
    $mid = (int) $m['id'];
    $paid = $totalPaid[$mid] ?? 0;
    $share = $totalShare[$mid] ?? 0;
    $settlement[] = [
        'id' => $mid,
        'name' => $m['name'],
        'total_paid' => $paid,
        'total_share' => $share,
        'balance' => $paid - $share,
    ];
}

$formData = $_SESSION['expense_form'] ?? null;
$pageTitle = 'Asia WordCamp 2026 - Expense Dashboard';
require_once 'includes/header.php';
?>

<div class="container">
    <header class="page-header">
        <h1>Asia WordCamp 2026 – Group Expenses</h1>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="add_member.php">Add Member</a>
            <a href="add_category.php">Add Category</a>
        </nav>
    </header>

    <!-- Add New Expense Form -->
    <section class="card form-section">
        <h2>Add New Expense</h2>
        <?php
        if (isset($_SESSION['expense_errors'])) {
            echo '<ul class="message error-list">';
            foreach ($_SESSION['expense_errors'] as $err) {
                echo '<li>' . htmlspecialchars($err) . '</li>';
            }
            echo '</ul>';
            unset($_SESSION['expense_errors']);
        }
        if (isset($_SESSION['expense_form'])) {
            unset($_SESSION['expense_form']);
        }
        ?>
        <form action="add_expense.php" method="POST" class="expense-form">
            <div class="form-row">
                <label for="paid_by_member_id">Paid By *</label>
                <select name="paid_by_member_id" id="paid_by_member_id" required>
                    <option value="">-- Select Member --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"<?= ($formData['paid_by_member_id'] ?? '') == $m['id'] ? ' selected' : '' ?>><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="category_id">Category *</label>
                <select name="category_id" id="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= ($formData['category_id'] ?? '') == $c['id'] ? ' selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="total_amount">Total Amount *</label>
                <input type="number" name="total_amount" id="total_amount" step="0.01" min="0.01" required placeholder="0.00" value="<?= htmlspecialchars($formData['total_amount'] ?? '') ?>">
            </div>
            <div class="form-row">
                <label for="description">Description *</label>
                <input type="text" name="description" id="description" required placeholder="e.g. Chirag paid for taxi for all" value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
            </div>
            <div class="form-row">
                <label for="date">Date *</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($formData['date'] ?? date('Y-m-d')) ?>" required>
            </div>

            <div class="form-row expense-for">
                <label>Expense For *</label>
                <div class="expense-for-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="expense_for_all" id="expense_for_all" value="1"<?= !empty($formData['expense_for_all']) ? ' checked' : '' ?>>
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
                                <input type="checkbox" name="member_ids[]" value="<?= $mid ?>" class="member-check"<?= $checked ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </label>    
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </div>
        </form>
    </section>

    <!-- All Expenses Table -->
    <section class="card table-section">
        <h2>All Expenses</h2>
        <?php if (empty($expenses)): ?>
            <p class="no-data">No expenses recorded yet. Add one above.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Paid By</th>
                            <th>Category</th>
                            <th class="amount">Total Amount</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['paid_by_name']) ?></td>
                                <td><?= htmlspecialchars($e['category_name']) ?></td>
                                <td class="amount"><?= number_format((float) $e['total_amount'], 2) ?></td>
                                <td><?= htmlspecialchars($e['description']) ?></td>
                                <td><?= htmlspecialchars($e['date']) ?></td>
                                <td>
                                    <a href="delete_expense.php?id=<?= (int) $e['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Member-wise Settlement Table -->
    <section class="card table-section" id="settlement">
        <h2>Member-wise Settlement</h2>
        <?php
        if (!empty($_SESSION['delete_success'])) {
            echo '<p class="message success">Expense deleted. Settlement below has been updated.</p>';
            unset($_SESSION['delete_success']);
        }
        if (!empty($_SESSION['delete_error'])) {
            echo '<p class="message error">' . htmlspecialchars($_SESSION['delete_error']) . '</p>';
            unset($_SESSION['delete_error']);
        }
        ?>
        <p class="summary-intro">Balance = Total Paid − Total Share. Positive = should receive; Negative = should pay; Zero = settled.</p>
        <?php if (empty($settlement)): ?>
            <p class="no-data">Add members first.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table summary-table">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th class="amount">Total Paid</th>
                            <th class="amount">Total Share</th>
                            <th class="amount">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settlement as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td class="amount"><?= number_format($row['total_paid'], 2) ?></td>
                                <td class="amount"><?= number_format($row['total_share'], 2) ?></td>
                                <td class="amount balance <?= $row['balance'] > 0 ? 'positive' : ($row['balance'] < 0 ? 'negative' : 'zero') ?>">
                                    <?= $row['balance'] > 0 ? '+' : '' ?><?= number_format($row['balance'], 2) ?>
                                    <span class="balance-desc"><?= $row['balance'] > 0 ? '(receive)' : ($row['balance'] < 0 ? '(pay)' : '(settled)') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
document.getElementById('expense_for_all').addEventListener('change', function() {
    var checks = document.querySelectorAll('.member-check');
    checks.forEach(function(c) { c.disabled = this.checked; }.bind(this));
});
// Initialize on load
(function() {
    var all = document.getElementById('expense_for_all');
    if (all && all.checked) {
        document.querySelectorAll('.member-check').forEach(function(c) { c.disabled = true; });
    }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
