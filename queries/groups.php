<?php
require_once('../api/apiStatus.php');
class Groups extends Api {
    public function __construct(){
        $this->conn = $this->connect();
    }

    public function getAllGroups() : string {
        
        $sql = $this->getAllGroupsQuery();
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
        $sql = $this->getGroupQuery();
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

    private function getAllGroupsQuery() : string {
        return "SELECT greeks.*, 
        CASE 
            WHEN greeks.creator = 'Default'
            THEN users.username = NULL
            ELSE users.username 
        END AS username, 
        COUNT(users.user_id) AS total_people FROM greeks
        LEFT JOIN user_groups ON greeks.greek_id = user_groups.greek_id
        LEFT JOIN users ON user_groups.user_id = users.user_id
        GROUP BY greeks.greek_id";
    }

    private function getGroupQuery() : string {
        return "SELECT greeks.*, 
        CASE 
            WHEN greeks.creator = 'Default'
            THEN users.username = NULL
            ELSE users.username 
        END AS username, 
        COUNT(users.user_id) AS total_people FROM greeks
        LEFT JOIN user_groups ON greeks.greek_id = user_groups.greek_id
        LEFT JOIN users ON user_groups.user_id = users.user_id
        WHERE greeks.greek_id = ?
        GROUP BY greeks.greek_id";
    }

    public function changePermissionGroup(string $id, string $type) : string {
        $status = $type == "enable" ? 1 : 0;
        $sql = "UPDATE greeks SET status = $status WHERE greek_id = ?";
        $stmt = $this->conn->prepare($sql);

        if(!$stmt){
            return $this->queryFailed();
        } 
        $stmt->bind_param('s', $id);
        return $stmt->execute() ? $this->editedResource() : $this->notFound();
    } 

    public function deleteGroup(string $id) : string {
        $imageUrl = $this->getGroupImageUrl($id);
        if ($this->deleteGroupById($id)) {
            if (!empty($imageUrl)) {
                unlink($this->imagePath['gods'] . $imageUrl);
            }
            return $this->deletedResource();
        } else {
            return $this->notFound();
        }
    }

    private function deleteGroupById(string $id): bool {
        $sql = "DELETE FROM greeks WHERE greek_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $id);
        return $stmt->execute();
    }

    private function getGroupImageUrl(string $id): ?string {
        $sql = "SELECT image_url FROM greeks WHERE greek_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['image_url'] ?? null;
        }
        return null;
    }
}
?>