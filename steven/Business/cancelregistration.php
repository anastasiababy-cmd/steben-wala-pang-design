
<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/dbConfig.php';

$passenger = getPassengerByID($pdo, (int) ($_GET['passenger_id'] ?? 0));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Passenger</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php renderAuthControls(); ?>

<h1>Delete Passenger?</h1>

<?php if ($passenger) { ?>

    <h3><?php echo htmlspecialchars($passenger['first_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <h3><?php echo htmlspecialchars($passenger['last_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <h3><?php echo htmlspecialchars($passenger['email'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>Added by: <?php echo htmlspecialchars($passenger['added_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Last updated: <?php echo htmlspecialchars($passenger['last_updated'] ?? 'Not recorded', ENT_QUOTES, 'UTF-8'); ?>
        by <?php echo htmlspecialchars($passenger['updated_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>

    <form action="core/handleforms.php?passenger_id=<?php echo (int) $passenger['passenger_id']; ?>" method="POST">

        <p>Are you sure you want to delete this passenger?</p>

        <input type="submit" name="deletePassengerBtn" value="Delete">

    </form>

<?php } else { ?>

    <p>Passenger not found.</p>

<?php } ?>

</body>
</html>
