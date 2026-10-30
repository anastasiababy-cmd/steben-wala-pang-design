<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

$requests = getBookingRequests($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Booking Requests</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderAuthControls(); ?>
    <h1>Booking Requests</h1>
    <?php if ($requests) { ?>
        <?php foreach ($requests as $request) { ?>
            <section>
                <h2>Request #<?php echo (int) $request['request_id']; ?> -
                    <?php echo htmlspecialchars($request['flight_number'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p>Customer: <?php echo htmlspecialchars($request['customer_first_name'] . ' ' . $request['customer_last_name'], ENT_QUOTES, 'UTF-8'); ?>
                    (<?php echo htmlspecialchars($request['customer_email'], ENT_QUOTES, 'UTF-8'); ?>)</p>
                <p><a href="admin_customer.php?user_id=<?php echo (int) $request['customer_user_id']; ?>">View customer information</a></p>
                <p>Passenger: <?php echo htmlspecialchars($request['passenger_first_name'] . ' ' . $request['passenger_last_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Passenger email: <?php echo htmlspecialchars($request['passenger_email'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Passenger phone: <?php echo htmlspecialchars($request['passenger_phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Route: <?php echo htmlspecialchars($request['route_origin'] . ' to ' . $request['route_destination'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Preferred departure: <?php echo htmlspecialchars($request['requested_departure'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Status: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $request['status'])), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($request['status'] === 'pending') { ?>
                    <h3>Approve and create booking</h3>
                    <form action="core/handleforms.php" method="POST">
                        <input type="hidden" name="request_id" value="<?php echo (int) $request['request_id']; ?>">
                        <p>
                            <label for="seat-<?php echo (int) $request['request_id']; ?>">Assigned seat</label>
                            <input id="seat-<?php echo (int) $request['request_id']; ?>" type="text" name="assigned_seat" maxlength="5" required>
                        </p>
                        <p>
                            <label for="price-<?php echo (int) $request['request_id']; ?>">Ticket price</label>
                            <input id="price-<?php echo (int) $request['request_id']; ?>" type="number" name="ticket_price" min="0" max="99999999.99" step="0.01" required>
                        </p>
                        <button type="submit" name="approveBookingRequestBtn">Approve and create booking</button>
                    </form>

                    <h3>Decline request</h3>
                    <form action="core/handleforms.php" method="POST">
                        <input type="hidden" name="request_id" value="<?php echo (int) $request['request_id']; ?>">
                        <p>
                            <label for="decline-response-<?php echo (int) $request['request_id']; ?>">Response to customer</label><br>
                            <textarea id="decline-response-<?php echo (int) $request['request_id']; ?>" name="admin_response" maxlength="1000"></textarea>
                        </p>
                        <button type="submit" name="rejectBookingRequestBtn">Decline request</button>
                    </form>

                    <h3>Return to customer for changes</h3>
                    <form action="core/handleforms.php" method="POST">
                        <input type="hidden" name="request_id" value="<?php echo (int) $request['request_id']; ?>">
                        <p>
                            <label for="return-response-<?php echo (int) $request['request_id']; ?>">Explain what needs to be corrected</label><br>
                            <textarea id="return-response-<?php echo (int) $request['request_id']; ?>" name="admin_response" maxlength="1000" required></textarea>
                        </p>
                        <button type="submit" name="returnBookingRequestBtn">Return for changes</button>
                    </form>
                <?php } elseif ($request['admin_response']) { ?>
                    <p>Admin response: <?php echo nl2br(htmlspecialchars($request['admin_response'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php } ?>
            </section>
            <hr>
        <?php } ?>
    <?php } else { ?>
        <p>No booking requests.</p>
    <?php } ?>
    <p><a href="index.php">Back to admin dashboard</a></p>
</body>
</html>
