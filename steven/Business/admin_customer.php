<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

$customer_id = (int) ($_GET['user_id'] ?? 0);
$customer = getCustomerByID($pdo, $customer_id);

if (!$customer) {
    http_response_code(404);
    exit('Customer not found.');
}

$bookings = getBookingsByUser($pdo, $customer_id);
$requests = getBookingRequestsByUser($pdo, $customer_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Information</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderAuthControls(); ?>
    <h1>Customer Information</h1>
    <p>Name: <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Username: <?php echo htmlspecialchars($customer['username'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Email: <?php echo htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Address: <?php echo htmlspecialchars($customer['address'] ?: 'Not provided', ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Age: <?php echo htmlspecialchars((string) ($customer['age'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p>Account created: <?php echo htmlspecialchars($customer['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>

    <h2>Bookings</h2>
    <?php if ($bookings) { ?>
        <?php foreach ($bookings as $booking) { ?>
            <p>
                <?php echo htmlspecialchars($booking['flight_number'], ENT_QUOTES, 'UTF-8'); ?>:
                <?php echo htmlspecialchars($booking['route_origin'] . ' to ' . $booking['route_destination'], ENT_QUOTES, 'UTF-8'); ?>,
                departure <?php echo htmlspecialchars($booking['scheduled_departure'] ?? 'Not scheduled', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php } ?>
    <?php } else { ?>
        <p>No bookings.</p>
    <?php } ?>

    <h2>Booking Requests</h2>
    <?php if ($requests) { ?>
        <?php foreach ($requests as $request) { ?>
            <p>
                <?php echo htmlspecialchars($request['flight_number'], ENT_QUOTES, 'UTF-8'); ?>:
                <?php echo htmlspecialchars(ucfirst($request['status']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars($request['route_origin'] . ' to ' . $request['route_destination'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php } ?>
    <?php } else { ?>
        <p>No booking requests.</p>
    <?php } ?>

    <p><a href="admin_booking_requests.php">Back to booking requests</a></p>
</body>
</html>
