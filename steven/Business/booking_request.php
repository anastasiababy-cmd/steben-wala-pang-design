<?php
require_once __DIR__ . '/core/auth.php';
requireCustomer();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

$requests = getBookingRequestsByUser($pdo, currentUserId());
$formInput = $_SESSION['booking_request_form'] ?? [];
$formError = $_SESSION['booking_request_error'] ?? '';
unset($_SESSION['booking_request_form'], $_SESSION['booking_request_error']);

function renderBookingRequestForm($request = null, $formInput = [])
{
    $values = array_merge($request ?? [], $formInput);
    $request_id = (int) ($values['request_id'] ?? 0);
    $value = function ($field) use ($values) {
        return htmlspecialchars((string) ($values[$field] ?? ''), ENT_QUOTES, 'UTF-8');
    };
    $departureValue = (string) ($values['requested_departure'] ?? '');
    if (strpos($departureValue, ' ') !== false) {
        $departureValue = str_replace(' ', 'T', substr($departureValue, 0, 16));
    }
    $departure = htmlspecialchars($departureValue, ENT_QUOTES, 'UTF-8');
    ?>
    <form action="core/handleforms.php" method="POST">
        <?php if ($request_id > 0) { ?>
            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
        <?php } ?>
        <h2>Passenger information</h2>
        <p>
            <label for="passenger_first_name-<?php echo $request_id ?: 'new'; ?>">First name</label>
            <input id="passenger_first_name-<?php echo $request_id ?: 'new'; ?>" type="text" name="passenger_first_name" maxlength="50" value="<?php echo $value('passenger_first_name'); ?>" required>
        </p>
        <p>
            <label for="passenger_last_name-<?php echo $request_id ?: 'new'; ?>">Last name</label>
            <input id="passenger_last_name-<?php echo $request_id ?: 'new'; ?>" type="text" name="passenger_last_name" maxlength="50" value="<?php echo $value('passenger_last_name'); ?>" required>
        </p>
        <p>
            <label for="passenger_email-<?php echo $request_id ?: 'new'; ?>">Passenger email</label>
            <input id="passenger_email-<?php echo $request_id ?: 'new'; ?>" type="email" name="passenger_email" maxlength="100" value="<?php echo $value('passenger_email'); ?>" required>
        </p>
        <p>
            <label for="passenger_phone-<?php echo $request_id ?: 'new'; ?>">Passenger phone</label>
            <input id="passenger_phone-<?php echo $request_id ?: 'new'; ?>" type="tel" name="passenger_phone" maxlength="20" value="<?php echo $value('passenger_phone'); ?>" required>
        </p>

        <h2>Trip information</h2>
        <p>
            <label for="flight_number-<?php echo $request_id ?: 'new'; ?>">Requested flight number</label>
            <input id="flight_number-<?php echo $request_id ?: 'new'; ?>" type="text" name="flight_number" maxlength="10" value="<?php echo $value('flight_number'); ?>" required>
        </p>
        <p>
            <label for="route_origin-<?php echo $request_id ?: 'new'; ?>">Origin</label>
            <input id="route_origin-<?php echo $request_id ?: 'new'; ?>" type="text" name="route_origin" maxlength="100" value="<?php echo $value('route_origin'); ?>" required>
        </p>
        <p>
            <label for="route_destination-<?php echo $request_id ?: 'new'; ?>">Destination</label>
            <input id="route_destination-<?php echo $request_id ?: 'new'; ?>" type="text" name="route_destination" maxlength="100" value="<?php echo $value('route_destination'); ?>" required>
        </p>
        <p>
            <label for="requested_departure-<?php echo $request_id ?: 'new'; ?>">Preferred departure</label>
            <input id="requested_departure-<?php echo $request_id ?: 'new'; ?>" type="datetime-local" name="requested_departure" value="<?php echo $departure; ?>" required>
        </p>
        <button type="submit" name="submitBookingRequestBtn"><?php echo $request_id > 0 ? 'Resubmit request' : 'Submit booking request'; ?></button>
    </form>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request a Flight Booking</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderAuthControls(); ?>
    <h1>Request a Flight Booking</h1>
    <p>Submit your passenger and trip details for an administrator to review.</p>

    <?php if (($_GET['submitted'] ?? '') === 'new') { ?>
        <p>Your booking request was submitted for review.</p>
    <?php } elseif (($_GET['submitted'] ?? '') === 'updated') { ?>
        <p>Your updated request was sent back for review.</p>
    <?php } ?>
    <?php if ($formError !== '') { ?>
        <?php if (is_array($formError)) { ?>
            <ul>
                <?php foreach ($formError as $message) { ?>
                    <li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <p><?php echo htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
    <?php } ?>

    <?php renderBookingRequestForm(null, (int) ($formInput['request_id'] ?? 0) === 0 ? $formInput : []); ?>

    <h2>My booking requests</h2>
    <?php if ($requests) { ?>
        <?php foreach ($requests as $request) { ?>
            <section>
                <h3><?php echo htmlspecialchars($request['flight_number'], ENT_QUOTES, 'UTF-8'); ?>:
                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $request['status'])), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>Passenger: <?php echo htmlspecialchars($request['passenger_first_name'] . ' ' . $request['passenger_last_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Route: <?php echo htmlspecialchars($request['route_origin'] . ' to ' . $request['route_destination'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Preferred departure: <?php echo htmlspecialchars($request['requested_departure'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($request['admin_response']) { ?>
                    <p>Admin response: <?php echo nl2br(htmlspecialchars($request['admin_response'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php } ?>
                <?php if ($request['status'] === 'needs_changes') { ?>
                    <h4>Update and resubmit this request</h4>
                    <?php
                    $editInput = (int) ($formInput['request_id'] ?? 0) === (int) $request['request_id'] ? $formInput : [];
                    renderBookingRequestForm($request, $editInput);
                    ?>
                <?php } ?>
            </section>
            <hr>
        <?php } ?>
    <?php } else { ?>
        <p>You have not made any booking requests.</p>
    <?php } ?>
    <p><a href="index.php">Back to my flights</a></p>
</body>
</html>
