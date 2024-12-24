<?php
include_once('headers.php');
require_once('../queries/users.php');
require_once('verifyToken.php');

$headers = apache_request_headers();
$data = json_decode(file_get_contents("php://input"), true);
$requestMethod = $_SERVER["REQUEST_METHOD"];


$users = new Users();

$token = $headers['Authorization'] ?? null;
if(isset($headers['Authorization'])){
    $token = explode(" ", $token);
    $token = $token[1];
} 

if($requestMethod == "OPTIONS") {
    header("HTTP/1.1 200 OK");
    exit();
}

if($TokenAuth->tokenExists($token) && $TokenAuth->tokenVerified($token)){
    if($requestMethod == "GET") {
        if(isset($_GET['id'])){
            echo $users->getUser($_GET['id']);
        } else if (isset($_GET['user_id'])){
            echo $users->getAdminInfo($_GET['user_id']);
        }
    }
    
    if($requestMethod == "POST"){
        $limit = isset($_GET['limit']) && $_GET['limit'] !== null ? 10 : 0;
        $page = isset($_GET['page']) ? $_GET['page'] : 1; 
        $offset = ($page - 1) * $limit;
        echo $users->getAllUsers($limit, $offset);
    }
    
    if($requestMethod == "DELETE"){
        $id = htmlentities($_GET['id']) ?? null;

        if(isset($id) && $id != null){
            echo $users->deleteUser($id);
        }
        
    }
    
    if($requestMethod == "PUT"){
        $id = htmlentities($_GET['id']) ?? null;
        $username = strlen(htmlentities($data['usernameEdit'])) >= 0 ? htmlentities($data['usernameEdit']) : null;
        $email = strlen(htmlentities($data['emailEdit'])) >= 0 ? htmlentities($data['emailEdit']) : null;

        if(isset($id) && $id !== null){
            echo $users->editUser($id, $username, $email);
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
            "message" => "Token not Verified",
        );
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode($response);
    }
}


?>