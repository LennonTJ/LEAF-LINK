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

// Read saved message / last projection from session (PRG support)
$saved_message = '';
if (!empty($_SESSION['saved_message'])) {
    $saved_message = $_SESSION['saved_message'];
    unset($_SESSION['saved_message']);
}
// Restore last projection to display results after redirect
if (!empty($_SESSION['last_projection']) && is_array($_SESSION['last_projection'])) {
    $lp = $_SESSION['last_projection'];
    $generated_grade = $lp['generated_grade'] ?? '';
    $price_per_kg = $lp['price_per_kg'] ?? 0;
    $revenue = $lp['revenue'] ?? 0;
    $debt = $lp['debt'] ?? floatval($grower['total_debt'] ?? 0);
    $payout = $lp['payout'] ?? 0;
    $recovery = $lp['recovery'] ?? 0;
    $risk = $lp['risk'] ?? '';
    $zero_pay = $lp['zero_pay'] ?? '';
    $recommendation = $lp['recommendation'] ?? '';
    $plant_position = $lp['plant_position'] ?? '';
    $quality = $lp['quality'] ?? '';
    $colour = $lp['colour'] ?? '';
    $style = $lp['style'] ?? '';
    $extra = $lp['extra'] ?? '';
    $result_ready = true;
    unset($_SESSION['last_projection']);
}

// Simulation Summary Data

$summary_count = 0;
$summary_mass = 0.00;
$summary_sales = 0.00;


if ($grower_id) {

    $ps = mysqli_prepare(
        $conn,
        "SELECT 
            COUNT(*) AS cnt,
            COALESCE(SUM(estimated_kg),0) AS mass,
            COALESCE(SUM(projected_revenue),0) AS sales
        FROM sale_projections
        WHERE grower_id = ?"
    );


    if ($ps) {

        mysqli_stmt_bind_param($ps, "i", $grower_id);

        mysqli_stmt_execute($ps);

        $prs = mysqli_stmt_get_result($ps);


        if ($prow = mysqli_fetch_assoc($prs)) {

            $summary_count = $prow['cnt'];

            $summary_mass = $prow['mass'];

            $summary_sales = $prow['sales'];

        }

    }

}

// lookup tables (used for explanations)
$quality_lookup = [
    "1" => "Very Good",
    "2" => "Good",
    "3" => "Fair",
    "4" => "Poor",
    "5" => "Very Poor",
];

$colour_lookup = [
    "E" => "Pale Lemon",
    "L" => "Lemon",
    "O" => "Orange",
    "R" => "Light Mahogany",
    "S" => "Dark Mahogany",
];

$style_lookup = [
    ""  => "None",
    "F" => "Ripe / Soft",
    "K" => "Close Grained",
    "U" => "Slatey",
];

$extra_lookup = [
    ""  => "None",
    "A" => "Spotted",
    "D" => "Harsh",
    "Q" => "Scorched",
    "V" => "Greenish",
    "G" => "Green",
];

// Plant positions used by TIMB grading
$plant_positions = ['P', 'X', 'C', 'L', 'T'];

$qualities = [];
$q_res = mysqli_query($conn, "SELECT quality_code, description FROM tobacco_quality ORDER BY quality_code ASC");
if ($q_res) {
    while ($r = mysqli_fetch_assoc($q_res)) {
        $qualities[] = ['code' => $r['quality_code'], 'description' => $r['description']];
    }
} else {
    $qualities = [ ['code'=>1,'description'=>'Very Good'], ['code'=>2,'description'=>'Good'], ['code'=>3,'description'=>'Fair'], ['code'=>4,'description'=>'Poor'], ['code'=>5,'description'=>'Very Poor'] ];
}

$colours = [];
$c_res = mysqli_query($conn, "SELECT code, name FROM tobacco_colours ORDER BY id ASC");
if ($c_res) {
    while ($r = mysqli_fetch_assoc($c_res)) {
        $colours[] = ['code' => $r['code'], 'name' => $r['name']];
    }
} else {
    $colours = [['code'=>'E','name'=>'Pale Lemon'],['code'=>'L','name'=>'Lemon'],['code'=>'O','name'=>'Orange'],['code'=>'R','name'=>'Light Mahogany'],['code'=>'S','name'=>'Dark Mahogany']];
}

$styles = [];
$s_res = mysqli_query($conn, "SELECT code, name FROM tobacco_styles ORDER BY id ASC");
if ($s_res) {
    while ($r = mysqli_fetch_assoc($s_res)) {
        $styles[] = ['code' => $r['code'], 'name' => $r['name']];
    }
} else {
    $styles = [['code'=>'F','name'=>'Ripe / Soft'],['code'=>'K','name'=>'Close Grained'],['code'=>'U','name'=>'Slatey']];
}

// Extras: try a table, otherwise fallback to $extra_lookup
$extras = [];
$e_check = mysqli_query($conn, "SHOW TABLES LIKE 'tobacco_extras'");
if ($e_check && mysqli_num_rows($e_check) > 0) {
    $e_res = mysqli_query($conn, "SELECT code, name FROM tobacco_extras ORDER BY id ASC");
    if ($e_res) {
        while ($r = mysqli_fetch_assoc($e_res)) {
            $extras[] = ['code'=>$r['code'],'name'=>$r['name']];
        }
    }
}
if (empty($extras)) {
    foreach ($extra_lookup as $k => $v) {
        $extras[] = ['code'=>$k,'name'=>$v];
    }
}

if(isset($_POST['project']))
{
    // Read inputs
    $plant_position = trim($_POST['plant_position'] ?? '');
    $quality = trim($_POST['quality'] ?? '');
    $colour = trim($_POST['colour'] ?? '');
    $style = trim($_POST['style'] ?? '');
    $extra = trim($_POST['extra'] ?? '');
    $kg = floatval($_POST['estimated_kg'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | BUILD GENERATED TIMB GRADE
    |--------------------------------------------------------------------------
    */

    $generated_grade =
        strtoupper(
            $plant_position .
            $quality .
            $colour .
            $style .
            $extra
        );


    /*
    |--------------------------------------------------------------------------
    | GET LATEST TIMB PRICE FOR GENERATED GRADE
    |--------------------------------------------------------------------------
    |
    | The old system used projection_prices.
    | We now use the uploaded TIMB Daily Average Price Matrix.
    |
    */

    $sql = "
        SELECT average_price
        FROM price_matrix
        WHERE grade = ?
        ORDER BY price_date DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Failed to prepare price lookup: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $generated_grade
    );

    mysqli_stmt_execute($stmt);

    $price_result = mysqli_stmt_get_result($stmt);

    $price = mysqli_fetch_assoc($price_result);


    /*
    |--------------------------------------------------------------------------
    | NO PRICE FOUND
    |--------------------------------------------------------------------------
    */

    if (!$price)
    {
        $_SESSION['saved_message'] =
            "No TIMB price was found for grade " .
            $generated_grade .
            ". Please upload the latest TIMB Daily Average Price Matrix.";

        header('Location: projection.php');
        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | PRICE FOUND
    |--------------------------------------------------------------------------
    */

    $price_per_kg = floatval($price['average_price']);

    $revenue = $price_per_kg * $kg;


    /*
    |--------------------------------------------------------------------------
    | DEBT / PAYOUT
    |--------------------------------------------------------------------------
    |
    | Leaving your existing prototype logic unchanged for now.
    */

    $debt = floatval($grower['total_debt'] ?? 0);

    $payout = $revenue - $debt;

    if($payout < 0)
    {
        $payout = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | RECOVERY PERCENTAGE
    |--------------------------------------------------------------------------
    */

    if($debt > 0)
    {
        $recovery = ($revenue / $debt) * 100;
    }
    else
    {
        $recovery = 100;
    }


    //RECOVERY RISK

    if($recovery >= 120)
    {
        $risk = "LOW";
    }
    elseif($recovery >= 100)
    {
        $risk = "MEDIUM";
    }
    else
    {
        $risk = "HIGH";
    }


    /*
    |--------------------------------------------------------------------------
    | ZERO PAY
    |--------------------------------------------------------------------------
    */

    if($revenue <= $debt)
    {
        $zero_pay = "YES";
    }
    else
    {
        $zero_pay = "NO";
    }


    /*
    |--------------------------------------------------------------------------
    | RECOMMENDATION
    |--------------------------------------------------------------------------
    */

    if($risk == "LOW")
    {
        $recommendation =
            "Excellent projection. This sale is expected to recover your debt and provide a positive payout.";
    }
    elseif($risk == "MEDIUM")
    {
        $recommendation =
            "Debt recovery is expected, but the surplus may be limited.";
    }
    else
    {
        $recommendation =
            "High risk of zero pay. Consider improving tobacco quality before marketing.";
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE PROJECTION
    |--------------------------------------------------------------------------
    */

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
            zero_pay_status,
            generated_grade
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?
        )
    ";

    $save_stmt = mysqli_prepare($conn, $save_sql);

    if ($save_stmt)
    {
        mysqli_stmt_bind_param(
            $save_stmt,
            "issddddsss",
            $grower_id,
            $plant_position,
            $quality,
            $kg,
            $price_per_kg,
            $revenue,
            $payout,
            $risk,
            $zero_pay,
            $generated_grade
        );

        $save_ok = mysqli_stmt_execute($save_stmt);

        if ($save_ok)
        {
            $_SESSION['saved_message'] =
                'Projection saved successfully.';
        }
        else
        {
            $_SESSION['saved_message'] =
                'Failed to save projection: ' .
                mysqli_error($conn);
        }
    }
    else
    {
        $_SESSION['saved_message'] =
            'Failed to prepare save statement: ' .
            mysqli_error($conn);
    }


    /*
    |--------------------------------------------------------------------------
    | STORE LAST PROJECTION FOR DISPLAY
    |--------------------------------------------------------------------------
    */

    $_SESSION['last_projection'] = [

        'generated_grade' => $generated_grade,

        'price_per_kg' => $price_per_kg,

        'revenue' => $revenue,

        'debt' => $debt,

        'payout' => $payout,

        'recovery' => $recovery,

        'risk' => $risk,

        'zero_pay' => $zero_pay,

        'recommendation' => $recommendation,

        'plant_position' => $plant_position,

        'quality' => $quality,

        'colour' => $colour,

        'style' => $style,

        'extra' => $extra

    ];


    /*
    |--------------------------------------------------------------------------
    | REDIRECT
    |--------------------------------------------------------------------------
    */

    header('Location: projection.php');

    exit();
}
?>

<!DOCTYPE html>

<html>

<head>

<title>LeafLink - Sale Projection Engine</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
/* In-system toast notification */
.sys-toast{
  position:fixed;
  top:20px;
  right:20px;
  background:#223322;
  color:#e6ffe6;
  padding:14px 18px;
  border-radius:8px;
  box-shadow:0 6px 20px rgba(0,0,0,0.25);
  z-index:9999;
  max-width:320px;
  font-weight:600;
}
.sys-toast .close-btn{
  background:transparent;
  border:none;
  color:inherit;
  font-size:16px;
  float:right;
  cursor:pointer;
}
.sys-toast.hide{ display:none; }
</style>

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

<?php if (!empty($saved_message)) { ?>
    <p style="color:green;"><strong><?php echo htmlspecialchars($saved_message); ?></strong></p>
<?php } ?>

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
            <h2>Sale Simulation Summary</h2>
            <p> Total Projections : <strong><?php echo intval($summary_count); ?></strong></p>
            <p> Total Mass : <strong><?php echo number_format($summary_mass,2); ?> kg</strong></p>
            <p> Total Projected Sales : <strong>$<?php echo number_format($summary_sales,2); ?></strong></p>
        </div>

<div class="card">

<h3 style="color: black;">Please provide tobacco details:</h3>
</br>

<form method="POST">

<label>Plant Position</label>

<br><br>

<select name="plant_position" required>
<option value="">Select Position</option>
<?php foreach ($plant_positions as $pp) { ?>
    <option value="<?php echo htmlspecialchars($pp); ?>" <?php echo (isset($plant_position) && $plant_position===$pp)?'selected':''; ?>><?php echo htmlspecialchars($pp); ?></option>
<?php } ?>
</select>

<br><br>


<label>Quality</label>

<br><br>

<select name="quality" required>
<option value="">Select Quality</option>
<?php foreach ($qualities as $qopt) { ?>
    <option value="<?php echo htmlspecialchars($qopt['code']); ?>" <?php echo (isset($quality) && $quality==$qopt['code'])?'selected':''; ?>><?php echo htmlspecialchars($qopt['code'] . ' — ' . $qopt['description']); ?></option>
<?php } ?>
</select>

<br><br>

<label>Leaf Colour</label>

<br><br>

<select name="colour" required>
    <option value="">Select Colour</option>
    <?php foreach ($colours as $co) { ?>
        <option value="<?php echo htmlspecialchars($co['code']); ?>" <?php echo (isset($colour) && $colour===$co['code'])?'selected':''; ?>><?php echo htmlspecialchars($co['name']); ?></option>
    <?php } ?>
</select>

<br><br>

<label>Style (optional)</label>

<br><br>

<select name="style">
    <option value="">None</option>
    <?php foreach ($styles as $st) { ?>
        <option value="<?php echo htmlspecialchars($st['code']); ?>" <?php echo (isset($style) && $style===$st['code'])?'selected':''; ?>><?php echo htmlspecialchars($st['name']); ?></option>
    <?php } ?>
</select>

<br><br>

<label>Extra Factor (optional)</label>

<br><br>

<select name="extra">
    <option value="">None</option>
    <?php foreach ($extras as $ex) { ?>
        <option value="<?php echo htmlspecialchars($ex['code']); ?>" <?php echo (isset($extra) && $extra===$ex['code'])?'selected':''; ?>><?php echo htmlspecialchars($ex['name']); ?></option>
    <?php } ?>
</select>

<br><br>

<label>Bale Kilograms</label>

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

<hr>

<p>
    <strong>Estimated TIMB Grade</strong><br>
    <span style="font-size:1.4rem;"><?php echo htmlspecialchars($generated_grade); ?></span>
</p>

<p>
    <strong>Classification</strong><br>
    <?php echo htmlspecialchars($plant_position === '' ? '' : $plant_position); ?> — <?php echo htmlspecialchars($quality_lookup[$quality] ?? $quality); ?> — <?php echo htmlspecialchars($colour_lookup[$colour] ?? $colour); ?> — <?php echo htmlspecialchars($style_lookup[$style] ?? $style); ?> — <?php echo htmlspecialchars($extra_lookup[$extra] ?? $extra); ?>
</p>

<p>
    <strong>Recommendation</strong><br>
    <?php echo htmlspecialchars($recommendation); ?>
</p>

<?php if (!empty($saved_message)) { ?>
    <p><em><?php echo htmlspecialchars($saved_message); ?></em></p>
<?php } ?>

</div>

<?php } ?>

</div>

</div>

</div>

</body>

<?php if (!empty($saved_message)) { ?>
    <div id="system-toast" class="sys-toast" role="status" aria-live="polite">
        <button class="close-btn" aria-label="Close" onclick="document.getElementById('system-toast').style.display='none'">×</button>
        <?php echo htmlspecialchars($saved_message); ?>
    </div>
    <script>
        // Auto-hide toast after 6 seconds
        (function(){
            try{
                const t = document.getElementById('system-toast');
                if(!t) return;
                setTimeout(()=>{ t.style.display='none'; }, 6000);
            }catch(e){}
        })();
    </script>
<?php } ?>

</html>