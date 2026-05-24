<?php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/attendance_helper.php';

/** @var PDO $pdo */

$_SESSION['current_module'] = 'committee';
checkAttendanceAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['delete_attendance'])) {
    header('Location: attendance_management.php');
    exit();
}

$selectedEventID = $_POST['event_id'] ?? '';
$eventRegistrationID = $_POST['event_registration_id'] ?? '';

try {
    $deletePoints = $pdo->prepare("
        DELETE p
        FROM points p
        JOIN event_attendance ea
            ON p.Attendance_ID = ea.Attendance_ID
        WHERE ea.EventRegistrationID = ?
    ");

    $deletePoints->execute([
        $eventRegistrationID
    ]);

    $deleteAttendance = $pdo->prepare("
        DELETE FROM event_attendance
        WHERE EventRegistrationID = ?
    ");

    $deleteAttendance->execute([
        $eventRegistrationID
    ]);

    redirectAttendance(
        $selectedEventID,
        'Attendance record deleted successfully. ',
        'success'
    );

} catch (PDOException $e) {
    redirectAttendance(
        $selectedEventID,
        'Delete failed: ' . $e->getMessage(),
        'danger'
    );
}
?>