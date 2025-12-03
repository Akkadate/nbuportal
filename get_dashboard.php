<?php
require 'auth.php'; 

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$host = "localhost";
$port = "5432";
$dbname = "nbu_portal";
$user = "postgres";
$password = "Tct85329$";

try {
    $dbconn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    
    // ข้อมูล User ปัจจุบัน
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];
    $userDeptId = $_SESSION['user_dept_id'] ?? 0; // ต้องเพิ่ม session นี้ตอน login (check_db.php)

    // 1. Favorites (เหมือนเดิม)
    $favQuery = "SELECT app_id FROM user_favorites WHERE user_id = $1";
    $favResult = pg_query_params($dbconn, $favQuery, array($userId));
    $favorites = [];
    if ($favResult) {
        while ($row = pg_fetch_assoc($favResult)) $favorites[] = (int)$row['app_id'];
    }

    // 2. Apps + Categories + Permission Check
    $roleFilter = ($userRole == 'student') ? "('all', 'student')" : "('all', 'staff', 'admin')";
    
    // SQL Query: ซับซ้อนขึ้นเล็กน้อยเพื่อเช็คสิทธิ์
    // เลือก App ที่ Role ตรง และ (ไม่มีการจำกัดหน่วยงาน หรือ หน่วยงานตรงกับ User)
    $appQuery = "
        SELECT a.*, c.category_name, c.display_order 
        FROM applications a 
        LEFT JOIN categories c ON a.category_id = c.category_id 
        WHERE a.is_active = TRUE 
          AND a.required_role IN $roleFilter
          AND (
              -- เงื่อนไข 1: ไม่มีการระบุหน่วยงานเฉพาะ (เป็น App ทั่วไป)
              NOT EXISTS (SELECT 1 FROM app_department_permissions WHERE app_id = a.app_id)
              OR 
              -- เงื่อนไข 2: มีการระบุ และ User อยู่ในหน่วยงานนั้น
              EXISTS (SELECT 1 FROM app_department_permissions WHERE app_id = a.app_id AND dept_id = $1)
          )
        ORDER BY c.display_order ASC, a.app_id ASC
    ";
                 
    $appResult = pg_query_params($dbconn, $appQuery, array($userDeptId));
    
    if (!$appResult) {
        throw new Exception("Error fetching apps");
    }

    $apps = [];
    while ($row = pg_fetch_assoc($appResult)) {
        $row['app_id'] = (int)$row['app_id'];
        $row['category_id'] = (int)$row['category_id'];
        if (empty($row['category_name'])) $row['category_name'] = 'General / อื่นๆ';
        $apps[] = $row;
    }

    echo json_encode(['status' => 'success', 'favorites' => $favorites, 'apps' => $apps]);
    pg_close($dbconn);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>