<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/dbConfig.php';

$getPassengerByID = getPassengerByID(
    $pdo,
    (int) ($_GET['passenger_id'] ?? 0)
);

if (!$getPassengerByID) {
    http_response_code(404);
    exit('Passenger not found.');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Passenger</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php renderAuthControls(); ?>

<h1>Edit Passenger Registration</h1>

<p>Added by: <?php echo htmlspecialchars($getPassengerByID['added_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>
<p>Last updated: <?php echo htmlspecialchars($getPassengerByID['last_updated'] ?? 'Not recorded', ENT_QUOTES, 'UTF-8'); ?>
    by <?php echo htmlspecialchars($getPassengerByID['updated_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>

<form action="core/handleforms.php?passenger_id=<?php echo (int) $getPassengerByID['passenger_id']; ?>" method="POST">

    <p>
        <label>First Name</label>
        <input
            type="text"
            name="first_name"
            value="<?php echo htmlspecialchars($getPassengerByID['first_name'], ENT_QUOTES, 'UTF-8'); ?>">
    </p>

    <p>
        <label>Last Name</label>
        <input
            type="text"
            name="last_name"
            value="<?php echo htmlspecialchars($getPassengerByID['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
    </p>

    <p>
        <label>Email</label>
        <input
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($getPassengerByID['email'], ENT_QUOTES, 'UTF-8'); ?>">
    </p>

    <p>
        <label>Phone</label>
        <input
            type="text"
            name="phone"
            value="<?php echo htmlspecialchars($getPassengerByID['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </p>

    <input type="submit"
           name="editPassengerBtn"
           value="Update Passenger">

</form>

</body>
</html>