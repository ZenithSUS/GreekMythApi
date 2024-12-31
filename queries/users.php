<?php
require_once('../api/apiStatus.php');
class Users extends Api {
    protected $errors = array();
    public function __construct(){
        $this->conn = $this->connect();
    }

    public function getAdminInfo(string $id) : string {
        $sql = "SELECT admin_users.username, admin_users.image_src, admin_users.email, admin_settings.dark_mode
        FROM Admin_Users 
        JOIN Admin_Settings ON
        admin_users.id = admin_settings.admin_id
        WHERE admin_users.id = ?";
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

    public function changeAdminPassword(string $id, ?string $newPassword = null, ?string $confirmNewPassword = null) : string {
       
        $this->checkPasswordFields($id, $newPassword, $confirmNewPassword);
        if(!empty($this->errors)){
            return $this->queryFailed("Edit", $this->errors);
        }

        $sql = $this->changePasswordQuery();
        $stmt = $this->conn->prepare($sql);

        if(!$stmt) {
            return $this->queryFailed();
        }

        $newPassHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt->bind_param('ss', $newPassHash, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0 ? $this->editedResource() : $this->queryFailed();
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
    

    public function editUser(string $id, ?string $username = null, ?string $email = null, string $type, $image = null) : string {
        $this->checkFields($username, $email, $type);
        if(!empty($this->errors)){
            return $this->queryFailed("Edit", $this->errors);
        }

        // Check Username Exists
        $sql = $this->getUsernameQuery($type);
        $Exists = $this->existsUsernameOrEmail($id, $sql, $username);
        
        if($Exists){
            $errors['usernameEdit'] = "Username already exists";
        }

        // Check Username Exists
        $sql = $this->getEmailQuery($type);
        $Exists = $this->existsUsernameOrEmail($id, $sql, $email);
        
        if($Exists){
            $errors['emailEdit'] = "Email already exists";
        }

        if(!empty($errors)){
            return $this->queryFailed("Edit", $errors);
        }

        $sql = $this->editUserQuery($type);
        $stmt = $this->conn->prepare($sql);
 
        if(!$stmt){
            return $this->queryFailed();
        }
        
        $stmt->bind_param('sss', $username, $email, $id);
        $stmt->execute();
            
        if($stmt->affected_rows > 0 || $this->changeImage($id, $image) !== false){
            $stmt->close();
            return $this->editedResource();
        }
        
        if(!empty($this->errors)){
            $stmt->close();
            return $this->queryFailed("Edit", $this->errors);
        }


        $sql = $this->getUserInfoQuery($type);
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
        }

        if(!empty($errors)){
            $stmt->close();
            return $this->queryFailed("Edit", $errors);
        }

        return $this->notFound();
    }

    public function check_Delete_Account_PasswordFields(string $id, ?string $password = null, ?string $confirmPassword = null) : string {
        $this->check_Admin_Delete_Password_Fields($password, $confirmPassword);
        if(!empty($this->errors)){
            return $this->queryFailed("DeleteAdmin", $this->errors);
        }

        if(!$this->verifyPassword($id, $password)){
            $this->errors['delstatus'] = "Wrong Password!";
        }

        if(!empty($this->errors)){
            return $this->queryFailed("DeleteAdmin", $this->errors);
        }

        return $this->success();
    }

    public function deleteAdminUser(string $id) : string {
        $sql = "SELECT image_src FROM admin_users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $id);

        if(!$stmt) {
            return $this->queryFailed("Delete");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image = $row['image_src'];

        if($result->num_rows > 0){
            if($image !== null){
                unlink($this->imagePath['admins'] . $image);
            } 
            $stmt->close();
            return $this->deleteAdminUserQuery($id);
        } 
        return $this->notFound(); 
    }

    private function deleteAdminUserQuery(string $id) {
        $sql = "DELETE FROM admin_users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $id);

        $stmt->execute();
        return $stmt->affected_rows > 0 ? $this->deletedResource() : $this->notFound();
    }

    public function deleteUser(string $id) : string {
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

    public function changeThemeAdmin(string $id, ?string $type = null, ?string $fontstyle = null) : string {
        $sql = $this->changeThemeAdminQuery($type);
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $fontstyle, $id);

        if(!$stmt){
            return $this->queryFailed();
        }

        $stmt->execute();
        return $stmt->affected_rows > 0 ? $this->editedResource() : $this->queryFailed();
    }

    private function getUserInfoQuery(string $type) : string {
        return $type === "user" ? "SELECT username, email FROM users WHERE user_id = ?" : "SELECT username, email FROM admin_users WHERE id = ?";
    }

    private function getUsernameQuery(string $type) : string {
        return $type === "user" ? "SELECT username FROM users WHERE user_id != ? AND username = ?" : "SELECT username FROM admin_users WHERE id != ? AND username = ?";
    }
    private function getEmailQuery(string $type) : string {
        return $type === "user" ? "SELECT email FROM users WHERE user_id != ? AND email = ?" : "SELECT email FROM admin_users WHERE id != ? AND email = ?";
    }

    private function existsUsernameOrEmail(string $id, string $sql, string $username) : bool {
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param('ss', $id, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? true : false;
    }

    private function editUserQuery(string $type) : string {
        if($type === "user"){
            return "UPDATE users 
            SET username = ?, email = ?
            WHERE user_id = ?";
        }

        return "UPDATE admin_users
            SET username = ?, email = ?
            WHERE id = ?";
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

    private function checkFields(?string $username = null, ?string $email = null, string $type) : void {
        // Check if username is not empty
        if (empty($username) || $username === null) {
            $this->errors['usernameEdit'] = "Please fill the username";
        } else if ($this->validateUsername($this->checkUsername($username, $type))) {
            foreach($this->checkUsername($username, $type) as $type => $hasType){
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

    private function checkPasswordFields(string $id, ?string $newPassword = null, ?string $confirmNewPassword = null) : void {
        // Check if new password is empty 
        if(empty($newPassword) && $newPassword === null) {
            $this->errors['newpassword'] = "Please fill the password";
        } 
    
        // Validate new password
        if($newPassword !== null && $this->validateAdminPassword($this->checkAdminPassword($newPassword))) {
            foreach($this->checkAdminPassword($newPassword) as $type => $hasType){
                if(!$hasType) $this->errors['passwordValid'][$type] = $type . " is required";
            }
        }
    
        // Check if new password is the same as the current password
        if($newPassword !== null && $this->currentPassword($id, $newPassword)) {
            $this->errors['newpassword'] = "The password must not be the old one";
        }
    
        // Check if new confirm password is empty
        if(empty($confirmNewPassword) && $confirmNewPassword === null) {
            $this->errors['newconfirmpassword'] = "Please fill the confirm password";
        } 
    
        // Check if new password and confirm password match
        if($confirmNewPassword !== null && $confirmNewPassword !== $newPassword) {
            $this->errors['newconfirmpassword'] = "Password doesn't match";
        }
    }

    private function check_Admin_Delete_Password_Fields(?string $password = null, ?string $confirmPassword = null) : void {
        // Check if new password is empty 
        if(empty($password) && $password === null) {
            $this->errors['password'] = "Please fill the password";
        } 
    
        // Check if new confirm password is empty
        if(empty($confirmPassword) && $confirmPassword === null) {
            $this->errors['confirmpassword'] = "Please fill the confirm password";
        } 
    
        // Check if new password and confirm password match
        if($confirmPassword !== null && $confirmPassword !== $password) {
            $this->errors['confirmpassword'] = "Password doesn't match";
        }
    }

    private function currentPassword(string $id, string $newPassword) : bool {
        $sql = "SELECT password FROM admin_users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $id);

        if(!$stmt){
            return false;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return password_verify($newPassword, $row['password']) ? true : false;
    }

    private function verifyPassword(string $id, string $password) : bool {
        $sql = "SELECT password FROM admin_users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $id);

        if(!$stmt){
            return false;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return password_verify($password, $row['password']) ? true : false;
    }

    private function changePasswordQuery() : string {
        return "UPDATE admin_users SET password = ? WHERE id = ?";
    }

    private function changeThemeAdminQuery(?string $type) : string {
        $switch = $type === "dark" ? 1 : 0;
        return "UPDATE admin_settings SET dark_mode = $switch, font_style = ? WHERE admin_id = ?";
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

    private function checkUsername(string $username, string $type) : array {
        $upperCase = !preg_match('/[A-Z]/', $username); 
        $lowerCase = !preg_match('/[a-z]/', $username); 
        $usernameLength = strlen($username) < 5;
        $specialchars = !preg_match('/[^A-Za-z0-9]/', $username);
        
        $errors = [
            "Uppercase" => $upperCase,
            "Lowercase" => $lowerCase,
            "5 characters" => $usernameLength
        ];

        if($type === "admin"){
            $errors['Special characters'] = $specialchars;
        }
        return $errors;
    }

    private function validateAdminPassword(array $password) : bool {
  
        foreach (array_keys($password) as $hasType){
            $status = $hasType ? true : false;
        }
        return $status;
    }

    private function checkAdminPassword(string $newPassword) : array {
        $uppercase = preg_match('/[A-Z]/', $newPassword);
        $lowercase = preg_match('/[a-z]/', $newPassword);
        $specialchars = preg_match('/[^A-Za-z0-9]/', $newPassword);
        $numericVal = preg_match('/[0-9]/', $newPassword);

       return [
            "Uppercase" => $uppercase,
            "Lowercase" => $lowercase,
            "Special characters" => $specialchars,
            "Numeric value" => $numericVal
       ];
    }

    private function changeImage(string $id, $image = null) : bool {
        if($image === null){
            return false;
        }

        $fileName = $image['name'];
        $fileSize = $image['size'];
        $fileTmpName = $image['tmp_name'];
        $fileError = $image['error'];
        $fileExt = explode('.', $fileName);
        $fileActualExt = strtolower(end($fileExt));
        $fileNameNew = uniqid('', true) . "." . $fileActualExt;
        $targetDirectory = 'C:/xampp/htdocs/GreekMyth/img/admin/' . $fileNameNew; 
        
        $allowed = array("jpeg", "png", "jpg");
    
        if ($fileError !== UPLOAD_ERR_OK) {
            $this->errors['imageEdit'] = 'Error Uploading Image!';
        }
    
        if (!in_array($fileActualExt, $allowed)) {
            $this->errors['imageEdit'] = 'Invalid Extension!';
        } 
    
        if ($fileSize > 5000000) {
            $this->errors['imageEdit'] = 'Image size too big!';
        }


        if(!empty($this->errors)){
            return false;
        }

        if(!move_uploaded_file($fileTmpName, $targetDirectory)) {
            $this->errors['imageEdit'] = 'Failed to upload image';
            return false;
        }

        $this->deleteExistingImage($id);
        $sql = "UPDATE admin_users SET image_src = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param('ss', $fileNameNew, $id);
        $stmt->execute();

        return $stmt->affected_rows > 0 ? true : false;
    }

    private function deleteExistingImage(string $id) : void {
        $sql = "SELECT image_src FROM admin_users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $id);

        if(!$stmt) {
            $this->queryFailed();
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image = $row['image_src'];

        if($result->num_rows > 0){
            if($image !== null){
                unlink($this->imagePath['admins'] . $image);
            } 
        }
    }
}
?>