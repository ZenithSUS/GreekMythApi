<?php
require_once('../config.php');
class Api extends Database {
    protected $conn;
    private $imagePath = [
        "default_users" => "C:/xampp/htdocs/GreekMyth/img/default.jpg",
        "admins" => "C:/xampp/htdocs/GreekMyth/img/admin/", 
        "gods" => "C:/xampp/htdocs/GreekMyth/img/gods/",
        "users" => "C:/xampp/htdocs/GreekMyth/img/u/"
    ];

    protected function queryFailed(string $type = null, array $errors = null) : string {
        if($type == "Edit"){
            $response = array(
                "status" => 422,
                "message" => "Unprocessable Content",
                "error" => $errors
            );
            header("HTTP/1.1 422 Unprocessable Content");
        } else if ($type == "Delete"){
            $response = array(
                "status" => 422,
                "message" => "Unprocessable Content",
            );
            header("HTTP/1.1 422 Unprocessable Content");
        } else {
            $response = array(
                "status" => 500,
                "message" => "Query Failed"
            );
            header("HTTP/1.1 500 Internal Server Error");
        }
        
        return json_encode($response);
    }


    protected function notFound(){
        $response = array(
            "status" => 404,
            "message" => "Not Found",
        );
        header("HTTP/1.1 Data not Found");
        return json_encode($response);
    }

    protected function Fetched($result, string $type) : string{
        $row = $result->fetch_all(MYSQLI_ASSOC);

        if ($type === "admins") {
            $i = 0;
            while (isset($row[$i])) {
                if (!empty($row[$i]['image_src'])) { 
                    $row[$i]['image_src'] = $this->imagePath['admins'] . $row[$i]['image_src'];
                } else {
                    $row[$i]['image_src'] = $this->imagePath['default_users'] . $row[$i]['image_src'];
                }
                $i++;
            }
        }

        if($type == "users"){
            $i = 0;
            while (isset($row[$i])) {
                if (!empty($row[$i]['profile_pic'])) { 
                    $row[$i]['profile_pic'] = $this->imagePath['users'] . $row[$i]['profile_pic'];
                } else {
                    $row[$i]['profile_pic'] = $this->imagePath['default_users'];
                }
                $i++;
            }
        }
    
        if ($type === "groups") {
            $i = 0;
            while (isset($row[$i])) {
                if (!empty($row[$i]['image_url'])) { 
                    $row[$i]['image_url'] = $this->imagePath['gods'] . $row[$i]['image_url'];
                }
                $i++;
            }
        }
        
        $response = array(
            "status" => 200,
            "message" => "success",
            "data" => $row
        );
        header("HTTP/1.1 200 Fetched Successfully!");
        return json_encode($response);
    }
    

    protected function success(){
        $response = array(
            "status" => 200,
            "message" => "Success"
        );
        header("HTTP/1.1 200 OK");
        return json_encode($response);
    }

    protected function editedResource(){
        $response = array(
            "status" => 200,
            "message" => "Modfied Successfully"
        );
        header("HTTP/1.1 Modified Sucessfully");
        return json_encode($response);
    }

    protected function deletedResource(){
        $response = array(
            "status" => 200,
            "message" => "Deleted Successfully"
        );
        header("HTTP/1.1 Modified Sucessfully");
        return json_encode($response);
    }
    

    public function regError(array $errors) : string {
        $response = array(
            "status" => 422,
            "message" => "Unprocessable Content",
            "error" => $errors
        );
        header("HTTP/1.1 422 Unprocessable Content");
        return json_encode($response);
    }

    public function created() : string {
        $response = array(
            "status" => 201,
            "message" => "Resource Created"
        );
        header("HTTP/1.1 201 Resource Created");
        return json_encode($response);
    }
}