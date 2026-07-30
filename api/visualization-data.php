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

COALESCE(SUM(sold_mass),0) total_mass,

COALESCE(SUM(sold_bales),0) total_bales,

COALESCE(SUM(rejected_bales),0) rejected_bales,

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

$total_mass=(float)$s['total_mass'];

$total_bales=(int)$s['total_bales'];

$rejected_bales=(int)$s['rejected_bales'];


// Average price per kg

$average_price = 0;

if($total_mass > 0){

    $average_price = round(
        $gross / $total_mass,
        2
    );

}



$recovered=$gross-$payout;



$percent=0;


if($total_debt>0){

$percent=round(
($recovered/$total_debt)*100,
2
);

}




return [

"total_production"=>$total_mass,

"total_bales"=>$total_bales,

"rejected_bales"=>$rejected_bales,

"average_price"=>$average_price,

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
| CONTRACTOR METRICS
|--------------------------------------------------------------------------
*/

function getContractorMetrics($contractor_id){

global $conn;


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$sql = "

SELECT

COUNT(DISTINCT c.grower_id) AS growers,

COALESCE(SUM(s.sold_mass),0) AS total_mass,

COALESCE(SUM(s.gross_value),0) AS total_sales,

COALESCE(SUM(s.net_payment),0) AS total_payout,

COALESCE(SUM(s.sold_bales),0) AS sold_bales,

COALESCE(SUM(s.rejected_bales),0) AS rejected_bales

FROM contracts c

LEFT JOIN growers g
ON c.grower_id=g.grower_id

LEFT JOIN sales s
ON s.grower_no=g.grower_no

WHERE c.contractor_id=?
AND c.status='active'

";

$stmt=mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($stmt,"i",$contractor_id);
mysqli_stmt_execute($stmt);

$r=mysqli_stmt_get_result($stmt);
$summary=mysqli_fetch_assoc($r);



/*
|--------------------------------------------------------------------------
| RECOVERY RISK
|--------------------------------------------------------------------------
*/

$riskSQL="

SELECT

COUNT(*) AS risk_count

FROM growers g

JOIN contracts c
ON g.grower_id=c.grower_id

WHERE c.contractor_id=?
AND c.status='active'
AND g.total_debt >
(
SELECT IFNULL(SUM(net_payment),0)

FROM sales

WHERE grower_no=g.grower_no
)

";

$stmt=mysqli_prepare($conn,$riskSQL);
mysqli_stmt_bind_param($stmt,"i",$contractor_id);
mysqli_stmt_execute($stmt);

$r=mysqli_stmt_get_result($stmt);
$risk=mysqli_fetch_assoc($r);



/*
|--------------------------------------------------------------------------
| TOP QUALITY GROWERS
|--------------------------------------------------------------------------
*/

$topSQL="

SELECT

CONCAT(g.first_name,' ',g.last_name) AS grower,

SUM(sg.mass) AS premium_kg

FROM contracts c

JOIN growers g
ON c.grower_id=g.grower_id

JOIN sales s
ON s.grower_no=g.grower_no

JOIN sale_grades sg
ON sg.sale_id=s.sale_id

WHERE c.contractor_id=?

AND c.status='active'

AND (

sg.grade LIKE 'L1%'

OR sg.grade LIKE 'L2%'

OR sg.grade LIKE 'X1%'

OR sg.grade LIKE 'X2%'

)

GROUP BY g.grower_id

ORDER BY premium_kg DESC

LIMIT 5

";

$stmt=mysqli_prepare($conn,$topSQL);
mysqli_stmt_bind_param($stmt,"i",$contractor_id);
mysqli_stmt_execute($stmt);

$res=mysqli_stmt_get_result($stmt);

$top=[];

while($row=mysqli_fetch_assoc($res)){

$top[]=$row;

}



return[

"growers"=>(int)$summary['growers'],

"mass"=>(float)$summary['total_mass'],

"sales"=>(float)$summary['total_sales'],

"payout"=>(float)$summary['total_payout'],

"sold_bales"=>(int)$summary['sold_bales'],

"rejected_bales"=>(int)$summary['rejected_bales'],

"risk_count"=>(int)$risk['risk_count'],

"top_growers"=>$top

];

}

/*
|--------------------------------------------------------------------------
| CONTRACTOR SALES TREND
|--------------------------------------------------------------------------
*/

function getContractorTrend($contractor_id){

global $conn;

$sql="

SELECT

DATE(s.sale_date) sale_date,

SUM(s.sold_mass) kg

FROM sales s

JOIN contracts c
ON s.grower_no=(
SELECT grower_no
FROM growers
WHERE grower_id=c.grower_id
)

WHERE c.contractor_id=?

GROUP BY DATE(s.sale_date)

ORDER BY sale_date

";

$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
$stmt,
"i",
$contractor_id
);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

$dates=[];

$kgs=[];

while($r=mysqli_fetch_assoc($result)){

$dates[]=$r['sale_date'];

$kgs[]=(float)$r['kg'];

}

return [

"dates"=>$dates,

"kgs"=>$kgs

];

}

/*
|--------------------------------------------------------------------------
| TOP GROWERS
|--------------------------------------------------------------------------
*/

function getTopGrowers($contractor_id){

global $conn;

$sql="

SELECT

g.first_name,

g.last_name,

SUM(s.sold_mass) kg

FROM sales s

JOIN growers g
ON s.grower_no=g.grower_no

JOIN contracts c
ON g.grower_id=c.grower_id

WHERE c.contractor_id=?

GROUP BY g.grower_id

ORDER BY kg DESC

LIMIT 10

";

$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
$stmt,
"i",
$contractor_id
);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

$names=[];

$kgs=[];

while($r=mysqli_fetch_assoc($result)){

$names[]=
$r['first_name']." ".$r['last_name'];

$kgs[]=(float)$r['kg'];

}

return [

"names"=>$names,

"kgs"=>$kgs

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

case "contractor_metrics":

echo json_encode(
getContractorMetrics($_SESSION['contractor_id'])
);

break;



case "contractor_sales_trend":

echo json_encode(
getContractorTrend($_SESSION['contractor_id'])
);

break;



case "top_growers":

echo json_encode(
getTopGrowers($_SESSION['contractor_id'])
);

break;


default:


echo json_encode([
"error"=>"Invalid action"
]);


break;



}



?>