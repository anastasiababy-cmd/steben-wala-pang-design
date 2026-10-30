<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

$requests = getRescheduleRequests($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Reschedule Requests</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderAuthControls(); ?>
    <h1>Reschedule Requests</h1>
    <?php if ($requests) { ?>
        <?php foreach ($requests as $request) { ?>
            <section class="container">
                <h2>Request #<?php echo (int) $request['request_id']; ?> -
                    <?php echo htmlspecialchars($request['flight_number'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p>Customer: <?php echo htmlspecialchars($request['passenger_first_name'] . ' ' . $request['passenger_last_name'], ENT_QUOTES, 'UTF-8'); ?>
                    (<?php echo htmlspecialchars($request['customer_username'], ENT_QUOTES, 'UTF-8'); ?>)</p>
                <p><a href="admin_customer.php?user_id=<?php echo (int) $request['customer_user_id']; ?>">View customer information</a></p>
                <p>Route: <?php echo htmlspecialchars($request['route_origin'] . ' to ' . $request['route_destination'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Current departure: <?php echo htmlspecialchars($request['scheduled_departure'] ?? 'Not scheduled', ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Requested departure: <?php echo htmlspecialchars($request['requested_departure'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Customer reason: <?php echo nl2br(htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8')); ?></p>
                <p>Status: <?php echo htmlspecialchars(ucfirst($request['status']), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($request['status'] === 'pending') { ?>
                    <form action="core/handleforms.php" method="POST">
                        <input type="hidden" name="request_id" value="<?php echo (int) $request['request_id']; ?>">
                        <p>
                            <label for="response-<?php echo (int) $request['request_id']; ?>">Response to customer</label><br>
                            <textarea id="response-<?php echo (int) $request['request_id']; ?>" name="admin_response" maxlength="1000"></textarea>
                        </p>
                        <button type="submit" name="approveRescheduleBtn">Approve and reschedule</button>
                        <button type="submit" name="rejectRescheduleBtn">Reject request</button>
                    </form>
                <?php } elseif ($request['admin_response']) { ?>
                    <p>Admin response: <?php echo nl2br(htmlspecialchars($request['admin_response'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php } ?>
            </section>
            <hr>
        <?php } ?>
    <?php } else { ?>
        <p>No reschedule requests.</p>
    <?php } ?>
    <p><a href="index.php">Back to admin dashboard</a></p>
</body>
</html>
