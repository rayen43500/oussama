<?php
// api/auth.php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (is_logged_in()) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'status' => $_SESSION['status']
        ]
    ]);
} else {
    http_response_code(418); // Unauthenticated client error (Using 401 standard for APIs)
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée ou non connectée.'
    ]);
}
