<?php
include_once('headers.php');
require_once('../queries/groups.php');
require_once('verifyToken.php');

$headers = apache_request_headers();
$groups = new Groups();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$token = $headers['Authorization'] ?? null;
if(isset($headers['Authorization'])){
    $token = explode(" ", $token);
    $token = $token[1];
}

if($requestMethod == "OPTIONS"){
    header("HTTP/1.1 200 OK");
    exit();
}

if($TokenAuth->tokenVerified($token) && $TokenAuth->tokenExists($token)){
    if($requestMethod == "POST"){
        echo $groups->getAllGroups();
    }

    if($requestMethod == "GET"){
        if(isset($_GET['greek_id'])){
            echo $groups->getGroup($_GET['greek_id']);
        } else {
            
        }
    }


} else {
    if($token == null){
        $response = array(
            "status" => 401,
            "message" => "Unauthorized"
        );
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode($response);
    } else {
        $response = array(
            "status" => 401,
            "message" => "Token not verified"
        );
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode($response);
    }
    
}
?>