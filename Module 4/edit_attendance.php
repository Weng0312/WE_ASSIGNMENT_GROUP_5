<?php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/attendance_helper.php';

/** @var PDO $pdo */

$_SESSION['current_module'] = 'committee';
checkAttendanceAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['edit_attendance'])) {
    header('Location: attendance_management.php');
    exit();
}

$selectedEventID = $_POST['event_id'] ?? '';
$eventRegistrationID = $_POST['event_registration_id'] ?? '';
$attendanceStatus = $_POST['attendance_status'] ?? '';

$checkInTime =
    ($attendanceStatus === 'Absent')
        ? null
        : date('Y-m-d H:i:s');

try {
    $updateAttendance = $pdo->prepare("
        UPDATE event_attendance
        SET attendanceStatus = ?,
            checkInTime = ?
        WHERE EventRegistrationID = ?
    ");

    $updateAttendance->execute([
        $attendanceStatus,
        $checkInTime,
        $eventRegistrationID
    ]);

    $pointsValue =
        getPoints($attendanceStatus);

    $attendanceIDStmt = $pdo->prepare("
        SELECT Attendance_ID
        FROM event_attendance
        WHERE EventRegistrationID = ?
    ");

    $attendanceIDStmt->execute([
        $eventRegistrationID
    ]);

    $attendanceRow =
        $attendanceIDStmt->fetch(PDO::FETCH_ASSOC);

    if ($attendanceRow) {
        $attendanceID =
            $attendanceRow['Attendance_ID'];

        $checkPointStmt = $pdo->prepare("
            SELECT Point_ID
            FROM points
            WHERE Attendance_ID = ?
        ");

        $checkPointStmt->execute([
            $attendanceID
        ]);

        if ($checkPointStmt->rowCount() > 0) {
            $updatePoints = $pdo->prepare("
                UPDATE points
                SET pointsValue = ?
                WHERE Attendance_ID = ?
            ");

            $updatePoints->execute([
                $pointsValue,
                $attendanceID
            ]);

        } else {
            $insertPoints = $pdo->prepare("
                INSERT INTO points
                (
                    pointsValue,
                    Attendance_ID
                )
                VALUES (?, ?)
            ");

            $insertPoints->execute([
                $pointsValue,
                $attendanceID
            ]);
        }
    }

    redirectAttendance(
        $selectedEventID,
        'Attendance updated successfully. ',
        'success'
    );

} catch (PDOException $e) {
    redirectAttendance(
        $selectedEventID,
        'Update failed: ' . $e->getMessage(),
        'danger'
    );
}
?>  