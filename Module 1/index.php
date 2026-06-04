<?php
session_start();

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Administrator') 
    {
        header("Location: admin_dashboard.php");
    } 
    else 
    {
        header("Location: student_dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['student_id']);
    $password = trim($_POST['password']);
    $selected_role = $_POST['role'];
    
    // Modified query to handle specialization (JOIN across subtype tables)
    $sql = "SELECT u.*, s.studentID, a.staffID, u.userName as name
            FROM user u
            LEFT JOIN student s ON u.User_ID = s.User_ID
            LEFT JOIN admin a ON u.User_ID = a.User_ID
            WHERE s.studentID = ? OR a.staffID = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$login_id, $login_id]);
    $user = $stmt->fetch();

$isValidRole = false;

if ($user && password_verify($password, $user['userPassword'])) {

    if ($user['userRole'] === 'Student' && $selected_role === 'Student') {
        $isValidRole = true;
    }

    if ($user['userRole'] === 'Administrator' && $selected_role === 'Administrator') {
        $isValidRole = true;
    }

    if ($isValidRole) {
        $_SESSION['user_id'] = $user['User_ID'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['userRole'];
        $_SESSION['studentID'] = ($user['userRole'] === 'Administrator') ? $user['staffID'] : $user['studentID'];
        $_SESSION['userProfilePicture'] = $user['userProfilePicture'];

        if ($user['userRole'] === 'Administrator') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();
    }
}

$error = "Invalid Student ID/Staff ID, password, or role selected.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FK Student Club Management</title>
    <link rel="stylesheet" href="../STYLE/CSS/Module1/index_CSS.css">
    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="system-header">
        <img src="../Images/logo.png" alt="Logo" class="system-logo">

        <div class="system-title">
            FK STUDENT CLUB <br>
            & EVENT MANAGEMENT SYSTEM
        </div>
    </div>
    
    <div>
        <div class="login-page">
            <div class="right-section">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-10">
                            <div class="card login-card">
                                <div class="card-body p-5">
                                    <div class="text-center mb-4">
                                        <h3 class="fw-bold text-primary">FK CLUB SYSTEM</h3>
                                        <p class="text-muted">Welcome back! Please login.</p>
                                    </div>

                                    <?php if ($error): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>

                                    <form action="index.php" method="POST">
                                        <div class="mb-3">
                                            <label for="student_id" class="form-label fw-bold">Student ID / Admin ID</label>
                                            <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter your Student ID or Admin ID" required autofocus>
                                        </div>
                                    
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Select Role</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="Administrator" selected>Administrator</option>
                                                <option value="Student">Student</option>
                                            </select>
                                        </div>
 
                                        <div class="mb-4">
                                            <label for="password" class="form-label fw-bold">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Log In</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>
</body>

</html>