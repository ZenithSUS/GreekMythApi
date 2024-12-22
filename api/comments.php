<?php
include_once('headers.php');
require_once('../queries/comments.php');
require_once('verifyToken.php');

$headers = apache_request_headers();
$commments = new Comments();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$token = $headers['Authorization'] ?? null;

if(isset($token)){
    $token = explode(" ", $token);
    $token = $token[1];
}

if($requestMethod == "OPTIONS"){
    header("HTTP/1.1 200 OK");
    exit;
}

if($TokenAuth->tokenExists($token) && $TokenAuth->tokenVerified($token)){
    if($requestMethod == "POST"){
        echo $commments->getAllComments();
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