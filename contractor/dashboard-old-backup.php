<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

// Determine contractor_id
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
$total_production = 0;
$total_revenue = 0;

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

    // Total production and revenue
    $q3 = "SELECT COALESCE(SUM(sp.estimated_kg), 0) AS total_prod,
            COALESCE(SUM(sp.projected_revenue), 0) AS total_rev
            FROM growers g
            JOIN contracts c ON g.grower_id = c.grower_id
            LEFT JOIN sale_projections sp ON g.grower_id = sp.grower_id
            WHERE c.contractor_id = ? AND c.status = 'active'";
    $s3 = mysqli_prepare($conn, $q3);
    if ($s3) {
        mysqli_stmt_bind_param($s3, "i", $contractor_id);
        mysqli_stmt_execute($s3);
        $r3 = mysqli_stmt_get_result($s3);
        $a3 = mysqli_fetch_assoc($r3);
        $total_production = (float)($a3['total_prod'] ?? 0);
        $total_revenue = (float)($a3['total_rev'] ?? 0);
    }
}

// Current season
$season_res = mysqli_query($conn, "SELECT season_name FROM seasons WHERE is_active=1 LIMIT 1");
if ($season_res && $sr = mysqli_fetch_assoc($season_res)) {
    $current_season = $sr['season_name'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LeafLink - Contractor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <h2><?php echo htmlspecialchars($contractor_name); ?> - Performance Dashboard</h2>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Contractor</h3>
        <hr>
        <a href="dashboard-enhanced.php">📊 Dashboard</a>

        <h4>Growers</h4>
        <a href="growers.php">👥 Assigned Growers</a>
        <a href="search.php">🔍 Search</a>

        <h4>Analytics</h4>
        <a href="performance.php">📈 Performance Analysis</a>
        <a href="reports.php">📋 Reports</a>

        <h4>Finance</h4>
        <a href="finances.php">💰 Financial Summary</a>

        <hr>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="content">

        <!-- Welcome Card -->
        <div class="card">
            <h2>Welcome, <?php echo htmlspecialchars($contractor_name); ?>!</h2>
            <p>Monitor your grower portfolio and track overall performance across all assigned farmers.</p>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-row">
            <div class="metric-card">
                <h3>Assigned Growers</h3>
                <p class="metric-value"><?php echo $assigned_growers; ?></p>
                <p class="metric-unit">Active Relationships</p>
            </div>

            <div class="metric-card">
                <h3>Total Production</h3>
                <p class="metric-value"><?php echo number_format($total_production, 0); ?></p>
                <p class="metric-unit">kg</p>
            </div>

            <div class="metric-card">
                <h3>Portfolio Revenue</h3>
                <p class="metric-value">$<?php echo number_format($total_revenue, 2); ?></p>
                <p class="metric-unit">Total Projected</p>
            </div>

            <div class="metric-card">
                <h3>Current Season</h3>
                <p class="metric-value"><?php echo htmlspecialchars($current_season ?: date('Y')); ?></p>
                <p class="metric-unit">Active Period</p>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Grower Comparison Chart -->
            <div class="chart-container">
                <h3>👨‍🌾 Top Grower Performance</h3>
                <canvas id="growerComparisonChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Production and revenue comparison across growers</p>
            </div>

            <!-- Contract Status Chart -->
            <div class="chart-container">
                <h3>📝 Contract Status Overview</h3>
                <canvas id="contractStatusChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Distribution of contract statuses</p>
            </div>

            <!-- Debt Status Chart -->
            <div class="chart-container">
                <h3>💳 Grower Debt Analysis</h3>
                <canvas id="debtStatusChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Outstanding debt by grower</p>
            </div>

        </div>

        <!-- Performance Insights Card -->
        <div class="card">
            <h2>Portfolio Performance Insights</h2>
            <div id="performanceInsights">
                <p style="text-align: center; color: #999; padding: 20px;">Loading insights...</p>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/charts.js"></script>

<script>
    const contractorId = <?php echo json_encode($contractor_id); ?>;

    async function loadDashboard() {
        if (!contractorId) {
            console.warn('No contractor ID available');
            return;
        }

        try {
            // Load Grower Comparison Chart
            const compRes = await fetch(`../api/visualization-data.php?action=grower_comparison&contractor_id=${contractorId}`);
            const compData = await compRes.json();
            if (compData.names && compData.names.length > 0) {
                LeafLinkCharts.createGrowerComparisonChart('growerComparisonChart', compData.names, compData.production, compData.revenue);
            } else {
                document.getElementById('growerComparisonChart').parentElement.innerHTML = 
                    '<div class="no-data"><p>No grower performance data available yet.</p></div>';
            }

            // Load Contract Status Chart
            const statusRes = await fetch(`../api/visualization-data.php?action=contract_status&contractor_id=${contractorId}`);
            const statusData = await statusRes.json();
            LeafLinkCharts.createContractStatusChart('contractStatusChart', statusData);

            // Load Debt Status Chart
            const debtRes = await fetch(`../api/visualization-data.php?action=debt_status&contractor_id=${contractorId}`);
            const debtData = await debtRes.json();
            if (debtData.names && debtData.names.length > 0) {
                LeafLinkCharts.createDebtStatusChart('debtStatusChart', debtData.names, debtData.debts);
            }

            // Generate Performance Insights
            generateInsights(compData, statusData, debtData);

        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    function generateInsights(compData, statusData, debtData) {
        const insights = document.getElementById('performanceInsights');
        
        let html = '<ul style="list-style: none; padding: 0;">';
        
        // Top performer
        if (compData.names && compData.names.length > 0) {
            const topIndex = compData.production.indexOf(Math.max(...compData.production));
            html += `<li style="padding: 10px; border-left: 4px solid #0bca44;">
                <strong>Top Producer:</strong> ${compData.names[topIndex]} with ${Math.round(compData.production[topIndex]).toLocaleString()} kg produced
            </li>`;
        }

        // Contract status insight
        const totalContracts = (statusData.active || 0) + (statusData.completed || 0) + (statusData.cancelled || 0);
        const activeRate = totalContracts > 0 ? ((statusData.active / totalContracts) * 100).toFixed(1) : 0;
        html += `<li style="padding: 10px; border-left: 4px solid #4dabf7;">
            <strong>Contract Health:</strong> ${activeRate}% of contracts are active (${statusData.active} active out of ${totalContracts} total)
        </li>`;

        // Debt alert
        const totalDebt = debtData.debts ? debtData.debts.reduce((a, b) => a + b, 0) : 0;
        const highDebtCount = debtData.debts ? debtData.debts.filter(d => d > 5000).length : 0;
        if (highDebtCount > 0) {
            html += `<li style="padding: 10px; border-left: 4px solid #ff6b6b;">
                <strong>⚠️ Debt Alert:</strong> ${highDebtCount} grower(s) have debt exceeding $5,000. Total portfolio debt: $${totalDebt.toLocaleString('en-US', {maximumFractionDigits: 2})}
            </li>`;
        } else {
            html += `<li style="padding: 10px; border-left: 4px solid #51cf66;">
                <strong>✓ Debt Status Good:</strong> Total portfolio debt: $${totalDebt.toLocaleString('en-US', {maximumFractionDigits: 2})}
            </li>`;
        }

        html += '</ul>';
        insights.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', loadDashboard);
</script>

</body>
</html>
