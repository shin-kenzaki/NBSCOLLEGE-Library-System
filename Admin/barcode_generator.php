<?php
require 'vendor/autoload.php';
require 'db.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

// Function to generate barcode
function generateBarcode($text, $width = 2, $height = 40) {
    if (empty($text)) {
        die('Please provide text for the barcode.');
    }

    try {
        $generator = new BarcodeGeneratorPNG();
        return $generator->getBarcode($text, $generator::TYPE_CODE_128, $width, $height);
    } catch (Exception $e) {
        die('Error generating barcode: ' . $e->getMessage());
    }
}

// Get parameters
$text = isset($_GET['text']) ? $_GET['text'] : '12345678';
$width = isset($_GET['width']) ? intval($_GET['width']) : 2;
$height = isset($_GET['height']) ? intval($_GET['height']) : 40;

// Set proper headers for PNG image
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
header('Content-Disposition: inline; filename="barcode.png"');

// Output the PNG barcode
echo generateBarcode($text, $width, $height);
