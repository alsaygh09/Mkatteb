<?php
/**
 * includes/auth.php
 * Session management, login, registration, and role checks.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/lang.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------
// Session helpers
// ---------------------------------------------------------------

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

// ---------------------------------------------------------------
// Redirects and guards
// ---------------------------------------------------------------

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        redirect($redirect);
    }
}

function requireAdmin(string $redirect = 'login.php'): void {
    if (!isAdmin()) {
        redirect($redirect);
    }
}

function requireGuest(string $redirect = 'account.php'): void {
    if (isLoggedIn()) {
        redirect($redirect);
    }
}

// ---------------------------------------------------------------
// Registration
// ---------------------------------------------------------------

/**
 * Register a new user.
 * Returns ['ok' => true] on success or ['error' => 'message'] on failure.
 */
function registerUser(string $name, string $email, string $password): array {
    $name     = trim($name);
    $email    = trim(strtolower($email));
    $password = trim($password);

    // Basic validation
    if (strlen($name) < 2) {
        return ['error' => t('error_name_min')];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => t('error_valid_email')];
    }
    if (strlen($password) < 6) {
        return ['error' => t('error_password_min')];
    }

    $pdo = db();

    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['error' => t('error_email_exists')];
    }

    // Hash password and insert
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $hash, 'user']);

    return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
}

// ---------------------------------------------------------------
// Login
// ---------------------------------------------------------------

/**
 * Attempt to log in a user.
 * Returns ['ok' => true] on success or ['error' => 'message'] on failure.
 */
function loginUser(string $email, string $password): array {
    $email    = trim(strtolower($email));
    $password = trim($password);

    if (empty($email) || empty($password)) {
        return ['error' => t('error_email_password_required')];
    }

    $pdo  = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['error' => t('error_invalid_login')];
    }

    if ($user['is_blocked']) {
        return ['error' => t('error_account_blocked')];
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_email'] = $user['email'];

    return ['ok' => true, 'role' => $user['role']];
}

// ---------------------------------------------------------------
// Logout
// ---------------------------------------------------------------

function logoutUser(): void {
    // Clear all session data
    $_SESSION = [];

    // Destroy session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

// ---------------------------------------------------------------
// CSRF helpers (lightweight)
// ---------------------------------------------------------------

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die(t('error_invalid_token'));
    }
}
