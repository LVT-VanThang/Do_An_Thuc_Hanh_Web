<?php
session_start();
include '../includes/ketnoidb.php';

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: quan_ly_don.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $ketNoiDb->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $password_md5 = md5($password);

    $sql = "SELECT * FROM admins WHERE username = '$username' AND password = '$password_md5'";
    $result = $ketNoiDb->query($sql);

    if ($result->num_rows == 1) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        header("Location: quan_ly_don.php");
        exit;
    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Admin</title>
    <link rel="stylesheet" href="../css/styleadmin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-body">
    
    <div class="login-card">
        <div class="login-header">
            <div style="font-size: 3rem; color: #d4af37; margin-bottom: 10px;">
                <i class="fas fa-hotel"></i>
            </div>
            <h2>Khách Sạn ABC</h2>
            <p>Trang này chỉ dành cho quản trị viên</p>
        </div>

        <?php if($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" class="login-input" placeholder="Tên đăng nhập" required autocomplete="off">
            <input type="password" name="password" class="login-input" placeholder="Mật khẩu" required>
            <button type="submit" class="btn-login-submit">
                <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP
            </button>
        </form>
        
        <div style="margin-top: 20px;">
            <a href="../khachhang/index.php" style="color: #888; font-size: 0.9rem; text-decoration: underline;">
                &larr; Quay về trang chủ
            </a>
        </div>
    </div>

</body>
</html>