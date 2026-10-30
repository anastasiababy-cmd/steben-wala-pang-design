<?php
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = findUserByLogin($pdo, $login);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: index.php');
        exit();
    }

    $error = 'Username/email or password is incorrect.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Airline Booking System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Log In</h1>

    <?php if (isset($_GET['registered'])) { ?>
        <p>Your account is registered. You can now log in.</p>
    <?php } ?>

    <?php if ($error !== '') { ?>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <form method="POST" action="login.php">
        <p>
            <label for="login">Username or email</label>
            <input id="login" type="text" name="login" autocomplete="username" required>
        </p>
        <p>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </p>
        <button type="submit">Log In</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register</a></p>
</body>
</html>
