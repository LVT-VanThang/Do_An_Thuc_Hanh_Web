<?php
// 1. Khởi động session để biết đang hủy phiên nào
session_start();

// 2. Xóa tất cả các biến trong session (như admin_logged_in, admin_user)
$_SESSION = array();

// 3. Hủy hoàn toàn phiên làm việc trên server
session_destroy();

// 4. Chuyển hướng về trang đăng nhập
header("Location: login.php");
exit;
?>