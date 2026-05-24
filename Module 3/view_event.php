<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || strpos($_SESSION['role'], 'Committee') === false) {
    header("Location: ../Module 1/index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: event_management.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        e.*,
        c.clubName
    FROM event e
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    WHERE e.Event_ID = ?
");

$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: event_management.php");
    exit();
}

$regStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM event_registration
    WHERE Event_ID = ?
");

$regStmt->execute([$id]);
$totalRegistered = $regStmt->fetchColumn();

$capacity = (int)$event['eventMaxParticipant'];
$percentage = $capacity > 0 ? min(100, round(($totalRegistered / $capacity) * 100)) : 0;

$eventTimestamp = strtotime($event['eventDate']);
$currentTimestamp = strtotime(date('Y-m-d'));

if ($eventTimestamp == $currentTimestamp) {
    $statusBadge = '<span class="badge bg-success">Ongoing</span>';
} elseif ($eventTimestamp > $currentTimestamp) {
    $statusBadge = '<span class="badge bg-primary">Upcoming</span>';
} else {
    $statusBadge = '<span class="badge bg-secondary">Completed</span>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body {
            background: #f8f9fb;
        }

        #content {
            width: 100%;
            padding: 2rem;
        }

        .detail-card {
            background: white;
            border-radius: 18px;
            padding: 32px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }

        .info-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .section-title {
            font-size: 14px;
            color: #6b7280;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .description-box {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            line-height: 1.8;
        }

        .progress {
            height: 10px;
            border-radius: 999px;
        }

        .progress-bar {
            background-color: #003ca0;
        }
    </style>
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper" class="d-flex">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>
                    <h2 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($event['eventTitle']); ?>
                    </h2>

                    <p class="text-muted mb-0">
                        Detailed event information and participation overview.
                    </p>
                </div>

                <div class="d-flex gap-2">

                    <a href="edit_event.php?id=<?php echo $event['Event_ID']; ?>"
                       class="btn btn-success">
                        <i class="bi bi-pencil"></i> Edit
                    </a>

                    <a href="event_management.php"
                       class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>

                </div>

            </div>

            <div class="detail-card">

                <div class="row g-4 mb-4">

                    <div class="col-md-4">
                        <div class="info-label">Event Date</div>

                        <div class="info-value">
                            <?php echo date('d M Y', strtotime($event['eventDate'])); ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-label">Start Time</div>

                        <div class="info-value">
                            <?php echo date('h:i A', strtotime($event['eventStartTime'])); ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-label">End Time</div>

                        <div class="info-value">
                            <?php echo date('h:i A', strtotime($event['eventEndTime'])); ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label">Venue</div>

                        <div class="info-value">
                            <?php echo htmlspecialchars($event['eventVenue']); ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-label">Club</div>

                        <div class="info-value">
                            <?php echo htmlspecialchars($event['clubName']); ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-label">Status</div>

                        <div class="info-value">
                            <?php echo $statusBadge; ?>
                        </div>
                    </div>

                </div>

                <hr class="my-4">

                <div class="mb-4">

                    <div class="section-title">
                        Event Description
                    </div>

                    <div class="description-box">
                        <?php echo nl2br(htmlspecialchars($event['eventDescription'])); ?>
                    </div>

                </div>

                <div class="mb-3">

                    <div class="section-title">
                        Participant Overview
                    </div>

                    <div class="d-flex justify-content-between mb-2">

                        <span class="fw-semibold">
                            Registered Participants
                        </span>

                        <span class="fw-bold">
                            <?php echo $totalRegistered; ?>
                            /
                            <?php echo $event['eventMaxParticipant']; ?>
                        </span>

                    </div>

                    <div class="progress">
                        <div class="progress-bar"
                             role="progressbar"
                             style="width: <?php echo $percentage; ?>%">
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>