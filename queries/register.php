<?php
require_once('../api/apiStatus.php');
class Register extends Api {
    private $username;
    private $email;
    private $password;
    private $confirm_password;
    private $image;
    public $errors = array();

    public function __construct(string $username, string $email, string $password, string $confirm_password, $image){
        $this->conn = $this->connect();
        $this->username = $username;
        $this->password = $password;
        $this->confirm_password = $confirm_password;
        $this->email = $email;
        $this->image = $image;
    }

    public function checkFields() : void {
        if(empty($this->username) || !isset($this->username)){
            $this->errors['username'] = "Please fill the username";
        } else if($this->userExists($this->username)){
            $this->errors['username'] = "Username already exists";
        } else if($this->validateUsername($this->checkUsername($this->username))) {
            foreach($this->checkUsername($this->username) as $type => $hasType){
                if($hasType) $this->errors['usernameValid'][$type] = $type . " is required";
            }
        }

        if(empty($this->email) || !isset($this->email)){
            $this->errors['email'] = "Please fill the email";
        } else if (!$this->validateEmail($this->email)) {
            $this->errors['email'] = "Invalid email address";
        } else if($this->emailExists($this->email)) {
            $this->errors['email'] = "Email already exists";
        }

        if(empty($this->password) || !isset($this->password)){
            $this->errors['password'] = "Please fill the password";
        } else if($this->validatePassword($this->checkPassword($this->password))) {
            foreach($this->checkPassword($this->password) as $type => $hasType){
                if(!$hasType) $this->errors['passwordValid'][$type] = $type . " is required";
            }
        }

        if(empty($this->confirm_password) || !isset($this->confirm_password)) {
            $this->errors['confirm_password'] = "Please fill the confirm password";
        } else if ($this->confirm_password != $this->password) {
            $this->errors['confirm_password'] = "Password does not match";
        }

      
        if (empty($this->errors) && isset($this->image) && $this->image !== null) {
            $imageInfo = $this->checkImage();
            if (isset($imageInfo['image'])) {
                $this->errors['image'] = $imageInfo['image']; 
            } else {
                $imageName = $imageInfo['name'];
            }
        }
        

        $status = empty($this->errors) ? $this->register($imageName) : $this->regError($this->errors); 
        echo $status;
    }

    public function register(string $imageName = null) : string {
        $sql = "INSERT INTO Admin_Users (id, username, email, password, image_src)
        VALUES (UUID(), ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed();
        } else {
            $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
            $stmt->bind_param('ssss', $this->username, $this->email, $hashed_password, $imageName);
            $stmt->execute();

            $status = !$stmt ? $this->queryFailed() : $this->created();
            return $status;
        }
    }

    public function validateUsername(array $username) : bool {
        foreach(array_keys($username) as $hasType) {
            $status = $hasType ? true : false;
        }
        return $status;
    }

    public function userExists(string $username) : bool {
        $sql = "SELECT username FROM Admin_Users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return false;
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            $status = $result->num_rows > 0 ? true : false;
            return $status;
        }
    }

    public function emailExists(string $email) : bool {
        $sql = "SELECT email FROM Admin_Users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return false;
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            $status = $result->num_rows > 0 ? true : false;
            return $status;
        }
    }

    public function validateEmail(string $email) : bool {
        $status = filter_var($email, FILTER_VALIDATE_EMAIL) ? true : false;
        return $status;
    }

    public function validatePassword(array $password) : bool {
  
        foreach (array_keys($password) as $hasType){
            $status = $hasType ? true : false;
        }
        return $status;
    }

    public function checkUsername(string $username) : array {
        $specialchars = !preg_match('/[^A-Za-z0-9]/', $username);
        $usernameLength = strlen($username) < 5;
        
        return [
            "Special characters" => $specialchars,
            "5 characters" => $usernameLength
        ];
    }

    public function checkPassword(string $password) : array {
        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $specialchars = preg_match('/[^A-Za-z0-9]/', $password);
        $numericVal = preg_match('/[0-9]/', $password);

       return [
            "Uppercase" => $uppercase,
            "Lowercase" => $lowercase,
            "Special characters" => $specialchars,
            "Numeric value" => $numericVal
       ];
    }

    public function checkImage() : array {
        $fileName = $this->image['name'];
        $fileSize = $this->image['size'];
        $fileTmpName = $this->image['tmp_name'];
        $fileError = $this->image['error'];
        $fileExt = explode('.', $fileName);
        $fileActualExt = strtolower(end($fileExt));
        $fileNameNew = uniqid('', true) . "." . $fileActualExt;
        $targetDirectory = 'C:/xampp/htdocs/GreekMyth/img/admin/' . $fileNameNew; 
        
        $allowed = array("jpeg", "png", "jpg");
    
        if ($fileError !== UPLOAD_ERR_OK) {
            return ['image' => 'Error Uploading Image!'];
        }
    
        if (!in_array($fileActualExt, $allowed)) {
            return ['image' => 'Invalid Extension!'];
        } 
    
        if ($fileSize > 5000000) {
            return ['image' => 'Image size too big!'];
        }

        if(!move_uploaded_file($fileTmpName, $targetDirectory)) {
            return ['image' => 'Failed to upload image'];
        } else {
            return ['name' => $fileNameNew];
        }
       
        
    }

}

?>