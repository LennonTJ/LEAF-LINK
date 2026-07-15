<?php

session_start();

include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (($_SESSION['role'] ?? '') != "grower") {
    header("Location: ../auth/login.php");
    exit();
}

$grower_id = $_SESSION['grower_id'];

$sql = "SELECT *
        FROM growers
        WHERE grower_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $grower_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$grower = mysqli_fetch_assoc($result);

$result_ready = false;

if(isset($_POST['project']))
{
    $plant_position = $_POST['plant_position'];
    $quality = $_POST['quality'];
    $kg = floatval($_POST['estimated_kg']);

    // Get today's estimated price
    $sql = "SELECT estimated_price
            FROM projection_prices
            WHERE plant_position = ?
            AND quality = ?
            ORDER BY price_date DESC
            LIMIT 1";

    $stmt = mysqli_prepare($conn,$sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $plant_position,
        $quality
    );

    mysqli_stmt_execute($stmt);

    $price_result = mysqli_stmt_get_result($stmt);

    $price = mysqli_fetch_assoc($price_result);

    if($price)
    {
        $price_per_kg = $price['estimated_price'];

        $revenue = $price_per_kg * $kg;

        $debt = $grower['total_debt'];

        $payout = $revenue - $debt;

        if($payout < 0)
        {
            $payout = 0;
        }

        // Recovery 
        if($debt > 0)
        {
            $recovery = ($revenue / $debt) * 100;
        }
        else
        {
            $recovery = 100;
        }

        // Risk
        if($recovery >= 100)
        {
            $risk = "Recovery Completed";
        }
        elseif($recovery >= 85)
        {
            $risk = "MEDIUM";
        }
        else
        {
            $risk = "HIGH";
        }

        // Zero Pay
        if($revenue <= $debt)
        {
            $zero_pay = "YES";
        }
        else
        {
            $zero_pay = "NO";
        }

        $result_ready = true;
    }

}
// Save projection

$save_sql = "
INSERT INTO sale_projections
(
grower_id,
plant_position,
quality,
estimated_kg,
estimated_price,
projected_revenue,
projected_payout,
recovery_risk,
zero_pay_status
)

VALUES
(
?,
?,
?,
?,
?,
?,
?,
?,
?
)
";


$save_stmt = mysqli_prepare($conn,$save_sql);


mysqli_stmt_bind_param(
    $save_stmt,
    "issddddss",
    $grower_id,
    $plant_position,
    $quality,
    $kg,
    $price_per_kg,
    $revenue,
    $payout,
    $risk,
    $zero_pay
);


mysqli_stmt_execute($save_stmt);
?>

<!DOCTYPE html>

<html>

<head>

<title>LeafLink - Sale Projection Engine</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="header">

    <h1>LeafLink</h1>

    <p>Sale Projection Engine</p>

</div>

<div class="layout">

<div class="sidebar">

<h3>Grower Portal</h3>

<hr>

<a href="dashboard.php">Dashboard</a>

<a href="projection.php">Sale Projection</a>

<a href="#">Projection History</a>

<hr>

<a href="../auth/logout.php">Logout</a>

</div>

<div class="content">

<div class="card">

<h2>

Welcome <?php echo $grower['first_name']; ?>

</h2>

<p>

<strong>Grower Number:</strong>

<?php echo $grower['grower_no']; ?>

</p>

<p>

<strong>Outstanding Debt:</strong>

$<?php echo number_format($grower['total_debt'],2); ?>

</p>

</div>

<div class="card">

<h2>Analyse Sale</h2>

<form method="POST">

<label>Plant Position</label>

<br><br>

<select name="plant_position" required>

<option value="">Select Position</option>

<option value="P">Primings</option>

<option value="X">Lugs</option>

<option value="C">Cutters</option>

<option value="L">Leaf</option>

<option value="T">Tips</option>

</select>

<br><br>

<label>Quality</label>

<br><br>

<select name="quality" required>

<option value="">Select Quality</option>

<option value="Very Poor">Very Poor</option>

<option value="Poor">Poor</option>

<option value="Fair">Fair</option>

<option value="Good">Good</option>

<option value="Very Good">Very Good</option>

</select>

<br><br>

<label>Estimated Kilograms</label>

<br><br>

<input
type="number"
step="0.01"
name="estimated_kg"
placeholder="e.g. 150"
required>

<br><br>

<button
type="submit"
name="project">

Analyse Sale

</button>

</form>
<?php if($result_ready){ ?>

<div class="card">

<h2>Projection Results</h2>

<p>

Estimated Price/kg

<strong>

$<?php echo number_format($price_per_kg,2); ?>

</strong>

</p>

<p>

Projected Revenue

<strong>

$<?php echo number_format($revenue,2); ?>

</strong>

</p>

<p>

Outstanding Debt

<strong>

$<?php echo number_format($debt,2); ?>

</strong>

</p>

<p>

Expected Payout

<strong>

$<?php echo number_format($payout,2); ?>

</strong>

</p>

<p>

Recovery

<strong>

<?php echo number_format($recovery,1); ?>%

</strong>

</p>

<p>

Recovery Risk

<strong>

<?php echo $risk; ?>

</strong>

</p>

<p>

Zero Pay Status

<strong>

<?php echo $zero_pay; ?>

</strong>

</p>

</div>

<?php } ?>

</div>

</div>

</div>

</body>

</html>