<?php
include_once('headers.php');
require_once('../queries/login.php');
require_once('../queries/register.php');

$headers = apache_request_headers();
$token = $headers['Authorization'] ?? null;

if (isset($token)) {
    $token = explode(" ", $token);
    $token = $token[1];
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "OPTIONS") {
    header("HTTP/1.1 200 OK");
    exit;
}


if ($requestMethod == "POST") {
    if (isset($_POST['Process']) && $_POST['Process'] == "Login") {
        $login = new Login();
        $username = htmlentities($_POST['User']);
        $password = htmlentities($_POST['Password']);
        $errors = array();

        if (empty($username) && empty($password)) {
            $errors['auth_status'] = "Please fill out the Fields";
            $response = array(
                "status" => 400,
                "message" => "Invalid data",
                "error" => $errors
            );
            header("HTTP/1.1 400 Bad Request");
            echo json_encode($response);
        } else {
            echo $login->login($username, $password);
        }
    }

    if (isset($_POST['Process']) && $_POST['Process'] == "Logout") {
        $login = new Login();
        if ($token != null) {
            echo $login->logout($token);
        } else {
            $response = array(
                "status" => 400,
                "message" => "Invalid data",
            );
            header("HTTP/1.1 400 Bad Request");
            echo json_encode($response);
        }
    }

    if (isset($_POST['Process']) && $_POST['Process'] == "Register") {
        // Handle the registration logic
        $username = htmlentities($_POST['username']);
        $email = htmlentities($_POST['email']);
        $password = htmlentities($_POST['password']);
        $confirm_password = htmlentities($_POST['confirm_password']);
        $image = isset($_FILES['image']) ? $_FILES['image'] : null;
        
        $register = new Register($username, $email, $password, $confirm_password, $image);
        $register->checkFields();
    }
}
?>
