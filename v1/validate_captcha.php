<?php
require_once('../helper/header.php');
$data = decryptData($_POST['data']);
// If session_id is sent, resume that session
if (isset($data['session_id'])) {
    session_id($data['session_id']);
}
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['captcha'])) {
    echo json_encode(["success" => false, "message" => "CAPTCHA expired or missing"]);
    exit;
}

$user_input = $data['captcha'] ?? '';
if (strcasecmp($user_input, $_SESSION['captcha']) === 0) {
    echo json_encode(["success" => true, "message" => "Captcha matched", "data" => encrypt($data['request_id'])]);
} else {
    echo json_encode(["success" => false, "message" => "Captcha does not match"]);
}
