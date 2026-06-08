<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: index.php");
    exit();
}

$message = '';
$messageType = '';

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM student WHERE User_ID = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM admin WHERE User_ID = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM club_membership WHERE User_ID = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM user WHERE User_ID = ?")->execute([$delete_id]);

        $pdo->commit();

        $message = "User deleted successfully.";
        $messageType = "success";

    } catch (Exception $e) {
        $pdo->rollBack();

        $message = "Error deleting user: " . $e->getMessage();
        $messageType = "danger";
    }
}

$search = trim($_GET['search'] ?? '');
$params = [];
$sql = "SELECT 
            u.*, 
            s.studentID, 
            a.staffID,
            cm.membershipRole
        FROM user u 
        LEFT JOIN student s ON u.User_ID = s.User_ID 
        LEFT JOIN admin a ON u.User_ID = a.User_ID
        LEFT JOIN club_membership cm ON u.User_ID = cm.User_ID";

if ($search !== '') {
    $sql .= " WHERE u.userName LIKE ? OR u.userEmail LIKE ? OR s.studentID LIKE ? OR a.staffID LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY u.User_ID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - FK System</title>
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include '../topbar.php'; ?>

    <div id="wrapper">

        <?php include '../sidebar.php'; ?>

        <div id="content">

            <div class="container-fluid">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2 fw-bold mb-0">Manage User Accounts</h1>

                    <a href="register.php" class="btn btn-primary">
                        Add New User
                    </a>
                </div>

                <!-- Search Filter Form -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body py-3">
                        <form method="GET" class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, student ID, or staff ID..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                                <?php if ($search !== ''): ?>
                                    <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0">

                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table table-hover align-middle mb-0">

                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Membership Role</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($users as $u): ?>

                                        <tr>
                                            <td class="ps-4">
                                                <span class="fw-bold">
                                                    <?php
                                                    echo ($u['userRole'] === 'Administrator')
                                                        ? htmlspecialchars($u['staffID'] ?? '-')
                                                        : htmlspecialchars($u['studentID'] ?? '-');
                                                    ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php echo htmlspecialchars($u['userName']); ?>
                                            </td>

                                            <td>
                                                <?php echo htmlspecialchars($u['userEmail']); ?>
                                            </td>

                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php 
                                                    $isCommitteeUser = ($u['userRole'] === 'Student' && !empty($u['membershipRole']) && $u['membershipRole'] !== 'Member');
                                                    echo $isCommitteeUser ? 'Student (Committee)' : htmlspecialchars($u['userRole']); 
                                                    ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php
                                                if ($isCommitteeUser) {
                                                    echo htmlspecialchars($u['membershipRole']);
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>

                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>

                                            <td class="text-end pe-4">

                                                <a href="edit_user.php?id=<?php echo $u['User_ID']; ?>"
                                                    class="btn btn-sm btn-outline-primary me-1">
                                                    Edit
                                                </a>

                                                <a href="manage_users.php?delete=<?php echo $u['User_ID']; ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                                    Delete
                                                </a>

                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>

</html>