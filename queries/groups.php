<?php
require_once('../api/apiStatus.php');
class Groups extends Api {
    public function __construct(){
        $this->conn = $this->connect();
    }

    public function getAllGroups() : string {
        
        $sql = "SELECT greeks.*, 
        CASE 
            WHEN greeks.creator = 'Default'
            THEN users.username = NULL
            ELSE users.username 
        END AS username, 
        COUNT(users.user_id) AS total_people FROM greeks
        LEFT JOIN user_groups ON greeks.greek_id = user_groups.greek_id
        LEFT JOIN users ON user_groups.user_id = users.user_id
        GROUP BY greeks.greek_id";

        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed();
        } else {
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows > 0){
                return $this->Fetched($result, "groups");
            } else {
                return $this->notFound();
            }
        }
    }

    public function getGroup(string $id) : string {
        $sql = "SELECT * FROM Greeks WHERE greek_id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed();
        } else {
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows > 0){
                return $this->Fetched($result, "groups");
            } else {
                return $this->notFound();
            }
        }
    }
}
?>