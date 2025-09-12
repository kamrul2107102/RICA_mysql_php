<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = get_json_input();
        
        if (!isset($data['username']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            exit();
        }
        
        $username = sanitize_input($data['username']);
        $password = sanitize_input($data['password']);
        
        // Add small delay to prevent timing attacks (optional but recommended)
        usleep(rand(100000, 300000)); // 100-300ms delay
        
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = get_client_ip();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Set session cookie parameters for security
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires' => time() + 86400, // 24 hours
                    'path' => $cookieParams['path'],
                    'domain' => $cookieParams['domain'],
                    'secure' => true, // Requires HTTPS
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );
            
            error_log("Successful admin login: $username from IP: " . get_client_ip());
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful',
                'user' => $username,
                'session_id' => session_id()
            ]);
            
        } else {
            // Log failed attempts
            error_log("Failed login attempt: $username from IP: " . get_client_ip());
            
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'error' => 'Invalid credentials',
                'hint' => 'Check username and password'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>