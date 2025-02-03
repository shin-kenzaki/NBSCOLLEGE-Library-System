<?php
header("Content-Type: application/json");
require 'db.php';

$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'GET':
        if (isset($_GET["books"])) {
            require 'endpoints/books.php';
        } elseif (isset($_GET["admins"])) {
            require 'endpoints/admins.php';
        } elseif (isset($_GET["schoolusers"])) {
            require 'endpoints/schoolusers.php';
        } elseif (isset($_GET["outsideusers"])) {
            require 'endpoints/outsideusers.php';
        } elseif (isset($_GET["writers"])) {
            require 'endpoints/writers.php';
        } elseif (isset($_GET["publishers"])) {
            require 'endpoints/publishers.php';
        } elseif (isset($_GET["contributors"])) {
            require 'endpoints/contributors.php';
        } elseif (isset($_GET["publications"])) {
            require 'endpoints/publications.php';
        } elseif (isset($_GET["reservations"])) {
            require 'endpoints/reservations.php';
        } elseif (isset($_GET["borrowings"])) {
            require 'endpoints/borrowings.php';
        } elseif (isset($_GET["fines"])) {
            require 'endpoints/fines.php';
        } else {
            echo json_encode(["error" => "Invalid request: Missing or incorrect parameters"]);
            exit;
        }
        break;

    case 'POST':
        if (isset($_GET["books"])) {
            require 'endpoints/books.php';
        } elseif (isset($_GET["admins"])) {
            require 'endpoints/admins.php';
        } elseif (isset($_GET["schoolusers"])) {
            require 'endpoints/schoolusers.php';
        } elseif (isset($_GET["outsideusers"])) {
            require 'endpoints/outsideusers.php';
        } elseif (isset($_GET["writers"])) {
            require 'endpoints/writers.php';
        } elseif (isset($_GET["publishers"])) {
            require 'endpoints/publishers.php';
        } elseif (isset($_GET["contributors"])) {
            require 'endpoints/contributors.php';
        } elseif (isset($_GET["publications"])) {
            require 'endpoints/publications.php';
        } elseif (isset($_GET["reservations"])) {
            require 'endpoints/reservations.php';
        } elseif (isset($_GET["borrowings"])) {
            require 'endpoints/borrowings.php';
        } elseif (isset($_GET["fines"])) {
            require 'endpoints/fines.php';
        } else {
            echo json_encode(["error" => "Invalid request: Missing or incorrect parameters"]);
            exit;
        }
        break;

    case 'PUT':
        if (isset($_GET["books"])) {
            require 'endpoints/books.php';
        } elseif (isset($_GET["admins"])) {
            require 'endpoints/admins.php';
        } elseif (isset($_GET["schoolusers"])) {
            require 'endpoints/schoolusers.php';
        } elseif (isset($_GET["outsideusers"])) {
            require 'endpoints/outsideusers.php';
        } elseif (isset($_GET["writers"])) {
            require 'endpoints/writers.php';
        } elseif (isset($_GET["publishers"])) {
            require 'endpoints/publishers.php';
        } elseif (isset($_GET["reservations"])) {
            require 'endpoints/reservations.php';
        } elseif (isset($_GET["borrowings"])) {
            require 'endpoints/borrowings.php';
        } elseif (isset($_GET["fines"])) {
            require 'endpoints/fines.php';
        } else {
            echo json_encode(["error" => "Invalid request: Missing or incorrect parameters"]);
            exit;
        }
        break;

    case 'DELETE':
        if (isset($_GET["books"])) {
            require 'endpoints/books.php';
        } elseif (isset($_GET["admins"])) {
            require 'endpoints/admins.php';
        } elseif (isset($_GET["schoolusers"])) {
            require 'endpoints/schoolusers.php';
        } elseif (isset($_GET["outsideusers"])) {
            require 'endpoints/outsideusers.php';
        } elseif (isset($_GET["writers"])) {
            require 'endpoints/writers.php';
        } elseif (isset($_GET["publishers"])) {
            require 'endpoints/publishers.php';
        } elseif (isset($_GET["contributors"])) {
            require 'endpoints/contributors.php';
        } elseif (isset($_GET["publications"])) {
            require 'endpoints/publications.php';
        } elseif (isset($_GET["reservations"])) {
            require 'endpoints/reservations.php';
        } elseif (isset($_GET["borrowings"])) {
            require 'endpoints/borrowings.php';
        } elseif (isset($_GET["fines"])) {
            require 'endpoints/fines.php';
        } else {
            echo json_encode(["error" => "Invalid request: Missing or incorrect parameters"]);
            exit;
        }
        break;

    default:
        echo json_encode(["error" => "Invalid request: Unsupported HTTP method"]);
        exit;
}
?>
