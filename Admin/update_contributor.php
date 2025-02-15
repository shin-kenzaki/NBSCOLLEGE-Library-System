<?php
include '../db.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

function findExistingWriter($conn, $firstname, $middleInit, $lastname) {
    $firstname = $conn->real_escape_string($firstname);
    $middleInit = $conn->real_escape_string($middleInit);
    $lastname = $conn->real_escape_string($lastname);
    
    $query = "SELECT id FROM writers 
              WHERE LOWER(firstname) = LOWER('$firstname') 
              AND LOWER(middle_init) = LOWER('$middleInit') 
              AND LOWER(lastname) = LOWER('$lastname')";
    
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['bulk_update']) && isset($_POST['combinations'])) {
        $conn->begin_transaction();
        try {
            foreach ($_POST['combinations'] as $combination) {
                $firstname = $conn->real_escape_string($combination['firstname']);
                $middleInit = $conn->real_escape_string($combination['middle_init']);
                $lastname = $conn->real_escape_string($combination['lastname']);
                $role = $conn->real_escape_string($combination['role']);
                $contributors = $combination['contributors'];

                // Check if writer already exists
                $existingWriterId = findExistingWriter($conn, $firstname, $middleInit, $lastname);
                
                if ($existingWriterId) {
                    $writerId = $existingWriterId;
                } else if ($combination['writerId']) {
                    // Update existing writer for first combination
                    $updateWriter = "UPDATE writers 
                                   SET firstname = '$firstname', 
                                       middle_init = '$middleInit', 
                                       lastname = '$lastname' 
                                   WHERE id = {$combination['writerId']}";
                    $conn->query($updateWriter);
                    $writerId = $combination['writerId'];
                } else {
                    // Insert new writer only if it doesn't exist
                    $insertWriter = "INSERT INTO writers (firstname, middle_init, lastname) 
                                   VALUES ('$firstname', '$middleInit', '$lastname')";
                    $conn->query($insertWriter);
                    $writerId = $conn->insert_id;
                }

                // Update contributors for this combination
                $contributorIds = implode(',', array_map('intval', $contributors));
                $updateContributors = "UPDATE contributors 
                                     SET writer_id = $writerId,
                                         role = '$role'
                                     WHERE id IN ($contributorIds)";
                $conn->query($updateContributors);
            }

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'All records updated successfully';
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else if (isset($_POST['updates']) && is_array($_POST['updates'])) {
        $success = true;
        
        foreach ($_POST['updates'] as $update) {
            $id = intval($update['id']);
            $firstname = isset($update['firstname']) ? $conn->real_escape_string($update['firstname']) : null;
            $middle_init = isset($update['middle_init']) ? $conn->real_escape_string($update['middle_init']) : null;
            $lastname = isset($update['lastname']) ? $conn->real_escape_string($update['lastname']) : null;
            $role = isset($update['role']) ? $conn->real_escape_string($update['role']) : null;

            // If writer name fields are provided, check for an existing writer
            if ($firstname !== null && $lastname !== null) {
                $existingWriterId = findExistingWriter($conn, $firstname, $middle_init, $lastname);
                if ($existingWriterId) {
                    // Use existing writer id for the contributor and update role
                    $updateContributor = "UPDATE contributors SET writer_id = $existingWriterId, role = '$role' WHERE id = $id";
                    if (!$conn->query($updateContributor)) {
                        $success = false;
                        break;
                    }
                    // Skip updating writers table as record already exists
                    continue;
                }
                // Else update the writer with new values if no duplicate is found
                $updateFields = [];
                if ($firstname !== null) $updateFields[] = "firstname = '$firstname'";
                if ($middle_init !== null) $updateFields[] = "middle_init = '$middle_init'";
                if ($lastname !== null) $updateFields[] = "lastname = '$lastname'";
                if (!empty($updateFields)) {
                    $updateQuery = "UPDATE writers SET " . implode(', ', $updateFields) . 
                                   " WHERE id = (SELECT writer_id FROM contributors WHERE id = $id)";
                    if (!$conn->query($updateQuery)) {
                        $success = false;
                        break;
                    }
                }
            }

            // Update role if set (for cases when writer name was not modified)
            if ($role !== null) {
                $updateRoleQuery = "UPDATE contributors SET role = '$role' WHERE id = $id";
                if (!$conn->query($updateRoleQuery)) {
                    $success = false;
                    break;
                }
            }
        }

        $response['success'] = $success;
        $response['message'] = $success ? 'Updated successfully' : 'Update failed';
    } else {
        $response['message'] = 'Invalid data received';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
exit;
