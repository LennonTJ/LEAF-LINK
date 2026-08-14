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

   

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LeafLink - Grower Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="dashboard-page">
<div class="header">
    <h1>LeafLink</h1>
    <span class="header-portal">Grower Portal</span>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Grower</h3>
        <hr>

        <a href="#">Dashboard</a>

        <h4>Sale Simulationtion</h4>
        <a href="projection.php">Simulate Sale</a>

        <h4>Support</h4>
        <a href="#">Contact Contractor</a>

        <hr>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="content">
        <!-- Compact Dashboard Header -->
        <div class="dashboard-header">
            <div class="header-greeting">
                <h2>Welcome, <?php echo htmlspecialchars(($grower['first_name'] ?? '') . ' ' . ($grower['last_name'] ?? '')); ?></h2>
            </div>
            <div class="header-badges">
                <span class="info-badge badge-grower">
                    <span class="badge-label">Grower #</span>
                    <span class="badge-value"><?php echo htmlspecialchars($grower['grower_no'] ?? ''); ?></span>
                </span>
                <span class="info-badge badge-contractor">
                    <span class="badge-label">Contractor</span>
                    <span class="badge-value"><?php echo htmlspecialchars($contractor_name ?: 'N/A'); ?></span>
                </span>
                <span class="info-badge badge-season">
                    <span class="badge-label">Season</span>
                    <span class="badge-value"><?php echo htmlspecialchars($season ?: date('Y')); ?></span>
                </span>
                <span class="info-badge badge-status <?php echo strtolower($status); ?>">
                    <span class="badge-label">Status</span>
                    <span class="badge-value"><?php echo htmlspecialchars($status); ?></span>
                </span>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="metrics-row" id="kpiCards">

            <div class="metric-card">

                <h3>Total Kgs Sold</h3>

                <p class="metric-value" id="metric_total_kgs">
                -
                </p>

                <p class="metric-unit">
                kg
                </p>

            </div>

            <div class="metric-card">

                <h3>Bales Sold</h3>

                <p class="metric-value" id="metric_total_bales">
                -
                </p>

                <p class="metric-unit">
                bales
                </p>

            </div>

            <div class="metric-card">

                <h3>Rejected Bales</h3>

                <p class="metric-value" id="metric_rejected_bales">
                -
                </p>

                <p class="metric-unit">
                bales
                </p>

            </div>

            <div class="metric-card">

                <h3>Average Price</h3>

                <p class="metric-value" id="metric_average_price">
                -
                </p>

                <p class="metric-unit">
                USD/kg
                </p>

            </div>

            <div class="metric-card">

                <h3>Total Revenue</h3>

                <p class="metric-value" id="metric_total_revenue">
                -
                </p>

                <p class="metric-unit">
                USD
                </p>

            </div>

            <div class="metric-card">

                <h3>Debt Recovered</h3>

                <p class="metric-value" id="metric_debt_recovered">
                -
                </p>

                <p class="metric-unit">
                %
                </p>

            </div>

            <div class="metric-card">

                <h3>Outstanding Debt</h3>

                <p class="metric-value" id="metric_total_debt">
                -
                </p>

                <p class="metric-unit">
                USD
                </p>

            </div>

        </div>

        <!-- Date Range Controls -->
        <div class="filter-bar">
            <label for="startDate">From:</label>
            <input type="date" id="startDate" name="startDate">
            <label for="endDate">To:</label>
            <input type="date" id="endDate" name="endDate">
            <button id="applyDateFilter">Apply</button>
            <button id="clearDateFilter" class="btn-secondary">Clear</button>
        </div>

        <!-- Charts Grid -->
        <div class="dashboard-grid">
            <div class="chart-card">
                <h4>Sales Performance Trend</h4>
                <canvas id="productionChart"></canvas>
                <p class="chart-note">Daily kilograms sold and revenue (dual axis). Use date filter to narrow range.</p>
            </div>
            <div class="chart-card">
                <h4>Tobacco Quality Distribution</h4>
                <canvas id="qualityChart"></canvas>
                <p class="chart-note">Volume (kg) by TIMB grade.</p>
            </div>
            <div class="chart-card">
                <h4>Revenue vs Debt Impact</h4>
                <canvas id="revenueDebtChart"></canvas>
                <p class="chart-note">Gross revenue → deductions → final expected payout.</p>
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


        const kgEl=document.getElementById('metric_total_kgs');
        const balesEl=document.getElementById('metric_total_bales');
        const rejectedEl=document.getElementById('metric_rejected_bales');
        const priceEl=document.getElementById('metric_average_price');
        const revEl=document.getElementById('metric_total_revenue');
        const debtPctEl=document.getElementById('metric_debt_recovered');
        const debtEl=document.getElementById('metric_total_debt');



        if(kgEl)
        kgEl.textContent =
        (metrics.total_production ?? 0)
        .toLocaleString('en-US',{
        maximumFractionDigits:0
        });



        if(balesEl)
        balesEl.textContent =
        (metrics.total_bales ?? 0);



        if(rejectedEl)
        rejectedEl.textContent =
        (metrics.rejected_bales ?? 0);



        if(priceEl)
        priceEl.textContent =
        "$"+(metrics.average_price ?? 0)
        .toLocaleString('en-US',{
        minimumFractionDigits:2
        });



        if(revEl)
        revEl.textContent =
        "$"+(metrics.total_revenue ?? 0)
        .toLocaleString('en-US',{
        minimumFractionDigits:2
        });



        if(debtPctEl)
        debtPctEl.textContent =
        (metrics.debt_recovered_percent ?? 0)+"%";



        if(debtEl)
        debtEl.textContent =
        "$"+(metrics.total_debt ?? 0)
        .toLocaleString('en-US',{
        minimumFractionDigits:2
        });


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
