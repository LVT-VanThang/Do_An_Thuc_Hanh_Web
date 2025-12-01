<?php
// Kiểm tra session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Bảo vệ: Chưa đăng nhập thì chuyển về trang login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Lấy tên trang hiện tại để tô màu menu
$trangHienTai = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị hệ thống - ABC Hotel</title>
    
    <link rel="stylesheet" href="../css/styleadmin.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

    <header class="admin-topbar">
        <div class="container">
            <div class="topbar-wrapper">
                
                <div class="admin-brand">
                    <a href="quan_ly_don.php">
                        <i class="fas fa-hotel"></i> 
                        <span>Hotel ABC</span>
                    </a>
                </div>

                <nav class="admin-menu">
                    <ul>
                        <li>
                             <a href="so_do_phong.php" class="...">
                                 <i class="fas fa-home"></i> <span>TimeLine Phòng</span>
                             </a>
                      </li>
                        <li>
                            <a href="quan_ly_don.php" class="<?php echo ($trangHienTai == 'quan_ly_don.php') ? 'active' : ''; ?>">
                                <i class="fas fa-file-invoice-dollar"></i> <span>Đơn Hàng</span>
                            </a>
                        </li>
                        <li>
                            <a href="quan_ly_phong.php" class="<?php echo ($trangHienTai == 'quan_ly_phong.php') ? 'active' : ''; ?>">
                                <i class="fa-solid fa-coins"></i> <span> Loại Phòng & Giá</span>
                            </a>
                        </li>
                        <li>
                            <a href="quan_ly_lien_he.php" class="<?php echo ($trangHienTai == 'quan_ly_lien_he.php') ? 'active' : ''; ?>">
                                <i class="fas fa-comment-dots"></i> <span>Tin Nhắn</span>
                            </a>
                        </li>
                        <li>
                             <a href="quan_ly_so_phong.php" class="...">
                                 <i class="fas fa-th-list"></i> <span>Quản lý Phòng</span>
                             </a>
                      </li>
                      <li>
                             <a href="danh_sach_dang_o.php" class="...">
                                 <i class="fas fa-door-open"></i> <span>Trả phòng</span>
                             </a>
                      </li>
                      
                    </ul>
                </nav>

                <div class="admin-profile">
                    
                   

                    <a href="logout.php" class="btn-logout" title="Đăng xuất">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

            </div>
        </div>
    </header>