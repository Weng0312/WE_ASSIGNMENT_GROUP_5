<?php
session_start();

require_once __DIR__ . '/../db_connect.php';

/** @var PDO $pdo */

// Security Check: If not logged in or not a Student, redirect to login
if (
    !isset($_SESSION['user_id']) || 
    (
        $_SESSION['role'] !== 'Student' && 
        strpos($_SESSION['role'], 'Committee') === false
    )
) {
    header("Location: index.php");
    exit();
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // FIXED: Appended AND clubStatus = 'Active' to hide globally deactivated clubs
    $query = "SELECT club_ID, clubName, clubDescription
              FROM club
              WHERE 
              (
                  clubName LIKE :search
                  OR clubDescription LIKE :search
              )
              AND clubStatus = 'Active'
              ORDER BY club_ID ASC";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%');
    $stmt->execute();

    $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club List - FK System</title>

    <link rel="stylesheet" href="../STYLE/CSS/Module_2/club_list_CSS.css">

    <link href="../STYLE/BOOTSTRAP/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>

    <?php include '../topbar.php'; ?>

    <div id="wrapper">
    <?php
        $dashboardType = 'student';
        include '../sidebar.php';
    ?>

        <div id="content">
            <div class="container-fluid">

                <div class="club-page-header mb-4">
                    <h2 class="fw-bold">Club List Page</h2>
                    <p class="text-muted mb-0">
                        Browse and explore all available clubs. Use search to find the club you're interested in.
                    </p>
                </div>

                <div class="club-filter-card mb-4">
                    <form action="" method="GET">
                        <div class="row g-4">

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Search Clubs</label>
                                <div class="search-box">
                                    <i class="bi bi-search"></i>
                                    <input 
                                        type="text" 
                                        name="search" 
                                        value="<?php echo htmlspecialchars($search); ?>" 
                                        class="form-control"
                                        placeholder="Search by club name or keyword..."
                                    >
                                </div>
                            </div>

                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                Search
                            </button>

                            <a href="club_list.php" class="btn btn-outline-secondary px-4">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                <div class="row g-4">

                    <?php if (count($clubs) > 0): ?>

                        <?php foreach ($clubs as $index => $club): ?>

                            <div class="col-lg-4 col-md-6 club-item <?php echo $index >= 3 ? 'extra-club d-none' : ''; ?>">
                                <div class="club-card">

                                    <div class="club-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>

                                    <h3 class="club-name">
                                        <?php echo htmlspecialchars($club['clubName']); ?>
                                    </h3>

                                    <p class="club-description">
                                        <?php echo htmlspecialchars($club['clubDescription']); ?>
                                    </p>

                                    <a href="club_details.php?id=<?php echo $club['club_ID']; ?>" class="btn-view">
                                        View Details
                                    </a>

                                </div>
                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="col-12">
                            <div class="empty-box">
                                <i class="bi bi-search"></i>
                                <p>No clubs found.</p>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>

                <?php if (count($clubs) > 3): ?>
                    <div class="view-more-wrapper">
                        <button type="button" class="btn-view-more" id="viewMoreBtn">
                            View All
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

    <script>
        const viewMoreBtn = document.getElementById('viewMoreBtn');

        if (viewMoreBtn) {
            viewMoreBtn.addEventListener('click', function () {
                const hiddenClubs = document.querySelectorAll('.extra-club');

                hiddenClubs.forEach(function (club) {
                    club.classList.remove('d-none');
                });

                viewMoreBtn.style.display = 'none';
            });
        }
    </script>

    <script src="../STYLE/BOOTSTRAP/bootstrap.bundle.min.js"></script>

</body>
</html>