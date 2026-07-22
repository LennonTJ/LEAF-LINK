<?php

require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

$file = "uploads\sales\TIMB_20260720_085346.pdf";

echo "Looking for: " . $file . "<br>";

if(!file_exists($file)){
    die("PDF NOT FOUND");
}

$parser = new Parser();

$pdf = $parser->parseFile($file);

$text = $pdf->getText();

preg_match('/Grower No\s+([A-Z0-9]+)/', $text, $match);

echo "<hr>";

if(isset($match[1])){

    echo "Grower Number Found: ";
    echo $match[1];

}else{

    echo "Grower Number Not Found";

}

echo "<pre>";
echo $text;
echo "</pre>";