<?php
session_start();
if (isset($_SESSION['error_message'])) {
    echo "<script>alert('" . $_SESSION['error_message'] . "');</script>";
    unset($_SESSION['error_message']);
}
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Allow Student and Committee
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] !== 'Student' && strpos($_SESSION['role'], 'Committee') === false)
) {
    header("Location: ../Module 1/index.php");
    exit();
}



$user_id = $_SESSION['user_id'];

// Leave Club Function
if (isset($_GET['leave'])) {

    $club_id = $_GET['leave'];

    $leaveStmt = $pdo->prepare("
        UPDATE club_membership
        SET membershipStatus = 'Inactive'
        WHERE User_ID = ? AND Club_ID = ?
    ");

    $leaveStmt->execute([$user_id, $club_id]);

    header("Location: my_membership.php");
    exit();
}

// Fetch Membership Data
$sql = "
    SELECT 
        c.Club_ID,
        c.clubName,
        c.clubDescription,
        cm.joinDate,
        cm.membershipStatus,
        cm.membershipRole
    FROM club_membership cm
    JOIN club c ON cm.Club_ID = c.Club_ID
    WHERE cm.User_ID = ?
    ORDER BY cm.joinDate DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Membership</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module 2/my_membership_CSS.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content">

        <div class="container-fluid py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">My Membership</h2>
                    <p class="text-muted">
                        View all clubs that you joined.
                    </p>
                </div>
            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <?php if (count($memberships) > 0): ?>

                        <div class="table-responsive">

                            <table class="table table-hover align-middle">

                                <thead class="table-dark">
                                    <tr>
                                        <th>Club</th>
                                        <th>Join Date</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th width="180">Action</th>
                                    </tr>
                                </thead>

                                <tbody>

                                <?php foreach ($memberships as $row): ?>

                                    <tr>

                                        <td>
                                            <div class="d-flex align-items-center">

                                                <div class="me-3">
                                                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center"
                                                         style="width:50px; height:50px;">
                                                        <i class="bi bi-people-fill"></i>
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($row['clubName']); ?>
                                                    </div>

                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($row['clubDescription']); ?>
                                                    </small>
                                                </div>

                                            </div>
                                        </td>

                                        <td>
                                            <?php echo date('d M Y', strtotime($row['joinDate'])); ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($row['membershipRole']); ?>
                                            </span>
                                        </td>

                                        <td>

                                            <?php if ($row['membershipStatus'] === 'Active'): ?>

                                                <span class="badge bg-success">
                                                    Active
                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-secondary">
                                                    Inactive
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?php if (
                                                $row['membershipRole'] === 'President' ||
                                                $row['membershipRole'] === 'Vice President' ||
                                                $row['membershipRole'] === 'Secretary' ||
                                                $row['membershipRole'] === 'Treasurer' ||
                                                $row['membershipRole'] === 'Committee'
                                            ): ?>

                                                <a href="switch_committee.php?club_id=<?php echo $row['Club_ID']; ?>"class="btn btn-primary btn-sm">
                                                <i class="bi bi-box-arrow-in-right"></i>Enter</a>

                                            <?php else: ?>
                                                <a href="switch_committee.php?club_id=<?php echo $row['Club_ID']; ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-box-arrow-in-right"></i>Enter</a>
                                            <?php endif; ?>

                                            <?php if ($row['membershipStatus'] === 'Active'): ?>

                                                <a href="my_membership.php?leave=<?php echo $row['Club_ID']; ?>"
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Are you sure you want to leave this club?')">

                                                    <i class="bi bi-trash"></i>
                                                    Leave
                                                </a>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="text-center py-5">

                            <i class="bi bi-people-fill fs-1 text-muted"></i>

                            <h4 class="mt-3">
                                No Membership Found
                            </h4>

                            <p class="text-muted">
                                You have not joined any clubs yet.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>