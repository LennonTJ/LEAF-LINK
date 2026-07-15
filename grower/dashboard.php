<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<?php include("../config/database.php");

$grower_id = $_SESSION['grower_id'] ?? null;
$grower = null;
$contractor_name = '';
$season = '';
$status = 'INACTIVE';
$summary_count = 0;
$summary_mass = 0.00;
$summary_sales = 0.00;

if ($grower_id) {
    $gstmt = mysqli_prepare($conn, "SELECT * FROM growers WHERE grower_id = ? LIMIT 1");
    mysqli_stmt_bind_param($gstmt, "i", $grower_id);
    mysqli_stmt_execute($gstmt);
    $gres = mysqli_stmt_get_result($gstmt);
    $grower = mysqli_fetch_assoc($gres);

    // find contractor via active contract
    $cstmt = mysqli_prepare($conn, "SELECT ct.contractor_name FROM contractors ct JOIN contracts c ON ct.contractor_id = c.contractor_id WHERE c.grower_id = ? AND c.status='active' LIMIT 1");
    if ($cstmt) {
        mysqli_stmt_bind_param($cstmt, "i", $grower_id);
        mysqli_stmt_execute($cstmt);
        $cres = mysqli_stmt_get_result($cstmt);
        if ($crow = mysqli_fetch_assoc($cres)) {
            $contractor_name = $crow['contractor_name'];
            $status = 'ACTIVE';
        }
    }

    // current season
    $sres = mysqli_query($conn, "SELECT season_name FROM seasons WHERE is_active=1 LIMIT 1");
    if ($sres && $sr = mysqli_fetch_assoc($sres)) {
        $season = $sr['season_name'];
    }

    // Summary from sale_projections for this grower
    $ps = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt, COALESCE(SUM(estimated_kg),0) AS mass, COALESCE(SUM(projected_revenue),0) AS sales FROM sale_projections WHERE grower_id = ?");
    if ($ps) {
        mysqli_stmt_bind_param($ps, "i", $grower_id);
        mysqli_stmt_execute($ps);
        $prs = mysqli_stmt_get_result($ps);
        if ($prow = mysqli_fetch_assoc($prs)) {
            $summary_count = $prow['cnt'] ?? 0;
            $summary_mass = $prow['mass'] ?? 0.00;
            $summary_sales = $prow['sales'] ?? 0.00;
        }
    }

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LeafLink - Grower Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="header">
    <h1>LeafLink</h1>
    <p>Grower Self-Service Portal</p>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Grower</h3>
        <hr>

        <a href="#">Dashboard</a>

        <h4>My Account</h4>
        <a href="#">My Profile</a>

        <h4>Finance</h4>
        <a href="#">Financial Summary</a>

        <h4>Sales</h4>
        <a href="#">Sales History</a>

        <h4>Sale Projection</h4>
        <a href="projection.php">Sale Projection</a>

        <h4>Support</h4>
        <a href="#">Contact Contractor</a>

        <hr>
        <a href="../logout.php">Logout</a>
    </div>

<div class="hero">
    <div class="content">
        <div class="card">
            <h2>Welcome <?php echo htmlspecialchars(($grower['first_name'] ?? '') . ' ' . ($grower['last_name'] ?? '')); ?></h2>
            <p><strong>Grower Number:</strong> <?php echo htmlspecialchars($grower['grower_no'] ?? ''); ?></p>
            <p><strong>Contractor:</strong> <?php echo htmlspecialchars($contractor_name ?: ''); ?></p>
            <p><strong>Season:</strong> <?php echo htmlspecialchars($season ?: date('Y')); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($status); ?></p>
        </div>

        <div class="card">
            <h2>Dashboard Summary</h2>
            <p> Total Projections : <strong><?php echo intval($summary_count); ?></strong></p>
            <p> Total Mass : <strong><?php echo number_format($summary_mass,2); ?> kg</strong></p>
            <p> Total Projected Sales : <strong>$<?php echo number_format($summary_sales,2); ?></strong></p>
        </div>
    </div>
</div>
</div>
</body>
</html>

