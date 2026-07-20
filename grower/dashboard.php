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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        <!-- Embedded Visualizations -->
        <div class="card">
            <h2>Performance Charts</h2>
            <!-- Date Range Controls -->
            <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
                <label for="startDate">From:</label>
                <input type="date" id="startDate" name="startDate">
                <label for="endDate">To:</label>
                <input type="date" id="endDate" name="endDate">
                <button id="applyDateFilter" style="padding:6px 10px;">Apply</button>
                <button id="clearDateFilter" style="padding:6px 10px;">Clear</button>
            </div>

            <div class="dashboard-grid" style="display:flex; gap:20px; flex-wrap:wrap;">
                <div style="flex:1 1 600px;">
                    <h4>Sales Performance Trend</h4>
                    <canvas id="productionChart"></canvas>
                    <p style="font-size:12px; color:#666; margin-top:6px;">Daily kilograms sold and revenue (dual axis). Use date filter to narrow range.</p>
                </div>
                <div style="flex:1 1 360px;">
                    <h4>Tobacco Quality Distribution</h4>
                    <canvas id="qualityChart"></canvas>
                    <p style="font-size:12px; color:#666; margin-top:6px;">Volume (kg) by TIMB grade.</p>
                </div>
                <div style="flex:1 1 360px;">
                    <h4>Revenue vs Debt Impact</h4>
                    <canvas id="revenueDebtChart"></canvas>
                    <p style="font-size:12px; color:#666; margin-top:6px;">Gross revenue → deductions → final expected payout.</p>
                </div>
            </div>

            <h3 style="margin-top:20px;">Performance Summary</h3>
            <div class="metrics-row" id="kpiCards">
                <div class="metric-card" id="card_total_kgs">
                    <h3>Total Kgs Sold</h3>
                    <p class="metric-value" id="metric_total_kgs">-</p>
                    <p class="metric-unit">kg</p>
                </div>
            <div class="card" style="margin-top:18px;">
                <h3>Farmer Performance Insights</h3>
                <ul id="insightsList" style="margin-top:10px; color:#333;"></ul>
            </div>

                <div class="metric-card" id="card_total_revenue">
                    <h3>Total Revenue</h3>
                    <p class="metric-value" id="metric_total_revenue">-</p>
                    <p class="metric-unit">USD</p>
                </div>

                <div class="metric-card" id="card_debt_recovered">
                    <h3>Debt Recovered</h3>
                    <p class="metric-value" id="metric_debt_recovered">-</p>
                    <p class="metric-unit">%</p>
                    <canvas id="debtProgressChart" style="max-width:140px; margin: 6px auto 0; display:block;"></canvas>
                </div>

                <div class="metric-card" id="card_total_debt">
                    <h3>Total Outstanding Debt</h3>
                    <p class="metric-value" id="metric_total_debt">-</p>
                    <p class="metric-unit">USD</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>

<!-- Load chart utilities and page JS -->
<script src="../assets/js/charts.js"></script>
<script>
    const growerId = <?php echo json_encode($grower_id); ?>;

    async function loadCharts() {
        if (!growerId) return;

        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;
        const dateParams = (startDate ? `&start_date=${encodeURIComponent(startDate)}` : '') + (endDate ? `&end_date=${encodeURIComponent(endDate)}` : '');

        try {
            // Sales trend (daily): combined kg + revenue
            const salesRes = await fetch(`../api/visualization-data.php?action=sales_trend&grower_id=${growerId}${dateParams}`);
            const salesData = await salesRes.json();
            if (salesData.dates && salesData.dates.length) {
                // destroy previous if exists
                if (window._salesChart instanceof Chart) window._salesChart.destroy();
                window._salesChart = LeafLinkCharts.createSalesAndRevenueTrendChart('productionChart', salesData.dates, salesData.kgs, salesData.revenues);
            }

            // Quality distribution by kg
            const qualityRes = await fetch(`../api/visualization-data.php?action=quality_distribution&grower_id=${growerId}${dateParams}`);
            const qualityData = await qualityRes.json();
            if (qualityData.grades && qualityData.grades.length) {
                if (window._qualityChart instanceof Chart) window._qualityChart.destroy();
                window._qualityChart = LeafLinkCharts.createQualityDistributionChart('qualityChart', qualityData.grades, qualityData.quantities);
            }

            // Revenue vs debt impact (using metrics values below)

            const metricsRes = await fetch(`../api/visualization-data.php?action=metrics&grower_id=${growerId}${dateParams}`);
            const metrics = await metricsRes.json();
            populateKpiCards(metrics);

            // Revenue vs Debt chart
            try {
                const labels = ['Gross Revenue', 'Debt Deduction', 'Final Payout'];
                const values = [metrics.gross_revenue || 0, metrics.debt_deduction || 0, metrics.final_expected_payout || 0];
                if (window._revenueDebtChart instanceof Chart) window._revenueDebtChart.destroy();
                window._revenueDebtChart = LeafLinkCharts.createRevenueDebtChart('revenueDebtChart', labels, values);
            } catch (err) {
                console.error('Revenue/Debt chart error', err);
            }

            // Populate insights
            const insightsEl = document.getElementById('insightsList');
            if (insightsEl) {
                insightsEl.innerHTML = '';
                (metrics.insights || []).forEach(i => {
                    const li = document.createElement('li'); li.textContent = i; insightsEl.appendChild(li);
                });
            }
        } catch (e) {
            console.error('Error loading charts', e);
        }
    }
    function populateKpiCards(metrics) {
        // Total kgs sold
        const kgEl = document.getElementById('metric_total_kgs');
        const revEl = document.getElementById('metric_total_revenue');
        const debtPctEl = document.getElementById('metric_debt_recovered');
        const totalDebtEl = document.getElementById('metric_total_debt');
        if (kgEl) kgEl.textContent = (metrics.total_production ?? 0).toLocaleString('en-US', {maximumFractionDigits:0});
        if (revEl) revEl.textContent = `$${(metrics.total_revenue ?? 0).toLocaleString('en-US', {maximumFractionDigits:2})}`;
        if (debtPctEl) debtPctEl.textContent = `${(metrics.debt_recovered_percent ?? 0)}%`;
        if (totalDebtEl) totalDebtEl.textContent = `$${(metrics.total_debt ?? 0).toLocaleString('en-US', {maximumFractionDigits:2})}`;

        // Render debt progress doughnut
        try {
            const pct = Math.max(0, Math.min(100, parseFloat(metrics.debt_recovered_percent || 0)));
            const ctx = document.getElementById('debtProgressChart').getContext('2d');
            // destroy existing chart instance if present
            if (window._debtProgressChart instanceof Chart) {
                window._debtProgressChart.destroy();
            }
            window._debtProgressChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Recovered', 'Remaining'],
                    datasets: [{
                        data: [pct, Math.max(0, 100 - pct)],
                        backgroundColor: [LeafLinkCharts.colors.success, LeafLinkCharts.colors.warning],
                        borderColor: ['#fff', '#fff'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    cutout: '70%'
                }
            });
        } catch (err) {
            console.error('Debt progress chart error', err);
        }
    }

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
            document.getElementById('applyDateFilter')?.addEventListener('click', () => loadCharts());
            document.getElementById('clearDateFilter')?.addEventListener('click', () => {
                startInput.value = '';
                endInput.value = '';
                loadCharts();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => { initDateInputs(); loadCharts(); });
</script>

