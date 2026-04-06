<?php
/**
 * Add a new expense category (frontend)
 */

require_once 'config.php';

ensure_session_started();

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

$allCategories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

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

    <?php if (!empty($_SESSION['is_admin'])): ?>
    <section class="card table-section">
        <h2>All Categories</h2>
        <?php if (!empty($_SESSION['category_delete_success'])): ?>
            <p class="message success">Category deleted.</p>
            <?php unset($_SESSION['category_delete_success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['category_delete_error'])): ?>
            <p class="message error"><?= htmlspecialchars($_SESSION['category_delete_error']) ?></p>
            <?php unset($_SESSION['category_delete_error']); ?>
        <?php endif; ?>
        <?php if (empty($allCategories)): ?>
            <p class="no-data">No categories yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allCategories as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td>
                                    <a href="delete_category.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category? Only allowed if no expense uses it.');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
