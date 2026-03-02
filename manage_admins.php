<?php
require_once 'config.php';

require_admin();

ensure_session_started();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = (int) ($_POST['member_id'] ?? 0);
    $isAdmin  = isset($_POST['is_admin']) ? 1 : 0;

    if ($memberId > 0) {
        $stmt = $pdo->prepare("UPDATE members SET is_admin = ? WHERE id = ?");
        $stmt->execute([$isAdmin, $memberId]);
        $message = 'Admin status updated.';
    }
}

$members = $pdo->query("SELECT id, name, is_admin FROM members ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Admins - Asia WordCamp 2026';
require_once 'includes/header.php';
?>

<div class="container">
    <header class="page-header">
        <h1>Asia WordCamp 2026 – Manage Admins</h1>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="add_member.php">Add Member</a>
            <a href="add_category.php">Add Category</a>
            <a href="add_advance_payment.php" class="btn btn-advance">Advance Payment</a>
        </nav>
    </header>

    <section class="card table-section">
        <h2>Admins</h2>
        <?php if ($message): ?>
            <p class="message success"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Admin</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['name']) ?></td>
                            <td><?= $m['is_admin'] ? 'Yes' : 'No' ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="member_id" value="<?= (int) $m['id'] ?>">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="is_admin" value="1"<?= $m['is_admin'] ? ' checked' : '' ?>>
                                        Admin
                                    </label>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>

