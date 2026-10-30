
<?php
require_once __DIR__ . '/core/auth.php';
requireLogin();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';
$isAdmin = $_SESSION['user_role'] === 'admin';
$bookings = $isAdmin ? getAllBookings($pdo) : getBookingsByUser($pdo, currentUserId());
?>

<!DOCTYPE html>
<html>
<head>
    <title>Airline Booking System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php renderAuthControls(); ?>

<h1>Airline Booking System</h1>

<?php if ($isAdmin) { ?>
<h2>Register Passenger and Book Flight</h2>

<form action="core/handleforms.php" method="POST">

    <h3>Passenger Information</h3>

    First Name: <input type="text" name="first_name" required><br><br>

    Last Name: <input type="text" name="last_name" required><br><br>

    Email: <input type="email" name="email" required><br><br>

    Phone: <input type="text" name="phone" required><br><br>

    <hr>

    <h3>Booking Information</h3>

    Flight Number: <input type="text" name="flight_number" required><br><br>

    Origin: <input type="text" name="route_origin" required><br><br>

    Destination: <input type="text" name="route_destination" required><br><br>

    Assigned Seat: <input type="text" name="assigned_seat" required><br><br>

    Ticket Price: <input type="number" name="ticket_price" step="0.01" min="0" required><br><br>

    Scheduled Departure: <input type="datetime-local" name="scheduled_departure" required><br><br>

    <input type="submit" name="registerAndBookBtn" value="Register and Book">

</form>

<hr>

<h2>Registered Passengers</h2>

<?php
$passengers = getAllPassengers($pdo);

foreach ($passengers as $row) {
?>

    <div class="container">

        <h3>Passenger ID: <?php echo (int) $row['passenger_id']; ?></h3>

        <p>Name: <?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name'], ENT_QUOTES, 'UTF-8'); ?></p>

        <p>Email: <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></p>

        <p>Phone: <?php echo htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

        <p>Added by: <?php echo htmlspecialchars($row['added_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>

        <p>Last updated: <?php echo htmlspecialchars($row['last_updated'] ?? 'Not recorded', ENT_QUOTES, 'UTF-8'); ?>
            by <?php echo htmlspecialchars($row['updated_by_name'] ?: 'Legacy record', ENT_QUOTES, 'UTF-8'); ?></p>

        <a href="viewbooking.php?passenger_id=<?php echo (int) $row['passenger_id']; ?>">View Booking</a> |

        <a href="editregistration.php?passenger_id=<?php echo (int) $row['passenger_id']; ?>">Edit Registration</a> |

        <a href="cancelregistration.php?passenger_id=<?php echo (int) $row['passenger_id']; ?>">Cancel Registration</a>

    </div>

<?php
}
?>

<hr>
<h2>All Bookings</h2>
<?php if ($bookings) { ?>
    <?php foreach ($bookings as $booking) { ?>
        <div class="container">
            <h3><?php echo htmlspecialchars($booking['flight_number'], ENT_QUOTES, 'UTF-8'); ?>
                - Booking #<?php echo (int) $booking['booking_id']; ?></h3>
            <p>Passenger: <?php echo htmlspecialchars($booking['passenger_first_name'] . ' ' . $booking['passenger_last_name'], ENT_QUOTES, 'UTF-8'); ?>
                (<?php echo htmlspecialchars($booking['passenger_email'], ENT_QUOTES, 'UTF-8'); ?>)</p>
            <p>Route: <?php echo htmlspecialchars($booking['route_origin'] . ' to ' . $booking['route_destination'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Scheduled departure: <?php echo htmlspecialchars($booking['scheduled_departure'] ?? 'Not scheduled', ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="editbooking.php?booking_id=<?php echo (int) $booking['booking_id']; ?>">View / edit booking schedule</a>
            <?php if (!empty($booking['customer_user_id'])) { ?>
                | <a href="admin_customer.php?user_id=<?php echo (int) $booking['customer_user_id']; ?>">View customer information</a>
            <?php } ?>
        </div>
    <?php } ?>
<?php } else { ?>
    <p>No bookings have been added.</p>
<?php } ?>

<?php } else { ?>
<h2>My Tickets and Scheduled Flights</h2>
<p><a href="booking_request.php">Request a new flight booking</a></p>
<p>You can view your tickets and request a different departure time. Airport staff review all requests.</p>
<?php if ($bookings) { ?>
    <?php foreach ($bookings as $booking) { ?>
        <div class="container">
            <h3><?php echo htmlspecialchars($booking['flight_number'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p>Passenger: <?php echo htmlspecialchars($booking['passenger_first_name'] . ' ' . $booking['passenger_last_name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Route: <?php echo htmlspecialchars($booking['route_origin'] . ' to ' . $booking['route_destination'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Seat: <?php echo htmlspecialchars($booking['assigned_seat'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Ticket price: <?php echo htmlspecialchars($booking['ticket_price'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Scheduled departure: <?php echo htmlspecialchars($booking['scheduled_departure'] ?? 'Not scheduled', ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="reschedule.php?booking_id=<?php echo (int) $booking['booking_id']; ?>">Request reschedule</a>
        </div>
    <?php } ?>
<?php } else { ?>
    <p>No tickets are linked to your account yet. Submit a booking request and an administrator will review it.</p>
<?php } ?>
<p><a href="reschedule.php">View my reschedule requests</a></p>
<?php } ?>

</body>
</html>
