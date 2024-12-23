<?php
include_once('headers.php');
require_once('../queries/posts.php');
require_once('verifyToken.php');

$headers = apache_request_headers();
$data = json_decode(file_get_contents("php://input"), true);
$posts = new Posts();


$token = $headers['Authorization'] ?? null;
if(isset($headers['Authorization'])){
    $token = explode(" ", $token);
    $token = $token[1];
} 

$requestMethod = $_SERVER['REQUEST_METHOD'];
if($requestMethod == "OPTIONS"){
    header("HTTP/1.1 200 OK");
    exit();
}

if($TokenAuth->tokenExists($token) && $TokenAuth->tokenVerified($token)){
    if($requestMethod == "POST"){
        echo $posts->getAllPosts();
    } 

    if($requestMethod == "GET"){
        if(isset($_GET['id'])){
            echo $posts->getPosts($_GET['id']);
        } else {
            echo $this->notFound();
        }
    }
    
    if($requestMethod == "PUT"){
        $type = $data['type'] ?? null;
        $id = $_GET['id'] ?? null;
        if(isset($id) && isset($type)){
            echo $posts->changePermissionPost($id, $type);
        }
    }

    if($requestMethod == "DELETE"){
        $id = $_GET['id'] ?? null;
        if(isset($id)){
            echo $posts->deletePost($id);
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
            "message" => "Token not Verified"
        );
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode($response);
    }
}


?>