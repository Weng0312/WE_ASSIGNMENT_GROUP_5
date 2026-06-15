<?php
session_start();

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/attendance_helper.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../Module_1/index.php");
    exit();
}

$selectedReport = $_GET['report_type'] ?? 'participants_per_event';
$selectedClubID = $_GET['club_id'] ?? 'all';
$selectedEventID = $_GET['event_id'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$search = trim($_GET['search'] ?? '');

$reportTitles = [
    'participants_per_event' => 'Number of Participants Per Event',
    'attendance_rate' => 'Attendance Rate For Each Event',
    'student_event_points' => 'Points Accumulated By Each Student Per Event',
    'overall_student_points' => 'Overall Semester / Date Range Points',
    'most_active_clubs' => 'Most Active Clubs Based On Event Organization'
];

if (!array_key_exists($selectedReport, $reportTitles)) {
    $selectedReport = 'participants_per_event';
}

/* ===============================
   MAIN FILTER SQL
================================ */
$where = [];
$params = [];

if ($selectedClubID !== 'all' && $selectedClubID !== '') {
    $where[] = "e.Club_ID = ?";
    $params[] = $selectedClubID;
}

if ($selectedEventID !== 'all' && $selectedEventID !== '') {
    $where[] = "e.Event_ID = ?";
    $params[] = $selectedEventID;
}

if (!empty($startDate)) {
    $where[] = "e.eventDate >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $where[] = "e.eventDate <= ?";
    $params[] = $endDate;
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/* ===============================
   FETCH SELECTED REPORT DATA
================================ */
$reportData = [];

if ($selectedReport === 'participants_per_event') {

    $reportWhere = $where;
    $reportParams = $params;
    if ($search !== '') {
        $reportWhere[] = "(e.eventTitle LIKE ? OR c.clubName LIKE ?)";
        $reportParams[] = "%$search%";
        $reportParams[] = "%$search%";
    }
    $reportWhereSQL = empty($reportWhere) ? '' : 'WHERE ' . implode(' AND ', $reportWhere);

    $stmt = $pdo->prepare("
        SELECT
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName,
            COUNT(DISTINCT er.EventRegistration_ID) AS totalParticipants
        FROM event e
        LEFT JOIN club c
            ON e.Club_ID = c.Club_ID
        LEFT JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        $reportWhereSQL
        GROUP BY
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName
        ORDER BY
            e.eventDate DESC,
            e.eventTitle ASC
    ");

    $stmt->execute($reportParams);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'attendance_rate') {

    $reportWhere = $where;
    $reportParams = $params;
    if ($search !== '') {
        $reportWhere[] = "(e.eventTitle LIKE ? OR c.clubName LIKE ?)";
        $reportParams[] = "%$search%";
        $reportParams[] = "%$search%";
    }
    $reportWhereSQL = empty($reportWhere) ? '' : 'WHERE ' . implode(' AND ', $reportWhere);

    $stmt = $pdo->prepare("
        SELECT
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName,

            COUNT(DISTINCT er.EventRegistration_ID) AS totalRegistered,

            SUM(
                CASE
                    WHEN ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
                    THEN 1
                    ELSE 0
                END
            ) AS totalAttended,

            SUM(
                CASE
                    WHEN ea.attendanceStatus = 'Absent'
                    THEN 1
                    ELSE 0
                END
            ) AS totalAbsent,

            ROUND(
                (
                    SUM(
                        CASE
                            WHEN ea.attendanceStatus IN ('Present', 'Late', 'Volunteer')
                            THEN 1
                            ELSE 0
                        END
                    ) / NULLIF(COUNT(DISTINCT er.EventRegistration_ID), 0)
                ) * 100,
                2
            ) AS attendanceRate

        FROM event e
        LEFT JOIN club c
            ON e.Club_ID = c.Club_ID
        LEFT JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID
        $reportWhereSQL
        GROUP BY
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            c.clubName
        ORDER BY
            e.eventDate DESC,
            e.eventTitle ASC
    ");

    $stmt->execute($reportParams);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'student_event_points') {

    $reportWhere = $where;
    $reportParams = $params;
    if ($search !== '') {
        $reportWhere[] = "(s.studentID LIKE ? OR u.userName LIKE ? OR e.eventTitle LIKE ? OR c.clubName LIKE ?)";
        $reportParams[] = "%$search%";
        $reportParams[] = "%$search%";
        $reportParams[] = "%$search%";
        $reportParams[] = "%$search%";
    }
    $reportWhereSQL = empty($reportWhere) ? '' : 'WHERE ' . implode(' AND ', $reportWhere);

    $stmt = $pdo->prepare("
        SELECT
            s.studentID,
            u.userName,
            e.eventTitle,
            e.eventDate,
            c.clubName,
            ea.attendanceStatus,
            COALESCE(p.pointsValue, 0) AS pointsValue
        FROM event e
        INNER JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        INNER JOIN student s
            ON er.User_ID = s.User_ID
        INNER JOIN user u
            ON er.User_ID = u.User_ID
        LEFT JOIN club c
            ON e.Club_ID = c.Club_ID
        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID
        LEFT JOIN points p
            ON ea.Attendance_ID = p.Attendance_ID
        $reportWhereSQL
        ORDER BY
            e.eventDate DESC,
            u.userName ASC
    ");

    $stmt->execute($reportParams);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'overall_student_points') {

    $reportWhere = $where;
    $reportParams = $params;
    if ($search !== '') {
        $reportWhere[] = "(s.studentID LIKE ? OR u.userName LIKE ?)";
        $reportParams[] = "%$search%";
        $reportParams[] = "%$search%";
    }
    $reportWhereSQL = empty($reportWhere) ? '' : 'WHERE ' . implode(' AND ', $reportWhere);

    $stmt = $pdo->prepare("
        SELECT
            s.studentID,
            u.userName,
            COUNT(DISTINCT e.Event_ID) AS totalEventsJoined,
            COALESCE(SUM(p.pointsValue), 0) AS totalPoints
        FROM event e
        INNER JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        INNER JOIN student s
            ON er.User_ID = s.User_ID
        INNER JOIN user u
            ON er.User_ID = u.User_ID
        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID
        LEFT JOIN points p
            ON ea.Attendance_ID = p.Attendance_ID
        $reportWhereSQL
        GROUP BY
            s.User_ID,
            s.studentID,
            u.userName
        ORDER BY
            totalPoints DESC,
            u.userName ASC
    ");

    $stmt->execute($reportParams);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($selectedReport === 'most_active_clubs') {

    $reportWhere = $where;
    $reportParams = $params;
    if ($search !== '') {
        $reportWhere[] = "c.clubName LIKE ?";
        $reportParams[] = "%$search%";
    }
    $reportWhereSQL = empty($reportWhere) ? '' : 'WHERE ' . implode(' AND ', $reportWhere);

    $stmt = $pdo->prepare("
        SELECT
            c.clubName,
            COUNT(DISTINCT e.Event_ID) AS eventsHeld,
            COUNT(DISTINCT er.EventRegistration_ID) AS totalParticipants,
            COALESCE(SUM(p.pointsValue), 0) AS totalPointsAwarded
        FROM club c
        LEFT JOIN event e
            ON c.Club_ID = e.Club_ID
        LEFT JOIN event_registration er
            ON e.Event_ID = er.Event_ID
        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID
        LEFT JOIN points p
            ON ea.Attendance_ID = p.Attendance_ID
        $reportWhereSQL
        GROUP BY
            c.Club_ID,
            c.clubName
        ORDER BY
            eventsHeld DESC,
            totalParticipants DESC,
            totalPointsAwarded DESC
    ");

    $stmt->execute($reportParams);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===============================
   GENERATE HTML FOR PDF
================================ */
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        h1 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 4px;
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            margin-bottom: 20px;
            color: #4b5563;
        }

        .filter-info {
            margin-bottom: 15px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

<h1><?= e($reportTitles[$selectedReport]) ?></h1>

<div class="subtitle">
    Generated on <?= date("F d, Y h:i A") ?>
</div>

<div class="filter-info">
    <strong>Club:</strong> <?= e($selectedClubID === 'all' ? 'All Clubs' : $selectedClubID) ?><br>
    <strong>Event:</strong> <?= e($selectedEventID === 'all' ? 'All Events' : $selectedEventID) ?><br>
    <strong>Date Range:</strong>
    <?= e(!empty($startDate) ? $startDate : 'All') ?>
    to
    <?= e(!empty($endDate) ? $endDate : 'All') ?>
    <?php if ($search !== ''): ?>
        <br><strong>Search Keyword:</strong> <?= e($search) ?>
    <?php endif; ?>
</div>

<?php if ($selectedReport === 'participants_per_event'): ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Event</th>
                <th>Club</th>
                <th>Event Date</th>
                <th>Total Participants</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($reportData)): ?>
                <?php $no = 1; ?>

                <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['eventTitle']) ?></td>
                        <td><?= e($row['clubName']) ?></td>
                        <td><?= e($row['eventDate']) ?></td>
                        <td><?= e($row['totalParticipants']) ?></td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($selectedReport === 'attendance_rate'): ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Event</th>
                <th>Club</th>
                <th>Registered</th>
                <th>Attended</th>
                <th>Absent</th>
                <th>Attendance Rate</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($reportData)): ?>
                <?php $no = 1; ?>

                <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['eventTitle']) ?></td>
                        <td><?= e($row['clubName']) ?></td>
                        <td><?= e($row['totalRegistered']) ?></td>
                        <td><?= e($row['totalAttended'] ?? 0) ?></td>
                        <td><?= e($row['totalAbsent'] ?? 0) ?></td>
                        <td><?= e(formatPercent($row['attendanceRate'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($selectedReport === 'student_event_points'): ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Event</th>
                <th>Club</th>
                <th>Status</th>
                <th>Points</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($reportData)): ?>
                <?php $no = 1; ?>

                <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['studentID']) ?></td>
                        <td><?= e($row['userName']) ?></td>
                        <td><?= e($row['eventTitle']) ?></td>
                        <td><?= e($row['clubName']) ?></td>
                        <td><?= e($row['attendanceStatus'] ?? 'Registered') ?></td>
                        <td><?= e($row['pointsValue']) ?></td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($selectedReport === 'overall_student_points'): ?>

    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Events Joined</th>
                <th>Total Points</th>
                <th>Recognition</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($reportData)): ?>
                <?php $rank = 1; ?>

                <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?= $rank++ ?></td>
                        <td><?= e($row['studentID']) ?></td>
                        <td><?= e($row['userName']) ?></td>
                        <td><?= e($row['totalEventsJoined']) ?></td>
                        <td><?= e($row['totalPoints']) ?></td>
                        <td><?= e(getRecognitionLevel((int)$row['totalPoints'])) ?></td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($selectedReport === 'most_active_clubs'): ?>

    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Club Name</th>
                <th>Events Held</th>
                <th>Total Participants</th>
                <th>Total Points Awarded</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($reportData)): ?>
                <?php $rank = 1; ?>

                <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?= $rank++ ?></td>
                        <td><?= e($row['clubName']) ?></td>
                        <td><?= e($row['eventsHeld']) ?></td>
                        <td><?= e($row['totalParticipants']) ?></td>
                        <td><?= e($row['totalPointsAwarded']) ?></td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php endif; ?>

</body>
</html>

<?php
$html = ob_get_clean();

/* ===============================
   PDF EXPORT
================================ */

/*
   This supports Dompdf if you installed it using Composer:
   composer require dompdf/dompdf
*/

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;

    if (class_exists('\Dompdf\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $fileName = str_replace(' ', '_', strtolower($reportTitles[$selectedReport])) . '.pdf';

        $dompdf->stream($fileName, ['Attachment' => true]);
        exit();
    }
}

/*
   Fallback:
   If Dompdf is not installed, this opens print page.
   User can choose "Save as PDF".
*/
echo $html;
echo "
<script>
    window.onload = function() {
        window.print();
    };
</script>
";
exit();
?>