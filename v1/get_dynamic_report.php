<?php
/*
|----------------------------------------------------------
|  fn_report_reportdata  –  REST endpoint
|----------------------------------------------------------
|  POST  /v1/fn_report_reportdata
|  Body: { data: "<encrypted JSON>" }
|
|  Decrypts → validates → calls public.get_applied_students_dynamic(
|      _table_name          TEXT,
|      selected_columns     TEXT[],
|      filter_conditions    JSONB,
|      group_by_columns     TEXT[],
|      sort_columns         JSONB,
|      limit_rows           INT,
|      offset_rows          INT,
|      count_columns        TEXT[],
|      sum_columns          TEXT[],
|      distinct_count_cols  TEXT[]
|  )
|  Re-encrypts and returns the rows along with total count.
|----------------------------------------------------------
*/

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

define('DATE_FORMAT', 'Y-m-d H:i:s.u');

$logPath  = '../../logs/fn_report_reportdata/';
$service  = 'v1/fn_report_reportdata';
$method   = $_SERVER['REQUEST_METHOD'];
$endpoint = $_SERVER['PHP_SELF'];
$reqTime  = date(DATE_FORMAT);

try {
    /* ── 1. Guard HTTP verb ─────────────────────────────── */
    if ($method !== 'POST') {
        http_response_code(405);
        $msg = 'Method Not Allowed';
        logMessage('error', $msg, $logPath, 0, 405, $method, $endpoint, $service, $_REQUEST, $_SERVER, $reqTime, date(DATE_FORMAT));
        echo json_encode(['success' => 0, 'message' => $msg]);
        exit;
    }

    /* ── 2. Decrypt & validate payload shape ─────────────── */
    if (empty($_POST['data'])) {
        throw new Exception('Missing encrypted payload');
    }

    $p = decryptData($_POST['data']);
    if (!$p || !is_array($p)) {
        throw new Exception('Invalid or corrupted payload');
    }

    /* ── 3. Validate required fields ─────────────────────── */
    if (empty($p['_table_name'])) {
        throw new Exception('_table_name is required');
    }

    if (!isset($p['selected_columns']) || !is_array($p['selected_columns']) || count(array_filter($p['selected_columns'])) === 0) {
        throw new Exception('selected_columns must be a non-empty array');
    }

    if (isset($p['count_columns']) && !is_array($p['count_columns'])) {
        throw new Exception('count_columns must be an array');
    }

    if (isset($p['sum_columns']) && !is_array($p['sum_columns'])) {
        throw new Exception('sum_columns must be an array');
    }

    /* ── 4. Helper functions ─────────────────────────────── */
    $pgArray = function (array $arr): string {
        $escaped = array_map(fn($s) => '"' . str_replace('"', '\"', trim($s)) . '"', $arr);
        return '{' . implode(',', $escaped) . '}';
    };

    $toJsonb = function ($val): ?string {
        if (empty($val)) return null;
        if (is_array($val) && array_values($val) === $val) {
            throw new Exception('filter_conditions & sort_columns must be JSON objects, not arrays');
        }

        // Normalize values for filter_conditions
        if (is_array($val)) {
            $processedVal = [];
            foreach ($val as $key => $value) {
                if (is_array($value) && count($value) === 1) {
                    $processedVal[$key] = $value[0];
                } elseif (is_array($value)) {
                    $processedVal[$key] = implode(',', $value);
                } else {
                    $processedVal[$key] = $value;
                }
            }
            $val = $processedVal;
        }
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    };

    /* ── 5. Build parameters ─────────────────────────────── */
    $params = [
        ':_table_name' => trim($p['_table_name']),
        ':selected_columns' => $pgArray($p['selected_columns']),
        ':filter_conditions' => $toJsonb($p['filter_conditions'] ?? null),
        ':group_by_columns' => !empty($p['group_by_columns']) ? $pgArray($p['group_by_columns']) : null,
        ':sort_columns' => $toJsonb($p['sort_columns'] ?? null),
        ':limit_rows' => isset($p['limit_rows']) ? (int) $p['limit_rows'] : null,
        ':offset_rows' => isset($p['offset_rows']) ? (int) $p['offset_rows'] : null,
        ':count_columns' => !empty($p['count_columns']) ? $pgArray($p['count_columns']) : null,
        ':sum_columns' => !empty($p['sum_columns']) ? $pgArray($p['sum_columns']) : null,
        ':distinct_count_columns' => !empty($p['distinct_count_columns']) ? $pgArray($p['distinct_count_columns']) : null
    ];

    /* ── 6. SQL call ─────────────────────────────────────── */
    $sql = <<<SQL
SELECT * FROM public.get_applied_students_dynamic(
    :_table_name,
    :selected_columns,
    :filter_conditions,
    :group_by_columns,
    :sort_columns,
    :limit_rows,
    :offset_rows,
    :count_columns,
    :sum_columns,
    :distinct_count_columns
)
SQL;

    $stmt = $read_db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue(
            $key,
            $val,
            is_null($val)
                ? PDO::PARAM_NULL
                : (in_array($key, [':limit_rows', ':offset_rows']) ? PDO::PARAM_INT : PDO::PARAM_STR)
        );
    }

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    /* ── 7. Handle result ────────────────────────────────── */
    $respTime = date(DATE_FORMAT);
    http_response_code(200);

    $result_data = json_decode($result['get_applied_students_dynamic'], true);

    if (!empty($result_data['data'])) {
        logMessage(
            'info',
            'Report data and count fetched',
            $logPath,
            1,
            200,
            $method,
            $endpoint,
            $service,
            $_REQUEST,
            $_SERVER,
            $reqTime,
            $respTime
        );

        echo json_encode([
            'success' => 1,
            'message' => 'Report data and count fetched',
            'data' => encrypt($result_data['data']),
            'total_count' => (int) ($result_data['total_count'] ?? 0)
        ]);
    } else {
        logMessage(
            'info',
            'No data found',
            $logPath,
            0,
            200,
            $method,
            $endpoint,
            $service,
            $_REQUEST,
            $_SERVER,
            $reqTime,
            $respTime
        );

        echo json_encode([
            'success' => 0,
            'message' => 'No data found',
            'data' => [],
            'total_count' => 0
        ]);
    }

} catch (Throwable $e) {
    http_response_code(400);
    $respTime = date(DATE_FORMAT);

    logMessage(
        'error',
        $e->getMessage(),
        $logPath,
        0,
        400,
        $method,
        $endpoint,
        $service,
        $_REQUEST,
        $_SERVER,
        $reqTime,
        $respTime
    );

    echo json_encode([
        'success' => 0,
        'message' => $e->getMessage(),
        'data' => [],
        'total_count' => 0
    ]);
} finally {
    $read_db = null;
}
?>
