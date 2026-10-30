<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dbConfig.php';
require_once __DIR__ . '/models.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$user_id = currentUserId();

function runInTransaction($pdo, $callback)
{
    $pdo->beginTransaction();

    try {
        $result = $callback();
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

if (isset($_POST['registerAndBookBtn'])) {
    requireAdmin();
    $scheduledInput = trim($_POST['scheduled_departure'] ?? '');
    $scheduledDate = DateTime::createFromFormat('Y-m-d\TH:i', $scheduledInput, new DateTimeZone('+08:00'));
    if (!$scheduledDate || $scheduledDate->format('Y-m-d\TH:i') !== $scheduledInput
        || $scheduledDate <= new DateTime('now', new DateTimeZone('+08:00'))) {
        http_response_code(400);
        exit('Enter a valid future scheduled departure.');
    }

    runInTransaction($pdo, function () use ($pdo, $user_id, $scheduledInput) {
        $passenger_id = insertPassenger(
            $pdo,
            $user_id,
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            trim($_POST['email']),
            trim($_POST['phone'])
        );

        $customer = findUserByEmail($pdo, trim($_POST['email']));
        insertBooking(
            $pdo,
            $user_id,
            (int) $passenger_id,
            $customer ? (int) $customer['user_id'] : null,
            trim($_POST['flight_number']),
            trim($_POST['route_origin']),
            trim($_POST['route_destination']),
            trim($_POST['assigned_seat']),
            $_POST['ticket_price'],
            str_replace('T', ' ', $scheduledInput)
        );
    });

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['insertPassengerBtn'])) {
    requireAdmin();
    runInTransaction($pdo, function () use ($pdo, $user_id) {
        insertPassenger(
            $pdo,
            $user_id,
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            trim($_POST['email']),
            trim($_POST['phone'])
        );
    });

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['editPassengerBtn'])) {
    requireAdmin();
    runInTransaction($pdo, function () use ($pdo, $user_id) {
        updatePassenger(
            $pdo,
            (int) ($_GET['passenger_id'] ?? 0),
            $user_id,
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            trim($_POST['email']),
            trim($_POST['phone'])
        );
    });

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['deletePassengerBtn'])) {
    requireAdmin();
    runInTransaction($pdo, function () use ($pdo, $user_id) {
        deletePassenger($pdo, (int) ($_GET['passenger_id'] ?? 0), $user_id);
    });

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['updateBookingBtn'])) {
    requireAdmin();
    $scheduled_departure = trim($_POST['scheduled_departure'] ?? '');
    $scheduledDate = DateTime::createFromFormat('Y-m-d\TH:i', $scheduled_departure, new DateTimeZone('+08:00'));
    if (!$scheduledDate || $scheduledDate->format('Y-m-d\TH:i') !== $scheduled_departure
        || $scheduledDate <= new DateTime('now', new DateTimeZone('+08:00'))) {
        http_response_code(400);
        exit('Enter a valid future scheduled departure.');
    }

    runInTransaction($pdo, function () use ($pdo, $user_id, $scheduled_departure) {
        updateBooking(
            $pdo,
            (int) ($_GET['booking_id'] ?? 0),
            $user_id,
            trim($_POST['flight_number']),
            trim($_POST['route_origin']),
            trim($_POST['route_destination']),
            trim($_POST['assigned_seat']),
            $_POST['ticket_price'],
            str_replace('T', ' ', $scheduled_departure)
        );
    });

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['deleteBookingBtn'])) {
    requireAdmin();
    runInTransaction($pdo, function () use ($pdo, $user_id) {
        deleteBooking($pdo, (int) ($_GET['booking_id'] ?? 0), $user_id);
    });

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['requestRescheduleBtn'])) {
    requireCustomer();
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $requested_departure = trim($_POST['requested_departure'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $date = DateTime::createFromFormat('Y-m-d\TH:i', $requested_departure, new DateTimeZone('+08:00'));

    if (!customerOwnsBooking($pdo, $booking_id, $user_id)) {
        http_response_code(404);
        exit('Ticket not found.');
    }
    if (!$date || $date->format('Y-m-d\TH:i') !== $requested_departure
        || $date <= new DateTime('now', new DateTimeZone('+08:00'))
        || $reason === '' || strlen($reason) > 1000) {
        http_response_code(400);
        exit('Enter a future departure time and a reason of at most 1000 characters.');
    }

    runInTransaction($pdo, function () use ($pdo, $booking_id, $user_id, $date, $reason) {
        if (!createRescheduleRequest($pdo, $booking_id, $user_id, $date->format('Y-m-d H:i:s'), $reason)) {
            throw new RuntimeException('There is already a pending reschedule request for this ticket.');
        }
    });

    header('Location: ../reschedule.php');
    exit();
}

if (isset($_POST['submitBookingRequestBtn'])) {
    requireCustomer();
    $request_id = (int) ($_POST['request_id'] ?? 0);
    $passenger_first_name = trim($_POST['passenger_first_name'] ?? '');
    $passenger_last_name = trim($_POST['passenger_last_name'] ?? '');
    $passenger_email = strtolower(trim($_POST['passenger_email'] ?? ''));
    $passenger_phone = trim($_POST['passenger_phone'] ?? '');
    $flight_number = trim($_POST['flight_number'] ?? '');
    $route_origin = trim($_POST['route_origin'] ?? '');
    $route_destination = trim($_POST['route_destination'] ?? '');
    $requested_departure = trim($_POST['requested_departure'] ?? '');
    $date = DateTime::createFromFormat('Y-m-d\TH:i', $requested_departure, new DateTimeZone('+08:00'));

    $errors = [];
    if ($passenger_first_name === '' || strlen($passenger_first_name) > 50) {
        $errors[] = 'Passenger first name is required and must be 50 characters or fewer.';
    }
    if ($passenger_last_name === '' || strlen($passenger_last_name) > 50) {
        $errors[] = 'Passenger last name is required and must be 50 characters or fewer.';
    }
    if (!filter_var($passenger_email, FILTER_VALIDATE_EMAIL) || strlen($passenger_email) > 100) {
        $errors[] = 'Enter a valid passenger email address of 100 characters or fewer.';
    }
    if ($passenger_phone === '' || strlen($passenger_phone) > 20) {
        $errors[] = 'Passenger phone is required and must be 20 characters or fewer.';
    }
    if ($flight_number === '' || strlen($flight_number) > 10) {
        $errors[] = 'Flight number is required and must be 10 characters or fewer.';
    }
    if ($route_origin === '' || strlen($route_origin) > 100) {
        $errors[] = 'Origin is required and must be 100 characters or fewer.';
    }
    if ($route_destination === '' || strlen($route_destination) > 100) {
        $errors[] = 'Destination is required and must be 100 characters or fewer.';
    }
    if (!$date || $date->format('Y-m-d\TH:i') !== $requested_departure
        || $date <= new DateTime('now', new DateTimeZone('+08:00'))) {
        $errors[] = 'Choose a valid departure date and time in the future.';
    }

    if ($errors) {
        $_SESSION['booking_request_error'] = $errors;
        $_SESSION['booking_request_form'] = [
            'request_id' => $request_id,
            'passenger_first_name' => $passenger_first_name,
            'passenger_last_name' => $passenger_last_name,
            'passenger_email' => $passenger_email,
            'passenger_phone' => $passenger_phone,
            'flight_number' => $flight_number,
            'route_origin' => $route_origin,
            'route_destination' => $route_destination,
            'requested_departure' => $requested_departure
        ];
        header('Location: ../booking_request.php');
        exit();
    }

    runInTransaction($pdo, function () use (
        $pdo,
        $user_id,
        $request_id,
        $passenger_first_name,
        $passenger_last_name,
        $passenger_email,
        $passenger_phone,
        $flight_number,
        $route_origin,
        $route_destination,
        $date
    ) {
        if ($request_id > 0) {
            if (!updateBookingRequest(
                $pdo,
                $request_id,
                $user_id,
                $passenger_first_name,
                $passenger_last_name,
                $passenger_email,
                $passenger_phone,
                $flight_number,
                $route_origin,
                $route_destination,
                $date->format('Y-m-d H:i:s')
            )) {
                throw new RuntimeException('This request is not available for editing.');
            }
        } else {
            createBookingRequest(
                $pdo,
                $user_id,
                $passenger_first_name,
                $passenger_last_name,
                $passenger_email,
                $passenger_phone,
                $flight_number,
                $route_origin,
                $route_destination,
                $date->format('Y-m-d H:i:s')
            );
        }
    });

    header('Location: ../booking_request.php?submitted=' . ($request_id > 0 ? 'updated' : 'new'));
    exit();
}

if (isset($_POST['approveBookingRequestBtn']) || isset($_POST['rejectBookingRequestBtn']) || isset($_POST['returnBookingRequestBtn'])) {
    requireAdmin();
    $decision = isset($_POST['approveBookingRequestBtn'])
        ? 'approved'
        : (isset($_POST['returnBookingRequestBtn']) ? 'needs_changes' : 'rejected');
    $request_id = (int) ($_POST['request_id'] ?? 0);
    $response = trim($_POST['admin_response'] ?? '');
    $assigned_seat = trim($_POST['assigned_seat'] ?? '');
    $ticket_price = trim($_POST['ticket_price'] ?? '');

    if (strlen($response) > 1000) {
        http_response_code(400);
        exit('The response must be 1000 characters or fewer.');
    }
    if ($decision === 'needs_changes' && $response === '') {
        http_response_code(400);
        exit('Explain what the customer needs to change before returning the request.');
    }
    if ($decision === 'approved'
        && ($assigned_seat === '' || strlen($assigned_seat) > 5
            || !is_numeric($ticket_price) || (float) $ticket_price < 0 || (float) $ticket_price > 99999999.99)) {
        http_response_code(400);
        exit('Enter a seat of at most 5 characters and a valid ticket price.');
    }

    runInTransaction($pdo, function () use ($pdo, $request_id, $user_id, $decision, $response, $assigned_seat, $ticket_price) {
        if (!reviewBookingRequest(
            $pdo,
            $request_id,
            $user_id,
            $decision,
            $response,
            $decision === 'approved' ? $assigned_seat : null,
            $decision === 'approved' ? $ticket_price : null
        )) {
            throw new RuntimeException('This request no longer exists or has already been reviewed.');
        }
    });

    header('Location: ../admin_booking_requests.php');
    exit();
}

if (isset($_POST['approveRescheduleBtn']) || isset($_POST['rejectRescheduleBtn'])) {
    requireAdmin();
    $decision = isset($_POST['approveRescheduleBtn']) ? 'approved' : 'rejected';
    $request_id = (int) ($_POST['request_id'] ?? 0);
    $response = trim($_POST['admin_response'] ?? '');

    if (strlen($response) > 1000) {
        http_response_code(400);
        exit('The response must be 1000 characters or fewer.');
    }

    runInTransaction($pdo, function () use ($pdo, $request_id, $user_id, $decision, $response) {
        if (!reviewRescheduleRequest($pdo, $request_id, $user_id, $decision, $response)) {
            throw new RuntimeException('This request no longer exists or has already been reviewed.');
        }
    });

    header('Location: ../admin_requests.php');
    exit();
}

http_response_code(400);
exit('Invalid form submission.');
