<?php

session_start();

require '../vendor/autoload.php';
require '../config/database.php';

use Smalot\PdfParser\Parser;


// Check PDF selected?

if(!isset($_GET['file'])){
    die("No PDF selected");
}


$file = $_GET['file'];

// get the file id
$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;


// PDF location

$pdfPath = __DIR__ . "/../uploads/sales/" . $file;


if(!file_exists($pdfPath)){
    die("PDF not found: " . $pdfPath);
}



// ===============================
// Read PDF
// ===============================

$parser = new Parser();

$pdf = $parser->parseFile($pdfPath);

$text = $pdf->getText();



// ===============================
// Extract Grower Number
// ===============================

preg_match(
    '/Grower No\s+([A-Z0-9]+)/',
    $text,
    $growerMatch
);


$grower_no = $growerMatch[1] ?? "Not Found";




// ===============================
// Find Grower
// ===============================


$grower = null;


$stmt = $conn->prepare(
    "SELECT * FROM growers WHERE grower_no = ?"
);


$stmt->bind_param(
    "s",
    $grower_no
);


$stmt->execute();


$result = $stmt->get_result();


if($result->num_rows > 0){

    $grower = $result->fetch_assoc();

}



// ===============================
// Extract Sale Date
// ===============================

$sale_date = null;


preg_match(
    '/(\d{2}[-\/]\d{2}[-\/]\d{4})/',
    $text,
    $dateMatch
);


if(isset($dateMatch[1])){


    // Convert - to /
    $clean_date = str_replace(
        "-",
        "/",
        $dateMatch[1]
    );


    $date = DateTime::createFromFormat(
        'd/m/Y',
        $clean_date
    );


    if($date){

        $sale_date = $date->format('Y-m-d');

    }

}



// ===============================
// Extract Values
// ===============================


preg_match(
    '/Gross Value\s+([\d\.]+)/i',
    $text,
    $grossMatch
);


$gross_value = $grossMatch[1] ?? 0;




preg_match(
    '/NET PAYMENT\s+([\d\.]+)/i',
    $text,
    $paymentMatch
);


$net_payment = $paymentMatch[1] ?? 0;





// ===============================
// Extract Bale Information
// ===============================


$grades = [];


preg_match_all(

'/([0-9A-Z\.\-%\/]+)\s+(\d+)\s+(\d+(?:\.\d+)?)\s+([A-Z][0-9][A-Z0-9]*)\s+([\d\.]+)\s+([\d\.]+)\s+(Yes|No)?/',


$text,

$matches,

PREG_SET_ORDER

);



foreach($matches as $row){


    $status = ($row[7] == "Yes")
        ? "REJECTED"
        : "SOLD";



    $grades[] = [

        "barcode"=>$row[1],

        "lot"=>$row[2],

        "mass"=>$row[3],

        "grade"=>$row[4],

        "price"=>$row[5],

        "value"=>$row[6],

        "status"=>$status

    ];

}





// ===============================
// Calculate Totals
// ===============================


$total_bales = count($grades);


$sold_bales = 0;

$rejected_bales = 0;


$sold_mass = 0;

$rejected_mass = 0;



foreach($grades as $g){


    if($g['status']=="SOLD"){


        $sold_bales++;

        $sold_mass += $g['mass'];


    }
    else{


        $rejected_bales++;

        $rejected_mass += $g['mass'];


    }

}





$presented_mass = $sold_mass + $rejected_mass;


// store session data for import page
$_SESSION['import_data'] = [

    'file_id' => $file_id,
    'file' => $file,

    'grower_no' => $grower_no,
    'sale_date' => $sale_date,

    'total_bales' => $total_bales,
    'sold_bales' => $sold_bales,
    'rejected_bales' => $rejected_bales,

    'presented_mass' => $presented_mass,
    'sold_mass' => $sold_mass,
    'rejected_mass' => $rejected_mass,

    'gross_value' => $gross_value,
    'net_payment' => $net_payment,

    'season' => date('Y'),

    'grades' => $grades

];


?>


<!DOCTYPE html>

<html>

<head>

<title>Confirm Sale Import</title>


<style>

body{

font-family:Arial;

margin:40px;

}


.card{

border:1px solid #ddd;

padding:20px;

border-radius:10px;

width:700px;

margin-bottom:20px;

}


.success{

color:green;

}


.warning{

color:red;

}


pre{

background:#f4f4f4;

padding:15px;

overflow:auto;

}


table{

border-collapse:collapse;

width:700px;

}


td,th{

border:1px solid #ccc;

padding:8px;

}


</style>


</head>


<body>



<h2>Sale Import Preview</h2>



<div class="card">


<p>
<b>PDF:</b>
<?= $file ?>
</p>


<p>
<b>Grower Number:</b>
<?= $grower_no ?>
</p>



<p>

<b>Grower Name:</b>

<?php


if($grower){


echo $grower['first_name']." ".$grower['last_name'];

echo "<br><span class='success'>Registered Grower ✓</span>";


}else{


echo "<span class='warning'>Grower not registered</span>";


}


?>

</p>


</div>





<div class="card">


<h3>Sale Summary</h3>


<p>
<b>Sale Date:</b>
<?= $sale_date ?>
</p>


<p>
<b>Total Bales:</b>
<?= $total_bales ?>
</p>


<p>
<b>Sold Bales:</b>
<?= $sold_bales ?>
</p>


<p>
<b>Rejected Bales:</b>
<?= $rejected_bales ?>
</p>


<p>
<b>Presented Mass:</b>
<?= $presented_mass ?> kg
</p>


<p>
<b>Sold Mass:</b>
<?= $sold_mass ?> kg
</p>


<p>
<b>Rejected Mass:</b>
<?= $rejected_mass ?> kg
</p>


<p>
<b>Gross Value:</b>
$<?= $gross_value ?>
</p>


<p>
<b>Net Payment:</b>
$<?= $net_payment ?>
</p>



</div>





<h3>Bale Details</h3>


<table>


<tr>

<th>Barcode</th>
<th>Lot</th>
<th>Grade</th>
<th>Mass</th>
<th>Price</th>
<th>Value</th>
<th>Status</th>

</tr>



<?php foreach($grades as $g): ?>


<tr>


<td><?= $g['barcode'] ?></td>

<td><?= $g['lot'] ?></td>

<td><?= $g['grade'] ?></td>

<td><?= $g['mass'] ?></td>

<td><?= $g['price'] ?></td>

<td><?= $g['value'] ?></td>

<td><?= $g['status'] ?></td>


</tr>


<?php endforeach; ?>


</table>
</br>

<form action="process_import.php" method="POST">

    <button type="submit" name="confirm">
        Confirm Import
    </button>

</form>



<h3>Raw Extracted Text</h3>


<pre>

<?= htmlspecialchars($text) ?>

</pre>



</body>

</html>