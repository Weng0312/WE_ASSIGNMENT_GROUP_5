<?php

date_default_timezone_set('Asia/Kuala_Lumpur');

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

function getRecognitionStatus($totalPoints)
{
    if ($totalPoints < 20) {
        return 'Warning / Reminder to participate more';
    }

    if ($totalPoints >= 20 && $totalPoints <= 49) {
        return 'Eligible for participation certificate';
    }

    if ($totalPoints >= 50 && $totalPoints <= 79) {
        return 'Eligible for active student award / bonus points';
    }

    return 'Outstanding participant; eligible for leadership award';
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
        header("Location: ../Module_1/student_dashboard.php");
        exit();
    }
}

function getModule4BaseUrl()
{
    $scheme = 'http';

    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

    return $scheme . '://' . $host . rtrim($dir, '/');
}

function buildQrAttendanceUrl($eventID)
{
    return getModule4BaseUrl() . '/qr_attendance.php?event_id=' . urlencode($eventID);
}

/*
    Auto mark Absent:
    If event ended and approved student has no attendance record,
    insert Absent attendance and -10 points.
*/
function markAbsentAfterEvent(PDO $pdo, $eventID = null)
{
    $whereEvent = '';
    $params = [];

    if (!empty($eventID)) {
        $whereEvent = 'AND e.Event_ID = ?';
        $params[] = $eventID;
    }

    $stmt = $pdo->prepare("
        SELECT
            er.EventRegistration_ID
        FROM event_registration er

        JOIN event e
            ON er.Event_ID = e.Event_ID

        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID = ea.EventRegistrationID

        WHERE er.eventRegistrationStatus = 'Approved'
          AND ea.Attendance_ID IS NULL
          AND CONCAT(e.eventDate, ' ', e.eventEndTime) < NOW()
          $whereEvent
    ");

    $stmt->execute($params);

    $missingAttendanceRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($missingAttendanceRows)) {
        return 0;
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

    $insertPoints = $pdo->prepare("
        INSERT INTO points
        (
            pointsValue,
            Attendance_ID
        )
        VALUES (?, ?)
    ");

    $createdCount = 0;

    foreach ($missingAttendanceRows as $row) {
        $insertAttendance->execute([
            'MANUAL',
            'AUTO_ABSENT',
            'Absent',
            null,
            $row['EventRegistration_ID']
        ]);

        $attendanceID = $pdo->lastInsertId();

        $insertPoints->execute([
            getPoints('Absent'),
            $attendanceID
        ]);

        $createdCount++;
    }

    return $createdCount;
}

?>