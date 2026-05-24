<?php
function getPoints($attendanceStatus)
{
    if ($attendanceStatus === 'Present') {
        return 10;
    }

    if ($attendanceStatus === 'Late') {
        return 5;
    }

    if ($attendanceStatus === 'Volunteer') {
        return 5;
    }

    if ($attendanceStatus === 'Absent') {
        return -10;
    }

    return 0;
}

function redirectAttendance($eventID, $message, $messageType)
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $messageType;

    header('Location: attendance_management.php?event_id=' . urlencode($eventID));
    exit();
}

function checkAttendanceAccess()
{
    if (
        !isset($_SESSION['user_id']) ||
        (
            $_SESSION['role'] !== 'Administrator' &&
            strpos($_SESSION['role'], 'Committee') === false
        )
    ) {
        header("Location: ../Module 1/student_dashboard.php");
        exit();
    }
}
?>