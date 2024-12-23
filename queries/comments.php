<?php
require_once('../api/apiStatus.php');
class Comments extends Api {

    public function __construct(){
        $this->conn = $this->connect();
    }

    public function getAllComments() : string {
        $sql = "SELECT comments.comment_id, users.username, comments.content, comments.created_at, comments.likes, comments.dislikes 
        FROM Comments JOIN Users ON
        comments.author = users.user_id";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed();
        } else {
            $stmt->execute();
            $result = $stmt->get_result();
            $status = $result->num_rows > 0 ? $this->Fetched($result, "comments") : $this->notFound();
            return $status;
        }
    }

}

?>