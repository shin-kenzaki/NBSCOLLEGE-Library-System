<?php
include '../db.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        if (isset($_POST['bulk_update']) && isset($_POST['combinations'])) {
            $combinations = json_decode(json_encode($_POST['combinations']), true);
            $processedPublishers = [];
            
            foreach ($combinations as $combo) {
                $place = $conn->real_escape_string($combo['place']);
                $year = intval($combo['year']);
                $publisher = $conn->real_escape_string($combo['publisher']);
                $publications = array_map('intval', $combo['publications']);
                
                // Check if publisher already exists with same name
                $checkPublisher = $conn->query("SELECT id FROM publishers WHERE LOWER(publisher) = LOWER('$publisher')");
                
                if ($checkPublisher && $checkPublisher->num_rows > 0) {
                    // Use existing publisher
                    $publisherRow = $checkPublisher->fetch_assoc();
                    $publisherId = $publisherRow['id'];
                    
                    // Update place if it's different
                    $updatePublisher = $conn->query("UPDATE publishers SET place = '$place' WHERE id = $publisherId");
                    if (!$updatePublisher) {
                        throw new Exception("Failed to update publisher place");
                    }
                } else if ($combo['publisherId'] && !isset($processedPublishers[$combo['publisherId']])) {
                    // Update existing publisher (only for first combination)
                    $publisherId = $combo['publisherId'];
                    $updatePublisher = $conn->query("UPDATE publishers SET publisher = '$publisher', place = '$place' WHERE id = $publisherId");
                    if (!$updatePublisher) {
                        throw new Exception("Failed to update publisher");
                    }
                    $processedPublishers[$publisherId] = true;
                } else {
                    // Create new publisher as no matching publisher found
                    $insertPublisher = $conn->query("INSERT INTO publishers (publisher, place) VALUES ('$publisher', '$place')");
                    if (!$insertPublisher) {
                        throw new Exception("Failed to create new publisher");
                    }
                    $publisherId = $conn->insert_id;
                }

                // Update publications for this combination
                $publicationIds = implode(',', $publications);
                $updatePublications = $conn->query(
                    "UPDATE publications 
                     SET publisher_id = $publisherId, publish_date = $year
                     WHERE id IN ($publicationIds)"
                );

                if (!$updatePublications) {
                    throw new Exception("Failed to update publications");
                }
            }
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'All updates completed successfully';
        } else {
            // Handle single updates
            if (isset($_POST['updates']) && is_array($_POST['updates'])) {
                $success = true;
                $message = '';
                
                foreach ($_POST['updates'] as $update) {
                    $id = intval($update['id']);
                    $place = isset($update['place']) ? $conn->real_escape_string($update['place']) : null;
                    $publisher = isset($update['publisher']) ? $conn->real_escape_string($update['publisher']) : null;
                    $year = isset($update['year']) ? intval($update['year']) : null;
        
                    if ($publisher !== null) {
                        // Check if publisher already exists (case-insensitive)
                        $checkPublisher = $conn->query("SELECT id FROM publishers WHERE LOWER(publisher) = LOWER('$publisher')");
                        if ($checkPublisher && $checkPublisher->num_rows > 0) {
                            $publisherRow = $checkPublisher->fetch_assoc();
                            $existingPublisherId = $publisherRow['id'];
                            // Update place if needed and assign existing publisher
                            if ($place !== null) {
                                $updatePublisher = $conn->query("UPDATE publishers SET place = '$place' WHERE id = $existingPublisherId");
                                if (!$updatePublisher) {
                                    $success = false;
                                    break;
                                }
                            }
                            $updatePubQuery = "UPDATE publications SET publisher_id = $existingPublisherId, publish_date = $year WHERE id = $id";
                            if (!$conn->query($updatePubQuery)) {
                                $success = false;
                                break;
                            }
                            continue;
                        }
                    }
        
                    // Fall back to normal update if no duplicate publisher is found
                    $updateFields = [];
                    if ($place !== null) $updateFields[] = "place = '$place'";
                    if ($publisher !== null) $updateFields[] = "publisher = '$publisher'";
                    if (!empty($updateFields)) {
                        $updateQuery = "UPDATE publishers SET " . implode(', ', $updateFields) . 
                                     " WHERE id = (SELECT publisher_id FROM publications WHERE id = $id)";
                        if (!$conn->query($updateQuery)) {
                            $success = false;
                            break;
                        }
                    }
        
                    // Always update publication's year if provided
                    if ($year !== null) {
                        $updateQuery = "UPDATE publications SET publish_date = $year WHERE id = $id";
                        if (!$conn->query($updateQuery)) {
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
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = 'Update failed: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
exit;
