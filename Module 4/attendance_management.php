<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

$_SESSION['current_module'] = 'committee';

$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);

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

//$message = '';
//$messageType = '';

$selectedEventID = $_GET['event_id'] ?? $_POST['event_id'] ?? '';

/* ===============================
   POINTS FUNCTION
================================ */
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

/* ===============================
   FETCH EVENTS
   COMMITTEE ONLY SEE OWN CLUB EVENTS
================================ */

if ($_SESSION['role'] === 'Administrator') {

    $eventStmt = $pdo->query("
        SELECT
            Event_ID,
            eventTitle,
            eventDate,
            eventStartTime,
            eventEndTime,
            eventVenue
        FROM event
        ORDER BY eventDate DESC
    ");

    $events =
        $eventStmt->fetchAll(PDO::FETCH_ASSOC);

} else {

    $eventStmt = $pdo->prepare("
        SELECT
            e.Event_ID,
            e.eventTitle,
            e.eventDate,
            e.eventStartTime,
            e.eventEndTime,
            e.eventVenue
        FROM event e
        WHERE EXISTS (
            SELECT 1
            FROM club_membership cm
            WHERE cm.Club_ID = e.Club_ID
              AND cm.User_ID = ?
              AND TRIM(cm.membershipStatus) = 'Active'
              AND TRIM(cm.membershipRole) <> 'Member'
        )
        ORDER BY e.eventDate DESC
    ");

    $eventStmt->execute([
        $_SESSION['user_id']
    ]);

    $events =
        $eventStmt->fetchAll(PDO::FETCH_ASSOC);
}

$allowedEventIDs =
    array_column($events, 'Event_ID');

if (
    empty($selectedEventID) ||
    !in_array($selectedEventID, $allowedEventIDs)
) {

    $selectedEventID =
        !empty($events)
            ? $events[0]['Event_ID']
            : '';
}

/* ===============================
   FETCH ALL REGISTERED STUDENTS
   FOR MANUAL ATTENDANCE DROPDOWN
================================ */
$pendingStudents = [];

if (!empty($selectedEventID)) {

    $pendingStudentStmt = $pdo->prepare("
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

    $pendingStudentStmt->execute([
        $selectedEventID
    ]);

    $pendingStudents =
        $pendingStudentStmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===============================
   FETCH ATTENDANCE TABLE
   + FETCH POINTS TABLE
   SHOW ONLY SAVED ATTENDANCE
================================ */
$tableStmt = $pdo->prepare("
    SELECT
        er.EventRegistration_ID,
        er.EventRegistration_ID
            AS EventRegistrationID,
        s.studentID,
        u.userName,
        ea.Attendance_ID,
        ea.attendanceType,
        ea.attendanceQR,
        ea.attendanceStatus,
        ea.checkInTime,
        p.pointsValue
    FROM event_registration er

    JOIN student s
        ON er.User_ID = s.User_ID

    JOIN user u
        ON er.User_ID = u.User_ID

    JOIN event_attendance ea
        ON er.EventRegistration_ID =
           ea.EventRegistrationID

    LEFT JOIN points p
        ON ea.Attendance_ID =
           p.Attendance_ID

    WHERE er.Event_ID = ?
      AND er.eventRegistrationStatus = 'Approved'

    ORDER BY
        ea.checkInTime DESC,
        u.userName ASC
");

$tableStmt->execute([
    $selectedEventID
]);

$students =
    $tableStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   EVENT INFO
================================ */
$eventInfo = null;

foreach ($events as $event) {

    if ($event['Event_ID']
        == $selectedEventID) {

        $eventInfo = $event;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Attendance Management</title>

    <link rel="stylesheet" href="../STYLE/CSS/Module 4/attendanceManagement_CSS.css">

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid p-4">

            <h1>Attendance Management Page</h1>

            <p class="subtitle">
                Mark attendance using QR scan
                or manual update.
            </p>

            <?php if (!empty($message)): ?>

                <div class="alert alert-<?php echo $messageType; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>

            <form method="GET" class="mb-4">

                <label class="fw-bold mb-2">
                    Select Event
                </label>

                <select name="event_id"
                        class="form-select w-50"
                        onchange="this.form.submit()">

                    <?php foreach ($events as $event): ?>

                        <option value="<?php echo $event['Event_ID']; ?>"
                            <?php
                            echo ($selectedEventID == $event['Event_ID'])
                                ? 'selected'
                                : '';
                            ?>>

                            <?php
                            echo htmlspecialchars($event['eventTitle']);
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </form>

            <div class="top-cards">

                <div class="card event-card">

                    <h5>Event Summary</h5>

                    <div class="event-info">

                        <div class="event-icon">

                            <i class="fa-solid fa-calendar-days"></i>

                        </div>

                        <div>

                            <h4>
                                <?php
                                echo htmlspecialchars(
                                    $eventInfo['eventTitle']
                                    ?? 'No Event Selected'
                                );
                                ?>
                            </h4>

                            <p>
                                <i class="fa-solid fa-calendar-days"></i>

                                <?php
                                echo htmlspecialchars(
                                    $eventInfo['eventDate']
                                    ?? '-'
                                );
                                ?>
                            </p>

                            <p>
                                <i class="fa-solid fa-location-dot"></i>

                                <?php
                                echo htmlspecialchars(
                                    $eventInfo['eventVenue']
                                    ?? '-'
                                );
                                ?>
                            </p>

                            <p>
                                <i class="fa-solid fa-clock"></i>

                                <?php
                                echo htmlspecialchars(
                                    $eventInfo['eventStartTime']
                                    ?? '-'
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $eventInfo['eventEndTime']
                                    ?? '-'
                                );
                                ?>
                            </p>

                        </div>

                    </div>

                </div>

                <div class="card qr-card">

                    <h5>QR Scan</h5>

                    <p>
                        Scan participant QR code
                        to mark attendance.
                    </p>

                    <?php
                    $qrLink =
                        "http://localhost/WE_ASSIGNMENT/Module%204/qr_attendance.php?event_id="
                        . urlencode($selectedEventID);

                    $qrImage =
                        "https://api.qrserver.com/v1/create-qr-code/?size=290x290&data="
                        . urlencode($qrLink);
                    ?>

                    <div class="qr-box">

                        <img src="<?php echo htmlspecialchars($qrImage); ?>"
                             alt="QR Code">

                    </div>

                    <button type="button"
                            class="btn-blue"
                            onclick="openQRPopup(
                                '<?php echo htmlspecialchars($qrImage, ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($eventInfo['eventTitle'] ?? 'Attendance QR Code', ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($qrLink, ENT_QUOTES); ?>'
                            )">

                        <i class="fa-solid fa-qrcode"></i>
                        Scan QR

                    </button>

                </div>

                <div class="card manual-card">

                    <h5>Manual Attendance</h5>

                    <p>
                        Search by student ID or name.
                        The system will automatically mark
                        the student as Present or Late
                        based on the event start time.
                    </p>

                    <div class="search-box mb-2">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="manualSearch"
                            placeholder="Search student ID or name..."
                            onkeyup="showStudentList()"
                        >

                    </div>

                    <div id="studentSuggestionBox"
                         class="student-suggestion-box"></div>

                    <form method="POST" action="manual_attendance.php">

                        <input type="hidden"
                               name="event_id"
                               value="<?php
                               echo htmlspecialchars(
                                   $selectedEventID
                               );
                               ?>">

                        <input type="hidden"
                               name="user_id"
                               id="selectedUserID">

                        <div id="selectedStudentText"
                             class="selected-student-text">

                            No student selected

                        </div>

                        <div class="d-flex gap-2 flex-wrap">

                            <button type="submit"
                                    name="mark_attendance"
                                    value="1"
                                    class="btn btn-outline-success">

                                <i class="fa-solid fa-save"></i>
                                Save Attendance

                            </button>

                        </div>

                    </form>

                </div>

            </div>

            <div class="legend mt-4">

                <strong>
                    Attendance Status Legend:
                </strong>

                <span>
                    <span class="dot green"></span>
                    Present
                </span>

                <span>
                    <span class="dot orange"></span>
                    Late
                </span>

                <span>
                    <span class="dot blue"></span>
                    Volunteer
                </span>

                <span>
                    <span class="dot red"></span>
                    Absent
                </span>

            </div>

            <div class="table-card mt-4">

                <table>

                    <thead>

                        <tr>

                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Check-In Time</th>
                            <th>Points</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($students)): ?>

                            <?php foreach ($students as $row): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['studentID']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['userName']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge <?php echo getBadgeClass($row['attendanceStatus']); ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['attendanceStatus']
                                            );
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['checkInTime']
                                            ?? '-'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['pointsValue']
                                            ?? getPoints($row['attendanceStatus'])
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <button type="button"
                                                class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editModal<?php echo $row['EventRegistrationID']; ?>">

                                            <i class="fa-solid fa-pen"></i>
                                            Edit

                                        </button>

                                        <form method="POST"
                                              action="delete_attendance.php"
                                              style="display:inline-block;"
                                              onsubmit="return confirm('Are you sure you want to delete this attendance record?');">

                                            <input type="hidden"
                                                   name="event_id"
                                                   value="<?php echo htmlspecialchars($selectedEventID); ?>">

                                            <input type="hidden"
                                                   name="event_registration_id"
                                                   value="<?php echo $row['EventRegistrationID']; ?>">

                                            <button type="submit"
                                                    name="delete_attendance"
                                                    class="btn btn-sm btn-danger">

                                                <i class="fa-solid fa-trash"></i>
                                                Delete

                                            </button>

                                        </form>

                                        <div class="modal fade"
                                             id="editModal<?php echo $row['EventRegistrationID']; ?>"
                                             tabindex="-1">

                                            <div class="modal-dialog">

                                                <div class="modal-content">

                                                    <form method="POST" action="edit_attendance.php">

                                                        <div class="modal-header">

                                                            <h5 class="modal-title">
                                                                Edit Attendance
                                                            </h5>

                                                            <button type="button"
                                                                    class="btn-close"
                                                                    data-bs-dismiss="modal"></button>

                                                        </div>

                                                        <div class="modal-body">

                                                            <input type="hidden"
                                                                   name="event_id"
                                                                   value="<?php echo htmlspecialchars($selectedEventID); ?>">

                                                            <input type="hidden"
                                                                   name="event_registration_id"
                                                                   value="<?php echo $row['EventRegistrationID']; ?>">

                                                            <div class="mb-3">

                                                                <label class="form-label">
                                                                    Student ID
                                                                </label>

                                                                <input type="text"
                                                                       class="form-control"
                                                                       value="<?php
                                                                       echo htmlspecialchars(
                                                                           $row['studentID']
                                                                       );
                                                                       ?>"
                                                                       readonly>

                                                            </div>

                                                            <div class="mb-3">

                                                                <label class="form-label">
                                                                    Name
                                                                </label>

                                                                <input type="text"
                                                                       class="form-control"
                                                                       value="<?php
                                                                       echo htmlspecialchars(
                                                                           $row['userName']
                                                                       );
                                                                       ?>"
                                                                       readonly>

                                                            </div>

                                                            <div class="mb-3">

                                                                <label class="form-label">
                                                                    Attendance Status
                                                                </label>

                                                                <select name="attendance_status"
                                                                        class="form-select"
                                                                        required>

                                                                    <option value="Present"
                                                                        <?php
                                                                        echo ($row['attendanceStatus'] === 'Present')
                                                                            ? 'selected'
                                                                            : '';
                                                                        ?>>
                                                                        Present
                                                                    </option>

                                                                    <option value="Late"
                                                                        <?php
                                                                        echo ($row['attendanceStatus'] === 'Late')
                                                                            ? 'selected'
                                                                            : '';
                                                                        ?>>
                                                                        Late
                                                                    </option>

                                                                    <option value="Volunteer"
                                                                        <?php
                                                                        echo ($row['attendanceStatus'] === 'Volunteer')
                                                                            ? 'selected'
                                                                            : '';
                                                                        ?>>
                                                                        Volunteer
                                                                    </option>

                                                                    <option value="Absent"
                                                                        <?php
                                                                        echo ($row['attendanceStatus'] === 'Absent')
                                                                            ? 'selected'
                                                                            : '';
                                                                        ?>>
                                                                        Absent
                                                                    </option>

                                                                </select>

                                                            </div>

                                                        </div>

                                                        <div class="modal-footer">

                                                            <button type="button"
                                                                    class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">
                                                                Cancel
                                                            </button>

                                                            <button type="submit"
                                                                    name="edit_attendance"
                                                                    class="btn btn-warning">

                                                                <i class="fa-solid fa-save"></i>
                                                                Update

                                                            </button>

                                                        </div>

                                                    </form>

                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7"
                                    class="text-center">

                                    No attendance records found
                                    for this event.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<div id="qrPopup" class="qr-popup">

    <div class="qr-popup-box">

        <button type="button"
                class="qr-close-x"
                onclick="closeQRPopup()">
            &times;
        </button>

        <h2 id="qrPopupEventName">
            Attendance QR Code
        </h2>

        <p class="qr-popup-text">
            Students scan this QR code to submit attendance.
        </p>

        <img id="qrPopupImage"
             src=""
             alt="Attendance QR Code"
             class="qr-popup-img">

        <div id="qrPopupLink"
             class="qr-popup-link"></div>

        <button type="button"
                class="qr-close-btn"
                onclick="closeQRPopup()">
            Close
        </button>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<script>
function openQRPopup(qrImage, eventName, qrLink)
{
    document.getElementById("qrPopupImage").src =
        qrImage;

    document.getElementById("qrPopupEventName").innerText =
        eventName;

    document.getElementById("qrPopupLink").innerText =
        qrLink;

    document.getElementById("qrPopup").style.display =
        "block";
}

function closeQRPopup()
{
    document.getElementById("qrPopup").style.display =
        "none";
}

document.getElementById("qrPopup").addEventListener(
    "click",
    function (e)
    {
        if (e.target === this)
        {
            closeQRPopup();
        }
    }
);

const students =
    <?php echo json_encode($pendingStudents); ?>;

function showStudentList()
{
    const keyword =
        document.getElementById("manualSearch")
            .value
            .toLowerCase()
            .trim();

    const box =
        document.getElementById("studentSuggestionBox");

    box.innerHTML = "";

    if (keyword === "")
    {
        box.style.display = "none";
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

    if (filtered.length === 0)
    {
        box.innerHTML =
            "<div class='student-suggestion-item'>No student found</div>";

        box.style.display = "block";
        return;
    }

    filtered.forEach(student =>
    {
        const item =
            document.createElement("div");

        item.className =
            "student-suggestion-item";

        item.textContent =
            student.studentID
            + " - "
            + student.userName;

        item.onclick = function ()
        {
            document.getElementById(
                "manualSearch"
            ).value =
                student.studentID
                + " - "
                + student.userName;

            document.getElementById(
                "selectedUserID"
            ).value =
                student.User_ID;

            document.getElementById(
                "selectedStudentText"
            ).innerText =
                "Selected: "
                + student.studentID
                + " - "
                + student.userName;

            box.style.display = "none";
        };

        box.appendChild(item);
    });

    box.style.display = "block";
}
</script>

</body>
</html>