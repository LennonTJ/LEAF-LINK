<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");


/*
|--------------------------------------------------------------------------
| GET CONTRACTOR ID
|--------------------------------------------------------------------------
*/

$contractor_id = $_SESSION['contractor_id'] ?? null;

if (!$contractor_id) {

    $uid = $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT contractor_id, username
         FROM users
         WHERE user_id=?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {

        $contractor_id = $user['contractor_id'];

        if (!$contractor_id && !empty($user['username'])) {

            $cstmt = mysqli_prepare(
                $conn,
                "SELECT contractor_id
                 FROM contractors
                 WHERE contractor_code=?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $cstmt,
                "s",
                $user['username']
            );

            mysqli_stmt_execute($cstmt);

            $cres = mysqli_stmt_get_result($cstmt);

            if ($crow = mysqli_fetch_assoc($cres)) {
                $contractor_id = $crow['contractor_id'];
            }
        }
    }
}

if (!$contractor_id) {
    die("Contractor not linked.");
}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function money($value)
{
    return "$" . number_format((float)$value, 2);
}


function percentage($value)
{
    return number_format((float)$value, 1) . "%";
}


/*
|--------------------------------------------------------------------------
| GET GROWER RECOVERY DATA
|--------------------------------------------------------------------------
*/

$sql = "
SELECT

    g.grower_id,
    g.grower_no,
    g.first_name,
    g.last_name,
    g.total_debt,

    COALESCE(
        SUM(s.net_payment),
        0
    ) AS recovered

FROM growers g

JOIN contracts c
    ON g.grower_id = c.grower_id

LEFT JOIN sales s
    ON g.grower_no = s.grower_no

WHERE c.contractor_id = ?

GROUP BY
    g.grower_id,
    g.grower_no,
    g.first_name,
    g.last_name,
    g.total_debt

ORDER BY
    g.first_name,
    g.last_name
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $contractor_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Risk Analysis | LeafLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | RISK TOOLBAR
        |--------------------------------------------------------------------------
        */

        .risk-toolbar {

            display:flex;
            gap:15px;
            align-items:center;
            justify-content:space-between;

            margin-bottom:20px;

            flex-wrap:wrap;

        }


        .search-box {

            flex:1;
            min-width:250px;

        }


        .search-box input {

            width:100%;

            padding:12px 15px;

            border:1px solid #ddd;

            border-radius:8px;

            font-size:14px;

            box-sizing:border-box;

        }


        .risk-filter {

            min-width:180px;

        }


        .risk-filter select {

            width:100%;

            padding:12px 15px;

            border:1px solid #ddd;

            border-radius:8px;

            font-size:14px;

            background:white;

            cursor:pointer;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        #riskTable {

            width:100%;

            border-collapse:collapse;

        }


        #riskTable th {

            text-align:left;

            padding:14px;

            background:#f7f8f7;

            border-bottom:1px solid #ddd;

        }


        #riskTable td {

            padding:14px;

            border-bottom:1px solid #eee;

            vertical-align:middle;

        }


        .risk-row {

            transition:background .15s ease;

        }


        .risk-row:hover {

            background:#f8fbf9;

        }


        /*
        |--------------------------------------------------------------------------
        | RISK BADGES
        |--------------------------------------------------------------------------
        */

        .risk-badge {

            display:inline-block;

            padding:5px 10px;

            border-radius:20px;

            font-size:12px;

            font-weight:600;

        }


        .risk-low {

            background:#e8f7ee;

            color:#198754;

        }


        .risk-medium {

            background:#fff4d6;

            color:#b77900;

        }


        .risk-high {

            background:#fde8e8;

            color:#c62828;

        }


        /*
        |--------------------------------------------------------------------------
        | DETAILS BUTTON
        |--------------------------------------------------------------------------
        */

        .details-btn {

            border:none;

            background:#1f6f3a;

            color:white;

            padding:8px 13px;

            border-radius:7px;

            cursor:pointer;

            font-size:13px;

            font-weight:600;

        }


        .details-btn:hover {

            opacity:.9;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY RESULTS
        |--------------------------------------------------------------------------
        */

        #noResults {

            display:none;

            text-align:center;

            padding:30px;

            color:#777;

        }


        .result-count {

            margin-top:12px;

            font-size:13px;

            color:#777;

        }


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .risk-modal {

            display:none;

            position:fixed;

            z-index:9999;

            inset:0;

            background:rgba(0,0,0,.55);

            padding:30px;

            overflow-y:auto;

            box-sizing:border-box;

        }


        .risk-modal-content {

            background:white;

            max-width:1050px;

            margin:30px auto;

            border-radius:14px;

            overflow:hidden;

            box-shadow:0 20px 60px rgba(0,0,0,.25);

        }


        .risk-modal-header {

            padding:22px 25px;

            background:#f7faf8;

            border-bottom:1px solid #e5e5e5;

            display:flex;

            justify-content:space-between;

            align-items:center;

            gap:20px;

        }


        .risk-modal-header h2 {

            margin:0;

        }


        .risk-modal-header small {

            color:#777;

        }


        .close-modal {

            border:none;

            background:transparent;

            font-size:28px;

            cursor:pointer;

            color:#666;

        }


        .risk-modal-body {

            padding:25px;

        }


        /*
        |--------------------------------------------------------------------------
        | DETAIL CARDS
        |--------------------------------------------------------------------------
        */

        .detail-grid {

            display:grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(180px,1fr)
                );

            gap:15px;

            margin-bottom:25px;

        }


        .detail-card {

            border:1px solid #e5e5e5;

            border-radius:10px;

            padding:18px;

            background:#fff;

        }


        .detail-card .label {

            font-size:12px;

            color:#777;

            margin-bottom:7px;

        }


        .detail-card .value {

            font-size:21px;

            font-weight:700;

        }


        /*
        |--------------------------------------------------------------------------
        | ANALYSIS GRID
        |--------------------------------------------------------------------------
        */

        .analysis-grid {

            display:grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(300px,1fr)
                );

            gap:20px;

        }


        .analysis-card {

            border:1px solid #e5e5e5;

            border-radius:10px;

            padding:20px;

        }


        .analysis-card h3 {

            margin-top:0;

            margin-bottom:15px;

        }


        .analysis-item {

            padding:10px 0;

            border-bottom:1px solid #eee;

            font-size:14px;

            line-height:1.5;

        }


        .analysis-item:last-child {

            border-bottom:none;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .status-positive {

            color:#198754;

            font-weight:600;

        }


        .status-warning {

            color:#b77900;

            font-weight:600;

        }


        .status-negative {

            color:#c62828;

            font-weight:600;

        }


        /*
        |--------------------------------------------------------------------------
        | RISK FACTOR ITEMS
        |--------------------------------------------------------------------------
        */

        .risk-factor {

            padding:12px 14px;

            margin-bottom:10px;

            border-radius:8px;

            background:#fff7f7;

            border-left:4px solid #c62828;

            font-size:14px;

        }


        .risk-factor:last-child {

            margin-bottom:0;

        }


        /*
        |--------------------------------------------------------------------------
        | POSITIVE INDICATOR
        |--------------------------------------------------------------------------
        */

        .positive-factor {

            padding:12px 14px;

            margin-bottom:10px;

            border-radius:8px;

            background:#f1faf4;

            border-left:4px solid #198754;

            font-size:14px;

        }


        /*
        |--------------------------------------------------------------------------
        | RECOMMENDATIONS
        |--------------------------------------------------------------------------
        */

        .recommendation {

            padding:14px 16px;

            margin-bottom:10px;

            border-radius:8px;

            background:#f7faf8;

            border-left:4px solid #1f6f3a;

            font-size:14px;

            line-height:1.5;

        }


        .recommendation strong {

            display:block;

            margin-bottom:4px;

        }


        /*
        |--------------------------------------------------------------------------
        | OVERALL ASSESSMENT
        |--------------------------------------------------------------------------
        */

        .assessment {

            padding:18px;

            border-radius:10px;

            margin-bottom:25px;

            background:#f7faf8;

            border:1px solid #e2ebe5;

        }


        .assessment h3 {

            margin-top:0;

        }


        .assessment p {

            margin-bottom:0;

            line-height:1.6;

        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media(max-width:700px){

            .risk-modal {

                padding:10px;

            }


            .risk-modal-content {

                margin:10px auto;

            }


            #riskTable th:nth-child(2),
            #riskTable td:nth-child(2) {

                display:none;

            }

        }

    </style>

</head>


<body>


<div class="header">

    <h1>LeafLink</h1>

    <h2>Recovery Risk Analysis</h2>

</div>


<div class="content">


<div class="card">


<!-- =========================================================
     SEARCH / FILTER
     ========================================================= -->

<div class="risk-toolbar">

    <div class="search-box">

        <input
            type="text"
            id="growerSearch"
            placeholder="Search by grower name or grower number..."
            autocomplete="off"
        >

    </div>


    <div class="risk-filter">

        <select id="riskFilter">

            <option value="ALL">
                All Risks
            </option>

            <option value="HIGH">
                High Risk
            </option>

            <option value="MEDIUM">
                Medium Risk
            </option>

            <option value="LOW">
                Low Risk
            </option>

        </select>

    </div>

</div>


<!-- =========================================================
     RISK TABLE
     ========================================================= -->

<table id="riskTable">

<thead>

<tr>

    <th>Grower</th>

    <th>Debt</th>

    <th>Recovered</th>

    <th>Risk</th>

    <th>Action</th>

</tr>

</thead>


<tbody>


<?php

$row_count = 0;


while ($r = mysqli_fetch_assoc($result)) {

    $grower_no =
        $r['grower_no'];

    $grower_name =
        trim(
            $r['first_name'] .
            " " .
            $r['last_name']
        );


    $debt =
        (float)$r['total_debt'];

    $paid =
        (float)$r['recovered'];


    /*
    |--------------------------------------------------------------------------
    | RECOVERY
    |--------------------------------------------------------------------------
    */

    $percent = 0;

    if ($debt > 0) {

        $percent =
            ($paid / $debt) * 100;

    }


    /*
    |--------------------------------------------------------------------------
    | SALES FETCH
    |--------------------------------------------------------------------------
    */

    $salesSql = "

    SELECT

        sale_date,
        delivered_mass,
        sold_mass,
        rejected_mass,
        total_bales,
        sold_bales,
        rejected_bales,
        gross_value,
        net_payment,
        grade

    FROM sales

    WHERE grower_no = ?

    ORDER BY sale_date ASC

    ";


    $salesStmt =
        mysqli_prepare(
            $conn,
            $salesSql
        );


    mysqli_stmt_bind_param(
        $salesStmt,
        "s",
        $grower_no
    );


    mysqli_stmt_execute(
        $salesStmt
    );


    $salesResult =
        mysqli_stmt_get_result(
            $salesStmt
        );


    $sales = [];


    while (
        $sale =
        mysqli_fetch_assoc(
            $salesResult
        )
    ) {

        $sales[] = $sale;

    }


    /*
    |--------------------------------------------------------------------------
    | BASIC TOTALS
    |--------------------------------------------------------------------------
    */

    $total_sales =
        count($sales);

    $total_mass = 0;

    $total_sold_mass = 0;

    $total_rejected_mass = 0;

    $total_revenue = 0;

    $quality_total = 0;

    $quality_count = 0;


    foreach (
        $sales as $sale
    ) {

        $total_mass +=
            (float)$sale['delivered_mass'];

        $total_sold_mass +=
            (float)$sale['sold_mass'];

        $total_rejected_mass +=
            (float)$sale['rejected_mass'];

        $total_revenue +=
            (float)$sale['net_payment'];


        /*
        |--------------------------------------------------------------------------
        | QUALITY EXTRACTION
        |--------------------------------------------------------------------------
        */

        $grade =
            strtoupper(
                trim(
                    $sale['grade'] ?? ""
                )
            );


        if (
            preg_match(
                '/^[A-Z](\d)/',
                $grade,
                $qualityMatch
            )
        ) {

            $quality_total +=
                (int)$qualityMatch[1];

            $quality_count++;

        }

    }


    $average_grade_quality = 0;


    if ($quality_count > 0) {

        $average_grade_quality =
            $quality_total /
            $quality_count;

    }


    /*
    |--------------------------------------------------------------------------
    | REJECTION RATE
    |--------------------------------------------------------------------------
    */

    $rejection_rate = 0;


    if ($total_mass > 0) {

        $rejection_rate =
            (
                $total_rejected_mass /
                $total_mass
            ) * 100;

    }


    /*
    |--------------------------------------------------------------------------
    | SOLD / DELIVERED EFFICIENCY
    |--------------------------------------------------------------------------
    */

    $sale_efficiency = 0;


    if ($total_mass > 0) {

        $sale_efficiency =
            (
                $total_sold_mass /
                $total_mass
            ) * 100;

    }


    /*
    |--------------------------------------------------------------------------
    | RECENT / PREVIOUS SALES
    |--------------------------------------------------------------------------
    */

    $recent_sales = [];

    $previous_sales = [];


    $sales_reversed =
        array_reverse(
            $sales
        );


    foreach (
        $sales_reversed as $index => $sale
    ) {

        if ($index < 3) {

            $recent_sales[] =
                $sale;

        }
        elseif ($index < 6) {

            $previous_sales[] =
                $sale;

        }

    }


    $recent_mass = 0;

    $previous_mass = 0;

    $recent_revenue = 0;

    $previous_revenue = 0;


    $recent_quality_total = 0;

    $recent_quality_count = 0;

    $previous_quality_total = 0;

    $previous_quality_count = 0;


    /*
    |--------------------------------------------------------------------------
    | RECENT
    |--------------------------------------------------------------------------
    */

    foreach (
        $recent_sales as $sale
    ) {

        $recent_mass +=
            (float)$sale['sold_mass'];

        $recent_revenue +=
            (float)$sale['net_payment'];


        $grade =
            strtoupper(
                trim(
                    $sale['grade'] ?? ""
                )
            );


        if (
            preg_match(
                '/^[A-Z](\d)/',
                $grade,
                $qm
            )
        ) {

            $recent_quality_total +=
                (int)$qm[1];

            $recent_quality_count++;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | PREVIOUS
    |--------------------------------------------------------------------------
    */

    foreach (
        $previous_sales as $sale
    ) {

        $previous_mass +=
            (float)$sale['sold_mass'];

        $previous_revenue +=
            (float)$sale['net_payment'];


        $grade =
            strtoupper(
                trim(
                    $sale['grade'] ?? ""
                )
            );


        if (
            preg_match(
                '/^[A-Z](\d)/',
                $grade,
                $qm
            )
        ) {

            $previous_quality_total +=
                (int)$qm[1];

            $previous_quality_count++;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | TREND CALCULATIONS
    |--------------------------------------------------------------------------
    */

    $volume_change = 0;

    if ($previous_mass > 0) {

        $volume_change =
            (
                (
                    $recent_mass -
                    $previous_mass
                )
                /
                $previous_mass
            ) * 100;

    }


    $revenue_change = 0;

    if ($previous_revenue > 0) {

        $revenue_change =
            (
                (
                    $recent_revenue -
                    $previous_revenue
                )
                /
                $previous_revenue
            ) * 100;

    }


    /*
    |--------------------------------------------------------------------------
    | QUALITY TREND
    |--------------------------------------------------------------------------
    */

    $recent_quality = 0;

    $previous_quality = 0;

    $quality_change = 0;


    if ($recent_quality_count > 0) {

        $recent_quality =
            $recent_quality_total /
            $recent_quality_count;

    }


    if ($previous_quality_count > 0) {

        $previous_quality =
            $previous_quality_total /
            $previous_quality_count;

    }


    if (
        $previous_quality_count > 0 &&
        $recent_quality_count > 0
    ) {

        $quality_change =
            $recent_quality -
            $previous_quality;

    }


    /*
    |--------------------------------------------------------------------------
    | LAST SALE
    |--------------------------------------------------------------------------
    */

    $last_sale_date = null;


    if (!empty($sales)) {

        $last_sale_date =
            $sales[
                count($sales) - 1
            ]['sale_date'];

    }


    /*
    |--------------------------------------------------------------------------
    | DAYS SINCE LAST SALE
    |--------------------------------------------------------------------------
    */

    $days_since_sale = null;


    if ($last_sale_date) {

        $lastTimestamp =
            strtotime(
                $last_sale_date
            );

        $days_since_sale =
            floor(
                (
                    time() -
                    $lastTimestamp
                )
                /
                86400
            );

    }


    /*
    |--------------------------------------------------------------------------
    | RISK SCORE
    |--------------------------------------------------------------------------
    |
    | This remains RULE-BASED.
    |
    */

    $risk_score = 0;


    /*
    | Debt recovery
    */

    if ($percent < 30) {

        $risk_score += 3;

    }
    elseif ($percent < 70) {

        $risk_score += 1;

    }


    /*
    | Volume decline
    */

    if ($volume_change <= -30) {

        $risk_score += 3;

    }
    elseif ($volume_change <= -20) {

        $risk_score += 2;

    }
    elseif ($volume_change <= -10) {

        $risk_score += 1;

    }


    /*
    | Revenue decline
    */

    if ($revenue_change <= -30) {

        $risk_score += 2;

    }
    elseif ($revenue_change <= -20) {

        $risk_score += 1;

    }


    /*
    | Quality decline
    */

    if ($quality_change <= -1) {

        $risk_score += 2;

    }
    elseif ($quality_change < 0) {

        $risk_score += 1;

    }


    /*
    | Low average quality
    */

    if (
        $quality_count > 0 &&
        $average_grade_quality <= 2
    ) {

        $risk_score += 1;

    }


    /*
    | Rejection
    */

    if ($rejection_rate >= 20) {

        $risk_score += 3;

    }
    elseif ($rejection_rate >= 10) {

        $risk_score += 2;

    }
    elseif ($rejection_rate >= 5) {

        $risk_score += 1;

    }


    /*
    | Stale sales
    */

    if (
        $days_since_sale !== null &&
        $days_since_sale >= 45
    ) {

        $risk_score += 2;

    }
    elseif (
        $days_since_sale !== null &&
        $days_since_sale >= 30
    ) {

        $risk_score += 1;

    }


    /*
    |--------------------------------------------------------------------------
    | FINAL RISK
    |--------------------------------------------------------------------------
    */

    if ($risk_score >= 6) {

        $risk = "HIGH";

        $risk_class = "risk-high";

    }
    elseif ($risk_score >= 3) {

        $risk = "MEDIUM";

        $risk_class = "risk-medium";

    }
    else {

        $risk = "LOW";

        $risk_class = "risk-low";

    }


    /*
    |--------------------------------------------------------------------------
    | RISK FACTORS
    |--------------------------------------------------------------------------
    */

    $risk_factors = [];

    $positive_indicators = [];

    $recommendations = [];


    /*
    |--------------------------------------------------------------------------
    | RECOVERY ANALYSIS
    |--------------------------------------------------------------------------
    */

    if ($percent < 30) {

        $risk_factors[] =
            "Only " .
            round($percent) .
            "% of the recorded debt has been recovered.";

        $recommendations[] = [
            "title" => "Prioritise debt recovery",
            "text" =>
                "Review this grower's outstanding balance and establish a clear recovery plan against future sales."
        ];

    }
    elseif ($percent < 70) {

        $risk_factors[] =
            "Debt recovery is currently below the preferred 70% level.";

        $recommendations[] = [
            "title" => "Monitor recovery closely",
            "text" =>
                "Track the grower's next sales against the outstanding balance and avoid allowing the unrecovered debt to continue increasing."
        ];

    }
    else {

        $positive_indicators[] =
            "Debt recovery is at " .
            round($percent) .
            "%, indicating a relatively strong recovery position.";

    }


    /*
    |--------------------------------------------------------------------------
    | SALES VOLUME
    |--------------------------------------------------------------------------
    */

    if (
        count($recent_sales) >= 2 &&
        count($previous_sales) >= 2
    ) {

        if ($volume_change <= -20) {

            $risk_factors[] =
                "Recent sold volume has declined by approximately " .
                round(abs($volume_change)) .
                "% compared with the previous sales period.";

            $recommendations[] = [
                "title" => "Investigate production decline",
                "text" =>
                    "Discuss recent production with the grower and determine whether the decline is linked to reduced output, delivery delays, crop conditions or other operational issues."
            ];

        }
        elseif ($volume_change >= 20) {

            $positive_indicators[] =
                "Recent sold volume has increased by approximately " .
                round($volume_change) .
                "%.";

            $recommendations[] = [
                "title" => "Maintain production momentum",
                "text" =>
                    "Maintain the current level of production support and monitor whether the positive volume trend continues."
            ];

        }

    }


    /*
    |--------------------------------------------------------------------------
    | REVENUE
    |--------------------------------------------------------------------------
    */

    if (
        count($recent_sales) >= 2 &&
        count($previous_sales) >= 2
    ) {

        if ($revenue_change <= -20) {

            $risk_factors[] =
                "Recent revenue has declined by approximately " .
                round(abs($revenue_change)) .
                "%.";

            $recommendations[] = [
                "title" => "Review revenue performance",
                "text" =>
                    "Compare recent grades, sold mass and prices to determine what is driving the reduction in revenue."
            ];

        }
        elseif ($revenue_change >= 20) {

            $positive_indicators[] =
                "Recent revenue has increased by approximately " .
                round($revenue_change) .
                "%.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | QUALITY
    |--------------------------------------------------------------------------
    */

    if ($quality_count > 0) {

        if ($quality_change <= -1) {

            $risk_factors[] =
                "Recorded average grade quality has declined from approximately " .
                number_format($previous_quality, 1) .
                " to " .
                number_format($recent_quality, 1) .
                ".";

            $recommendations[] = [
                "title" => "Address declining tobacco quality",
                "text" =>
                    "Review the recent grade distribution with the grower and identify where quality has fallen. Focus production support on harvesting, curing, grading and handling practices that can improve the next deliveries."
            ];

        }
        elseif ($average_grade_quality <= 2) {

            $risk_factors[] =
                "The grower's recorded average grade quality is relatively low at approximately " .
                number_format($average_grade_quality, 1) .
                " out of 5.";

            $recommendations[] = [
                "title" => "Improve grade quality",
                "text" =>
                    "Review the grower's recent grades and provide targeted guidance on production and post-harvest practices that can improve tobacco quality."
            ];

        }
        elseif ($quality_change >= 1) {

            $positive_indicators[] =
                "Recorded average grade quality has improved in the recent sales period.";

            $recommendations[] = [
                "title" => "Maintain quality improvements",
                "text" =>
                    "Encourage the grower to maintain the practices contributing to the improved grade performance."
            ];

        }

    }


    /*
    |--------------------------------------------------------------------------
    | REJECTION
    |--------------------------------------------------------------------------
    */

    if ($rejection_rate >= 20) {

        $risk_factors[] =
            "Rejected tobacco represents approximately " .
            round($rejection_rate, 1) .
            "% of delivered mass.";

        $recommendations[] = [
            "title" => "Urgently reduce rejection",
            "text" =>
                "Review the causes of rejected tobacco with the grower and prioritise corrective action before the next delivery."
        ];

    }
    elseif ($rejection_rate >= 10) {

        $risk_factors[] =
            "Rejected tobacco represents approximately " .
            round($rejection_rate, 1) .
            "% of delivered mass.";

        $recommendations[] = [
            "title" => "Reduce tobacco rejection",
            "text" =>
                "Investigate the main causes of rejection and work with the grower on improving preparation, handling and tobacco quality before the next sale."
        ];

    }
    elseif (
        $total_sales > 0 &&
        $rejection_rate < 5
    ) {

        $positive_indicators[] =
            "Tobacco rejection is currently below 5% of delivered mass.";

    }


    /*
    |--------------------------------------------------------------------------
    | SALE EFFICIENCY
    |--------------------------------------------------------------------------
    */

    if (
        $total_mass > 0 &&
        $sale_efficiency < 70
    ) {

        $risk_factors[] =
            "Less than 70% of delivered mass is currently reflected as sold mass.";

        $recommendations[] = [
            "title" => "Review unsold tobacco",
            "text" =>
                "Check whether the remaining delivered tobacco is awaiting sale, has been rejected or requires further processing."
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | STALE SALES
    |--------------------------------------------------------------------------
    */

    if (
        $days_since_sale !== null &&
        $days_since_sale >= 45
    ) {

        $risk_factors[] =
            "No sale has been recorded for approximately " .
            $days_since_sale .
            " days.";

        $recommendations[] = [
            "title" => "Follow up on current production",
            "text" =>
                "Contact the grower to confirm production and delivery status and determine whether there are delays or other issues affecting sales activity."
        ];

    }
    elseif (
        $days_since_sale !== null &&
        $days_since_sale >= 30
    ) {

        $risk_factors[] =
            "The grower has gone approximately " .
            $days_since_sale .
            " days since the last recorded sale.";

        $recommendations[] = [
            "title" => "Monitor delivery activity",
            "text" =>
                "Check the grower's current production and expected delivery schedule to ensure sales activity remains on track."
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | NO SALES
    |--------------------------------------------------------------------------
    */

    if ($total_sales === 0) {

        $risk_factors[] =
            "No sales have been recorded for this grower.";

        $recommendations[] = [
            "title" => "Confirm production status",
            "text" =>
                "Follow up with the grower to confirm whether production and deliveries are active and whether any issues are preventing sales from being recorded."
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | POSITIVE OVERALL CONDITION
    |--------------------------------------------------------------------------
    */

    if (
        empty($risk_factors) &&
        !empty($positive_indicators)
    ) {

        $positive_indicators[] =
            "No major negative indicators were identified from the available sales and recovery data.";

    }


    /*
    |--------------------------------------------------------------------------
    | DEFAULTS
    |--------------------------------------------------------------------------
    */

    if (empty($risk_factors)) {

        $risk_factors[] =
            "No major negative risk indicators were detected from the available data.";

    }


    if (empty($recommendations)) {

        $recommendations[] = [
            "title" => "Continue routine monitoring",
            "text" =>
                "Maintain normal monitoring of sales, tobacco quality and debt recovery and review this grower's position after the next recorded sale."
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | TREND LABELS
    |--------------------------------------------------------------------------
    */

    if ($volume_change <= -20) {

        $volume_label = "Declining";

        $volume_class = "status-negative";

    }
    elseif ($volume_change >= 20) {

        $volume_label = "Increasing";

        $volume_class = "status-positive";

    }
    else {

        $volume_label = "Stable";

        $volume_class = "status-warning";

    }


    if ($revenue_change <= -20) {

        $revenue_label = "Declining";

        $revenue_class = "status-negative";

    }
    elseif ($revenue_change >= 20) {

        $revenue_label = "Increasing";

        $revenue_class = "status-positive";

    }
    else {

        $revenue_label = "Stable";

        $revenue_class = "status-warning";

    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH DATA
    |--------------------------------------------------------------------------
    */

    $search_text =
        strtolower(
            $grower_no .
            " " .
            $grower_name
        );


    $row_count++;

?>


<tr
    class="risk-row"
    data-risk="<?php echo $risk; ?>"
    data-search="<?php echo htmlspecialchars($search_text); ?>"
>


<td>

    <strong>
        <?php
        echo htmlspecialchars(
            $grower_name
        );
        ?>
    </strong>

    <br>

    <small>
        <?php
        echo htmlspecialchars(
            $grower_no
        );
        ?>
    </small>

</td>


<td>

    <?php
    echo money($debt);
    ?>

</td>


<td>

    <?php
    echo money($paid);
    ?>

</td>


<td>

    <span
        class="risk-badge <?php echo $risk_class; ?>"
    >

        <?php
        echo $risk;
        ?>

    </span>

    <br>

    <small>

        <?php
        echo round($percent);
        ?>%
        recovered

    </small>

</td>


<td>

    <button
        type="button"
        class="details-btn"
        onclick="openGrowerDetails(
            '<?php echo htmlspecialchars(
                $grower_no,
                ENT_QUOTES
            ); ?>'
        )"
    >

        View Details

    </button>

</td>


</tr>


<!-- =========================================================
     HIDDEN DETAILS
     ========================================================= -->

<div
    class="grower-details-data"
    id="details-<?php echo htmlspecialchars($grower_no); ?>"
    style="display:none;"
>


<div class="detail-grid">


    <div class="detail-card">

        <div class="label">
            Total Sales
        </div>

        <div class="value">
            <?php echo $total_sales; ?>
        </div>

    </div>


    <div class="detail-card">

        <div class="label">
            Sold Mass
        </div>

        <div class="value">

            <?php
            echo number_format(
                $total_sold_mass,
                2
            );
            ?>

            kg

        </div>

    </div>


    <div class="detail-card">

        <div class="label">
            Total Revenue
        </div>

        <div class="value">

            <?php
            echo money(
                $total_revenue
            );
            ?>

        </div>

    </div>


    <div class="detail-card">

        <div class="label">
            Debt Recovered
        </div>

        <div class="value">

            <?php
            echo percentage(
                $percent
            );
            ?>

        </div>

    </div>


</div>


<!-- =========================================================
     OVERALL ASSESSMENT
     ========================================================= -->

<div class="assessment">

    <h3>
        🧭 Overall Assessment
    </h3>


    <p>

        <?php

        if ($risk === "HIGH") {

            echo
                "This grower currently requires closer attention. "
                .
                "The available sales and recovery data shows "
                .
                "multiple indicators that may affect financial "
                .
                "performance or future recovery.";

        }
        elseif ($risk === "MEDIUM") {

            echo
                "This grower is showing some indicators that "
                .
                "should be monitored. The situation does not "
                .
                "necessarily indicate immediate danger, but "
                .
                "targeted follow-up may help prevent the "
                .
                "position from worsening.";

        }
        else {

            echo
                "This grower is currently in a relatively stable "
                .
                "position based on the available sales and "
                .
                "recovery data. Continue routine monitoring "
                .
                "and maintain the practices contributing to "
                .
                "current performance.";

        }

        ?>

    </p>

</div>


<div class="analysis-grid">


<!-- =========================================================
     SALES PERFORMANCE
     ========================================================= -->

<div class="analysis-card">

    <h3>
        📊 Sales Performance
    </h3>


    <div class="analysis-item">

        <strong>
            Volume Trend:
        </strong>

        <span class="<?php echo $volume_class; ?>">

            <?php
            echo $volume_label;
            ?>

        </span>

        <?php if (
            count($recent_sales) >= 2 &&
            count($previous_sales) >= 2
        ): ?>

            <br>

            <small>

                Recent vs previous:
                <?php

                echo
                    ($volume_change >= 0 ? "+" : "") .
                    round($volume_change) .
                    "%";

                ?>

            </small>

        <?php endif; ?>

    </div>


    <div class="analysis-item">

        <strong>
            Revenue Trend:
        </strong>

        <span class="<?php echo $revenue_class; ?>">

            <?php
            echo $revenue_label;
            ?>

        </span>

        <?php if (
            count($recent_sales) >= 2 &&
            count($previous_sales) >= 2
        ): ?>

            <br>

            <small>

                Recent vs previous:
                <?php

                echo
                    ($revenue_change >= 0 ? "+" : "") .
                    round($revenue_change) .
                    "%";

                ?>

            </small>

        <?php endif; ?>

    </div>


    <div class="analysis-item">

        <strong>
            Last Sale:
        </strong>

        <?php

        echo $last_sale_date
            ? date(
                "d-M-Y",
                strtotime(
                    $last_sale_date
                )
            )
            : "No sales recorded";

        ?>

    </div>


    <?php if (
        $days_since_sale !== null
    ): ?>

        <div class="analysis-item">

            <strong>
                Days Since Last Sale:
            </strong>

            <?php
            echo $days_since_sale;
            ?>

            days

        </div>

    <?php endif; ?>


    <div class="analysis-item">

        <strong>
            Sold / Delivered:
        </strong>

        <?php
        echo number_format(
            $sale_efficiency,
            1
        );
        ?>%

    </div>

</div>


<!-- =========================================================
     QUALITY
     ========================================================= -->

<div class="analysis-card">

    <h3>
        🏷️ Quality & Production
    </h3>


    <div class="analysis-item">

        <strong>
            Average Grade Quality:
        </strong>

        <?php

        if ($quality_count > 0) {

            echo number_format(
                $average_grade_quality,
                1
            ) . " / 5";

        }
        else {

            echo "Not available";

        }

        ?>

    </div>


    <?php if (
        $quality_count > 0 &&
        $recent_quality_count > 0 &&
        $previous_quality_count > 0
    ): ?>

        <div class="analysis-item">

            <strong>
                Quality Trend:
            </strong>

            <?php

            if ($quality_change <= -1) {

                echo '<span class="status-negative">Declining</span>';

            }
            elseif ($quality_change >= 1) {

                echo '<span class="status-positive">Improving</span>';

            }
            else {

                echo '<span class="status-warning">Stable</span>';

            }

            ?>

        </div>

    <?php endif; ?>


    <div class="analysis-item">

        <strong>
            Delivered Mass:
        </strong>

        <?php
        echo number_format(
            $total_mass,
            2
        );
        ?>
        kg

    </div>


    <div class="analysis-item">

        <strong>
            Rejected Mass:
        </strong>

        <?php
        echo number_format(
            $total_rejected_mass,
            2
        );
        ?>
        kg

    </div>


    <div class="analysis-item">

        <strong>
            Rejection Rate:
        </strong>

        <span
            class="<?php

            echo
                $rejection_rate >= 10
                ? "status-negative"
                : "status-positive";

            ?>"
        >

            <?php
            echo number_format(
                $rejection_rate,
                1
            );
            ?>%

        </span>

    </div>

</div>


<!-- =========================================================
     RECOVERY
     ========================================================= -->

<div class="analysis-card">

    <h3>
        💳 Recovery Position
    </h3>


    <div class="analysis-item">

        <strong>
            Total Debt:
        </strong>

        <?php
        echo money($debt);
        ?>

    </div>


    <div class="analysis-item">

        <strong>
            Recovered:
        </strong>

        <?php
        echo money($paid);
        ?>

    </div>


    <div class="analysis-item">

        <strong>
            Outstanding:
        </strong>

        <?php

        echo money(
            max(
                0,
                $debt - $paid
            )
        );

        ?>

    </div>


    <div class="analysis-item">

        <strong>
            Recovery Rate:
        </strong>

        <?php
        echo round($percent);
        ?>%

    </div>

</div>


<!-- =========================================================
     RISK FACTORS
     ========================================================= -->

<div class="analysis-card">

    <h3>
        ⚠️ Risk Factors
    </h3>


    <?php foreach (
        $risk_factors as $factor
    ): ?>

        <div class="risk-factor">

            <?php
            echo htmlspecialchars(
                $factor
            );
            ?>

        </div>

    <?php endforeach; ?>


    <?php if (
        !empty($positive_indicators)
    ): ?>

        <h3 style="margin-top:22px;">

            ✅ Positive Indicators

        </h3>


        <?php foreach (
            $positive_indicators as $positive
        ): ?>

            <div class="positive-factor">

                <?php
                echo htmlspecialchars(
                    $positive
                );
                ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>


</div>


</div>


<!-- =========================================================
     RECOMMENDATIONS
     ========================================================= -->

<div
    class="analysis-card"
    style="margin-top:20px;"
>

    <h3>
        💡 Recommended Actions
    </h3>


    <?php foreach (
        $recommendations as $recommendation
    ): ?>

        <div class="recommendation">

            <strong>

                <?php
                echo htmlspecialchars(
                    $recommendation['title']
                );
                ?>

            </strong>


            <?php

            echo htmlspecialchars(
                $recommendation['text']
            );

            ?>

        </div>

    <?php endforeach; ?>


</div>


</div>


<?php

}

?>


<tr id="noResults">

    <td colspan="5">

        No growers match your search
        or risk filter.

    </td>

</tr>


</tbody>

</table>


<div class="result-count">

    Showing

    <strong id="visibleCount">
        <?php echo $row_count; ?>
    </strong>

    of

    <strong>
        <?php echo $row_count; ?>
    </strong>

    growers

</div>


</div>


</div>


<!-- =========================================================
     MODAL
     ========================================================= -->

<div
    id="riskModal"
    class="risk-modal"
    onclick="closeModalOutside(event)"
>


<div class="risk-modal-content">


<div class="risk-modal-header">


<div>

    <h2 id="modalGrowerName">
        Grower Risk Details
    </h2>

    <small id="modalGrowerNumber"></small>

</div>


<button
    type="button"
    class="close-modal"
    onclick="closeGrowerDetails()"
>

    &times;

</button>


</div>


<div
    class="risk-modal-body"
    id="modalBody"
>

</div>


</div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| SEARCH / FILTER
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById(
        "growerSearch"
    );


const riskFilter =
    document.getElementById(
        "riskFilter"
    );


const rows =
    document.querySelectorAll(
        ".risk-row"
    );


const visibleCount =
    document.getElementById(
        "visibleCount"
    );


const noResults =
    document.getElementById(
        "noResults"
    );


function filterGrowers()
{

    const searchTerm =
        searchInput.value
        .toLowerCase()
        .trim();


    const selectedRisk =
        riskFilter.value;


    let visible = 0;


    rows.forEach(function(row)
    {

        const searchData =
            row.dataset.search;


        const rowRisk =
            row.dataset.risk;


        const searchMatch =
            searchData.includes(
                searchTerm
            );


        const riskMatch =
            selectedRisk === "ALL" ||
            rowRisk === selectedRisk;


        if (
            searchMatch &&
            riskMatch
        ){

            row.style.display = "";

            visible++;

        }
        else{

            row.style.display = "none";

        }

    });


    visibleCount.textContent =
        visible;


    if (visible === 0){

        noResults.style.display =
            "table-row";

    }
    else{

        noResults.style.display =
            "none";

    }

}


searchInput.addEventListener(
    "input",
    filterGrowers
);


riskFilter.addEventListener(
    "change",
    filterGrowers
);


/*
|--------------------------------------------------------------------------
| OPEN DETAILS
|--------------------------------------------------------------------------
*/

function openGrowerDetails(
    growerNo
){

    const source =
        document.getElementById(
            "details-" + growerNo
        );


    if (!source){

        return;

    }


    let growerName = "";

    let risk = "";


    rows.forEach(function(row)
    {

        const searchData =
            row.dataset.search;


        if (
            searchData.includes(
                growerNo.toLowerCase()
            )
        ){

            const nameElement =
                row.querySelector(
                    "td strong"
                );


            const riskElement =
                row.querySelector(
                    ".risk-badge"
                );


            if (nameElement){

                growerName =
                    nameElement.textContent.trim();

            }


            if (riskElement){

                risk =
                    riskElement.textContent.trim();

            }

        }

    });


    document.getElementById(
        "modalGrowerName"
    ).textContent =
        growerName;


    document.getElementById(
        "modalGrowerNumber"
    ).textContent =
        growerNo +
        " • " +
        risk +
        " RISK";


    document.getElementById(
        "modalBody"
    ).innerHTML =
        source.innerHTML;


    document.getElementById(
        "riskModal"
    ).style.display =
        "block";


    document.body.style.overflow =
        "hidden";

}


/*
|--------------------------------------------------------------------------
| CLOSE
|--------------------------------------------------------------------------
*/

function closeGrowerDetails()
{

    document.getElementById(
        "riskModal"
    ).style.display =
        "none";


    document.body.style.overflow =
        "";

}


function closeModalOutside(event)
{

    if (
        event.target.id ===
        "riskModal"
    ){

        closeGrowerDetails();

    }

}


document.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key === "Escape"
        ){

            closeGrowerDetails();

        }

    }
);

</script>


</body>

</html>