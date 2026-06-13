<?php

date_default_timezone_set('Asia/Kuala_Lumpur');

/* ===============================
   COMMON DISPLAY HELPERS
================================ */
if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('selected')) {
    function selected($value1, $value2)
    {
        return ((string)$value1 === (string)$value2) ? 'selected' : '';
    }
}

if (!function_exists('formatPercent')) {
    function formatPercent($value)
    {
        return number_format((float)$value, 2) . '%';
    }
}

/* ===============================
   ATTENDANCE / POINT HELPERS
================================ */
if (!function_exists('getPoints')) {
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
}

if (!function_exists('getBadgeClass')) {
    function getBadgeClass($attendanceStatus)
    {
        if ($attendanceStatus === 'Present') {
            return 'present';
        }

        if ($attendanceStatus === 'Late') {
            return 'late';
        }

        if ($attendanceStatus === 'Volunteer') {
            return 'volunteer';
        }

        if ($attendanceStatus === 'Absent') {
            return 'absent';
        }

        return 'pending';
    }
}

if (!function_exists('getRecognitionStatus')) {
    function getRecognitionStatus($totalPoints)
    {
        $totalPoints = (int)$totalPoints;

        if ($totalPoints < 20) {
            return 'Warning / Reminder to participate more';
        }

        if ($totalPoints <= 49) {
            return 'Eligible for participation certificate';
        }

        if ($totalPoints <= 79) {
            return 'Eligible for active student award / bonus points';
        }

        return 'Outstanding participant; eligible for leadership award';
    }
}

if (!function_exists('getRecognitionLevel')) {
    function getRecognitionLevel($totalPoints)
    {
        $totalPoints = (int)$totalPoints;

        if ($totalPoints < 20) {
            return 'Warning / Reminder';
        }

        if ($totalPoints <= 49) {
            return 'Eligible for Certificate';
        }

        if ($totalPoints <= 79) {
            return 'Active Student Award';
        }

        return 'Outstanding Participant';
    }
}

if (!function_exists('getRecognitionClass')) {
    function getRecognitionClass($totalPoints)
    {
        $totalPoints = (int)$totalPoints;

        if ($totalPoints < 20) {
            return 'warning';
        }

        if ($totalPoints <= 49) {
            return 'certificate';
        }

        if ($totalPoints <= 79) {
            return 'active';
        }

        return 'outstanding';
    }
}

/* ===============================
   ACCESS / REDIRECT HELPERS
================================ */
if (!function_exists('redirectAttendance')) {
    function redirectAttendance($eventID, $message, $messageType)
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $messageType;

        header('Location: attendance_management.php?event_id=' . urlencode($eventID));
        exit();
    }
}

if (!function_exists('checkAttendanceAccess')) {
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
}

/* ===============================
   QR / LOCATION HELPERS
================================ */
if (!function_exists('getModule4BaseUrl')) {
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
}

if (!function_exists('buildQrAttendanceUrl')) {
    function buildQrAttendanceUrl($eventID)
    {
        return getModule4BaseUrl() . '/qr_attendance.php?event_id=' . urlencode($eventID);
    }
}

if (!function_exists('calculateDistanceMeters')) {
    function calculateDistanceMeters($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000;

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

/* ===============================
   AUTO MARK ABSENT AFTER EVENT ENDED
================================ */
if (!function_exists('markAbsentAfterEvent')) {
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
}