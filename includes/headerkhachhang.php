<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($tieuDeTrang) ? $tieuDeTrang : 'Khách sạn ABC'; ?></title>
    <link rel="stylesheet" href="../css/stylekhachhang.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="dau-trang">
        <div class="container">
            <div class="logo">
                <a href="index.php">KHÁCH SẠN ABC</a>
            </div>

            <nav class="thanh-dieu-huong">
                <ul>
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="danh_sach_phong.php">Đặt phòng</a></li>
                    <li><a href="gioi_thieu.php">Giới thiệu</a></li>
                    <li><a href="lien_he.php">Liên hệ</a></li>
                </ul>
            </nav>

            <div class="hanh-dong">
                <a href="tel:0901234567" class="hotline"><i class="fas fa-phone"></i> 090.123.4567</a>
                <a href="../admin/login.php" class="nut-dat-ngay">Đăng Nhập</a>
            </div>
        </div>
    </header>

