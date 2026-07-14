<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");

    exit();

}

?>
<!DOCTYPE html>
<html>

<head>

<title>Contractor Dashboard</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="header">

<h1>LeafLink</h1>

<h2>Smoke Merchant Tobacco</h2>

</div>

<div class="layout">

<div class="sidebar">

<h3>Contractor</h3>

<hr>

<a href="#">Dashboard</a>

<h4>Growers</h4>

<a href="#">Assigned Growers</a>

<h4>Sales</h4>

<a href="#">Sales History</a>

<h4>Finance</h4>

<a href="#">Payments</a>

<h4>Account</h4>

<a href="#">My Profile</a>

<hr>

<a href="../logout.php">Logout</a>

</div>

    <div class="hero">
        <div class="content">

    <div class="container">


<div class="card">

<h2>Welcome Contractor</h2>

</div>

<div class="card">

<p>Growers Assigned : <strong>3</strong></p>

<p>Active Contracts : <strong>3</strong></p>

<p>Current Season : <strong>2026</strong></p>

</div>

<div class="card">

<h3>Assigned Growers</h3>

<ul>

<li>V175259 - Lennon Jenifani</li>

<li>V175260 - Tawanda Moyo</li>

<li>V175261 - Rumbidzai Dube</li>

</ul>

</div>

<a class="btn" href="../logout.php">Logout</a>


        </div>
</div>
    </div>
</div>

</body>
</html>

