<?php
/**
 * Asia WordCamp 2026 - Group Expense Management
 * Dashboard: Add expense form, All expenses table, Member-wise settlement, Admin summary
 */

require_once 'config.php';

session_start();

$admin = $pdo->query("SELECT id, name FROM members WHERE is_admin = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Fetch all expenses with paid-by name, category name, share count
$expenses = $pdo->query("
    SELECT e.id, e.paid_by_member_id, e.category_id, e.total_amount, e.description, e.date,
           m.name AS paid_by_name, c.name AS category_name,
           (SELECT COUNT(*) FROM expense_shares es WHERE es.expense_id = e.id) AS share_count
    FROM expenses e
    JOIN members m ON e.paid_by_member_id = m.id
    JOIN categories c ON e.category_id = c.id
    ORDER BY e.date DESC, e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($expenses as &$e) {
    $e['share_count'] = (int) $e['share_count'];
    $e['per_person'] = $e['share_count'] > 0 ? round((float) $e['total_amount'] / $e['share_count'], 2) : 0;
}
unset($e);

$members = $pdo->query("SELECT id, name, is_admin FROM members ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$totalPaid = [];
$totalAdvance = [];
$totalShare = [];
foreach ($members as $m) {
    $totalPaid[$m['id']] = 0;
    $totalAdvance[$m['id']] = 0;
    $totalShare[$m['id']] = 0;
}

$stmt = $pdo->query("SELECT paid_by_member_id, total_amount FROM expenses");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mid = (int) $row['paid_by_member_id'];
    if (isset($totalPaid[$mid])) {
        $totalPaid[$mid] += (float) $row['total_amount'];
    }
}

try {
    $stmt = $pdo->query("SELECT member_id, amount FROM advance_payments");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mid = (int) $row['member_id'];
        if (isset($totalAdvance[$mid])) {
            $totalAdvance[$mid] += (float) $row['amount'];
        }
    }
} catch (PDOException $e) {}

$stmt = $pdo->query("SELECT member_id, share_amount FROM expense_shares");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mid = (int) $row['member_id'];
    if (isset($totalShare[$mid])) {
        $totalShare[$mid] += (float) $row['share_amount'];
    }
}

// Build expense columns for Member-wise Settlement (category + description)
$expenseColumns = [];
$memberShares = [];
foreach ($members as $m) {
    $memberShares[(int) $m['id']] = [];
}
$expensesForSettlement = $pdo->query("
    SELECT e.id, c.name AS category_name, e.description
    FROM expenses e
    JOIN categories c ON e.category_id = c.id
    ORDER BY e.date ASC, e.id ASC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($expensesForSettlement as $ex) {
    $eid = (int) $ex['id'];
    $header = htmlspecialchars($ex['category_name']) . '(' . htmlspecialchars($ex['description']) . ')';
    $expenseColumns[] = ['id' => $eid, 'header' => $header];
}
$sharesData = $pdo->query("SELECT expense_id, member_id, share_amount FROM expense_shares")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sharesData as $s) {
    $eid = (int) $s['expense_id'];
    $mid = (int) $s['member_id'];
    if (isset($memberShares[$mid])) {
        $memberShares[$mid][$eid] = (float) $s['share_amount'];
    }
}

$formData = $_SESSION['expense_form'] ?? null;
$showExpenseForm = !empty($_SESSION['expense_errors']);
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
            <a href="add_advance_payment.php" class="btn btn-advance">Advance Payment</a>
            <form action="clear_db.php" method="POST" class="nav-clear-form" onsubmit="return confirm('Clear ALL data? This cannot be undone.');">
                <button type="submit" class="btn btn-danger">Clear Database</button>
            </form>
        </nav>
    </header>

    <!-- Add New Expense -->
    <section class="card form-section">
        <h2>Add New Expense</h2>
        <?php if (isset($_SESSION['expense_errors'])): ?>
            <ul class="message error-list">
                <?php foreach ($_SESSION['expense_errors'] as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php unset($_SESSION['expense_errors']); ?>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" id="toggleExpenseForm">Add Expense</button>
        <form action="add_expense.php" method="POST" class="expense-form" id="expenseForm" style="<?= $showExpenseForm ? '' : 'display:none;' ?>">
            <div class="form-row">
                <label for="paid_by_member_id">Paid By *</label>
                <select name="paid_by_member_id" id="paid_by_member_id" required>
                    <option value="">-- Select Member --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"<?= (($formData['paid_by_member_id'] ?? $admin['id'] ?? '') == $m['id']) ? ' selected' : '' ?>><?= htmlspecialchars($m['name']) ?><?= !empty($m['is_admin']) ? ' (Admin)' : '' ?></option>
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
                <input type="text" name="description" id="description" required placeholder="e.g. Kaushik booked train for 5 people" value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
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
                <button type="button" class="btn btn-secondary" id="cancelExpenseForm">Cancel</button>
            </div>
        </form>
    </section>

    <?php if ($admin): ?>
    <!-- Admin Credit / Debit Summary -->
    <section class="card table-section">
        <h2>Admin (<?= htmlspecialchars($admin['name']) ?>) – Credit & Debit</h2>
        <?php
        $adminId = (int) $admin['id'];
        $adminDebit = $totalPaid[$adminId] ?? 0;
        $adminCredit = $totalAdvance[$adminId] ?? 0;
        $adminShare = $totalShare[$adminId] ?? 0;
        $adminBalance = ($adminDebit + $adminCredit) - $adminShare;
        ?>
        <div class="admin-summary">
            <div class="admin-row"><span class="label">Credit (Advance received):</span> <span class="amount positive"><?= number_format($adminCredit, 2) ?></span></div>
            <div class="admin-row"><span class="label">Balance:</span> <span class="amount balance <?= $adminBalance >= 0 ? 'positive' : 'negative' ?>"><?= $adminBalance >= 0 ? '+' : '' ?><?= number_format($adminBalance, 2) ?></span></div>
        </div>
    </section>
    <?php endif; ?>

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
                            <th class="amount">Per Person</th>
                            <th>People</th>
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
                                <td class="amount"><?= number_format($e['per_person'], 2) ?></td>
                                <td><?= $e['share_count'] ?></td>
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
        if (!empty($_SESSION['advance_success'])) {
            echo '<p class="message success">Advance payment added. Settlement below has been updated.</p>';
            unset($_SESSION['advance_success']);
        }
        if (!empty($_SESSION['delete_error'])) {
            echo '<p class="message error">' . htmlspecialchars($_SESSION['delete_error']) . '</p>';
            unset($_SESSION['delete_error']);
        }
        if (!empty($_SESSION['clear_success'])) {
            echo '<p class="message success">All data cleared.</p>';
            unset($_SESSION['clear_success']);
        }
        if (!empty($_SESSION['clear_error'])) {
            echo '<p class="message error">' . htmlspecialchars($_SESSION['clear_error']) . '</p>';
            unset($_SESSION['clear_error']);
        }
        if (isset($_SESSION['expense_form'])) {
            unset($_SESSION['expense_form']);
        }
        ?>
        <p class="summary-intro">Per-person expense by category (transposed: expenses as rows, members as columns). Last row = total expense per member.</p>
        <?php if (empty($members)): ?>
            <p class="no-data">Add members first.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table summary-table settlement-grid settlement-transposed">
                    <thead>
                        <tr>
                            <th>Expense</th>
                            <?php foreach ($members as $m): ?>
                                <th class="amount"><?= htmlspecialchars($m['name']) ?><?= !empty($m['is_admin']) ? ' <span class="badge-admin">Admin</span>' : '' ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenseColumns as $col): ?>
                            <tr>
                                <td><?= $col['header'] ?></td>
                                <?php foreach ($members as $m): ?>
                                    <?php $amt = $memberShares[(int) $m['id']][$col['id']] ?? 0; ?>
                                    <td class="amount"><?= number_format($amt, 2) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td class="total-col">Total Expense</td>
                            <?php foreach ($members as $m): ?>
                                <?php
                                $mid = (int) $m['id'];
                                $rowTotal = array_sum($memberShares[$mid] ?? []);
                                ?>
                                <td class="amount total-col"><?= number_format($rowTotal, 2) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
document.getElementById('toggleExpenseForm').addEventListener('click', function() {
    var form = document.getElementById('expenseForm');
    form.style.display = form.style.display === 'none' ? 'grid' : 'none';
});
var cancelBtn = document.getElementById('cancelExpenseForm');
if (cancelBtn) cancelBtn.addEventListener('click', function() {
    document.getElementById('expenseForm').style.display = 'none';
});
var expenseForAll = document.getElementById('expense_for_all');
if (expenseForAll) {
    expenseForAll.addEventListener('change', function() {
        var checks = document.querySelectorAll('.member-check');
        checks.forEach(function(c) { c.disabled = this.checked; }.bind(this));
    });
    if (expenseForAll.checked) {
        document.querySelectorAll('.member-check').forEach(function(c) { c.disabled = true; });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
