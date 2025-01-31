<?php
header("Content-Type: application/json");
require 'db.php';

$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'GET':
        if (isset($_GET["books"])) {
            require 'books.php';
        } elseif (isset($_GET["users"])) {
            require 'users.php';
        }
        break;

    case 'POST':
        require 'books.php';
        break;

    case 'PUT':
        require 'books.php';
        break;

    case 'DELETE':
        require 'books.php';
        break;

    default:
        echo json_encode(["message" => "Invalid Request"]);
        break;
}
?>
