<?php
include_once('headers.php');
require_once('../queries/groups.php');
require_once('verifyToken.php');

$headers = apache_request_headers();
$data = json_decode(file_get_contents("php://input"), true);
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
        $limit = isset($_GET['limit']) && $_GET['limit'] !== null ? 10 : 0;
        $page = isset($_GET['page']) ? $_GET['page'] : 1; 
        $offset = ($page - 1) * $limit;
        echo $groups->getAllGroups($limit, $offset);
    }

    if($requestMethod == "GET"){
        $id = htmlentities($_GET['id']) ?? null;
        if(isset($id)){
            echo $groups->getGroup($id);
        }
    }

    if($requestMethod == "PUT"){
        $id = htmlentities($_GET['id']) ?? null;
        $type = $data['type'];
        if(isset($id) && isset($type)){
            echo $groups->changePermissionGroup($id, $type);
        }
    }

    if($requestMethod == "DELETE"){
        $id = htmlentities($_GET['id']) ?? null;
        if(isset($id)){
            echo $groups->deleteGroup($id);
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