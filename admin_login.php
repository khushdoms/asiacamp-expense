<?php
require_once 'config.php';

ensure_session_started();

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid admin password.';
    }
}

$pageTitle = 'Admin Login - Asia WordCamp 2026';
require_once 'includes/header.php';
?>

<div class="container">
    <header class="page-header">
        <h1>Asia WordCamp 2026 – Admin Login</h1>
        <nav class="nav-links">
            <a href="index.php">Dashboard</a>
        </nav>
    </header>

    <section class="card form-section">
        <h2>Admin Login</h2>
        <?php if ($error): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" class="expense-form">
            <div class="form-row">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Login</button>
                <a href="index.php" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>

