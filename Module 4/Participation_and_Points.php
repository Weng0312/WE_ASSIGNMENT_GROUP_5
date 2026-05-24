<?php
session_start();

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (
    !isset($_SESSION['user_id']) || 
    ($_SESSION['role'] !== 'Student' && strpos($_SESSION['role'], 'Committee') === false)
) {
    header("Location: ../Module 1/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Events joined count */
$sqlEventJoined = "
    SELECT COUNT(*) AS totalEventsJoined
    FROM event_registration
    WHERE User_ID = ?
";

$stmtEventJoined = $pdo->prepare($sqlEventJoined);
$stmtEventJoined->execute([$user_id]);
$eventJoinedData = $stmtEventJoined->fetch(PDO::FETCH_ASSOC);

$totalEventsJoined = $eventJoinedData['totalEventsJoined'] ?? 0;

/* Participation history - only show events that already have attendance */
$sqlHistory = "
    SELECT 
        e.eventDate,
        e.eventTitle,
        c.clubName,
        a.attendanceStatus AS status,

        CASE
            WHEN UPPER(TRIM(a.attendanceStatus)) = 'PRESENT' THEN 10
            WHEN UPPER(TRIM(a.attendanceStatus)) = 'LATE' THEN 5
            WHEN UPPER(TRIM(a.attendanceStatus)) = 'VOLUNTEER' THEN 5
            WHEN UPPER(TRIM(a.attendanceStatus)) = 'ABSENT' THEN -10
            ELSE 0
        END AS points

    FROM event_attendance a

    INNER JOIN event_registration er
        ON a.EventRegistrationID = er.EventRegistration_ID

    INNER JOIN event e
        ON er.Event_ID = e.Event_ID

    LEFT JOIN club c
        ON e.Club_ID = c.Club_ID

    WHERE er.User_ID = ?

    ORDER BY e.eventDate DESC
";

$stmtHistory = $pdo->prepare($sqlHistory);
$stmtHistory->execute([$user_id]);
$participationHistory = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

/* Total points: sum all attendance points */
$totalPoints = 0;

foreach ($participationHistory as $row) {
    $totalPoints += (int)$row['points'];
}

/* Recognition level */
if ($totalPoints < 20) {
    $recognitionLevel = "Warning";
    $recognitionMessage = "Reminder to participate more";
} elseif ($totalPoints <= 49) {
    $recognitionLevel = "Active";
    $recognitionMessage = "Eligible for participation certificate";
} elseif ($totalPoints <= 79) {
    $recognitionLevel = "Highly Active Student";
    $recognitionMessage = "Eligible for active student award / bonus points";
} else {
    $recognitionLevel = "Outstanding Participant";
    $recognitionMessage = "Eligible for leadership award / priority registration";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Participation & Points</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module 4/PAP_CSS.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php
        $dashboardType = 'student';
        include '../sidebar.php';
    ?>

    <div id="content">

        <div class="container-fluid pp-page">

            <h1 class="pp-title">Participation & Points</h1>
            <p class="pp-subtitle">Track your participation, points, and recognition level.</p>

            <div class="row g-4 mb-4">

                <div class="col-md-4">
                    <div class="pp-card d-flex align-items-center">
                        <div class="pp-icon green">
                            <i class="fa-solid fa-star"></i>
                        </div>

                        <div>
                            <h6>Total Points</h6>
                            <h2 class="text-success">
                                <?= htmlspecialchars($totalPoints) ?>
                            </h2>
                            <p>
                                <i class="fa-solid fa-arrow-trend-up"></i>
                                Points you have earned
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="pp-card d-flex align-items-center">
                        <div class="pp-icon purple">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>

                        <div>
                            <h6>Recognition Level</h6>
                            <h2 class="text-purple">
                                <?= htmlspecialchars($recognitionLevel) ?>
                            </h2>
                            <p>
                                <i class="fa-solid fa-award"></i>
                                <?= htmlspecialchars($recognitionMessage) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="pp-card d-flex align-items-center">
                        <div class="pp-icon blue">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div>
                            <h6>Events Joined</h6>
                            <h2 class="text-primary">
                                <?= htmlspecialchars($totalEventsJoined) ?>
                            </h2>
                            <p>
                                <i class="fa-solid fa-calendar-days"></i>
                                Events you have registered
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="pp-table-card">

                <h5>Participation History</h5>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Event</th>
                                <th>Club</th>
                                <th>Status</th>
                                <th>Points</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($participationHistory)): ?>

                                <?php foreach ($participationHistory as $row): ?>

                                    <?php
                                        $status = strtoupper(trim($row['status'] ?? ''));
                                    ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars(date("M d, Y", strtotime($row['eventDate']))) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row['eventTitle']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row['clubName'] ?? 'N/A') ?>
                                        </td>

                                        <td>
                                            <?php if ($status === 'PRESENT'): ?>
                                                <span class="badge bg-success">Present</span>

                                            <?php elseif ($status === 'LATE'): ?>
                                                <span class="badge bg-warning text-dark">Late</span>

                                            <?php elseif ($status === 'VOLUNTEER'): ?>
                                                <span class="badge bg-primary">Volunteer</span>

                                            <?php elseif ($status === 'ABSENT'): ?>
                                                <span class="badge bg-danger">Absent</span>

                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($row['status'] ?? 'Unknown') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row['points']) ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No attended event history found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>