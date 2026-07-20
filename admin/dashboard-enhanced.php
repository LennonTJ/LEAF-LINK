<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

// System-wide statistics
$registered_growers = 0;
$contractors_count = 0;
$active_contracts = 0;
$current_season = '';
$total_production = 0;
$total_revenue = 0;
$total_system_debt = 0;

$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM growers");
if ($r) { $row = mysqli_fetch_assoc($r); $registered_growers = $row['cnt'] ?? 0; }

$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM contractors");
if ($r) { $row = mysqli_fetch_assoc($r); $contractors_count = $row['cnt'] ?? 0; }

$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM contracts WHERE status='active'");
if ($r) { $row = mysqli_fetch_assoc($r); $active_contracts = $row['cnt'] ?? 0; }

$r = mysqli_query($conn, "SELECT season_name FROM seasons WHERE is_active=1 LIMIT 1");
if ($r && $sr = mysqli_fetch_assoc($r)) { $current_season = $sr['season_name']; }

// Total production and revenue
$r = mysqli_query($conn, "SELECT COALESCE(SUM(estimated_kg), 0) AS total_prod, COALESCE(SUM(projected_revenue), 0) AS total_rev FROM sale_projections");
if ($r) {
    $row = mysqli_fetch_assoc($r);
    $total_production = (float)($row['total_prod'] ?? 0);
    $total_revenue = (float)($row['total_rev'] ?? 0);
}

// Total debt
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total_debt), 0) AS total_debt FROM growers");
if ($r) {
    $row = mysqli_fetch_assoc($r);
    $total_system_debt = (float)($row['total_debt'] ?? 0);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LeafLink - System Administrator</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <p>System Administrator Dashboard</p>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Administrator</h3>
        <hr>
        <a href="dashboard-enhanced.php">📊 Dashboard</a>

        <h4>System Management</h4>
        <a href="growers.php">👨‍🌾 Growers</a>
        <a href="contractors.php">🏢 Contractors</a>
        <a href="contracts.php">📝 Contracts</a>

        <h4>Analytics</h4>
        <a href="analytics.php">📈 System Analytics</a>
        <a href="reports.php">📋 Reports</a>

        <h4>Configuration</h4>
        <a href="users.php">👥 Users</a>
        <a href="seasons.php">📅 Seasons</a>
        <a href="settings.php">⚙️ Settings</a>

        <hr>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="content">

        <!-- Welcome Card -->
        <div class="card">
            <h2>System Overview</h2>
            <p>Monitor the entire contract farming ecosystem and track system-wide performance metrics.</p>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-row">
            <div class="metric-card">
                <h3>Total Growers</h3>
                <p class="metric-value"><?php echo $registered_growers; ?></p>
                <p class="metric-unit">Registered Farmers</p>
            </div>

            <div class="metric-card">
                <h3>Contractors</h3>
                <p class="metric-value"><?php echo $contractors_count; ?></p>
                <p class="metric-unit">Active Organizations</p>
            </div>

            <div class="metric-card">
                <h3>Active Contracts</h3>
                <p class="metric-value"><?php echo $active_contracts; ?></p>
                <p class="metric-unit">Current Agreements</p>
            </div>

            <div class="metric-card">
                <h3>Current Season</h3>
                <p class="metric-value"><?php echo htmlspecialchars($current_season ?: date('Y')); ?></p>
                <p class="metric-unit">Active Period</p>
            </div>
        </div>

        <!-- Production & Revenue Metrics -->
        <div class="metrics-row">
            <div class="metric-card">
                <h3>System Production</h3>
                <p class="metric-value"><?php echo number_format($total_production, 0); ?></p>
                <p class="metric-unit">Kilograms</p>
            </div>

            <div class="metric-card">
                <h3>Portfolio Value</h3>
                <p class="metric-value">$<?php echo number_format($total_revenue, 2); ?></p>
                <p class="metric-unit">Projected Revenue</p>
            </div>

            <div class="metric-card">
                <h3>System Debt</h3>
                <p class="metric-value" style="<?php echo $total_system_debt > 50000 ? 'color: #ff6b6b;' : 'color: #51cf66;'; ?>">
                    $<?php echo number_format($total_system_debt, 2); ?>
                </p>
                <p class="metric-unit">Outstanding</p>
            </div>

            <div class="metric-card">
                <h3>Avg Contract Size</h3>
                <p class="metric-value"><?php echo $active_contracts > 0 ? number_format($total_production / $active_contracts, 0) : '0'; ?></p>
                <p class="metric-unit">kg per contract</p>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Contract Status Overview -->
            <div class="chart-container">
                <h3>📋 Contract Status Distribution</h3>
                <canvas id="contractStatusChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">All contracts by status</p>
            </div>

            <!-- Regional Performance -->
            <div class="chart-container">
                <h3>🗺️ Regional Production Heatmap</h3>
                <canvas id="regionalChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Production by province/region</p>
            </div>

            <!-- Debt Analysis -->
            <div class="chart-container">
                <h3>💳 System Debt Analysis</h3>
                <canvas id="debtAnalysisChart"></canvas>
                <p style="font-size: 12px; color: #999; margin-top: 10px;">Top debtors in the system</p>
            </div>

        </div>

        <!-- System Health Summary -->
        <div class="card">
            <h2>System Health Summary</h2>
            <div id="systemHealth">
                <p style="text-align: center; color: #999; padding: 20px;">Loading system metrics...</p>
            </div>
        </div>

        <!-- Regional Performance Table -->
        <div class="card">
            <h2>Regional Performance Breakdown</h2>
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Region/Province</th>
                        <th>Growers</th>
                        <th>Production (kg)</th>
                        <th>Revenue ($)</th>
                        <th>Avg per Grower</th>
                    </tr>
                </thead>
                <tbody id="regionalTableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #999;">Loading regional data...</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="../assets/js/charts.js"></script>

<script>
    async function loadAdminDashboard() {
        try {
            // Load Contract Status Chart
            const statusRes = await fetch('../api/visualization-data.php?action=contract_status');
            const statusData = await statusRes.json();
            LeafLinkCharts.createContractStatusChart('contractStatusChart', statusData);

            // Load Debt Analysis Chart
            const debtRes = await fetch('../api/visualization-data.php?action=debt_status');
            const debtData = await debtRes.json();
            if (debtData.names && debtData.names.length > 0) {
                LeafLinkCharts.createDebtStatusChart('debtAnalysisChart', debtData.names, debtData.debts);
            }

            // Load Regional Performance
            const regionalRes = await fetch('../api/visualization-data.php?action=regional_performance');
            const regionalData = await regionalRes.json();
            
            if (regionalData && regionalData.length > 0) {
                // Create bar chart for regional production
                const labels = regionalData.map(r => r.region);
                const production = regionalData.map(r => r.production);
                
                const ctx = document.getElementById('regionalChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Production (kg)',
                            data: production,
                            backgroundColor: LeafLinkCharts.colors.primary,
                            borderColor: LeafLinkCharts.colors.text,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Production (kg)' }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });

                // Populate regional table
                let html = '';
                regionalData.forEach(region => {
                    const avgPerGrower = region.growers > 0 ? (region.production / region.growers).toFixed(0) : 0;
                    html += `
                        <tr>
                            <td><strong>${region.region || 'Unknown'}</strong></td>
                            <td>${region.growers}</td>
                            <td>${Number(region.production).toLocaleString('en-US', {maximumFractionDigits: 0})}</td>
                            <td>$${Number(region.revenue).toLocaleString('en-US', {maximumFractionDigits: 2})}</td>
                            <td>${Number(avgPerGrower).toLocaleString('en-US')} kg</td>
                        </tr>
                    `;
                });
                document.getElementById('regionalTableBody').innerHTML = html;
            }

            // Generate System Health Summary
            generateSystemHealth(statusData, debtData, regionalData);

        } catch (error) {
            console.error('Error loading admin dashboard:', error);
        }
    }

    function generateSystemHealth(statusData, debtData, regionalData) {
        const health = document.getElementById('systemHealth');
        
        let html = '<ul style="list-style: none; padding: 0;">';
        
        // Contract health
        const totalContracts = (statusData.active || 0) + (statusData.completed || 0) + (statusData.cancelled || 0);
        const activeRate = totalContracts > 0 ? ((statusData.active / totalContracts) * 100).toFixed(1) : 0;
        const healthColor = activeRate >= 80 ? '#51cf66' : activeRate >= 60 ? '#ffd43b' : '#ff6b6b';
        html += `<li style="padding: 10px; border-left: 4px solid ${healthColor};">
            <strong>Contract Health:</strong> ${activeRate}% of contracts active
        </li>`;

        // Debt analysis
        const totalDebt = debtData.debts ? debtData.debts.reduce((a, b) => a + b, 0) : 0;
        const highDebtCount = debtData.debts ? debtData.debts.filter(d => d > 5000).length : 0;
        const debtColor = highDebtCount <= 2 ? '#51cf66' : '#ff6b6b';
        html += `<li style="padding: 10px; border-left: 4px solid ${debtColor};">
            <strong>Debt Status:</strong> ${highDebtCount} grower(s) with high debt. System total: $${Number(totalDebt).toLocaleString('en-US', {maximumFractionDigits: 2})}
        </li>`;

        // Regional coverage
        const regionCount = Array.isArray(regionalData) ? regionalData.length : 0;
        html += `<li style="padding: 10px; border-left: 4px solid #4dabf7;">
            <strong>Regional Coverage:</strong> Active in ${regionCount} regions
        </li>`;

        // Overall system status
        const overallHealth = (activeRate >= 75 && highDebtCount <= 3) ? '✓ Healthy' : '⚠️ Needs Attention';
        const statusColor = (activeRate >= 75 && highDebtCount <= 3) ? '#51cf66' : '#ff6b6b';
        html += `<li style="padding: 10px; border-left: 4px solid ${statusColor}; font-weight: bold;">
            <strong>Overall System Status:</strong> ${overallHealth}
        </li>`;

        html += '</ul>';
        health.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', loadAdminDashboard);
</script>

</body>
</html>
