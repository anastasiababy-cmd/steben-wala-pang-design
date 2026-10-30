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
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $address = trim($_POST['address'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($first_name === '' || $last_name === ''
        || !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)
        || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter your name, a username with 3-50 letters/numbers/underscores, and a valid email address.';
    } elseif ($age !== '' && (!ctype_digit($age) || (int) $age > 120)) {
        $error = 'Enter an age between 0 and 120, or leave it blank.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'The passwords do not match.';
    } else {
        $pdo->beginTransaction();
        try {
            $user_id = createUser(
                $pdo,
                $username,
                $first_name,
                $last_name,
                $email,
                $address,
                $age,
                password_hash($password, PASSWORD_DEFAULT)
            );
            recordAudit($pdo, $user_id, 'User', $user_id, 'CREATE');
            $pdo->commit();
            header('Location: login.php?registered=1');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                $error = 'That username or email address is already in use.';
            } else {
                throw $e;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Airline Booking System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Create an Account</h1>

    <?php if ($error !== '') { ?>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <form method="POST" action="register.php">
        <p>
            <label for="first_name">First name</label>
            <input id="first_name" type="text" name="first_name" maxlength="50" autocomplete="given-name" required>
        </p>
        <p>
            <label for="last_name">Last name</label>
            <input id="last_name" type="text" name="last_name" maxlength="50" autocomplete="family-name" required>
        </p>
        <p>
            <label for="username">Username</label>
            <input id="username" type="text" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" autocomplete="username" required>
        </p>
        <p>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="100" autocomplete="email" required>
        </p>
        <p>
            <label for="address">Address</label>
            <input id="address" type="text" name="address" maxlength="255" autocomplete="street-address">
        </p>
        <p>
            <label for="age">Age</label>
            <input id="age" type="number" name="age" min="0" max="120">
        </p>
        <p>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>
        </p>
        <p>
            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
        </p>
        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Log in</a></p>
</body>
</html>
