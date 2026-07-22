<?php
/**
 * LeafLink Visualization Data Endpoints
 * Returns JSON data for Chart.js visualizations
 */

include("../config/database.php");

header('Content-Type: application/json');

session_start();
// Require login for API access
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Prefer session-scoped IDs to prevent exposing other users' data
if (isset($_SESSION['grower_id'])) {
    $grower_id = $_SESSION['grower_id'];
}
if (isset($_SESSION['contractor_id'])) {
    $contractor_id = $_SESSION['contractor_id'];
}

$action = $_GET['action'] ?? '';
$grower_id = $_GET['grower_id'] ?? null;
$contractor_id = $_GET['contractor_id'] ?? null;
// Optional date range filters (expect YYYY-MM-DD)
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Validate dates (basic YYYY-MM-DD) and build SQL date filter fragment
$dateFilter = '';
if ($start_date) {
    // allow only YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        $dateFilter .= " AND projection_date >= '" . mysqli_real_escape_string($conn, $start_date) . "' ";
    }
}
if ($end_date) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        $dateFilter .= " AND projection_date <= '" . mysqli_real_escape_string($conn, $end_date) . "' ";
    }
}

/**
 * Get production trend data for a grower
 */
function getProductionTrendData($grower_id) {
    global $conn;
    global $dateFilter;

    $sql = "SELECT DATE_FORMAT(projection_date, '%Y-%m') AS month, 
            COALESCE(SUM(estimated_kg), 0) AS production
            FROM sale_projections 
            WHERE grower_id = ? " . $dateFilter . "
            GROUP BY DATE_FORMAT(projection_date, '%Y-%m')
            ORDER BY month ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $labels = [];
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['month'];
        $data[] = (float)$row['production'];
    }
    
    return ['labels' => $labels, 'data' => $data];
}

/**
 * Get revenue performance data
 */
function getRevenueData($grower_id) {
    global $conn;
    global $dateFilter;

    $sql = "SELECT DATE_FORMAT(projection_date, '%Y-%m') AS month,
            COALESCE(SUM(projected_revenue), 0) AS projected,
            COALESCE(SUM(projected_payout), 0) AS actual
            FROM sale_projections 
            WHERE grower_id = ? " . $dateFilter . "
            GROUP BY DATE_FORMAT(projection_date, '%Y-%m')
            ORDER BY month ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $labels = [];
    $projected = [];
    $actual = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['month'];
        $projected[] = (float)$row['projected'];
        $actual[] = (float)$row['actual'];
    }
    
    return [
        'labels' => $labels,
        'projected' => $projected,
        'actual' => $actual
    ];
}

/**
 * Get quality distribution data
 */
function getQualityDistributionData($grower_id) {
    global $conn;
    global $dateFilter;

    // Return quantity (kg) per grade so farmer sees production by grade
    $sql = "SELECT generated_grade, COALESCE(SUM(estimated_kg),0) AS kg
            FROM sale_projections
            WHERE grower_id = ? AND generated_grade IS NOT NULL " . $dateFilter . "
            GROUP BY generated_grade
            ORDER BY kg DESC
            LIMIT 20";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $grades = [];
    $quantities = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $grades[] = $row['generated_grade'];
        $quantities[] = (float)$row['kg'];
    }

    return ['grades' => $grades, 'quantities' => $quantities];
}


/**
 * Sales trend - daily sales entries (date, kg, revenue)
 */
function getSalesTrendData($grower_id) {
    global $conn, $dateFilter;

    $sql = "SELECT DATE(projection_date) AS sale_date,
            COALESCE(SUM(estimated_kg),0) AS kg,
            COALESCE(SUM(projected_revenue),0) AS revenue
            FROM sale_projections
            WHERE grower_id = ? " . $dateFilter . "
            GROUP BY DATE(projection_date)
            ORDER BY sale_date ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $dates = [];
    $kgs = [];
    $revenues = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = $row['sale_date'];
        $kgs[] = (float)$row['kg'];
        $revenues[] = (float)$row['revenue'];
    }

    return ['dates' => $dates, 'kgs' => $kgs, 'revenues' => $revenues];
}

/**
 * Get contractor's grower performance comparison
 */
function getGrowerComparisonData($contractor_id) {
    global $conn;
    
    $sql = "SELECT g.grower_no, CONCAT(g.first_name, ' ', g.last_name) AS name,
            COALESCE(SUM(sp.estimated_kg), 0) AS production,
            COALESCE(SUM(sp.projected_revenue), 0) AS revenue
            FROM growers g
            JOIN contracts c ON g.grower_id = c.grower_id
            LEFT JOIN sale_projections sp ON g.grower_id = sp.grower_id
            WHERE c.contractor_id = ? AND c.status = 'active'
            GROUP BY g.grower_id
            ORDER BY production DESC
            LIMIT 15";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $contractor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $names = [];
    $production = [];
    $revenue = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $names[] = substr($row['name'], 0, 15);
        $production[] = (float)$row['production'];
        $revenue[] = (float)$row['revenue'];
    }
    
    return [
        'names' => $names,
        'production' => $production,
        'revenue' => $revenue
    ];
}

/**
 * Get grower debt status
 */
function getGrowerDebtData($contractor_id = null) {
    global $conn;
    
    if ($contractor_id) {
        $sql = "SELECT g.grower_no, CONCAT(g.first_name, ' ', g.last_name) AS name,
                g.total_debt
                FROM growers g
                JOIN contracts c ON g.grower_id = c.grower_id
                WHERE c.contractor_id = ? AND c.status = 'active'
                ORDER BY g.total_debt DESC
                LIMIT 15";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $contractor_id);
    } else {
        $sql = "SELECT grower_no, CONCAT(first_name, ' ', last_name) AS name,
                total_debt
                FROM growers
                ORDER BY total_debt DESC
                LIMIT 20";
        $stmt = mysqli_prepare($conn, $sql);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $names = [];
    $debts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $names[] = substr($row['name'], 0, 15);
        $debts[] = (float)$row['total_debt'];
    }
    
    return ['names' => $names, 'debts' => $debts];
}

/**
 * Get contract status overview
 */
function getContractStatusData($contractor_id = null) {
    global $conn;
    
    if ($contractor_id) {
        $sql = "SELECT status, COUNT(*) AS count
                FROM contracts
                WHERE contractor_id = ?
                GROUP BY status";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $contractor_id);
    } else {
        $sql = "SELECT status, COUNT(*) AS count
                FROM contracts
                GROUP BY status";
        $stmt = mysqli_prepare($conn, $sql);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $statusCounts = ['active' => 0, 'completed' => 0, 'cancelled' => 0];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
    
    return $statusCounts;
}

/**
 * Get performance metrics summary
 */
function getPerformanceMetrics($grower_id) {
    global $conn;
    global $dateFilter;

    $metrics = [
        'total_production' => 0,
        'number_of_sales' => 0,
        'total_revenue' => 0,
        'avg_production' => 0,
        'total_debt' => 0,
        'quality_grades' => 0,
        'total_payouts' => 0,
        'debt_recovered_percent' => 0,
        'dominant_grade' => null,
        'expected_payout' => 0,
        'gross_revenue' => 0,
        'debt_deduction' => 0,
        'final_expected_payout' => 0,
    ];
    
    // Total production
    $sql = "SELECT COALESCE(SUM(estimated_kg), 0) AS total, COUNT(*) AS sales_count, COALESCE(SUM(projected_revenue),0) AS gross_revenue FROM sale_projections WHERE grower_id = ? " . $dateFilter;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $metrics['total_production'] = (float)$row['total'];
    $metrics['number_of_sales'] = (int)($row['sales_count'] ?? 0);
    $metrics['gross_revenue'] = (float)($row['gross_revenue'] ?? 0);
    
    // Total revenue (duplicate of gross_revenue but keep for compatibility)
    $sql = "SELECT COALESCE(SUM(projected_revenue), 0) AS total FROM sale_projections WHERE grower_id = ? " . $dateFilter;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $metrics['total_revenue'] = (float)$row['total'];

    // Total payouts (used as expected payout)
    $sql = "SELECT COALESCE(SUM(projected_payout), 0) AS total_payouts FROM sale_projections WHERE grower_id = ? " . $dateFilter;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $metrics['total_payouts'] = (float)$row['total_payouts'];
    $metrics['expected_payout'] = $metrics['total_payouts'];
    
    // Average production per entry
    $sql = "SELECT COALESCE(AVG(estimated_kg), 0) AS avg FROM sale_projections WHERE grower_id = ? " . $dateFilter;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $metrics['avg_production'] = (float)$row['avg'];
    
    // Total debt
    $sql = "SELECT total_debt FROM growers WHERE grower_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $metrics['total_debt'] = (float)($row['total_debt'] ?? 0);

    // Debt recovered percentage (proxy = total_payouts / total_debt)
    // Debt recovered amount (proxy) and percent
    $recovered_amt = min($metrics['total_payouts'], $metrics['total_debt']);
    $metrics['debt_recovered_amount'] = $recovered_amt;
    if ($metrics['total_debt'] > 0) {
        $metrics['debt_recovered_percent'] = round(min(100, ($recovered_amt / $metrics['total_debt']) * 100), 2);
    } else {
        $metrics['debt_recovered_percent'] = 0.0;
    }

    $metrics['outstanding_debt'] = max(0, $metrics['total_debt'] - $recovered_amt);

    // Revenue vs debt deduction: gross revenue minus expected payout = deductions
    $metrics['debt_deduction'] = max(0, $metrics['gross_revenue'] - $metrics['expected_payout']);
    $metrics['final_expected_payout'] = $metrics['expected_payout'];
    
    // Unique grades
    $sql = "SELECT COUNT(DISTINCT generated_grade) AS count FROM sale_projections WHERE grower_id = ? AND generated_grade IS NOT NULL " . $dateFilter;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $metrics['quality_grades'] = (int)$row['count'];

    // Dominant grade by volume
    $sql = "SELECT generated_grade, COALESCE(SUM(estimated_kg),0) AS kg FROM sale_projections WHERE grower_id = ? AND generated_grade IS NOT NULL " . $dateFilter . " GROUP BY generated_grade ORDER BY kg DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $metrics['dominant_grade'] = $row['generated_grade'];
    }

    // Insights: simple rule-based
    $metrics['insights'] = [];
    // Example insight: debt recovery
    if ($metrics['debt_recovered_percent'] > 0) {
        $metrics['insights'][] = "Your debt recovery rate is {$metrics['debt_recovered_percent']}%.";
    } else {
        $metrics['insights'][] = "No debt recovery recorded in the selected period.";
    }

    // Production trend insight (compare last 3 days vs previous 3 days)
    $sql = "SELECT DATE(projection_date) AS sale_date, COALESCE(SUM(estimated_kg),0) AS kg FROM sale_projections WHERE grower_id = ? " . $dateFilter . " GROUP BY DATE(projection_date) ORDER BY sale_date DESC LIMIT 6";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $grower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $kgs = [];
    while ($r = mysqli_fetch_assoc($result)) { $kgs[] = (float)$r['kg']; }
    if (count($kgs) >= 4) {
        $recent = array_sum(array_slice($kgs, 0, 3));
        $prev = array_sum(array_slice($kgs, 3, 3));
        if ($prev > 0) {
            $pct = round((($recent - $prev) / $prev) * 100, 1);
            $metrics['insights'][] = $pct >= 0 ? "Production changed by +{$pct}% compared to previous period." : "Production changed by {$pct}% compared to previous period.";
        }
    }
    
    return $metrics;
}

/**
 * Get regional performance data
 */
function getRegionalPerformanceData() {
    global $conn;
    
    $sql = "SELECT g.province AS region,
            COUNT(DISTINCT g.grower_id) AS growers,
            COALESCE(SUM(sp.estimated_kg), 0) AS production,
            COALESCE(SUM(sp.projected_revenue), 0) AS revenue
            FROM growers g
            LEFT JOIN sale_projections sp ON g.grower_id = sp.grower_id
            WHERE g.province IS NOT NULL AND g.province != ''
            GROUP BY g.province
            ORDER BY production DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'region' => $row['region'],
            'growers' => (int)$row['growers'],
            'production' => (float)$row['production'],
            'revenue' => (float)$row['revenue']
        ];
    }
    
    return $data;
}

// Route requests
switch ($action) {
    case 'production_trend':
        if ($grower_id) {
            echo json_encode(getProductionTrendData($grower_id));
        }
        break;
    
    case 'revenue':
        if ($grower_id) {
            echo json_encode(getRevenueData($grower_id));
        }
        break;
    
    case 'quality_distribution':
        if ($grower_id) {
            echo json_encode(getQualityDistributionData($grower_id));
        }
        break;

    case 'sales_trend':
        if ($grower_id) {
            echo json_encode(getSalesTrendData($grower_id));
        }
        break;
    
    case 'grower_comparison':
        if ($contractor_id) {
            echo json_encode(getGrowerComparisonData($contractor_id));
        }
        break;
    
    case 'debt_status':
        if ($contractor_id) {
            echo json_encode(getGrowerDebtData($contractor_id));
        } else {
            echo json_encode(getGrowerDebtData());
        }
        break;
    
    case 'contract_status':
        if ($contractor_id) {
            echo json_encode(getContractStatusData($contractor_id));
        } else {
            echo json_encode(getContractStatusData());
        }
        break;
    
    case 'metrics':
        if ($grower_id) {
            echo json_encode(getPerformanceMetrics($grower_id));
        }
        break;
    
    case 'regional_performance':
        echo json_encode(getRegionalPerformanceData());
        break;
    
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

?>
