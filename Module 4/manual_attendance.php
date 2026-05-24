<?php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/attendance_helper.php';

/** @var PDO $pdo */

$_SESSION['current_module'] = 'committee';
checkAttendanceAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mark_attendance'])) {
    header('Location: attendance_management.php');
    exit();
}

$selectedEventID = $_POST['event_id'] ?? '';
$selectedUserID = $_POST['user_id'] ?? '';

if (empty($selectedEventID) || empty($selectedUserID)) {
    redirectAttendance($selectedEventID, 'Please select event and student.', 'danger');
}

try {
    $eventStmt = $pdo->prepare("
        SELECT
            eventDate,
            eventStartTime
        FROM event
        WHERE Event_ID = ?
    ");

    $eventStmt->execute([
        $selectedEventID
    ]);

    $eventRow =
        $eventStmt->fetch(PDO::FETCH_ASSOC);

    $attendanceStatus =
        'Present';

    $checkInTime =
        date('Y-m-d H:i:s');

    if ($eventRow) {
        $eventDateTime =
            strtotime(
                $eventRow['eventDate'] .
                ' ' .
                $eventRow['eventStartTime']
            );

        $currentDateTime =
            time();

        if ($currentDateTime > $eventDateTime) {
            $attendanceStatus =
                'Late';
        }
    }

    $registrationStmt = $pdo->prepare("
        SELECT EventRegistration_ID
        FROM event_registration
        WHERE Event_ID = ?
          AND User_ID = ?
          AND eventRegistrationStatus = 'Approved'
    ");

    $registrationStmt->execute([
        $selectedEventID,
        $selectedUserID
    ]);

    $registrationRow =
        $registrationStmt->fetch(PDO::FETCH_ASSOC);

    if (!$registrationRow) {
        redirectAttendance(
            $selectedEventID,
            'This student is not registered or approved for this event.',
            'danger'
        );
    }

    $eventRegistrationID =
        $registrationRow['EventRegistration_ID'];

    $checkAttendanceStmt = $pdo->prepare("
        SELECT Attendance_ID
        FROM event_attendance
        WHERE EventRegistrationID = ?
    ");

    $checkAttendanceStmt->execute([
        $eventRegistrationID
    ]);

    if ($checkAttendanceStmt->rowCount() > 0) {
        redirectAttendance(
            $selectedEventID,
            'Error: This student has already taken attendance for this event.',
            'danger'
        );
    }

    $stmt = $pdo->prepare("
        INSERT INTO event_attendance
        (
            attendanceType,
            attendanceQR,
            attendanceStatus,
            checkInTime,
            EventRegistrationID
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        'MANUAL',
        'NA',
        $attendanceStatus,
        $checkInTime,
        $eventRegistrationID
    ]);

    $attendanceID =
        $pdo->lastInsertId();

    $pointsValue =
        getPoints($attendanceStatus);

    $pointStmt = $pdo->prepare("
        INSERT INTO points
        (
            pointsValue,
            Attendance_ID
        )
        VALUES (?, ?)
    ");

    $pointStmt->execute([
        $pointsValue,
        $attendanceID
    ]);

    redirectAttendance(
        $selectedEventID,
        'Attendance saved successfully.',
        'success'
    );

} catch (PDOException $e) {
    redirectAttendance(
        $selectedEventID,
        'Database Error: ' . $e->getMessage(),
        'danger'
    );
}
?>