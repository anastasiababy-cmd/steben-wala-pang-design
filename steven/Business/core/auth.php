<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireLogin()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'core' ? '../login.php' : 'login.php'));
        exit();
    }

}

function currentUserId()
{
    requireLogin();
    return (int) $_SESSION['user_id'];
}

function requireRole($role)
{
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== $role) {
        http_response_code(403);
        exit('You are not authorized to access this page.');
    }
}

function requireAdmin()
{
    requireRole('admin');
}

function requireCustomer()
{
    requireRole('customer');
}

function renderAuthControls()
{
    if (empty($_SESSION['user_id'])) {
        return;
    }

    $name = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
    echo '<p>Signed in as ' . $name . '</p>';
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        echo '<p><a href="index.php">Admin dashboard</a> | <a href="admin_booking_requests.php">Booking requests</a> | <a href="admin_requests.php">Reschedule requests</a> | <a href="auditlog.php">Audit history</a></p>';
    } else {
        echo '<p><a href="index.php">My flights</a> | <a href="booking_request.php">Request a booking</a> | <a href="customer_profile.php">Edit my information</a></p>';
    }
    echo '<form action="core/logout.php" method="POST">';
    echo '<button type="submit">Log out</button>';
    echo '</form>';
}
