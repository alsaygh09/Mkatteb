<?php
/**
 * public/register.php
 * New user registration.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireGuest('account.php');

$pageTitle = 'Create Account';
$errors    = [];
$formData  = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    $formData = ['name' => $name, 'email' => $email];

    // Client-side-style server validation
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $result = registerUser($name, $email, $password);
        if (isset($result['error'])) {
            $errors[] = $result['error'];
        } else {
            // Auto-login after registration
            loginUser($email, $password);
            flashSet('success', 'Welcome to Mkatteb, ' . $name . '!');
            redirect('index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-card__header">
            <h1 class="auth-card__title">Create Account</h1>
            <p class="auth-card__sub">Join Mkatteb and start reading today.</p>
        </div>

        <?php if ($errors): ?>
        <div class="flash flash--error">
            <?php foreach ($errors as $err): ?>
                <p><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('register.php')) ?>" class="auth-form" novalidate>
            <?= csrfField() ?>

            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-input"
                       value="<?= e($formData['name']) ?>" required
                       placeholder="e.g. Ahmed Al-Khalil" autocomplete="name">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="<?= e($formData['email']) ?>" required
                       placeholder="you@example.com" autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input"
                       required minlength="6"
                       placeholder="At least 6 characters" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="confirm" class="form-label">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" class="form-input"
                       required minlength="6"
                       placeholder="Repeat your password" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn--primary btn--full">Create Account</button>
        </form>

        <p class="auth-card__footer">
            Already have an account? <a href="<?= e(url('login.php')) ?>">Log in</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
