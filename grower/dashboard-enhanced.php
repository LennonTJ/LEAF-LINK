<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
include("../config/database.php");

$grower_id = $_SESSION['grower_id'] ?? null;
$grower = null;
$contractor_name = '';
$season = '';
$status = 'INACTIVE';

// Fetch grower details
if ($grower_id) {
    $gstmt = mysqli_prepare($conn, "SELECT * FROM growers WHERE grower_id = ? LIMIT 1");
    mysqli_stmt_bind_param($gstmt, "i", $grower_id);
    mysqli_stmt_execute($gstmt);
    $gres = mysqli_stmt_get_result($gstmt);
    $grower = mysqli_fetch_assoc($gres);

    // Find contractor via active contract
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

    // Current season
    $sres = mysqli_query($conn, "SELECT season_name FROM seasons WHERE is_active=1 LIMIT 1");
    if ($sres && $sr = mysqli_fetch_assoc($sres)) {
        $season = $sr['season_name'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LeafLink - Grower Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <p>Grower Performance Dashboard</p>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Grower Portal</h3>
        <hr>
        <a href="dashboard.php">📊 Dashboard</a>
        
        <h4>My Account</h4>
        <a href="profile.php">👤 My Profile</a>
        <a href="projection.php">📈 Projections</a>
        
        <h4>Reports</h4>
        <a href="view.php">📋 View Records</a>
        <a href="search.php">🔍 Search</a>
        
        <hr>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="content">
        
        <!-- Welcome Card -->
        <div class="card">
            <h2>Welcome, <?php echo htmlspecialchars($grower['first_name'] ?? 'Grower'); ?>!</h2>
            <p>Monitor your farming performance and track your production metrics in real-time.</p>
        </div>

        <!-- Performance Metrics -->
        <div class="metrics-row">
            <div class="metric-card">
                <h3>Contract Status</h3>
                <p class="metric-value"><?php echo $status; ?></p>
                <p class="metric-unit"><?php echo htmlspecialchars($contractor_name ?: 'No Active Contract'); ?></p>
            </div>

            <div class="metric-card">
                <h3>Current Season</h3>
                <p class="metric-value"><?php echo htmlspecialchars($season ?: date('Y')); ?></p>
                <p class="metric-unit">Active Period</p>
            </div>

            <div class="metric-card">
                <h3>Farm Size</h3>
                <p class="metric-value"><?php echo number_format($grower['hectares'] ?? 0, 2); ?></p>
                <p class="metric-unit">Hectares</p>
            </div>

            <div class="metric-card">
                <h3>Total Debt</h3>
                <p class="metric-value">$<?php echo number_format($grower['total_debt'] ?? 0, 2); ?></p>
                <p class="metric-unit">Outstanding</p>
            </div>
        </div>

        <!-- Date Range Controls -->
        <div class="card" style="margin-top: 10px;">
            <h3>Filter by Date Range</h3>
            <div style="display:flex; gap:10px; align-items:center;">
                <label for="startDate">From:</label>
                <input type="date" id="startDate" name="startDate">
                <label for="endDate">To:</label>
                <input type="date" id="endDate" name="endDate">
                <button id="applyDateFilter" style="padding:6px 10px;">Apply</button>
                <button id="clearDateFilter" style="padding:6px 10px;">Clear</button>
            </div>
            <p style="font-size:12px; color:#666; margin-top:8px;">Use the date controls to limit chart data (filters use projection date).</p>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Production Trend Chart -->
            <div class="chart-container">
                <h3>📈 Production Trend</h3>
                <canvas id="productionChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Total production tracked over time (kg)</p>
            </div>

            <!-- Quality Distribution Chart -->
            <div class="chart-container">
                <h3>🏆 Quality Grade Distribution</h3>
                <canvas id="qualityChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Distribution of produced grades</p>
            </div>

            <!-- Revenue Performance Chart -->
            <div class="chart-container">
                <h3>💰 Revenue Performance</h3>
                <canvas id="revenueChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Projected vs actual revenue</p>
            </div>

        </div>

        <!-- Performance Summary Card -->
        <div class="card">
            <h2>Performance Summary</h2>
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody id="metricsTableBody">
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 40px; color: #999;">Loading metrics...</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Load Chart Library -->
<script src="../assets/js/charts.js"></script>

<script>
    const growerId = <?php echo json_encode($grower_id); ?>;

    // Function to load and render all charts
    async function loadCharts() {
        if (!growerId) {
            console.warn('No grower ID available');
            return;
        }

        // read date filters
        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;
        const dateParams = (startDate ? `&start_date=${encodeURIComponent(startDate)}` : '') + (endDate ? `&end_date=${encodeURIComponent(endDate)}` : '');

        try {
            // Load Production Trend Data
            const productionRes = await fetch(`../api/visualization-data.php?action=production_trend&grower_id=${growerId}${dateParams}`);
            const productionData = await productionRes.json();
            if (productionData.labels && productionData.labels.length > 0) {
                LeafLinkCharts.createProductionTrendChart('productionChart', productionData.labels, productionData.data);
            }

            // Load Quality Distribution Data
            const qualityRes = await fetch(`../api/visualization-data.php?action=quality_distribution&grower_id=${growerId}${dateParams}`);
            const qualityData = await qualityRes.json();
            if (qualityData.grades && qualityData.grades.length > 0) {
                LeafLinkCharts.createQualityDistributionChart('qualityChart', qualityData.grades, qualityData.counts);
            }

            // Load Revenue Data
            const revenueRes = await fetch(`../api/visualization-data.php?action=revenue&grower_id=${growerId}${dateParams}`);
            const revenueData = await revenueRes.json();
            if (revenueData.labels && revenueData.labels.length > 0) {
                LeafLinkCharts.createRevenueChart('revenueChart', revenueData.labels, revenueData.projected, revenueData.actual);
            }

            // Load Performance Metrics
            const metricsRes = await fetch(`../api/visualization-data.php?action=metrics&grower_id=${growerId}${dateParams}`);
            const metrics = await metricsRes.json();
            populateMetricsTable(metrics);

        } catch (error) {
            console.error('Error loading charts:', error);
        }
    }

    // Initialize date inputs with defaults (last 6 months to today)
    function initDateInputs() {
        const end = new Date();
        const start = new Date();
        start.setMonth(end.getMonth() - 6);

        const format = d => d.toISOString().split('T')[0];
        const startInput = document.getElementById('startDate');
        const endInput = document.getElementById('endDate');
        if (startInput && endInput) {
            if (!startInput.value) startInput.value = format(start);
            if (!endInput.value) endInput.value = format(end);

            // Apply and Clear handlers
            document.getElementById('applyDateFilter')?.addEventListener('click', () => loadCharts());
            document.getElementById('clearDateFilter')?.addEventListener('click', () => {
                startInput.value = '';
                endInput.value = '';
                loadCharts();
            });
        }
    }

    // Function to populate metrics table
    function populateMetricsTable(metrics) {
        const tbody = document.getElementById('metricsTableBody');
        tbody.innerHTML = `
            <tr>
                <td><strong>Total Production</strong></td>
                <td>${metrics.total_production.toLocaleString('en-US', {maximumFractionDigits: 0})}</td>
                <td>kg</td>
            </tr>
            <tr>
                <td><strong>Total Revenue</strong></td>
                <td>$${metrics.total_revenue.toLocaleString('en-US', {maximumFractionDigits: 2})}</td>
                <td>USD</td>
            </tr>
            <tr>
                <td><strong>Average Production per Entry</strong></td>
                <td>${metrics.avg_production.toLocaleString('en-US', {maximumFractionDigits: 2})}</td>
                <td>kg</td>
            </tr>
            <tr>
                <td><strong>Quality Grades Produced</strong></td>
                <td>${metrics.quality_grades}</td>
                <td>Types</td>
            </tr>
            <tr>
                <td><strong>Outstanding Debt</strong></td>
                <td style="${metrics.total_debt > 0 ? 'color: #ff6b6b; font-weight: bold;' : 'color: #51cf66; font-weight: bold;'}">
                    $${metrics.total_debt.toLocaleString('en-US', {maximumFractionDigits: 2})}
                </td>
                <td>USD</td>
            </tr>
        `;
    }

    // Load charts when page loads
    document.addEventListener('DOMContentLoaded', () => { initDateInputs(); loadCharts(); });
</script>

</body>
</html>
