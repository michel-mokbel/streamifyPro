<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

function json_error(int $code, string $message): void {
  http_response_code($code);
  echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

$action = isset($_GET['action']) ? strtolower(trim((string)$_GET['action'])) : '';
if (!in_array($action, ['login', 'signup'], true)) {
  json_error(400, 'Unknown action. Use ?action=login or ?action=signup');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

// Use mysqli connection from config.php ($conn)
if (!isset($conn) || !($conn instanceof mysqli)) {
  json_error(500, 'Database connection failed');
}

if ($action === 'signup') {
  $username = trim((string)($input['username'] ?? ''));
  $email = trim((string)($input['email'] ?? ''));
  $password = (string)($input['password'] ?? '');
  if ($username === '' || $email === '' || $password === '') {
    json_error(400, 'Missing username, email, or password');
  }
  $hash = password_hash($password, PASSWORD_BCRYPT);
  $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
  if (!$stmt) json_error(500, 'Signup failed');
  $stmt->bind_param('sss', $username, $email, $hash);
  if (!$stmt->execute()) {
    if ($conn->errno === 1062) { // duplicate
      json_error(409, 'Username or email already exists');
    }
    json_error(500, 'Signup failed');
  }
  $_SESSION['user'] = ['id' => $stmt->insert_id, 'username' => $username, 'email' => $email];
  echo json_encode(['ok' => true, 'user' => $_SESSION['user']]);
  exit;
}

if ($action === 'login') {
  $username = trim((string)($input['username'] ?? ''));
  $password = (string)($input['password'] ?? '');
  if ($username === '' || $password === '') {
    json_error(400, 'Missing username or password');
  }
  $stmt = $conn->prepare('SELECT user_id, username, email, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1');
  if (!$stmt) json_error(500, 'Login failed');
  $stmt->bind_param('ss', $username, $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $user = $res ? $res->fetch_assoc() : null;
  if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error(401, 'Invalid credentials');
  }
  $_SESSION['user'] = ['id' => (int)$user['user_id'], 'username' => $user['username'], 'email' => $user['email']];
  echo json_encode(['ok' => true, 'user' => $_SESSION['user']]);
  exit;
}
?>


