<?php
require_once __DIR__ . '/core/auth.php';
requireCustomer();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

$user_id = currentUserId();
$booking_id = (int) ($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$booking = $booking_id > 0 ? getBookingByID($pdo, $booking_id) : null;

if ($booking && !customerOwnsBooking($pdo, $booking_id, $user_id)) {
    http_response_code(404);
    exit('Ticket not found.');
}

$requests = getRescheduleRequestsByUser($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request a Flight Reschedule</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderAuthControls(); ?>
    <h1>Request a Flight Reschedule</h1>

    <?php if ($booking) { ?>
        <h2><?php echo htmlspecialchars($booking['flight_number'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p>Current scheduled departure:
            <?php echo htmlspecialchars($booking['scheduled_departure'] ?? 'Not scheduled', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <form action="core/handleforms.php" method="POST">
            <input type="hidden" name="booking_id" value="<?php echo (int) $booking['booking_id']; ?>">
            <p>
                <label for="requested_departure">Preferred departure</label>
                <input id="requested_departure" type="datetime-local" name="requested_departure" required>
            </p>
            <p>
                <label for="reason">Reason for request</label><br>
                <textarea id="reason" name="reason" maxlength="1000" required></textarea>
            </p>
            <button type="submit" name="requestRescheduleBtn">Submit request</button>
        </form>
    <?php } elseif ($booking_id > 0) { ?>
        <p>Ticket not found.</p>
    <?php } ?>

    <h2>My Requests</h2>
    <?php if ($requests) { ?>
        <?php foreach ($requests as $request) { ?>
            <div>
                <h3><?php echo htmlspecialchars($request['flight_number'], ENT_QUOTES, 'UTF-8'); ?>:
                    <?php echo htmlspecialchars(ucfirst($request['status']), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>Current departure: <?php echo htmlspecialchars($request['scheduled_departure'] ?? 'Not scheduled', ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Requested departure: <?php echo htmlspecialchars($request['requested_departure'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Reason: <?php echo nl2br(htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php if ($request['admin_response']) { ?>
                    <p>Admin response: <?php echo nl2br(htmlspecialchars($request['admin_response'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php } ?>
            </div>
            <hr>
        <?php } ?>
    <?php } else { ?>
        <p>You have not made any reschedule requests.</p>
    <?php } ?>
    <p><a href="index.php">Back to my flights</a></p>
</body>
</html>
