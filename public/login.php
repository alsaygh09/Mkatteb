<?php
/**
 * public/login.php
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireGuest('account.php');

$pageTitle = 'Login';
$error     = '';
$email     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = loginUser($email, $password);

    if (isset($result['error'])) {
        $error = $result['error'];
    } else {
        flashSet('success', 'Welcome back!');
        // Redirect admins to dashboard
        if ($result['role'] === 'admin') {
            redirect('admin/dashboard.php');
        }
        redirect('index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-card__header">
            <h1 class="auth-card__title">Welcome Back</h1>
            <p class="auth-card__sub">Log in to your Mkatteb account.</p>
        </div>

        <?php if ($error): ?>
        <div class="flash flash--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login.php')) ?>" class="auth-form" novalidate>
            <?= csrfField() ?>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="<?= e($email) ?>" required
                       placeholder="you@example.com" autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input"
                       required placeholder="Your password" autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn--primary btn--full">Log In</button>
        </form>

        <p class="auth-card__footer">
            Don't have an account? <a href="<?= e(url('register.php')) ?>">Create one</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
