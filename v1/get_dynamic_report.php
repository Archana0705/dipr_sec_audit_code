<?php
/*
|----------------------------------------------------------
|  fn_report_reportdata  –  REST endpoint
|----------------------------------------------------------
|  POST  /v1/fn_report_reportdata
|  Body: { data: "<encrypted JSON>" }
|
|  Decrypts → validates → calls public.fn_report_reportdata_test(
|      typeid            INT,
|      selected_columns  TEXT[],
|      filter_conditions JSONB,
|      group_by_columns  TEXT[],
|      sort_columns      JSONB,
|      limit_rows        INT,
|      offset_rows       INT,
|      count_columns     TEXT[],
|      sum_columns       TEXT[]
|  )
|  Re‑encrypts and returns the rows along with total count.
|----------------------------------------------------------
*/

require_once '../../helper/header.php';
require_once '../../helper/log_file.php';
require_once '../../config_v2/read_database.php';

define('DATE_FORMAT', 'Y-m-d H:i:s.u');

$logPath  = '../../logs/fn_report_reportdata/';
$service  = 'v1/fn_report_reportdata';
$method   = $_SERVER['REQUEST_METHOD'];
$endpoint = $_SERVER['PHP_SELF'];
$reqTime  = date(DATE_FORMAT);

try {
    /* ── 1. Guard HTTP verb ──────────────────────────────── */
    if ($method !== 'POST') {
        http_response_code(405);
        $msg = 'Method Not Allowed';
        logMessage('error', $msg, $logPath, 0, 405,
                   $method, $endpoint, $service, $_REQUEST, $_SERVER,
                   $reqTime, date(DATE_FORMAT));
        echo json_encode(['success' => 0, 'message' => $msg]);
        exit;
    }

    /* ── 2. Decrypt & validate payload shape ─────────────── */
    if (empty($_POST['data'])) {
        throw new Exception('Missing encrypted payload');
    }
    
    // Decrypt the payload
    $p = decryptData($_POST['data']);
    if (!$p || !is_array($p)) {
        throw new Exception('Invalid or corrupted payload');
    }

    /* ── 3. Validate required fields ─────────────────────── */
    // Validate selected_columns (must be non-empty array of strings)
    if (
        !isset($p['selected_columns']) ||
        !is_array($p['selected_columns']) ||
        count(array_filter($p['selected_columns'])) === 0
    ) {
        throw new Exception('selected_columns must be a non-empty array');
    }

    // Validate count_columns (if provided, must be array)
    if (isset($p['count_columns']) && !is_array($p['count_columns'])) {
        throw new Exception('count_columns must be an array');
    }

    // Validate sum_columns (if provided, must be array)
    if (isset($p['sum_columns']) && !is_array($p['sum_columns'])) {
        throw new Exception('sum_columns must be an array');
    }

    /* ── 4. Helpers ──────────────────────────────────────── */
    // Convert PHP array to Postgres text[] literal
    $pgArray = function (array $arr): string {
        $escaped = array_map(
            fn($s) => '"' . str_replace('"', '\"', trim($s)) . '"',
            $arr
        );
        return '{' . implode(',', $escaped) . '}';
    };

    // Convert assoc-array/object to jsonb, else NULL
    $toJsonb = function ($val): ?string {
        if (empty($val)) {
            return null;
        }
        if (is_array($val) && array_values($val) === $val) {
            throw new Exception('filter_conditions & sort_columns must be JSON objects, not arrays');
        }
        
        // Handle filter_conditions: convert array values to single values or comma-separated for IN clauses
        if (is_array($val)) {
            $processedVal = [];
            foreach ($val as $key => $value) {
                if (is_array($value) && count($value) === 1) {
                    $processedVal[$key] = $value[0];
                } elseif (is_array($value)) {
                    $processedVal[$key] = implode(',', $value); // Convert array to comma-separated string
                } else {
                    $processedVal[$key] = $value;
                }
            }
            $val = $processedVal;
        }
        
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    };
    
    /* ── 5. Build parameter array ────────────────────────── */
    $params = [
        ':typeid'            => (int)($p['typeid']),
        ':selected_columns'  => $pgArray($p['selected_columns']),
        ':filter_conditions' => $toJsonb($p['filter_conditions'] ?? null),
        ':group_by_columns'  => isset($p['group_by_columns']) && !is_null($p['group_by_columns']) && !empty($p['group_by_columns'])
                                    ? $pgArray($p['group_by_columns'])
                                    : null,
        ':sort_columns'      => $toJsonb($p['sort_columns'] ?? null),
        ':limit_rows'        => isset($p['limit_rows'])  ? (int)$p['limit_rows']  : null,
        ':offset_rows'       => isset($p['offset_rows']) ? (int)$p['offset_rows'] : null,
        ':count_columns'     => isset($p['count_columns']) && !empty($p['count_columns'])
                                    ? $pgArray($p['count_columns'])
                                    : null,
        ':sum_columns'       => isset($p['sum_columns']) && !empty($p['sum_columns'])
                                    ? $pgArray($p['sum_columns'])
                                    : null,
        ':distinct_count_columns'       => isset($p['distinct_count_columns']) && !is_null($p['distinct_count_columns']) && !empty($p['distinct_count_columns'])
                                    ? $pgArray($p['distinct_count_columns'])
                                    : null
    ];

    /* ── 6. Prepare & run data query ─────────────────────── */
    $sql = <<<SQL
SELECT * FROM public.fn_report_reportdata_test(
    :typeid,
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
                : (in_array($key, [':typeid', ':limit_rows', ':offset_rows'])
                    ? PDO::PARAM_INT
                    : PDO::PARAM_STR)
        );
    }

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    /* ── 7. Process result and respond ───────────────────── */
    $respTime = date(DATE_FORMAT);
    http_response_code(200);

 
        $result_data = json_decode($result['fn_report_reportdata_test'], true);
       if ($result_data['data']) {    

            logMessage('info', 'Report data and count fetched', $logPath, 1, 200,
                       $method, $endpoint, $service, $_REQUEST, $_SERVER,
                       $reqTime, $respTime);

            echo json_encode([
                'success' => 1,
                'message' => 'Report data and count fetched',
                'data'    => encrypt($result_data['data']),
                'total_count' => (int)$result_data['total_count']
            ]);
       
    } else {
        logMessage('info', 'No data found', $logPath, 0, 200,
                   $method, $endpoint, $service, $_REQUEST, $_SERVER,
                   $reqTime, $respTime);

        echo json_encode([
            'success' => 0,
            'message' => 'No data found',
            'data'    => [],
            'total_count' => 0
        ]);
    }

} catch (Throwable $e) {

    http_response_code(400);
    $respTime = date(DATE_FORMAT);
    logMessage('error', $e->getMessage(), $logPath, 0, 400,
               $method, $endpoint, $service, $_REQUEST, $_SERVER,
               $reqTime, $respTime);

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