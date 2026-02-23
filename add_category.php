<?php
/**
 * Add a new expense category (frontend)
 */

require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Please enter a category name.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $message = 'Category "' . htmlspecialchars($name) . '" added successfully.';
            $name = '';
        } catch (PDOException $e) {
            $error = 'Could not add category. It may already exist. Please try again.';
        }
    }
}

$pageTitle = 'Add Category - Asia WordCamp 2026';
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
        <h2>Add New Category</h2>
        <?php if ($message): ?>
            <p class="message success"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="add_category.php" method="POST" class="expense-form">
            <div class="form-row">
                <label for="name">Category Name *</label>
                <input type="text" name="name" id="name" required placeholder="e.g. Train, Dinner, Hotel" value="<?= htmlspecialchars($name ?? '') ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Category</button>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
