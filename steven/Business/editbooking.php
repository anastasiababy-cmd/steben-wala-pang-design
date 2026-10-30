
<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

if (!isset($_GET['booking_id'])) {
    die("Booking ID is missing.");
}

$booking = getBookingByID($pdo, (int) $_GET['booking_id']);

if (!$booking) {
    die("Booking not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php renderAuthControls(); ?>

<h1>Edit Booking</h1>

<p>Added by: <?php echo htmlspecialchars($booking['added_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>
<p>Last updated: <?php echo htmlspecialchars($booking['last_updated'] ?? 'Not recorded', ENT_QUOTES, 'UTF-8'); ?>
    by <?php echo htmlspecialchars($booking['updated_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>

<form action="core/handleforms.php?booking_id=<?php echo (int) $booking['booking_id']; ?>" method="POST">

    <p>
        <label>Flight Number</label>
        <input type="text" name="flight_number"
        value="<?php echo htmlspecialchars($booking['flight_number'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label>Origin</label>
        <input type="text" name="route_origin"
        value="<?php echo htmlspecialchars($booking['route_origin'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label>Destination</label>
        <input type="text" name="route_destination"
        value="<?php echo htmlspecialchars($booking['route_destination'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label>Seat</label>
        <input type="text" name="assigned_seat"
        value="<?php echo htmlspecialchars($booking['assigned_seat'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label>Price</label>
        <input type="number" step="0.01" name="ticket_price"
        value="<?php echo htmlspecialchars($booking['ticket_price'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label>Scheduled Departure</label>
        <input type="datetime-local" name="scheduled_departure"
        value="<?php echo htmlspecialchars($booking['scheduled_departure'] ? str_replace(' ', 'T', substr($booking['scheduled_departure'], 0, 16)) : '', ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <input type="submit" name="updateBookingBtn" value="Update Booking">

</form>

<br>
<a href="index.php">Back to Home</a>

</body>
</html>
