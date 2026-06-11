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

try {

    $delete = $pdo->prepare("
        DELETE FROM club_membership
        WHERE User_ID = ?
        AND Club_ID = ?
    ");

    $delete->execute([$user_id, $club_id]);

    $_SESSION['success'] = "Successfully left the club.";

} catch (PDOException $e) {

    $_SESSION['error'] = "Database Error: " . $e->getMessage();
}

header("Location: club_details.php?id=" . $club_id);
exit();
?>