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

<a href="upload_sales.php">Upload Sale sheets</a>

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

<h2>Welcome <?php echo htmlspecialchars($contractor_name); ?></h2>

<p>Current Season :
<strong><?php echo htmlspecialchars($current_season ?: date('Y')); ?></strong></p>

</div>


<h2 style="margin:20px 0;">
Contractor Performance
</h2>


<div class="metrics-row">


<div class="metric-card">

<h3>Growers</h3>

<div class="metric-value" id="metricGrowers">-</div>

<div class="metric-unit">Active</div>

</div>



<div class="metric-card">

<h3>Total Kg</h3>

<div class="metric-value" id="metricKg">-</div>

<div class="metric-unit">kg</div>

</div>



<div class="metric-card">

<h3>Total Revenue</h3>

<div class="metric-value" id="metricRevenue">-</div>

<div class="metric-unit">USD</div>

</div>



<div class="metric-card">

<h3>Average Price</h3>

<div class="metric-value" id="metricPrice">-</div>

<div class="metric-unit">$/kg</div>

</div>



<div class="metric-card">

<h3>Bales Sold</h3>

<div class="metric-value" id="metricBales">-</div>

<div class="metric-unit">Bales</div>

</div>



<div class="metric-card">

<h3>Rejected</h3>

<div class="metric-value" id="metricRejected">-</div>

<div class="metric-unit">Bales</div>

</div>



<div class="metric-card">

<h3>Recovery Risk</h3>

<div class="metric-value" id="metricRisk">-</div>

<div class="metric-unit">Growers</div>

</div>


</div>



<div class="dashboard-grid">

    <div>

        <h3>Sales Trend</h3>

        <canvas id="salesTrend"></canvas>

    </div>


    <div>

        <h3>Grade Distribution</h3>

        <canvas id="gradeChart"></canvas>

    </div>

</div>

<div class="card">

<div class="card">

<h3>Recovery Risk Overview</h3>


<div class="metrics-row">


<div class="metric-card">

<h3>High Risk</h3>

<div class="metric-value" id="highRisk">
-
</div>

<p class="metric-unit">
Growers
</p>

</div>



<div class="metric-card">

<h3>Medium Risk</h3>

<div class="metric-value" id="mediumRisk">
-
</div>

<p class="metric-unit">
Growers
</p>

</div>




<div class="metric-card">

<h3>Low Risk</h3>

<div class="metric-value" id="lowRisk">
-
</div>

<p class="metric-unit">
Growers
</p>

</div>


</div>

</div>

</div>

<script>

async function loadContractorMetrics(){

const response = await fetch(
"../api/contractor-data.php?action=metrics"
);


const data = await response.json();


document.getElementById("metricGrowers").textContent =
data.growers;


document.getElementById("metricKg").textContent =
Number(data.mass).toLocaleString();


document.getElementById("metricRevenue").textContent =
"$"+Number(data.sales).toLocaleString();


document.getElementById("metricPrice").textContent =
"$"+(data.sales/data.mass).toFixed(2);



document.getElementById("metricRejected").textContent =
data.rejected_bales;


document.getElementById("metricRisk").textContent =
data.risk_count;

document.getElementById("metricBales").textContent =
    data.total_bales ?? 0;



}


document.addEventListener(
"DOMContentLoaded",
loadContractorMetrics
);


</script>
<script>

async function loadContractorMetrics(){

    try{

        const response = await fetch("../api/contractor-data.php?action=metrics");

        const data = await response.json();


        console.log("Contractor Metrics:", data);


        document.getElementById("metricGrowers").textContent =
            data.active_growers ?? 0;


        document.getElementById("metricKg").textContent =
            (data.total_kg ?? 0).toLocaleString() + " kg";


        document.getElementById("metricRevenue").textContent =
            "$" + (data.total_revenue ?? 0).toFixed(2);


        document.getElementById("metricPrice").textContent =
            "$" + (data.average_price ?? 0).toFixed(2);


    }
    catch(error){

        console.error(
            "Contractor dashboard error:",
            error
        );

    }

}


document.addEventListener(
"DOMContentLoaded",
loadContractorMetrics
);


</script>
<script>
// load risk data
async function loadRecoveryRisk(){

    try{

        const response = await fetch(
            "../api/contractor-data.php?action=recovery_risk"
        );


        const data = await response.json();


        console.log("Recovery Risk:", data);



        let rows = "";


        data.forEach(g => {


            rows += `

            <tr>

            <td>
            ${g.grower}
            </td>


            <td>
            $${Number(g.revenue).toFixed(2)}
            </td>


            <td>
            $${Number(g.debt).toFixed(2)}
            </td>


            <td>
            ${g.recovery}%
            </td>


            <td>
            <span class="performance-badge ${g.risk.toLowerCase()}">
            ${g.risk}
            </span>
            </td>


            </tr>

            `;


        });


        document.getElementById("riskTable").innerHTML = rows;


    }
    catch(error){

        console.error(
            "Recovery risk error:",
            error
        );

    }

}



window.addEventListener(
"load",
loadRecoveryRisk
);


/// risk sammury data
async function loadRecoverySummary(){

    try{


        const res = await fetch(
            "../api/contractor-data.php?action=recovery_summary"
        );


        const data = await res.json();


        console.log(
            "Recovery Summary:",
            data
        );


        document.getElementById("highRisk").textContent =
            data.high ?? 0;


        document.getElementById("mediumRisk").textContent =
            data.medium ?? 0;


        document.getElementById("lowRisk").textContent =
            data.low ?? 0;



    }
    catch(error){

        console.error(
            "Recovery summary error:",
            error
        );

    }

}


window.addEventListener(
"load",
loadRecoverySummary
);

</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>


// ===============================
// SALES TREND CHART
// ===============================

async function loadSalesTrend(){

    try{

        const res = await fetch(
            "../api/contractor-data.php?action=sales_trend"
        );

        const data = await res.json();


        const ctx = document
        .getElementById("salesTrend")
        .getContext("2d");


        new Chart(ctx,{

            type:"line",

            data:{

                labels:data.dates,

                datasets:[{

                    label:"Kg Sold",

                    data:data.kgs,

                    tension:0.3

                }]

            },


            options:{

                responsive:true,

                plugins:{

                    legend:{
                        display:true
                    }

                },


                scales:{

                    y:{

                        beginAtZero:true,

                        title:{
                            display:true,
                            text:"Kilograms"
                        }

                    }

                }

            }

        });


    }
    catch(error){

        console.error(
            "Sales trend error:",
            error
        );

    }

}




// ===============================
// GRADE DISTRIBUTION CHART
// ===============================

async function loadGradeDistribution(){

    try{


        const res = await fetch(
            "../api/contractor-data.php?action=grade_distribution"
        );


        const data = await res.json();



        const ctx = document
        .getElementById("gradeChart")
        .getContext("2d");



        new Chart(ctx,{

            type:"doughnut",


            data:{


                labels:data.grades,


                datasets:[{

                    label:"Kg",

                    data:data.kgs

                }]

            },


            options:{

                responsive:true

            }


        });



    }
    catch(error){

        console.error(
            "Grade chart error:",
            error
        );

    }


}




// LOAD DASHBOARD DATA

document.addEventListener(
"DOMContentLoaded",
()=>{

    loadSalesTrend();

    loadGradeDistribution();

});


</script>

</body>
</html>

