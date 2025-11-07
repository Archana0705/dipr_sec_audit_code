<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
require_once('../helper/header.php');
require_once('../helper/db/dipr_read.php');
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
session_start();
//  $stmtTruncate = $dipr_read_db->prepare("TRUNCATE TABLE user_sessions");
//     $stmtTruncate->execute();
function respondServerError($message = "Internal server error", $httpCode = 500, $exception = null)
{
    if ($exception instanceof Exception) {
        error_log("DB ERROR: " . $exception->getMessage());
    }
    http_response_code($httpCode);
    echo json_encode(["success" => 0, "message" => $exception->getMessage()]);
    exit;
}
function checkRateLimit($ip, $maxRequests = 1, $windowSeconds = 10)
{
    $key = "rate_limit_" . md5($ip);
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    // Keep only requests within the time window
    $_SESSION[$key] = array_filter($_SESSION[$key], fn($t) => ($t > $now - $windowSeconds));
    $_SESSION[$key][] = $now;

    if (count($_SESSION[$key]) > $maxRequests) {
        http_response_code(429); // Too Many Requests
        echo json_encode(["success" => 0, "message" => "Rate limit exceeded. Try later."]);
        exit;
    }
}
checkRateLimit($_SERVER['REMOTE_ADDR']);
function containsForbiddenPattern($value, &$found = null)
{
    $found = [];
    if (preg_match('/[<>&\'"]/', $value)) {
        $found[] = 'forbidden characters < > & \' "';
    }

    $patterns = [
        '/<\s*script\b/i' => '<script>',
        '/<\s*style\b/i' => '<style>',
        '/on\w+\s*=/i' => 'event_handler (onclick, onerror, etc.)',
        '/style\s*=/i' => 'style attribute',
        '/javascript\s*:/i' => 'javascript: URI',
        '/data\s*:/i' => 'data: URI',
        '/expression\s*\(/i' => 'CSS expression()',
        '/url\s*\(\s*["\']?\s*javascript\s*:/i' => 'url(javascript:...)',
        '/<\s*iframe\b/i' => '<iframe>',
        '/<\s*svg\b/i' => '<svg>',
        '/<\s*img\b[^>]*on\w+/i' => 'img with on* handler',
        '/<\s*meta\b/i' => '<meta>',
        '/<\/\s*script\s*>/i' => '</script>',
    ];

    foreach ($patterns as $pat => $desc) {
        if (preg_match($pat, $value)) {
            $found[] = $desc;
        }
    }
    return !empty($found);
}
function validateInputRecursive($data, &$badFields, $parentKey = '')
{
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            $keyName = $parentKey === '' ? $k : ($parentKey . '.' . $k);
            validateInputRecursive($v, $badFields, $keyName);
        }
        return;
    }
    if (!is_string($data))
        return;

    $value = $data;
    $found = [];
    if (containsForbiddenPattern($value, $found)) {
        $badFields[$parentKey] = $found;
    }
}
$inputData = file_get_contents("php://input");
$data = json_decode($inputData, true);
if (!$data && !empty($_POST)) {
    $data = $_POST;
}
if (isset($data['data'])) {
    $data = decryptData($data['data']);
}
// $encryptedData = $data['data'] ?? null;
// if (!$encryptedData) {
//     http_response_code(400);
//      exit;
// }

if (empty($data['action'])) {
    http_response_code(400);
    exit;
}
$badFields = [];
validateInputRecursive($data, $badFields);

if (!empty($badFields)) {
    $messages = [];
    foreach ($badFields as $field => $reasons) {
        $messages[] = "$field: " . implode(', ', (array) $reasons);
    }
    http_response_code(400);
    // echo json_encode([
    //     "success" => 0,
    //     "message" => "Invalid input detected (possible HTML/CSS/JS injection).",
    //     "details" => $messages
    // ]);
    exit;
}
$action = $data['action'] ?? null;
$request_id = $data['request_id'] ?? null;
unset($data['request_id']);
$limit = isset($data['limit']) && $data['limit'] !== null ? max(1, (int) $data['limit']) : null;
$offset = $limit !== null ? (isset($data['offset']) ? max(0, (int) $data['offset']) : 0) : null;
$search = $data['search'] ?? null;
$search_key = $data['search_key'] ?? null;

switch ($action) {

    case 'function_call':
        $functionName = $data['function_name'] ?? null;
        $params = $data['params'] ?? [];
        $columns = $data['columns'] ?? '*';
        $limit = isset($data['limit']) ? (int) $data['limit'] : 10;
        $offset = isset($data['offset']) ? (int) $data['offset'] : 0;

        if (!$functionName) {
            echo json_encode(["success" => 0, "message" => "Missing function_name"]);
            exit;
        }

        try {
            // Base SQL query
            $paramPlaceholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($params)));
            $sql = "SELECT $columns FROM $functionName($paramPlaceholders) LIMIT :limit OFFSET :offset";

            $stmt = $dipr_read_db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 🔹 Count total records (for pagination)
            $countSql = "SELECT COUNT(*) AS total_count FROM $functionName($paramPlaceholders)";
            $countStmt = $dipr_read_db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            $countStmt->execute();
            $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
            $totalCount = $countRow['total_count'] ?? 0;

            echo json_encode([
                "success" => 1,
                "message" => "Data retrieved successfully",
                "data" => encrypt($rows),
                "pagination" => [
                    "limit" => $limit,
                    "offset" => $offset,
                    "total" => (int) $totalCount
                ]
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["success" => 0, "message" => "Database error", "error" => $e->getMessage()]);
            exit;
        }


        break;

    case 'add_user':

        // Your code for 'add_user' action goes here
        break;




    default:
        http_response_code(400);
}