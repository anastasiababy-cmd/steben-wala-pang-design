<?php
require_once __DIR__ . '/core/auth.php';
requireAdmin();
require_once __DIR__ . '/core/dbConfig.php';
require_once __DIR__ . '/core/models.php';

$events = getAuditHistory($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit History - Airline Booking System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php renderAuthControls(); ?>
    <h1>Audit History</h1>
    <p>Showing the latest 200 passenger and booking changes.</p>

    <?php if ($events) { ?>
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Record</th>
                    <th>Record ID</th>
                    <th>User</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['action_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($event['entity_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $event['entity_id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($event['actor_name'], ENT_QUOTES, 'UTF-8'); ?>
                            (<?php echo htmlspecialchars($event['actor_email'], ENT_QUOTES, 'UTF-8'); ?>)
                        </td>
                        <td><?php echo htmlspecialchars($event['occurred_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No changes have been recorded yet.</p>
    <?php } ?>

    <p><a href="index.php">Back to Passengers</a></p>
</body>
</html>
