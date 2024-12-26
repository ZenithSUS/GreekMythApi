<?php
include_once('headers.php');
require_once('../queries/users.php');
require_once('verifyToken.php');

$headers = apache_request_headers();
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

    if($requestMethod == "POST" && !isset($_GET['id'])){
        $limit = isset($_GET['limit']) && $_GET['limit'] !== null ? 10 : 0;
        $page = isset($_GET['page']) ? $_GET['page'] : 1; 
        $offset = ($page - 1) * $limit;
        echo $users->getAllUsers($limit, $offset);
    }

    if($requestMethod == "POST" && isset($_GET['id'])){
        $id = htmlentities($_GET['id']) ?? null;
        $type = htmlentities(isset($_POST['type'])) ? htmlentities($_POST['type']) : null;
        
        if(isset($id) && $id !== null && ($type === "user" || $type === "admin")){
            $username = strlen(htmlentities($_POST['usernameEdit'])) > 0 ? htmlentities($_POST['usernameEdit']) : null;
            $email = strlen(htmlentities($_POST['emailEdit'])) > 0 ? htmlentities($_POST['emailEdit']) : null;
            echo $users->editUser($id, $username, $email, $type); 
        }

        if(isset($id) && $id !== null && $type === "adminPass"){
            $newPassword = strlen(htmlentities($_POST['newpassword'])) > 0 ? htmlentities($_POST['newpassword']) : null;
            $confirmNewPassword = strlen(htmlentities($_POST['newconfirmpassword'])) > 0 ? htmlentities($_POST['newconfirmpassword']) : null;
            echo $users->changeAdminPassword($id, $newPassword, $confirmNewPassword);
        }

        if(isset($id) && $id !== null && $type === "deleteAdmin"){
            $password = strlen(htmlentities($_POST['password'])) > 0 ? htmlentities($_POST['password']) : null;
            $confirmPassword = strlen(htmlentities($_POST['confirmpassword'])) > 0 ? htmlentities($_POST['confirmpassword']) : null;
            echo $users->check_Delete_Account_PasswordFields($id, $password, $confirmPassword);
        }
    }

    if($requestMethod == "GET") {
        if(isset($_GET['id'])){
            echo $users->getUser($_GET['id']);
        } else if (isset($_GET['user_id'])){
            echo $users->getAdminInfo($_GET['user_id']);
        }
    }
    
    if($requestMethod == "DELETE"){
        $id = htmlentities($_GET['id']) ?? null;
        $type = htmlentities($_GET['type']) ?? null;

        if((isset($id) && $id !== null) && $type === "user"){
            echo $users->deleteUser($id);
        }
        
        if((isset($id) && $id !== null) && $type === "admin"){
            echo $users->deleteAdminUser($id);
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