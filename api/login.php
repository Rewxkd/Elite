<?php
session_start();
include '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirm_password === '') {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Enter a valid email address']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    $stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $existing = $stmt->get_result();
    if ($existing && $existing->num_rows > 0) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $username, $email, $hashed_password);
        $stmt->execute();
        $user_id = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO wallets (user_id, balance, total_wagered) VALUES (?, 0.00, 0.00)');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['user_id'] = $user_id;
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
} elseif ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }

    $stmt = $conn->prepare('SELECT user_id, password FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user_query = $stmt->get_result();

    if (!$user_query || $user_query->num_rows === 0) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }

    $user = $user_query->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }

    $_SESSION['user_id'] = (int)$user['user_id'];
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
