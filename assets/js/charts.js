/**
 * LeafLink Data Visualization Library
 * Charts and visualization utilities using Chart.js
 */

// Color scheme matching LeafLink theme
const LEAFLINK_COLORS = {
    primary: '#0bca44',      // Green
    secondary: '#f4a261',    // Orange
    accent: '#e76f51',       // Red-orange
    light_green: '#b8d4b0',  // Light green
    text: '#137813',         // Dark green
    light_bg: '#f0f5f1',     // Light background
    warning: '#ff6b6b',      // Red for warnings
    success: '#51cf66',      // Green for success
    info: '#4dabf7'          // Blue for info
};

/**
 * Production Trend Chart - Shows kg produced over time/seasons
 * @param {string} canvasId - Canvas element ID
 * @param {array} labels - Period labels (months/seasons)
 * @param {array} data - Production data in kg
 */
function createProductionTrendChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Production (kg)',
                data: data,
                borderColor: LEAFLINK_COLORS.primary,
                backgroundColor: 'rgba(11, 202, 68, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: LEAFLINK_COLORS.primary,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    labels: { font: { size: 14, weight: 'bold' } }
                }

/**
 * Sales & Revenue Trend - combined line chart for kg and revenue over dates
 * @param {string} canvasId
 * @param {array} labels
 * @param {array} kgData
 * @param {array} revenueData
 */
function createSalesAndRevenueTrendChart(canvasId, labels, kgData, revenueData) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Kilograms (kg)',
                    data: kgData,
                    borderColor: LEAFLINK_COLORS.primary,
                    backgroundColor: 'rgba(11,202,68,0.08)',
                    yAxisID: 'yKg',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Revenue (USD)',
                    data: revenueData,
                    borderColor: LEAFLINK_COLORS.info,
                    backgroundColor: 'rgba(77,171,247,0.08)',
                    yAxisID: 'yRev',
                    fill: false,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                yKg: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Kilograms (kg)' }
                },
                yRev: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Revenue (USD)' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

/**
 * Revenue vs Debt Impact Chart - simple bar showing gross, deduction, final
 */
function createRevenueDebtChart(canvasId, labels, values) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Amount (USD)',
                data: values,
                backgroundColor: [LEAFLINK_COLORS.info, LEAFLINK_COLORS.warning, LEAFLINK_COLORS.primary]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'USD' } } }
        }
    });
}
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Kilograms (kg)' }
                }
            }
        }
    });
}

/**
 * Revenue Performance Chart - Shows projected vs actual revenue
 * @param {string} canvasId - Canvas element ID
 * @param {array} labels - Period labels
 * @param {array} projectedData - Projected revenue
 * @param {array} actualData - Actual revenue
 */
function createRevenueChart(canvasId, labels, projectedData, actualData) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Projected Revenue',
                    data: projectedData,
                    backgroundColor: LEAFLINK_COLORS.light_green,
                    borderColor: LEAFLINK_COLORS.primary,
                    borderWidth: 2
                },
                {
                    label: 'Actual Revenue',
                    data: actualData,
                    backgroundColor: LEAFLINK_COLORS.primary,
                    borderColor: LEAFLINK_COLORS.text,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    labels: { font: { size: 14, weight: 'bold' } }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Revenue ($)' }
                }
            }
        }
    });
}

/**
 * Quality Distribution Chart - Pie chart showing grade distribution
 * @param {string} canvasId - Canvas element ID
 * @param {array} grades - Grade codes (L2O, L2FA, etc.)
 * @param {array} counts - Count of each grade
 */
function createQualityDistributionChart(canvasId, grades, counts) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    const colors = [
        LEAFLINK_COLORS.primary,
        LEAFLINK_COLORS.secondary,
        LEAFLINK_COLORS.accent,
        LEAFLINK_COLORS.light_green,
        LEAFLINK_COLORS.info
    ];
    
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: grades,
            datasets: [{
                data: counts,
                backgroundColor: colors.slice(0, grades.length),
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 12, weight: 'bold' }, padding: 15 }
                }
            }
        }
    });
}

/**
 * Grower Performance Comparison - Bar chart comparing multiple growers
 * @param {string} canvasId - Canvas element ID
 * @param {array} growerNames - List of grower names
 * @param {array} productionData - Production kg per grower
 * @param {array} revenueData - Revenue per grower
 */
function createGrowerComparisonChart(canvasId, growerNames, productionData, revenueData) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: growerNames,
            datasets: [
                {
                    label: 'Production (kg)',
                    data: productionData,
                    backgroundColor: LEAFLINK_COLORS.primary,
                    borderColor: LEAFLINK_COLORS.text,
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenue ($)',
                    data: revenueData,
                    backgroundColor: LEAFLINK_COLORS.secondary,
                    borderColor: LEAFLINK_COLORS.accent,
                    borderWidth: 1,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    labels: { font: { size: 12, weight: 'bold' } }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Production (kg)' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Revenue ($)' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

/**
 * Debt Status Chart - Horizontal bar showing grower debt levels
 * @param {string} canvasId - Canvas element ID
 * @param {array} growerNames - Grower names
 * @param {array} debtAmounts - Debt amounts
 */
function createDebtStatusChart(canvasId, growerNames, debtAmounts) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    // Color code by debt level
    const colors = debtAmounts.map(debt => {
        if (debt > 5000) return LEAFLINK_COLORS.warning;      // Red - high debt
        if (debt > 2000) return LEAFLINK_COLORS.secondary;    // Orange - medium
        return LEAFLINK_COLORS.success;                        // Green - low/no debt
    });
    
    return new Chart(ctx, {
        type: 'barH',
        data: {
            labels: growerNames,
            datasets: [{
                label: 'Total Debt ($)',
                data: debtAmounts,
                backgroundColor: colors,
                borderColor: '#000',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Debt Amount ($)' }
                }
            }
        }
    });
}

/**
 * Contract Status Overview - Pie chart
 * @param {string} canvasId - Canvas element ID
 * @param {object} statusCounts - {active, completed, cancelled}
 */
function createContractStatusChart(canvasId, statusCounts) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Active', 'Completed', 'Cancelled'],
            datasets: [{
                data: [
                    statusCounts.active || 0,
                    statusCounts.completed || 0,
                    statusCounts.cancelled || 0
                ],
                backgroundColor: [
                    LEAFLINK_COLORS.primary,
                    LEAFLINK_COLORS.light_green,
                    LEAFLINK_COLORS.warning
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 12, weight: 'bold' } }
                }
            }
        }
    });
}

/**
 * Regional Performance Heatmap Data - Prepares data for regional analysis
 * @param {array} regionData - Array of {region, growers, production, revenue}
 */
function createRegionalPerformanceMatrix(regionData) {
    return {
        regions: regionData.map(r => r.region),
        growerCounts: regionData.map(r => r.growers),
        production: regionData.map(r => r.production),
        revenue: regionData.map(r => r.revenue)
    };
}

/**
 * Performance Metrics Card Helper
 * Creates KPI cards for dashboard
 */
function createMetricsCard(title, value, unit, trend = null) {
    const trendHTML = trend ? `
        <p style="color: ${trend > 0 ? LEAFLINK_COLORS.success : LEAFLINK_COLORS.warning}; font-weight: bold;">
            ${trend > 0 ? '↑' : '↓'} ${Math.abs(trend)}%
        </p>
    ` : '';
    
    return `
        <div class="metric-card">
            <h3>${title}</h3>
            <p class="metric-value">${value.toLocaleString()}</p>
            <p class="metric-unit">${unit}</p>
            ${trendHTML}
        </div>
    `;
}

// Export for use in modules
window.LeafLinkCharts = {
    colors: LEAFLINK_COLORS,
    createProductionTrendChart,
    createRevenueChart,
    createQualityDistributionChart,
    createGrowerComparisonChart,
    createSalesAndRevenueTrendChart,
    createRevenueDebtChart,
    createDebtStatusChart,
    createContractStatusChart,
    createRegionalPerformanceMatrix,
    createMetricsCard
};
