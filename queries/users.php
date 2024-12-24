<?php
require_once('../api/apiStatus.php');
class Users extends Api {
    protected $errors = array();
    public function __construct(){
        $this->conn = $this->connect();
    }

    public function getAdminInfo(string $id) : string {
        $sql = "SELECT username, image_src, email FROM Admin_Users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt) {
            return $this->queryFailed();
        } else {
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows > 0){
                return $this->Fetched($result, "admins");
            } else {
                return $this->notFound();
            }
            $stmt->close();
        }
    }

    public function getAllUsers(int $limit = 0, int $offset = 0) : string {
        $sql = $this->BuildUserQuery(null, $limit, $offset);
        $stmt = $this->conn->prepare($sql);
     
        if(!$stmt) {
            return $this->queryFailed();
        } else {
            $stmt->execute();
            $result = $stmt->get_result();
            $totalPages = $this->getTotalPageUser($limit);
            if($result->num_rows > 0){
                return $this->Fetched($result, "users", $totalPages);
            } else {
                return $this->notFound();
            }
            $stmt->close();
        }
    }

    public function getUser(string $id) : string {
        $sql = $this->BuildUserQuery($id);
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed();
        } 

        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
            
        if($result->num_rows > 0){
            $stmt->close();
            return $this->Fetched($result, "users");
        } else {
            return $this->notFound();
        }
    }

    private function BuildUserQuery(string $id = null, int $limit = 0, int $offset = 0) : string {
        if(isset($id) && $id !== null) {
            return "SELECT users.username, users.email, users.bio, users.profile_pic, users.joined_at, 
            (SELECT COUNT(posts.author) FROM posts WHERE posts.author = users.user_id) AS totalPosts, 
            (SELECT COUNT(comments.author) FROM comments WHERE comments.author = users.user_id) AS totalComments,
            (SELECT COUNT(friends.user_id) FROM friends WHERE friends.user_id = users.user_id) AS totalFriends,
            (SELECT COUNT(user_groups.user_id) FROM user_groups WHERE user_groups.user_id = users.user_id) AS totalGroups
            FROM users 
            WHERE users.user_id = ?";
        }

        if(isset($limit) && $limit > 0){
            return "SELECT users.user_id, users.username, users.email, users.joined_at, 
            users.profile_pic, users.bio, COUNT(friends.user_id) AS totalFriends
            FROM users
            LEFT JOIN friends ON users.user_id = friends.user_id
            GROUP BY users.user_id
            LIMIT $limit OFFSET $offset";
        }

        return "SELECT users.user_id, users.username, users.email, users.joined_at, 
        users.profile_pic, users.bio, COUNT(friends.user_id) AS totalFriends
        FROM users
        LEFT JOIN friends ON users.user_id = friends.user_id
        GROUP BY users.user_id";
    }

    private function getTotalPageUser(int $limit) : int {
        $sql = "SELECT COUNT(*) FROM users";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt) {
            return 0;
        }
        
        if($limit > 0 && $stmt->execute()) {
            $result = $stmt->get_result();
            $totalRecords = $result->fetch_assoc()['COUNT(*)'];
            $totalPages = ceil($totalRecords / $limit);
            return $totalPages;
        }

        return 0;
    }
    

    public function editUser($id, $username, $email) : string {
        $sql = "UPDATE users 
            SET username = ?, email = ?
            WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);

        $this->checkFields($username, $email);

        if(!empty($this->errors)){
            return $this->queryFailed("Edit", $this->errors);
        }
     
        if(!$stmt){
            return $this->queryFailed();
        } 
        
        $stmt->bind_param('sss', $username, $email, $id);
        $stmt->execute();
            
        if($stmt->affected_rows > 0){
            $stmt->close();
            return $this->editedResource();
        } 
        $sql = "SELECT username, email FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt) {
            return $this->queryFailed();
        } 
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Check username and email fields
        if($row['username'] === $username && $row['email'] === $email){
            $errors['status'] = "Please edit information";
            $stmt->close();
            return $this->queryFailed("Edit", $errors);
        } 
        return $this->notFound();
    }

    public function deleteUser($id){
        $sql = "SELECT profile_pic FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt) {
            return $this->queryFailed("Delete");
        } 
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image = $row['profile_pic'];

        if($result->num_rows > 0){
            if($image !== null){
                unlink($this->imagePath['users'] . $image);
            } 
            $stmt->close();
            return $this->deleteUserQuery($id);
        } 
        return $this->notFound();  
    }

    private function deleteUserQuery(string $id){
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed("Delete");
        } 
        $stmt->bind_param('s', $id);
        return $stmt->execute() ? $this->deletedResource() : $this->notFound();    
    }

    private function checkFields(string $username, string $email) : void {
        // Check if username is not empty
        if (empty($username) || $username === null) {
            $this->errors['usernameEdit'] = "Please fill the username";
        } else if ($this->validateUsername($this->checkUsername($username))) {
            foreach($this->checkUsername($username) as $type => $hasType){
                if($hasType) $this->errors['usernameValid'][$type] = $type . " is required";
            }
        }
        // Check if email is not empty
        if (empty($email) || $email === null) {
            $this->errors['emailEdit'] = "Please fill the email";
        // Check if email is valis
        } else if (!$this->checkEmail($email)){
            $this->errors['emailEdit'] = "Please enter a valid email";
        }
    }

    private function checkEmail(string $email) : bool{
        $status = filter_var($email, FILTER_VALIDATE_EMAIL) ? true : false;
        return $status;
    }

    private function validateUsername(array $username) : bool {
        foreach(array_keys($username) as $hastype){
            $status = $hastype ? true : false;
        }
        return $status;
    }

    private function checkUsername(string $username) : array {
        $upperCase = !preg_match('/[A-Z]/', $username); 
        $lowerCase = !preg_match('/[a-z]/', $username); 
        $usernameLength = strlen($username) < 5;
        
        return [
            "Uppercase" => $upperCase,
            "Lowercase" => $lowerCase,
            "5 characters" => $usernameLength
        ];
    }
}
?>