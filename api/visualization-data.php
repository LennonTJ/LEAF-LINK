<?php
/**
 * LeafLink Real Sales Visualization API
 * Powered by:
 * sales
 * sale_grades
 */

include("../config/database.php");

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error'=>'Unauthorized']);
    exit();
}


/*
|--------------------------------------------------------------------------
| GET LOGGED IN GROWER
|--------------------------------------------------------------------------
*/

$grower_id = $_SESSION['grower_id'] ?? null;

if (!$grower_id) {
    echo json_encode(['error'=>'No grower linked']);
    exit();
}


/*
|--------------------------------------------------------------------------
| Convert grower_id -> grower_no
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT grower_no 
     FROM growers 
     WHERE grower_id=? 
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $grower_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$grower = mysqli_fetch_assoc($result);


if(!$grower){

    echo json_encode(['error'=>'Grower not found']);
    exit();

}


$grower_no = $grower['grower_no'];



$action = $_GET['action'] ?? '';



/*
|--------------------------------------------------------------------------
| SALES TREND
|--------------------------------------------------------------------------
*/

function getSalesTrend($grower_no){

global $conn;


$sql="
SELECT 
DATE(sale_date) AS sale_date,
SUM(delivered_mass) AS kg,
SUM(gross_value) AS revenue

FROM sales

WHERE grower_no=?

GROUP BY DATE(sale_date)

ORDER BY sale_date ASC
";


$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
$stmt,
"s",
$grower_no
);

mysqli_stmt_execute($stmt);


$result=mysqli_stmt_get_result($stmt);



$dates=[];
$kgs=[];
$revenues=[];


while($row=mysqli_fetch_assoc($result)){


$dates[]=$row['sale_date'];

$kgs[]=(float)$row['kg'];

$revenues[]=(float)$row['revenue'];


}


return [

"dates"=>$dates,
"kgs"=>$kgs,
"revenues"=>$revenues

];


}





/*
|--------------------------------------------------------------------------
| QUALITY DISTRIBUTION
|--------------------------------------------------------------------------
*/

function getQualityDistribution($grower_no){

global $conn;


$sql="
SELECT 
sg.grade,
SUM(sg.mass) AS kg

FROM sale_grades sg

JOIN sales s

ON sg.sale_id=s.sale_id


WHERE s.grower_no=?


GROUP BY sg.grade

ORDER BY kg DESC
";


$stmt=mysqli_prepare($conn,$sql);


mysqli_stmt_bind_param(
$stmt,
"s",
$grower_no
);


mysqli_stmt_execute($stmt);


$result=mysqli_stmt_get_result($stmt);



$grades=[];
$quantities=[];


while($row=mysqli_fetch_assoc($result)){


$grades[]=$row['grade'];

$quantities[]=(float)$row['kg'];


}


return [

"grades"=>$grades,

"quantities"=>$quantities

];


}





/*
|--------------------------------------------------------------------------
| PERFORMANCE METRICS
|--------------------------------------------------------------------------
*/

function getMetrics($grower_no){

global $conn;


/*
TOTAL SALES
*/

$sql="
SELECT

COALESCE(SUM(delivered_mass),0) total_mass,

COALESCE(SUM(gross_value),0) gross,

COALESCE(SUM(net_payment),0) payout,

COUNT(*) sales_count


FROM sales

WHERE grower_no=?
";


$stmt=mysqli_prepare($conn,$sql);


mysqli_stmt_bind_param(
$stmt,
"s",
$grower_no
);


mysqli_stmt_execute($stmt);


$result=mysqli_stmt_get_result($stmt);


$s=mysqli_fetch_assoc($result);



$total_debt=0;



/*
GET CURRENT DEBT
*/

$dstmt=mysqli_prepare(
$conn,
"SELECT total_debt 
 FROM growers 
 WHERE grower_no=? 
 LIMIT 1"
);


mysqli_stmt_bind_param(
$dstmt,
"s",
$grower_no
);


mysqli_stmt_execute($dstmt);


$dres=mysqli_stmt_get_result($dstmt);


if($d=mysqli_fetch_assoc($dres)){

$total_debt=(float)$d['total_debt'];

}




$gross=(float)$s['gross'];

$payout=(float)$s['payout'];



$recovered=$gross-$payout;



$percent=0;


if($total_debt>0){

$percent=round(
($recovered/$total_debt)*100,
2
);

}




return [

"total_production"=>(float)$s['total_mass'],

"total_revenue"=>$gross,

"number_of_sales"=>(int)$s['sales_count'],

"avg_production"=>

$s['sales_count']>0 ?

round(
$s['total_mass']/$s['sales_count'],
2
)

:0,


"total_debt"=>$total_debt,


"debt_recovered_percent"=>$percent,


"gross_revenue"=>$gross,


"debt_deduction"=>$recovered,


"final_expected_payout"=>$payout,


"expected_payout"=>$payout,


"insights"=>[

"Your dashboard is based on verified TIMB sale records.",

"Total deductions recovered: $".number_format($recovered,2)

]

];


}




/*
|--------------------------------------------------------------------------
| ROUTES
|--------------------------------------------------------------------------
*/

switch($action){



case "sales_trend":

echo json_encode(
getSalesTrend($grower_no)
);

break;



case "quality_distribution":

echo json_encode(
getQualityDistribution($grower_no)
);

break;



case "metrics":

echo json_encode(
getMetrics($grower_no)
);

break;



default:


echo json_encode([
"error"=>"Invalid action"
]);


break;



}



?>