<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/models.php';
require_once __DIR__ . '/core/dbConfig.php';

$booking = getBookingByID($pdo, (int) ($_GET['booking_id'] ?? 0));
if (!$booking) {
    http_response_code(404);
    exit('Booking not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h1>Are you sure you want to cancel this booking?</h1>

<?php renderAuthControls(); ?>

<p>Flight Number: <?php echo htmlspecialchars($booking['flight_number'], ENT_QUOTES, 'UTF-8'); ?></p>
<p>Added by: <?php echo htmlspecialchars($booking['added_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>
<p>Last updated: <?php echo htmlspecialchars($booking['last_updated'] ?? 'Not recorded', ENT_QUOTES, 'UTF-8'); ?>
    by <?php echo htmlspecialchars($booking['updated_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>

<form action="core/handleforms.php?booking_id=<?php echo (int) $booking['booking_id']; ?>" method="POST">
    <input type="submit" name="deleteBookingBtn" value="Cancel Booking">
</form>
</body>
</html>