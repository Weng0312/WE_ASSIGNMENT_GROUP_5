<?php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/attendance_helper.php';

/** @var PDO $pdo */

date_default_timezone_set('Asia/Kuala_Lumpur');

$eventID =
    $_GET['event_id'] ?? null;

$message =
    "";

$messageType =
    "";

if (!$eventID) {
    die("Invalid QR Code. Event ID not found.");
}

/* =========================
   QR LINK TO SAVE INTO DATABASE
========================= */
$qrLink = buildQrAttendanceUrl($eventID);

/* =========================
   GET EVENT DETAILS
========================= */
try {
    $eventStmt = $pdo->prepare("
        SELECT 
            Event_ID,
            eventTitle,
            eventDate,
            eventStartTime,
            eventEndTime,
            eventVenue
        FROM event
        WHERE Event_ID = ?
    ");

    $eventStmt->execute([
        $eventID
    ]);

    $event =
        $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found.");
    }

} catch (PDOException $e) {
    die("Error loading event: " . $e->getMessage());
}

/* =========================
   GET ALL APPROVED STUDENTS
   FOR QR ATTENDANCE DROPDOWN
========================= */
try {
    $studentListStmt = $pdo->prepare("
        SELECT
            s.User_ID,
            s.studentID,
            u.userName,
            ea.Attendance_ID,
            ea.attendanceStatus
        FROM event_registration er

        JOIN student s
            ON er.User_ID = s.User_ID

        JOIN user u
            ON er.User_ID = u.User_ID

        LEFT JOIN event_attendance ea
            ON er.EventRegistration_ID =
               ea.EventRegistrationID

        WHERE er.Event_ID = ?
          AND er.eventRegistrationStatus = 'Approved'

        ORDER BY s.studentID ASC
    ");

    $studentListStmt->execute([
        $eventID
    ]);

    $students =
        $studentListStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $students = [];
}

/* =========================
   SUBMIT QR ATTENDANCE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentID =
        trim($_POST['studentID'] ?? '');

    if (empty($studentID)) {

        $message =
            "Please select your Student ID.";

        $messageType =
            "error";

    } else {

        try {

            /* =========================
               CHECK STUDENT EXISTS
            ========================= */
            $studentStmt = $pdo->prepare("
                SELECT 
                    s.User_ID,
                    s.studentID,
                    u.userName
                FROM student s

                JOIN user u
                    ON s.User_ID = u.User_ID

                WHERE s.studentID = ?
                LIMIT 1
            ");

            $studentStmt->execute([
                $studentID
            ]);

            $student =
                $studentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {

                $message =
                    "Student ID not found.";

                $messageType =
                    "error";

            } else {

                $selectedUserID =
                    $student['User_ID'];

                /* =========================
                   CHECK APPROVED REGISTRATION
                ========================= */
                $registrationStmt = $pdo->prepare("
                    SELECT EventRegistration_ID
                    FROM event_registration
                    WHERE Event_ID = ?
                      AND User_ID = ?
                      AND eventRegistrationStatus = 'Approved'
                    LIMIT 1
                ");

                $registrationStmt->execute([
                    $eventID,
                    $selectedUserID
                ]);

                $registrationRow =
                    $registrationStmt->fetch(PDO::FETCH_ASSOC);

                if (!$registrationRow) {

                    $message =
                        "You are not registered or approved for this event.";

                    $messageType =
                        "error";

                } else {

                    $eventRegistrationID =
                        $registrationRow['EventRegistration_ID'];

                    /* =========================
                       CHECK DUPLICATE ATTENDANCE
                    ========================= */
                    $checkAttendanceStmt = $pdo->prepare("
                        SELECT Attendance_ID
                        FROM event_attendance
                        WHERE EventRegistrationID = ?
                        LIMIT 1
                    ");

                    $checkAttendanceStmt->execute([
                        $eventRegistrationID
                    ]);

                    $existingAttendance =
                        $checkAttendanceStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingAttendance) {

                        $message =
                            "You already submitted attendance for this event.";

                        $messageType =
                            "error";

                    } else {

                        /* =========================
                           AUTO STATUS SAME AS
                           MANUAL ATTENDANCE
                        ========================= */
                        $attendanceStatus =
                            "Present";

                        $checkInTime =
                            date('Y-m-d H:i:s');

                        $eventDateTime =
                            strtotime(
                                $event['eventDate'] .
                                ' ' .
                                $event['eventStartTime']
                            );

                        $currentDateTime =
                            time();

                        if ($currentDateTime > $eventDateTime) {
                            $attendanceStatus =
                                "Late";
                        }

                        /* =========================
                           INSERT QR ATTENDANCE
                           BASED ON MANUAL TABLE
                        ========================= */
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
                            'QR',
                            $qrLink,
                            $attendanceStatus,
                            $checkInTime,
                            $eventRegistrationID
                        ]);

                        $attendanceID =
                            $pdo->lastInsertId();

                        /* =========================
                           INSERT POINTS
                           BASED ON MANUAL POINTS
                        ========================= */
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

                        $message =
                            "Attendance submitted successfully." .
                            ".";

                        $messageType =
                            "success";
                    }
                }
            }

        } catch (PDOException $e) {

            $message =
                "Database error: " . $e->getMessage();

            $messageType =
                "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>QR Attendance Form</title>

    <link rel="stylesheet" href="../STYLE/CSS/Module 4/qr_attendance_CSS.css">

</head>

<body class="attendance-page">

<div class="attendance-card">

    <h1>QR Attendance Form</h1>

    <p class="subtitle center">
        Please enter your Student ID to submit attendance.
    </p>

    <div class="event-info">

        <p>
            <strong>Event:</strong>
            <?php echo htmlspecialchars($event['eventTitle']); ?>
        </p>

        <p>
            <strong>Date:</strong>
            <?php echo htmlspecialchars($event['eventDate']); ?>
        </p>

        <p>
            <strong>Start Time:</strong>
            <?php echo htmlspecialchars($event['eventStartTime']); ?>
        </p>

        <p>
            <strong>End Time:</strong>
            <?php echo htmlspecialchars($event['eventEndTime']); ?>
        </p>

        <p>
            <strong>Venue:</strong>
            <?php echo htmlspecialchars($event['eventVenue'] ?? 'N/A'); ?>
        </p>

    </div>

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label for="studentID">
                Student ID
            </label>

            <input 
                type="text" 
                id="studentID" 
                name="studentID" 
                placeholder="Type your Student ID"
                autocomplete="off"
                required
            >

            <div id="suggestionBox" class="suggestion-box"></div>

        </div>

        <div class="form-group">

            <label for="studentName">
                Student Name
            </label>

            <input 
                type="text" 
                id="studentName" 
                class="readonly"
                placeholder="Student name will show automatically"
                readonly
            >

        </div>

        <button type="submit" class="btn-submit">
            Submit Attendance
        </button>

    </form>

    <p class="note">
        Your attendance will be marked as Present or Late based on submission time.
    </p>

</div>

<script>
const students =
    <?php echo json_encode($students); ?>;

const studentInput =
    document.getElementById("studentID");

const studentNameInput =
    document.getElementById("studentName");

const suggestionBox =
    document.getElementById("suggestionBox");

studentInput.addEventListener("keyup", function () {

    const keyword =
        this.value.trim().toLowerCase();

    studentNameInput.value = "";
    suggestionBox.innerHTML = "";

    if (keyword === "") {

        suggestionBox.style.display = "none";
        return;
    }

    const filtered =
        students.filter(student =>
    {
        const studentID =
            student.studentID.toLowerCase();

        const userName =
            student.userName.toLowerCase();

        return studentID.startsWith(keyword)
            || userName.includes(keyword);
    });

    if (filtered.length === 0) {

        suggestionBox.innerHTML =
            "<div class='suggestion-item'>No student found</div>";

        suggestionBox.style.display = "block";
        return;
    }

    filtered.slice(0, 10).forEach(student => {

        const item =
            document.createElement("div");

        item.className =
            "suggestion-item";

        /*
            Dropdown only shows student ID.
            Name will only show after user chooses ID.
        */
        item.textContent =
            student.studentID;

        item.onclick = function () {

            studentInput.value =
                student.studentID;

            studentNameInput.value =
                student.userName;

            suggestionBox.style.display =
                "none";
        };

        suggestionBox.appendChild(item);
    });

    suggestionBox.style.display = "block";
});

document.addEventListener("click", function (e) {

    if (
        !studentInput.contains(e.target) &&
        !suggestionBox.contains(e.target)
    ) {
        suggestionBox.style.display = "none";
    }
});
</script>

</body>
</html>