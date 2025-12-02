<?php
// ตั้งค่า Session ให้จำได้นาน 1 วัน
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เช็คว่าไฟล์นี้ถูกเรียกโดยตรงหรือไม่ (Direct Access)
// ถ้าใช่ -> ให้ทำงานส่ง JSON กลับไป
// ถ้าไม่ใช่ (ถูก include โดย check_db.php หรือ get_dashboard.php) -> ให้อยู่เฉยๆ
if (basename($_SERVER['SCRIPT_FILENAME']) === 'auth.php') {
    
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'status' => 'authenticated',
                'user' => [
                    'name' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'role' => $_SESSION['user_role'],
                    'dept' => $_SESSION['user_dept'],
                    'avatar' => $_SESSION['user_avatar'] ?? ''
                ]
            ]);
        } else {
            echo json_encode(['status' => 'guest']);
        }
    }
    exit; // จบการทำงานเฉพาะเมื่อถูกเรียกโดยตรง
}
?>