<?php 
$tieuDeTrang = "Trang chủ - Khách sạn ABC";
include '../includes/ketnoidb.php'; 
include '../includes/headerkhachhang.php'; 
?>

<main>
    <section class="banner-intro">
        
        <video autoplay muted loop playsinline class="video-bg">
            <source src="../images/videokhachsan.mp4" type="video/mp4">
            Trình duyệt của bạn không hỗ trợ video.
        </video>

        <div class="overlay"></div>
        
        <div class="intro-content">
            <h1 class="intro-title">KHÁCH SẠN ABC LUXURY</h1>
            <p class="intro-desc">
                Trải nghiệm kỳ nghỉ đẳng cấp 5 sao bên bờ biển thơ mộng.<br>
                Nơi cảm xúc thăng hoa và dịch vụ hoàn hảo.
            </p>
            
            <a href="danh_sach_phong.php" class="btn-big-cta">
                Đặt Phòng Ngay
            </a>
        </div>
    </section>

    <section class="container about-section">
        <h2 class="tieu-de-muc">Về Chúng Tôi</h2>
        <p class="about-desc">
            Tọa lạc tại vị trí đắc địa nhất thành phố, Khách sạn ABC tự hào mang đến không gian nghỉ dưỡng sang trọng với hệ thống phòng ốc hiện đại, hồ bơi vô cực, nhà hàng Á-Âu và Spa thư giãn. Chúng tôi cam kết mang lại cho quý khách những giây phút khó quên.
        </p>
        
        <div class="features-grid">
            <div class="icon-box">
                <i class="fas fa-swimming-pool feature-icon"></i>
                <p>Hồ bơi vô cực</p>
            </div>
            <div class="icon-box">
                <i class="fas fa-utensils feature-icon"></i>
                <p>Nhà hàng 5 sao</p>
            </div>
            <div class="icon-box">
                <i class="fas fa-spa feature-icon"></i>
                <p>Spa & Massage</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 50px;">
            <a href="./gioi_thieu.php" class="btn-secondary">Xem Chi Tiết Về Chúng Tôi</a>
        </div>
    </section>
</main>

<?php 
if(isset($ketNoiDb)) {
    $ketNoiDb->close();
}
include '../includes/footerkhachhang.php'; 
?>