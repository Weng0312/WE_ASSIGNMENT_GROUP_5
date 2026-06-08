<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Module_1/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$club_id = $_GET['id'] ?? 0;

if (!$club_id) {
    header("Location: club_list.php");
    exit();
}

// GET CLUB DETAILS
$stmt = $pdo->prepare("SELECT * FROM club WHERE Club_ID = ?");
$stmt->execute([$club_id]);
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    die("Club not found.");
}

// CHECK MEMBERSHIP
$membership = $pdo->prepare("
    SELECT *
    FROM club_membership
    WHERE User_ID = ?
    AND Club_ID = ?
");
$membership->execute([$user_id, $club_id]);
$isJoined = $membership->rowCount() > 0;

// GET COMMITTEE MEMBERS
$committee = $pdo->prepare("
    SELECT 
        u.userName,
        u.User_ID,
        cm.membershipRole
    FROM user u
    INNER JOIN club_membership cm
        ON u.User_ID = cm.User_ID
    WHERE cm.Club_ID = ?
    AND cm.membershipRole != 'Member'
    AND cm.membershipStatus = 'Active'
");
$committee->execute([$club_id]);
$committeeMembers = $committee->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Details</title>

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/CSS/Module_1_SD_CSS.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body { background: #f5f7fb; }
        .club-container { padding: 30px; }
        .club-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .club-icon {
            width: 140px;
            height: 140px;
            background: #edf3ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .club-icon i {
            font-size: 70px;
            color: #0d6efd;
        }
        .committee-row {
            border-bottom: 1px solid #e9ecef;
            padding: 14px 0;
        }
        .member-avatar {
            width: 55px;
            height: 55px;
            background: #edf3ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0d6efd;
            font-size: 30px;
        }
        .role-badge {
            background: #edf3ff;
            color: #0d6efd;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        .join-btn,
        .leave-btn {
            min-width: 220px;
            border-radius: 12px;
            padding: 14px;
            font-size: 22px;
            font-weight: 600;
        }
    </style>
</head>

<body>

<?php include '../topbar.php'; ?>

<div id="wrapper">

    <?php include '../sidebar.php'; ?>

    <div id="content" class="w-100">

        <div class="container-fluid club-container">

            <h1 class="fw-bold mb-2">Club Details Page</h1>

            <a href="club_list.php" class="text-decoration-none">
                <i class="bi bi-chevron-left"></i> Back to Club List
            </a>

            <div class="club-card mt-4">

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="row align-items-center">

                    <div class="col-md-2 text-center">
                        <div class="club-icon mx-auto">
                            <i class="bi bi-laptop"></i>
                        </div>
                    </div>

                    <div class="col-md-10">
                        <h1 class="fw-bold">
                            <?php echo htmlspecialchars($club['clubName'] ?? ''); ?>
                        </h1>

                        <p class="fs-5 text-secondary">
                            <?php echo htmlspecialchars($club['clubDescription'] ?? ''); ?>
                        </p>

                        <p class="mb-2">
                            <i class="bi bi-person-fill me-2"></i>
                            <strong>Faculty Advisor:</strong>
                            <?php echo htmlspecialchars($club['clubAdvisor'] ?? ''); ?>
                        </p>

                        <p>
                            <i class="bi bi-calendar-event-fill me-2"></i>
                            <strong>Established:</strong>
                            <?php echo htmlspecialchars($club['clubEstablished'] ?? ''); ?>
                        </p>
                    </div>

                </div>

                <hr class="my-4">

                <h2 class="fw-bold mb-4">Committee Members</h2>

                <?php if ($committeeMembers): ?>
                    <?php foreach ($committeeMembers as $member): ?>

                        <div class="committee-row d-flex justify-content-between align-items-center">

                            <div class="d-flex align-items-center gap-3">
                                <div class="member-avatar">
                                    <i class="bi bi-person-fill"></i>
                                </div>

                                <div>
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($member['userName'] ?? ''); ?>
                                    </h5>

                                    <p class="mb-0 text-secondary">
                                        User ID: <?php echo htmlspecialchars($member['User_ID'] ?? ''); ?>
                                    </p>
                                </div>
                            </div>

                             <span class="role-badge">
                                 <?php echo htmlspecialchars($member['membershipRole'] ?? ''); ?>
                             </span>

                        </div>

                    <?php endforeach; ?>
                <?php else: ?>

                    <div class="alert alert-secondary">
                        No committee members available.
                    </div>

                <?php endif; ?>

                <div class="d-flex gap-4 mt-4">

                    <form action="join_club.php?id=<?php echo $club_id; ?>" method="POST">
                        <button
                            type="submit"
                            name="join_club"
                            class="btn btn-primary join-btn"
                            <?php echo $isJoined ? 'disabled' : ''; ?>
                        >
                            <i class="bi bi-people-fill me-2"></i>
                            <?php echo $isJoined ? 'Already Joined' : 'Join Club'; ?>
                        </button>
                    </form>

                    <form action="leave_club.php?id=<?php echo $club_id; ?>" method="POST">
                        <button
                            type="submit"
                            name="leave_club"
                            class="btn btn-outline-danger leave-btn"
                            <?php echo !$isJoined ? 'disabled' : ''; ?>
                        >
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Leave Club
                        </button>
                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>