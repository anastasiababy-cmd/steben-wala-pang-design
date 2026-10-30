<?php
require_once __DIR__ . '/core/auth.php';
requireCustomer();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

$user_id = currentUserId();
$customer = getCustomerByID($pdo, $user_id);
$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer['username'] = trim($_POST['username'] ?? '');
    $customer['first_name'] = trim($_POST['first_name'] ?? '');
    $customer['last_name'] = trim($_POST['last_name'] ?? '');
    $customer['email'] = strtolower(trim($_POST['email'] ?? ''));
    $customer['address'] = trim($_POST['address'] ?? '');
    $customer['age'] = trim($_POST['age'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $customer['username'])
        || $customer['first_name'] === '' || strlen($customer['first_name']) > 50
        || $customer['last_name'] === '' || strlen($customer['last_name']) > 50
        || !filter_var($customer['email'], FILTER_VALIDATE_EMAIL) || strlen($customer['email']) > 100
        || strlen($customer['address']) > 255
        || ($customer['age'] !== '' && (!ctype_digit((string) $customer['age']) || (int) $customer['age'] > 120))) {
        $error = 'Enter a valid username, name, email, address, and age.';
    } else {
        $pdo->beginTransaction();
        try {
            updateCustomerProfile(
                $pdo,
                $user_id,
                $customer['username'],
                $customer['first_name'],
                $customer['last_name'],
                $customer['email'],
                $customer['address'],
                $customer['age']
            );
            recordAudit($pdo, $user_id, 'User', $user_id, 'UPDATE');
            $pdo->commit();
            $_SESSION['user_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
            $saved = true;
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
    <title>Edit My Information</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderAuthControls(); ?>
    <h1>Edit My Information</h1>
    <?php if ($saved) { ?>
        <p>Your information was updated.</p>
    <?php } ?>
    <?php if ($error !== '') { ?>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>
    <form method="POST" action="customer_profile.php">
        <p>
            <label for="username">Username</label>
            <input id="username" type="text" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" autocomplete="username" value="<?php echo htmlspecialchars($customer['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </p>
        <p>
            <label for="first_name">First name</label>
            <input id="first_name" type="text" name="first_name" maxlength="50" value="<?php echo htmlspecialchars($customer['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </p>
        <p>
            <label for="last_name">Last name</label>
            <input id="last_name" type="text" name="last_name" maxlength="50" value="<?php echo htmlspecialchars($customer['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </p>
        <p>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="100" value="<?php echo htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </p>
        <p>
            <label for="address">Address</label>
            <input id="address" type="text" name="address" maxlength="255" value="<?php echo htmlspecialchars($customer['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </p>
        <p>
            <label for="age">Age</label>
            <input id="age" type="number" name="age" min="0" max="120" value="<?php echo htmlspecialchars((string) ($customer['age'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </p>
        <button type="submit">Save changes</button>
    </form>
    <p><a href="index.php">Back to my flights</a></p>
</body>
</html>
