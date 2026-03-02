<?php
/**
 * Add a new member to the group
 */

require_once 'config.php';

ensure_session_started();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $isAdmin = (!empty($_SESSION['is_admin']) && isset($_POST['is_admin'])) ? 1 : 0;
    if ($name === '') {
        $error = 'Please enter a member name.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO members (name, is_admin) VALUES (?, ?)");
            $stmt->execute([$name, $isAdmin]);
            $message = 'Member "' . htmlspecialchars($name) . '" added successfully.';
            $name = ''; // clear for next add
        } catch (PDOException $e) {
            $error = 'Could not add member. Please try again.';
        }
    }
}

$pageTitle = 'Add Member - Asia WordCamp 2026';
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
        </nav>
    </header>

    <section class="card form-section">
        <h2>Add New Member</h2>
        <?php if ($message): ?>
            <p class="message success"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="add_member.php" method="POST" class="expense-form">
            <div class="form-row">
                <label for="name">Member Name *</label>
                <input type="text" name="name" id="name" required placeholder="e.g. Kaushik" value="<?= htmlspecialchars($name ?? '') ?>">
            </div>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <div class="form-row">
                    <label></label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_admin" id="is_admin" value="1"<?= !empty($_POST['is_admin']) ? ' checked' : '' ?>>
                        Is Admin
                    </label>
                </div>
            <?php endif; ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Member</button>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
