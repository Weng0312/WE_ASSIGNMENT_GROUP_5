<?php
// ==========================================
// [SESSION INITIALIZATION & ADMIN CHECK]
// ==========================================
session_start();
require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: index.php");
    exit();
}

$message = '';
$messageType = '';

// ==========================================
// [USER ACCOUNT DELETION TRANSACTION]
// ==========================================
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

// ==========================================
// [SEARCH FILTERING & RETRIEVAL LOGIC]
// ==========================================
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role_filter'] ?? 'all';
$programme_filter = $_GET['programme_filter'] ?? 'all';
$status_filter = $_GET['status_filter'] ?? 'all';

$params = [];
$where_clauses = [];

if ($search !== '') {
    $where_clauses[] = "(u.userName LIKE ? OR u.userEmail LIKE ? OR s.studentID LIKE ? OR a.staffID LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== 'all') {
    if ($role_filter === 'Committee') {
        $where_clauses[] = "u.userRole = 'Student' AND cm.membershipRole IS NOT NULL AND cm.membershipRole != 'Member'";
    } else {
        $where_clauses[] = "u.userRole = ?";
        $params[] = $role_filter;
    }
}

if ($programme_filter !== 'all') {
    $where_clauses[] = "s.programmeName = ?";
    $params[] = $programme_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "u.userStatus = ?";
    $params[] = $status_filter;
}

$sql = "SELECT 
            u.*, 
            s.studentID, 
            s.programmeName,
            a.staffID,
            cm.membershipRole
        FROM user u 
        LEFT JOIN student s ON u.User_ID = s.User_ID 
        LEFT JOIN admin a ON u.User_ID = a.User_ID
        LEFT JOIN club_membership cm ON u.User_ID = cm.User_ID";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body>

    <?php include '../topbar.php'; ?>

    <div id="wrapper">

        <?php include '../sidebar.php'; ?>

        <!-- ========================================== -->
        <!-- [USER ACCOUNTS DASHBOARD & CRUD VIEW] -->
        <!-- ========================================== -->
        <div id="content">

            <div class="container-fluid">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2 fw-bold mb-0">Manage User Accounts</h1>

                    <a href="register.php" class="btn btn-primary">
                        Add New User
                    </a>
                </div>

                <!-- Search & Filter Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <form method="GET" class="row g-3">
                            <!-- Search Input -->
                            <div class="col-md-3">
                                <label for="searchInput" class="form-label small fw-bold text-secondary">Search
                                    Keyword</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search by name, email, ID..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>

                            <!-- Role Filter -->
                            <div class="col-md-3">
                                <label for="roleFilterSelect" class="form-label small fw-bold text-secondary">User
                                    Role</label>
                                <select name="role_filter" id="roleFilterSelect" class="form-select form-select-sm">
                                    <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles
                                    </option>
                                    <option value="Student" <?php echo $role_filter === 'Student' ? 'selected' : ''; ?>>
                                        Student (Regular)</option>
                                    <option value="Committee" <?php echo $role_filter === 'Committee' ? 'selected' : ''; ?>>Student (Committee)</option>
                                    <option value="Administrator" <?php echo $role_filter === 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>

                            <!-- Programme Filter -->
                            <div class="col-md-3">
                                <label for="programmeFilterSelect"
                                    class="form-label small fw-bold text-secondary">Academic Programme</label>
                                <select name="programme_filter" id="programmeFilterSelect"
                                    class="form-select form-select-sm">
                                    <option value="all" <?php echo $programme_filter === 'all' ? 'selected' : ''; ?>>All
                                        Programmes</option>
                                    <option value="Software Engineering" <?php echo $programme_filter === 'Software Engineering' ? 'selected' : ''; ?>>Software Engineering</option>
                                    <option value="Multimedia Software" <?php echo $programme_filter === 'Multimedia Software' ? 'selected' : ''; ?>>Multimedia Software</option>
                                    <option value="Computer System & Networking" <?php echo $programme_filter === 'Computer System & Networking' ? 'selected' : ''; ?>>
                                        Computer System & Networking</option>
                                    <option value="Cyber Security" <?php echo $programme_filter === 'Cyber Security' ? 'selected' : ''; ?>>Cyber Security</option>
                                </select>
                            </div>

                            <!-- User Status Filter -->
                            <div class="col-md-3">
                                <label for="statusFilterSelect" class="form-label small fw-bold text-secondary">User
                                    Status</label>
                                <select name="status_filter" id="statusFilterSelect" class="form-select form-select-sm">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All
                                        Statuses</option>
                                    <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>
                                        Active</option>
                                    <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-md-12 d-flex justify-content-end gap-2 mt-2">
                                <button type="submit" class="btn btn-sm btn-primary px-3">Apply Filters</button>
                                <?php if ($search !== '' || $role_filter !== 'all' || $programme_filter !== 'all' || $status_filter !== 'all'): ?>
                                    <a href="manage_users.php" class="btn btn-sm btn-outline-secondary px-3">Reset
                                        Filters</a>
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
                                        <th>Academic Programme</th>
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
                                                if ($u['userRole'] === 'Student') {
                                                    echo htmlspecialchars($u['programmeName'] ?? '-');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
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
                                                <?php
                                                $status = htmlspecialchars($u['userStatus'] ?? 'Active');
                                                $badgeClass = ($status === 'Active') ? 'bg-success' : 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $status; ?></span>
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