<?php
require_once 'classes/init.php';

checking(1);

$errors = [];
$username = '';
$is_ajax_request = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

function login_json_response(string $status, string $message, array $extra = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message,
    ], $extra));
    exit;
}

if (isset($_POST['login_admin'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));

    if ($username === '') {
        $errors['username'] = 'Please, the input is empty.';
    }

    if ($password === '') {
        $errors['password'] = 'Please, the input is empty.';
    }

    if (empty($errors)) {
        $admin_user = admin::verify_admin($username, $password);

        if ($admin_user) {
            $session->login($admin_user);

            if ($is_ajax_request) {
                login_json_response('success', 'Login successful.', [
                    'redirect' => 'index.php',
                ]);
            }

            header('Location: index.php');
            exit;
        }

        $errors['form'] = 'Invalid username or password.';
    }

    if ($is_ajax_request) {
        login_json_response('error', 'Please fix the highlighted fields.', [
            'errors' => $errors,
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to the User Management System.">
    <title>UserBase - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo time(); ?>">
    <script src="assets/js/login.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
    <main class="login-shell">
        <section class="login-container">
            <div class="login-header">
                <div class="login-badge">Admin Portal</div>
                <h1>Sign in</h1>
                <p>Enter your credentials to continue to the dashboard.</p>
            </div>

            <?php if (isset($errors['form'])): ?>
                <div class="login-alert login-alert-error">
                    <?php echo htmlspecialchars($errors['form']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        placeholder="Enter your username"
                        value="<?php echo htmlspecialchars($username); ?>"
                        autofocus>
                    <span class="error-message"><?php echo isset($errors['username']) ? htmlspecialchars($errors['username']) : ''; ?></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Enter your password">
                    <span class="error-message"><?php echo isset($errors['password']) ? htmlspecialchars($errors['password']) : ''; ?></span>
                </div>

                <?php if (isset($errors['form'])): ?>
                    <div class="login-alert login-alert-error login-form-alert">
                        <?php echo htmlspecialchars($errors['form']); ?>
                    </div>
                <?php else: ?>
                    <div class="login-alert login-alert-error login-form-alert" style="display:none;"></div>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="login_admin" class="login-btn">Sign in</button>
                </div>
            </form>

            <div class="login-footer">
                <span>Admin dashboard</span>
                <span class="login-footer-separator">•</span>
                <span>Secure admin access</span>
            </div>
        </section>
    </main>
</body>
</html>