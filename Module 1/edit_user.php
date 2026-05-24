<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Security Check: Only Administrators can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: manage_users.php");
    exit();
}

$message = '';
$messageType = '';

// Fetch clubs
$clubStmt = $pdo->query("SELECT Club_ID, clubName FROM club ORDER BY clubName ASC");
$clubs = $clubStmt->fetchAll();

// Fetch user
$stmt = $pdo->prepare("
    SELECT 
        u.*, 
        cm.Club_ID,
        cm.membershipRole
    FROM user u
    LEFT JOIN club_membership cm ON u.User_ID = cm.User_ID
    WHERE u.User_ID = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $clubID = $_POST['clubID'] ?? null;
    $membershipRole = $_POST['membershipRole'] ?? null;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE user 
            SET userName = ?, userEmail = ?, userRole = ? 
            WHERE User_ID = ?
        ");
        $stmt->execute([$name, $email, $role, $id]);

        if ($role === 'Committee') {

            $check = $pdo->prepare("SELECT * FROM club_membership WHERE User_ID = ?");
            $check->execute([$id]);
            $membership = $check->fetch();

            if ($membership) {
                $stmt = $pdo->prepare("
                    UPDATE club_membership 
                    SET Club_ID = ?, membershipRole = ?
                    WHERE User_ID = ?
                ");
                $stmt->execute([$clubID, $membershipRole, $id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO club_membership 
                    (joinDate, membershipStatus, membershipRole, User_ID, Club_ID)
                    VALUES (CURDATE(), 'Active', ?, ?, ?)
                ");
                $stmt->execute([$membershipRole, $id, $clubID]);
            }

        } else {
            $stmt = $pdo->prepare("DELETE FROM club_membership WHERE User_ID = ?");
            $stmt->execute([$id]);
        }

        $pdo->commit();

        $message = "User updated successfully!";
        $messageType = "success";

        // Refresh data
        $stmt = $pdo->prepare("
            SELECT 
                u.*, 
                cm.Club_ID,
                cm.membershipRole
            FROM user u
            LEFT JOIN club_membership cm ON u.User_ID = cm.User_ID
            WHERE u.User_ID = ?
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

    } catch (Exception $e) {
        $pdo->rollBack();

        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid">

            <div class="row justify-content-center">

                <div class="col-lg-6">

                    <div class="card shadow-sm border-0">

                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Edit User Details</h5>
                        </div>

                        <div class="card-body p-4">

                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $messageType; ?>">
                                    <?php echo $message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Full Name</label>
                                    <input type="text" name="name" class="form-control"
                                        value="<?php echo htmlspecialchars($user['userName']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($user['userEmail']); ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Role</label>
                                    <select name="role" id="role" class="form-select" required>

                                        <option value="Administrator"
                                            <?php if ($user['userRole'] == 'Administrator') echo 'selected'; ?>>
                                            Administrator
                                        </option>

                                        <option value="Student"
                                            <?php if ($user['userRole'] == 'Student') echo 'selected'; ?>>
                                            Student
                                        </option>

                                        <option value="Committee"
                                            <?php if ($user['userRole'] == 'Committee') echo 'selected'; ?>>
                                            Committee
                                        </option>

                                    </select>
                                </div>

                                <div id="committeeSection" style="display:none;">

                                    <div class="border rounded p-3 mb-4 bg-light">

                                        <h6 class="fw-bold mb-4">
                                            Committee Assignment
                                        </h6>

                                        <div class="row">

                                            <div class="col-md-6 mb-3">

                                                <label class="form-label fw-bold">
                                                    Assigned Club
                                                </label>

                                                <select name="clubID" id="clubID" class="form-select">

                                                    <option value="">Select Club</option>

                                                    <?php foreach ($clubs as $club): ?>
                                                        <option value="<?php echo $club['Club_ID']; ?>"
                                                            <?php if (($user['Club_ID'] ?? '') == $club['Club_ID']) echo 'selected'; ?>>
                                                            <?php echo htmlspecialchars($club['clubName']); ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                </select>

                                            </div>

                                            <div class="col-md-6 mb-3">

                                                <label class="form-label fw-bold">
                                                    Committee Position
                                                </label>

                                                <select name="membershipRole" id="membershipRole" class="form-select">

                                                    <option value="">Select Position</option>

                                                    <option value="President"
                                                        <?php if (($user['membershipRole'] ?? '') == 'President') echo 'selected'; ?>>
                                                        President
                                                    </option>

                                                    <option value="Vice President"
                                                        <?php if (($user['membershipRole'] ?? '') == 'Vice President') echo 'selected'; ?>>
                                                        Vice President
                                                    </option>

                                                    <option value="Secretary"
                                                        <?php if (($user['membershipRole'] ?? '') == 'Secretary') echo 'selected'; ?>>
                                                        Secretary
                                                    </option>

                                                    <option value="Treasurer"
                                                        <?php if (($user['membershipRole'] ?? '') == 'Treasurer') echo 'selected'; ?>>
                                                        Treasurer
                                                    </option>

                                                    <option value="Event Coordinator"
                                                        <?php if (($user['membershipRole'] ?? '') == 'Event Coordinator') echo 'selected'; ?>>
                                                        Event Coordinator
                                                    </option>

                                                    <option value="Normal Committee"
                                                        <?php if (($user['membershipRole'] ?? '') == 'Normal Committee') echo 'selected'; ?>>
                                                        Normal Committee
                                                    </option>

                                                </select>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <div class="d-grid gap-2">

                                    <button type="submit" class="btn btn-primary fw-bold py-2">
                                        Update User
                                    </button>

                                    <a href="manage_users.php" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

<script>
function toggleCommitteeSection() {
    const role = document.getElementById("role");
    const committeeSection = document.getElementById("committeeSection");
    const clubID = document.getElementById("clubID");
    const membershipRole = document.getElementById("membershipRole");

    if (role.value === "Committee") {
        committeeSection.style.display = "block";
        clubID.required = true;
        membershipRole.required = true;
    } else {
        committeeSection.style.display = "none";
        clubID.required = false;
        membershipRole.required = false;
        clubID.value = "";
        membershipRole.value = "";
    }
}

document.getElementById("role").addEventListener("change", toggleCommitteeSection);
toggleCommitteeSection();
</script>

</body>
</html>