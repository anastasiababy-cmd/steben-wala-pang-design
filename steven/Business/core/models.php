<?php

function findUserByEmail($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT * FROM Users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function getCustomerByID($pdo, $user_id)
{
    $stmt = $pdo->prepare(
        'SELECT user_id, username, first_name, last_name, email, address, age, created_at
         FROM Users
         WHERE user_id = ? AND role = \'customer\''
    );
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function updateCustomerProfile($pdo, $user_id, $username, $first_name, $last_name, $email, $address, $age)
{
    $stmt = $pdo->prepare(
        'UPDATE Users
         SET username = ?, first_name = ?, last_name = ?, email = ?, address = ?, age = ?
         WHERE user_id = ? AND role = \'customer\''
    );
    $stmt->execute([
        $username,
        $first_name,
        $last_name,
        $email,
        $address !== '' ? $address : null,
        $age !== '' ? $age : null,
        $user_id
    ]);

    return $stmt->rowCount() > 0;
}

function findUserByLogin($pdo, $login)
{
    $stmt = $pdo->prepare('SELECT * FROM Users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$login, $login]);
    return $stmt->fetch();
}

function createUser($pdo, $username, $first_name, $last_name, $email, $address, $age, $password_hash)
{
    $stmt = $pdo->prepare(
        'INSERT INTO Users (username, first_name, last_name, email, address, age, password_hash, role)
         VALUES (?, ?, ?, ?, ?, ?, ?, \'customer\')'
    );
    $stmt->execute([
        $username,
        $first_name,
        $last_name,
        $email,
        $address !== '' ? $address : null,
        $age !== '' ? $age : null,
        $password_hash
    ]);

    return $pdo->lastInsertId();
}

function recordAudit($pdo, $user_id, $entity_type, $entity_id, $action_type)
{
    $stmt = $pdo->prepare(
        'INSERT INTO AuditLog (entity_type, entity_id, action_type, actor_user_id)
         VALUES (?, ?, ?, ?)'
    );
    return $stmt->execute([$entity_type, $entity_id, $action_type, $user_id]);
}

function insertPassenger($pdo, $user_id, $first_name, $last_name, $email, $phone)
{
    $stmt = $pdo->prepare(
        'INSERT INTO Passengers
         (first_name, last_name, email, phone, added_by, updated_by, last_updated)
         VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(6))'
    );
    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $user_id,
        $user_id
    ]);

    $passenger_id = $pdo->lastInsertId();
    recordAudit($pdo, $user_id, 'Passenger', $passenger_id, 'CREATE');
    return $passenger_id;
}

function getAllPassengers($pdo)
{
    $sql = 'SELECT p.*,
                   CONCAT_WS(\' \', creator.first_name, creator.last_name) AS added_by_name,
                   CONCAT_WS(\' \', updater.first_name, updater.last_name) AS updated_by_name
            FROM Passengers p
            LEFT JOIN Users creator ON creator.user_id = p.added_by
            LEFT JOIN Users updater ON updater.user_id = p.updated_by';
    return $pdo->query($sql)->fetchAll();
}

function getPassengerByID($pdo, $passenger_id)
{
    $sql = 'SELECT p.*,
                   CONCAT_WS(\' \', creator.first_name, creator.last_name) AS added_by_name,
                   CONCAT_WS(\' \', updater.first_name, updater.last_name) AS updated_by_name
            FROM Passengers p
            LEFT JOIN Users creator ON creator.user_id = p.added_by
            LEFT JOIN Users updater ON updater.user_id = p.updated_by
            WHERE p.passenger_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$passenger_id]);
    return $stmt->fetch();
}

function updatePassenger($pdo, $passenger_id, $user_id, $first_name, $last_name, $email, $phone)
{
    $stmt = $pdo->prepare(
        'UPDATE Passengers
         SET first_name = ?, last_name = ?, email = ?, phone = ?,
             updated_by = ?, last_updated = CURRENT_TIMESTAMP(6)
         WHERE passenger_id = ?'
    );
    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $user_id,
        $passenger_id
    ]);

    if ($stmt->rowCount() === 0 && !getPassengerByID($pdo, $passenger_id)) {
        return false;
    }

    recordAudit($pdo, $user_id, 'Passenger', $passenger_id, 'UPDATE');
    return true;
}

function deletePassenger($pdo, $passenger_id, $user_id)
{
    if (!getPassengerByID($pdo, $passenger_id)) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT booking_id FROM Bookings WHERE passenger_id = ?');
    $stmt->execute([$passenger_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($bookings as $booking_id) {
        recordAudit($pdo, $user_id, 'Booking', $booking_id, 'DELETE');
    }

    $stmt = $pdo->prepare('DELETE FROM Bookings WHERE passenger_id = ?');
    $stmt->execute([$passenger_id]);

    recordAudit($pdo, $user_id, 'Passenger', $passenger_id, 'DELETE');
    $stmt = $pdo->prepare('DELETE FROM Passengers WHERE passenger_id = ?');
    $stmt->execute([$passenger_id]);
    return $stmt->rowCount() > 0;
}

function insertBooking($pdo, $user_id, $passenger_id, $customer_user_id, $flight_number, $route_origin, $route_destination, $assigned_seat, $ticket_price, $scheduled_departure)
{
    $stmt = $pdo->prepare(
        'INSERT INTO Bookings
         (passenger_id, flight_number, route_origin, route_destination, assigned_seat, ticket_price,
          scheduled_departure, customer_user_id, added_by, updated_by, last_updated)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(6))'
    );
    $stmt->execute([
        $passenger_id,
        $flight_number,
        $route_origin,
        $route_destination,
        $assigned_seat,
        $ticket_price,
        $scheduled_departure,
        $customer_user_id,
        $user_id,
        $user_id
    ]);

    $booking_id = $pdo->lastInsertId();
    recordAudit($pdo, $user_id, 'Booking', $booking_id, 'CREATE');
    return $booking_id;
}

function getBookingsByUser($pdo, $user_id)
{
    $sql = 'SELECT b.*,
                   p.first_name AS passenger_first_name,
                   p.last_name AS passenger_last_name,
                   p.email AS passenger_email,
                   CONCAT_WS(\' \', creator.first_name, creator.last_name) AS added_by_name,
                   CONCAT_WS(\' \', updater.first_name, updater.last_name) AS updated_by_name
            FROM Bookings b
            INNER JOIN Passengers p ON p.passenger_id = b.passenger_id
            LEFT JOIN Users creator ON creator.user_id = b.added_by
            LEFT JOIN Users updater ON updater.user_id = b.updated_by
            WHERE b.customer_user_id = ?
            ORDER BY b.scheduled_departure, b.booking_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getAllBookings($pdo)
{
    $sql = 'SELECT b.*,
                   p.first_name AS passenger_first_name,
                   p.last_name AS passenger_last_name,
                   p.email AS passenger_email,
                   CONCAT_WS(\' \', creator.first_name, creator.last_name) AS added_by_name,
                   CONCAT_WS(\' \', updater.first_name, updater.last_name) AS updated_by_name
            FROM Bookings b
            INNER JOIN Passengers p ON p.passenger_id = b.passenger_id
            LEFT JOIN Users creator ON creator.user_id = b.added_by
            LEFT JOIN Users updater ON updater.user_id = b.updated_by
            ORDER BY b.booking_id DESC';
    return $pdo->query($sql)->fetchAll();
}

function customerOwnsBooking($pdo, $booking_id, $user_id)
{
    $stmt = $pdo->prepare('SELECT 1 FROM Bookings WHERE booking_id = ? AND customer_user_id = ?');
    $stmt->execute([$booking_id, $user_id]);
    return (bool) $stmt->fetchColumn();
}

function createRescheduleRequest($pdo, $booking_id, $user_id, $requested_departure, $reason)
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM RescheduleRequests
         WHERE booking_id = ? AND status = \'pending\''
    );
    $stmt->execute([$booking_id]);
    if ((int) $stmt->fetchColumn() > 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO RescheduleRequests
         (booking_id, customer_user_id, requested_departure, reason)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $booking_id,
        $user_id,
        $requested_departure,
        $reason
    ]);
    recordAudit($pdo, $user_id, 'RescheduleRequest', $pdo->lastInsertId(), 'CREATE');
    return true;
}

function createBookingRequest($pdo, $user_id, $passenger_first_name, $passenger_last_name, $passenger_email, $passenger_phone, $flight_number, $route_origin, $route_destination, $requested_departure)
{
    $stmt = $pdo->prepare(
        'INSERT INTO BookingRequests
         (customer_user_id, passenger_first_name, passenger_last_name, passenger_email, passenger_phone,
          flight_number, route_origin, route_destination, requested_departure)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $user_id,
        $passenger_first_name,
        $passenger_last_name,
        $passenger_email,
        $passenger_phone,
        $flight_number,
        $route_origin,
        $route_destination,
        $requested_departure
    ]);

    $request_id = $pdo->lastInsertId();
    recordAudit($pdo, $user_id, 'BookingRequest', $request_id, 'CREATE');
    return $request_id;
}

function updateBookingRequest($pdo, $request_id, $user_id, $passenger_first_name, $passenger_last_name, $passenger_email, $passenger_phone, $flight_number, $route_origin, $route_destination, $requested_departure)
{
    $stmt = $pdo->prepare(
        'UPDATE BookingRequests
         SET passenger_first_name = ?, passenger_last_name = ?, passenger_email = ?, passenger_phone = ?,
             flight_number = ?, route_origin = ?, route_destination = ?, requested_departure = ?,
             status = \'pending\', admin_response = NULL, reviewed_by = NULL, reviewed_at = NULL
         WHERE request_id = ? AND customer_user_id = ? AND status = \'needs_changes\''
    );
    $stmt->execute([
        $passenger_first_name,
        $passenger_last_name,
        $passenger_email,
        $passenger_phone,
        $flight_number,
        $route_origin,
        $route_destination,
        $requested_departure,
        $request_id,
        $user_id
    ]);

    if ($stmt->rowCount() > 0) {
        recordAudit($pdo, $user_id, 'BookingRequest', $request_id, 'UPDATE');
        return true;
    }
    return false;
}

function getBookingRequests($pdo)
{
    $sql = 'SELECT r.*, u.first_name AS customer_first_name, u.last_name AS customer_last_name,
                   u.email AS customer_email
            FROM BookingRequests r
            INNER JOIN Users u ON u.user_id = r.customer_user_id
            ORDER BY (r.status = \'pending\') DESC, r.created_at DESC';
    return $pdo->query($sql)->fetchAll();
}

function getBookingRequestsByUser($pdo, $user_id)
{
    $stmt = $pdo->prepare(
        'SELECT * FROM BookingRequests
         WHERE customer_user_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function reviewBookingRequest($pdo, $request_id, $admin_id, $decision, $response, $assigned_seat = null, $ticket_price = null)
{
    if (!in_array($decision, ['approved', 'rejected', 'needs_changes'], true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM BookingRequests
         WHERE request_id = ? AND status = \'pending\'
         FOR UPDATE'
    );
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    if (!$request) {
        return false;
    }

    if ($decision === 'approved') {
        $stmt = $pdo->prepare('SELECT passenger_id FROM Passengers WHERE email = ?');
        $stmt->execute([$request['passenger_email']]);
        $passenger_id = $stmt->fetchColumn();
        if (!$passenger_id) {
            $passenger_id = insertPassenger(
                $pdo,
                $admin_id,
                $request['passenger_first_name'],
                $request['passenger_last_name'],
                $request['passenger_email'],
                $request['passenger_phone']
            );
        }
        insertBooking(
            $pdo,
            $admin_id,
            (int) $passenger_id,
            (int) $request['customer_user_id'],
            $request['flight_number'],
            $request['route_origin'],
            $request['route_destination'],
            $assigned_seat,
            $ticket_price,
            $request['requested_departure']
        );
    }

    $stmt = $pdo->prepare(
        'UPDATE BookingRequests
         SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, admin_response = ?
         WHERE request_id = ? AND status = \'pending\''
    );
    $stmt->execute([$decision, $admin_id, $response, $request_id]);
    if ($stmt->rowCount() > 0) {
        recordAudit($pdo, $admin_id, 'BookingRequest', $request_id, 'UPDATE');
        return true;
    }
    return false;
}

function getRescheduleRequests($pdo)
{
    $sql = 'SELECT r.*, b.flight_number, b.scheduled_departure, b.route_origin, b.route_destination,
                   p.first_name AS passenger_first_name, p.last_name AS passenger_last_name,
                   p.email AS passenger_email, u.username AS customer_username
            FROM RescheduleRequests r
            INNER JOIN Bookings b ON b.booking_id = r.booking_id
            INNER JOIN Passengers p ON p.passenger_id = b.passenger_id
            INNER JOIN Users u ON u.user_id = r.customer_user_id
            ORDER BY (r.status = \'pending\') DESC, r.created_at DESC';
    return $pdo->query($sql)->fetchAll();
}

function getRescheduleRequestsByUser($pdo, $user_id)
{
    $sql = 'SELECT r.*, b.flight_number, b.scheduled_departure, b.route_origin, b.route_destination
            FROM RescheduleRequests r
            INNER JOIN Bookings b ON b.booking_id = r.booking_id
            WHERE r.customer_user_id = ?
            ORDER BY r.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function reviewRescheduleRequest($pdo, $request_id, $admin_id, $decision, $response)
{
    $stmt = $pdo->prepare(
        'SELECT booking_id, requested_departure
         FROM RescheduleRequests
         WHERE request_id = ? AND status = \'pending\''
    );
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    if (!$request) {
        return false;
    }

    if ($decision === 'approved') {
        $stmt = $pdo->prepare(
            'UPDATE Bookings
             SET scheduled_departure = ?, updated_by = ?, last_updated = CURRENT_TIMESTAMP(6)
             WHERE booking_id = ?'
        );
        $stmt->execute([$request['requested_departure'], $admin_id, $request['booking_id']]);
        recordAudit($pdo, $admin_id, 'Booking', $request['booking_id'], 'UPDATE');
    }

    $stmt = $pdo->prepare(
        'UPDATE RescheduleRequests
         SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, admin_response = ?
         WHERE request_id = ? AND status = \'pending\''
    );
    $stmt->execute([$decision, $admin_id, $response, $request_id]);
    if ($stmt->rowCount() > 0) {
        recordAudit($pdo, $admin_id, 'RescheduleRequest', $request_id, 'UPDATE');
        return true;
    }
    return false;
}

function getBookingsByPassenger($pdo, $passenger_id)
{
    $sql = 'SELECT b.*,
                   CONCAT_WS(\' \', creator.first_name, creator.last_name) AS added_by_name,
                   CONCAT_WS(\' \', updater.first_name, updater.last_name) AS updated_by_name
            FROM Bookings b
            LEFT JOIN Users creator ON creator.user_id = b.added_by
            LEFT JOIN Users updater ON updater.user_id = b.updated_by
            WHERE b.passenger_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$passenger_id]);
    return $stmt->fetchAll();
}

function getBookingByID($pdo, $booking_id)
{
    $sql = 'SELECT b.*,
                   CONCAT_WS(\' \', creator.first_name, creator.last_name) AS added_by_name,
                   CONCAT_WS(\' \', updater.first_name, updater.last_name) AS updated_by_name
            FROM Bookings b
            LEFT JOIN Users creator ON creator.user_id = b.added_by
            LEFT JOIN Users updater ON updater.user_id = b.updated_by
            WHERE b.booking_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id]);
    return $stmt->fetch();
}

function updateBooking($pdo, $booking_id, $user_id, $flight_number, $route_origin, $route_destination, $assigned_seat, $ticket_price, $scheduled_departure)
{
    $stmt = $pdo->prepare(
        'UPDATE Bookings
         SET flight_number = ?, route_origin = ?, route_destination = ?,
             assigned_seat = ?, ticket_price = ?, scheduled_departure = ?, updated_by = ?,
             last_updated = CURRENT_TIMESTAMP(6)
         WHERE booking_id = ?'
    );
    $stmt->execute([
        $flight_number,
        $route_origin,
        $route_destination,
        $assigned_seat,
        $ticket_price,
        $scheduled_departure,
        $user_id,
        $booking_id
    ]);

    if ($stmt->rowCount() === 0 && !getBookingByID($pdo, $booking_id)) {
        return false;
    }

    recordAudit($pdo, $user_id, 'Booking', $booking_id, 'UPDATE');
    return true;
}

function deleteBooking($pdo, $booking_id, $user_id)
{
    $stmt = $pdo->prepare('DELETE FROM Bookings WHERE booking_id = ?');
    $stmt->execute([$booking_id]);

    if ($stmt->rowCount() === 0) {
        return false;
    }

    recordAudit($pdo, $user_id, 'Booking', $booking_id, 'DELETE');
    return true;
}

function getAuditHistory($pdo)
{
    $sql = 'SELECT a.entity_type, a.entity_id, a.action_type, a.occurred_at,
                   CONCAT_WS(\' \', u.first_name, u.last_name) AS actor_name,
                   u.email AS actor_email
            FROM AuditLog a
            INNER JOIN Users u ON u.user_id = a.actor_user_id
            ORDER BY a.occurred_at DESC, a.audit_id DESC
            LIMIT 200';
    return $pdo->query($sql)->fetchAll();
}
