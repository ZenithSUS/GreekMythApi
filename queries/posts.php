<?php
require_once('../api/apiStatus.php');

class Posts extends Api {

    public function __construct(){
        $this->conn = $this->connect();
    }
    
    public function getAllPosts() : string {
        $sql = "SELECT posts.post_id, users.username, posts.title, posts.content, posts.created_at, posts.likes, posts.dislikes, greeks.name
        FROM Users JOIN Posts ON users.user_id = posts.author
        LEFT JOIN Greeks ON posts.greek_group = greeks.greek_id
        ORDER BY posts.created_at DESC";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
           return $this->queryFailed();
        } else {
            $stmt->execute();
            $result = $stmt->get_result();
            $status = $result->num_rows > 0 ? $this->Fetched($result, "posts") : $this->notFound();
            return $status;
        }
    }

    public function getPosts(string $id) : string {
        $sql = "SELECT posts.post_id, posts.author, users.username, posts.title, posts.content, posts.created_at, posts.likes, posts.dislikes, greeks.name 
        FROM Posts JOIN Users ON users.user_id = posts.author
        LEFT JOIN Greeks ON posts.greek_group = greeks.greek_id
        WHERE posts.post_id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed();
        } else {
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result();

            $status = $result->num_rows > 0 ? $this->Fetched($result, "posts") : $this->notFound();
            return $status;
        }
    }

}
?>