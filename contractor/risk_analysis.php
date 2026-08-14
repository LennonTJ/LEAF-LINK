<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$contractor_id = $_SESSION['contractor_id'] ?? null;

if(!$contractor_id){

    $uid = $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT contractor_id, username
         FROM users
         WHERE user_id=?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt,"i",$uid);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if($user){

        $contractor_id = $user['contractor_id'];

        // Fallback using contractor_code
        if(!$contractor_id && !empty($user['username'])){

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

            if($crow = mysqli_fetch_assoc($cres)){
                $contractor_id = $crow['contractor_id'];
            }
        }
    }
}

if(!$contractor_id){
    die("Contractor not linked.");
}


/*
|--------------------------------------------------------------------------
| GET GROWER RECOVERY DATA
|--------------------------------------------------------------------------
*/

$sql="
SELECT

g.grower_no,
g.first_name,
g.last_name,

g.total_debt,

COALESCE(SUM(s.net_payment),0) recovered

FROM growers g

JOIN contracts c
ON g.grower_id=c.grower_id

LEFT JOIN sales s
ON g.grower_no=s.grower_no

WHERE c.contractor_id=?

GROUP BY g.grower_id
";

$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $contractor_id
);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html>

<head>

<title>Risk Analysis | LeafLink</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>

/* =========================================================
   RISK ANALYSIS FILTERS
   ========================================================= */

.risk-toolbar{
    display:flex;
    gap:15px;
    align-items:center;
    justify-content:space-between;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.search-box{
    flex:1;
    min-width:250px;
}

.search-box input{
    width:100%;
    padding:12px 15px;
    border:1px solid #ddd;
    border-radius:8px;
    font-size:14px;
    box-sizing:border-box;
}

.risk-filter{
    min-width:180px;
}

.risk-filter select{
    width:100%;
    padding:12px 15px;
    border:1px solid #ddd;
    border-radius:8px;
    font-size:14px;
    background:white;
    cursor:pointer;
}

.result-count{
    margin-top:12px;
    font-size:13px;
    color:#777;
}


/* =========================================================
   RISK BADGES
   ========================================================= */

.risk-badge{
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.risk-low{
    background:#e8f7ee;
    color:#198754;
}

.risk-medium{
    background:#fff4d6;
    color:#b77900;
}

.risk-high{
    background:#fde8e8;
    color:#c62828;
}


/* =========================================================
   EMPTY SEARCH RESULT
   ========================================================= */

#noResults{
    display:none;
    text-align:center;
    padding:30px;
    color:#777;
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


<!-- =====================================================
     SEARCH & FILTER CONTROLS
     ===================================================== -->

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

            <option value="ALL">All Risks</option>

            <option value="HIGH">High Risk</option>

            <option value="MEDIUM">Medium Risk</option>

            <option value="LOW">Low Risk</option>

        </select>

    </div>

</div>


<table id="riskTable">

<tr>

<th>Grower</th>
<th>Debt</th>
<th>Recovered</th>
<th>Risk</th>

</tr>


<?php

$row_count = 0;

while($r=mysqli_fetch_assoc($result)){

    $debt=$r['total_debt'];

    $paid=$r['recovered'];

    $percent=0;

    if($debt>0){
        $percent=($paid/$debt)*100;
    }


    /*
    |--------------------------------------------------------------------------
    | RISK CALCULATION
    |--------------------------------------------------------------------------
    */

    if($percent>=70){

        $risk="LOW";
        $risk_class="risk-low";

    }
    elseif($percent>=30){

        $risk="MEDIUM";
        $risk_class="risk-medium";

    }
    else{

        $risk="HIGH";
        $risk_class="risk-high";

    }


    $grower_name = trim(
        $r['first_name']." ".$r['last_name']
    );

    $search_text = strtolower(
        $r['grower_no']." ".$grower_name
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
        <?php echo htmlspecialchars($grower_name); ?>
    </strong>

    <br>

    <small>
        <?php echo htmlspecialchars($r['grower_no']); ?>
    </small>

</td>


<td>

    $<?php echo number_format($debt,2); ?>

</td>


<td>

    $<?php echo number_format($paid,2); ?>

</td>


<td>

    <span class="risk-badge <?php echo $risk_class; ?>">

        <?php echo $risk; ?>

    </span>

    <br>

    <small>
        <?php echo round($percent); ?>% recovered
    </small>

</td>


</tr>


<?php } ?>


<tr id="noResults">

<td colspan="4">

    No growers match your search or risk filter.

</td>

</tr>


</table>


<div class="result-count">

    Showing <strong id="visibleCount"><?php echo $row_count; ?></strong>
    of <strong><?php echo $row_count; ?></strong> growers

</div>


</div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| RISK ANALYSIS SEARCH & FILTER
|--------------------------------------------------------------------------
*/

const searchInput = document.getElementById("growerSearch");
const riskFilter = document.getElementById("riskFilter");

const rows = document.querySelectorAll(".risk-row");

const visibleCount = document.getElementById("visibleCount");
const noResults = document.getElementById("noResults");


function filterGrowers(){

    const searchTerm = searchInput.value
        .toLowerCase()
        .trim();

    const selectedRisk = riskFilter.value;

    let visible = 0;


    rows.forEach(function(row){

        const searchData = row.dataset.search;
        const rowRisk = row.dataset.risk;


        /*
        |--------------------------------------------------------------------------
        | SEARCH MATCH
        |--------------------------------------------------------------------------
        */

        const searchMatch =
            searchData.includes(searchTerm);


        /*
        |--------------------------------------------------------------------------
        | RISK MATCH
        |--------------------------------------------------------------------------
        */

        const riskMatch =
            selectedRisk === "ALL" ||
            rowRisk === selectedRisk;


        /*
        |--------------------------------------------------------------------------
        | SHOW / HIDE
        |--------------------------------------------------------------------------
        */

        if(searchMatch && riskMatch){

            row.style.display = "";
            visible++;

        }
        else{

            row.style.display = "none";

        }

    });


    /*
    |--------------------------------------------------------------------------
    | UPDATE RESULT COUNT
    |--------------------------------------------------------------------------
    */

    visibleCount.textContent = visible;


    /*
    |--------------------------------------------------------------------------
    | SHOW EMPTY MESSAGE
    |--------------------------------------------------------------------------
    */

    if(visible === 0){

        noResults.style.display = "table-row";

    }
    else{

        noResults.style.display = "none";

    }

}


/*
|--------------------------------------------------------------------------
| LISTEN FOR SEARCH
|--------------------------------------------------------------------------
*/

searchInput.addEventListener(
    "input",
    filterGrowers
);


/*
|--------------------------------------------------------------------------
| LISTEN FOR RISK FILTER
|--------------------------------------------------------------------------
*/

riskFilter.addEventListener(
    "change",
    filterGrowers
);

</script>


</body>

</html>