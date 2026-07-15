<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");

    exit();

}

include("../config/database.php");

// determine contractor_id from session or users table
$contractor_id = $_SESSION['contractor_id'] ?? null;
if (!$contractor_id) {
    $uid = $_SESSION['user_id'];
    $u_stmt = mysqli_prepare($conn, "SELECT contractor_id FROM users WHERE user_id = ? LIMIT 1");
    if ($u_stmt) {
        mysqli_stmt_bind_param($u_stmt, "i", $uid);
        mysqli_stmt_execute($u_stmt);
        $ures = mysqli_stmt_get_result($u_stmt);
        $uro = mysqli_fetch_assoc($ures);
        $contractor_id = $uro['contractor_id'] ?? null;
    }
}
// fallback: match contractors.contractor_code to username if contractor_id not set
if (!$contractor_id) {
    $uid = $_SESSION['user_id'];
    $u_stmt2 = mysqli_prepare($conn, "SELECT username FROM users WHERE user_id = ? LIMIT 1");
    if ($u_stmt2) {
        mysqli_stmt_bind_param($u_stmt2, "i", $uid);
        mysqli_stmt_execute($u_stmt2);
        $ures2 = mysqli_stmt_get_result($u_stmt2);
        $uro2 = mysqli_fetch_assoc($ures2);
        $uname = $uro2['username'] ?? null;
        if ($uname) {
            $cstmt2 = mysqli_prepare($conn, "SELECT contractor_id FROM contractors WHERE contractor_code = ? LIMIT 1");
            if ($cstmt2) {
                mysqli_stmt_bind_param($cstmt2, "s", $uname);
                mysqli_stmt_execute($cstmt2);
                $cres2 = mysqli_stmt_get_result($cstmt2);
                $crow2 = mysqli_fetch_assoc($cres2);
                $contractor_id = $crow2['contractor_id'] ?? null;
            }
        }
    }
}

$contractor_name = 'Contractor';
if ($contractor_id) {
    $cstmt = mysqli_prepare($conn, "SELECT contractor_name FROM contractors WHERE contractor_id = ? LIMIT 1");
    if ($cstmt) {
        mysqli_stmt_bind_param($cstmt, "i", $contractor_id);
        mysqli_stmt_execute($cstmt);
        $cres = mysqli_stmt_get_result($cstmt);
        $crow = mysqli_fetch_assoc($cres);
        if ($crow) $contractor_name = $crow['contractor_name'];
    }
}

// Stats
$assigned_growers = 0;
$active_contracts = 0;
$current_season = '';
if ($contractor_id) {
    $q = "SELECT COUNT(DISTINCT c.grower_id) AS cnt FROM contracts c WHERE c.contractor_id = ? AND c.status = 'active'";
    $s = mysqli_prepare($conn, $q);
    if ($s) {
        mysqli_stmt_bind_param($s, "i", $contractor_id);
        mysqli_stmt_execute($s);
        $r = mysqli_stmt_get_result($s);
        $a = mysqli_fetch_assoc($r);
        $assigned_growers = $a['cnt'] ?? 0;
    }

    $q2 = "SELECT COUNT(*) AS cnt FROM contracts WHERE contractor_id = ? AND status = 'active'";
    $s2 = mysqli_prepare($conn, $q2);
    if ($s2) {
        mysqli_stmt_bind_param($s2, "i", $contractor_id);
        mysqli_stmt_execute($s2);
        $r2 = mysqli_stmt_get_result($s2);
        $a2 = mysqli_fetch_assoc($r2);
        $active_contracts = $a2['cnt'] ?? 0;
    }
}

// current season
$season_res = mysqli_query($conn, "SELECT season_name FROM seasons WHERE is_active=1 LIMIT 1");
if ($season_res && $sr = mysqli_fetch_assoc($season_res)) {
    $current_season = $sr['season_name'];
}

// Assigned growers list
$assigned_list = [];
if ($contractor_id) {
    $ql = "SELECT g.grower_no, g.first_name, g.last_name FROM growers g JOIN contracts c ON g.grower_id = c.grower_id WHERE c.contractor_id = ? AND c.status='active' LIMIT 50";
    $sl = mysqli_prepare($conn, $ql);
    if ($sl) {
        mysqli_stmt_bind_param($sl, "i", $contractor_id);
        mysqli_stmt_execute($sl);
        $rl = mysqli_stmt_get_result($sl);
        while ($row = mysqli_fetch_assoc($rl)) {
            $assigned_list[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html>

<head>

<title>Contractor Dashboard</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="header">

<h1>LeafLink</h1>

<h2><?php echo htmlspecialchars($contractor_name); ?></h2>

</div>

<div class="layout">

<div class="sidebar">

<h3>Contractor</h3>

<hr>

<a href="#">Dashboard</a>

<h4>Growers</h4>

<a href="#">Assigned Growers</a>

<h4>Sales</h4>

<a href="#">Sales History</a>

<h4>Finance</h4>

<a href="#">Payments</a>

<h4>Account</h4>

<a href="#">My Profile</a>

<hr>

<a href="../logout.php">Logout</a>

</div>

    <div class="hero">
        <div class="content">

    <div class="container">


<div class="card">

<h2>Welcome Contractor</h2>

</div>

<div class="card">

<p>Growers Assigned : <strong><?php echo intval($assigned_growers); ?></strong></p>

<p>Active Contracts : <strong><?php echo intval($active_contracts); ?></strong></p>

<p>Current Season : <strong><?php echo htmlspecialchars($current_season ?: date('Y')); ?></strong></p>

</div>

<div class="card">

<h3>Assigned Growers</h3>

<ul>
<?php foreach ($assigned_list as $g) { ?>
    <li><?php echo htmlspecialchars($g['grower_no'] . ' - ' . $g['first_name'] . ' ' . $g['last_name']); ?></li>
<?php } ?>
</ul>

</div>

<a class="btn" href="../logout.php">Logout</a>


        </div>
</div>
    </div>
</div>

</body>
</html>

