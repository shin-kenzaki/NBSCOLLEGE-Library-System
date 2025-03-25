<?php
require '../db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$school_id = $data['school_id'];
$firstname = $data['firstname'];
$lastname = $data['lastname'];
$email = $data['email'];
$password = $data['password'] ?? ''; // Optional password field
$usertype = $data['usertype'] ?? ''; // Optional user type field

// Validate email domain based on user type
if (!preg_match('/@(student\.)?nbscollege\.edu\.ph$/', $email)) {
  echo json_encode(['success' => false, 'message' => 'Please input a valid school email address.']);
  exit();
} elseif ($usertype === 'Student' && !preg_match('/@student\.nbscollege\.edu\.ph$/', $email)) {
  echo json_encode(['success' => false, 'message' => 'Invalid email domain for the selected user type.']);
  exit();
} elseif (($usertype === 'Faculty' || $usertype === 'Staff') && !preg_match('/@nbscollege\.edu\.ph$/', $email)) {
  echo json_encode(['success' => false, 'message' => 'Invalid email domain for the selected user type.']);
  exit();
}

// Validate password length (if provided)
if (!empty($password) && strlen($password) < 8) {
  echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
  exit();
}

// Check if school ID already exists
$check_id_query = "SELECT school_id FROM users WHERE school_id = ?";
$stmt = $conn->prepare($check_id_query);
$stmt->bind_param("s", $school_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
  echo json_encode(['success' => false, 'message' => 'School ID is already registered!']);
  exit();
}

// Check if full name already exists
$check_name_query = "SELECT id FROM users WHERE firstname = ? AND lastname = ?";
$stmt = $conn->prepare($check_name_query);
$stmt->bind_param("ss", $firstname, $lastname);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
  echo json_encode(['success' => false, 'message' => 'A user with this name already exists!']);
  exit();
}

// Check if email already exists
$check_email_query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($check_email_query);
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
  echo json_encode(['success' => false, 'message' => 'Email address is already registered!']);
  exit();
}

echo json_encode(['success' => true]);