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
        $eventStartDateTime =
            strtotime($eventRow['eventDate'] . ' ' . $eventRow['eventStartTime']);

        $currentDateTime =
            strtotime($checkInTime);

        if ($currentDateTime > $eventStartDateTime) {
            $attendanceStatus = 'Late';
        }
    }

    $registrationStmt = $pdo->prepare("
        SELECT
            EventRegistration_ID
        FROM event_registration
        WHERE Event_ID = ?
          AND User_ID = ?
          AND eventRegistrationStatus = 'Approved'
        LIMIT 1
    ");

    $registrationStmt->execute([
        $selectedEventID,
        $selectedUserID
    ]);

    $registration =
        $registrationStmt->fetch(PDO::FETCH_ASSOC);

    if (!$registration) {
        redirectAttendance(
            $selectedEventID,
            'This student is not an approved participant for this event.',
            'danger'
        );
    }

    $eventRegistrationID =
        $registration['EventRegistration_ID'];

    $duplicateStmt = $pdo->prepare("
        SELECT
            Attendance_ID
        FROM event_attendance
        WHERE EventRegistrationID = ?
        LIMIT 1
    ");

    $duplicateStmt->execute([
        $eventRegistrationID
    ]);

    if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
        redirectAttendance(
            $selectedEventID,
            'Attendance already exists for this student.',
            'warning'
        );
    }

    $insertAttendance = $pdo->prepare("
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

    $insertAttendance->execute([
        'MANUAL',
        'MANUAL_ENTRY',
        $attendanceStatus,
        $checkInTime,
        $eventRegistrationID
    ]);

    $attendanceID =
        $pdo->lastInsertId();

    $insertPoints = $pdo->prepare("
        INSERT INTO points
        (
            pointsValue,
            Attendance_ID
        )
        VALUES (?, ?)
    ");

    $insertPoints->execute([
        getPoints($attendanceStatus),
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
        'Error saving attendance: ' . $e->getMessage(),
        'danger'
    );
}