<?php
$tieuDeTrang = "Giới thiệu - Khách sạn ABC";
include '../includes/ketnoidb.php';
include '../includes/headerkhachhang.php';
?>

<main>
    <div class="page-banner">
        <div class="page-banner-overlay"></div>
        <div class="container page-banner-content">
            <h1 class="page-title">Về Chúng Tôi</h1>
            <p class="page-subtitle">Hơn 15 năm kiến tạo những kỳ nghỉ dưỡng đẳng cấp</p>
        </div>
    </div>

    <section class="container page-padding">
        <div class="detail-wrapper">
            <div class="col-image">
                <img src="../images/hoboi.jpg" alt="Sảnh khách sạn" class="detail-img">
            </div>
            <div class="col-info desc-box">
                <h2 style="color: #d4af37; margin-bottom: 20px;">Câu chuyện của ABC Hotel</h2>
                <p>Được thành lập vào năm 2010, Khách sạn ABC tọa lạc tại vị trí "tựa sơn hướng thủy" đắc địa nhất của thành phố biển xinh đẹp. Khởi đầu từ một khách sạn boutique nhỏ, qua hơn một thập kỷ phát triển, chúng tôi đã vươn mình trở thành biểu tượng nghỉ dưỡng 5 sao hàng đầu khu vực.</p>
                <p>Với sứ mệnh mang lại "Ngôi nhà thứ hai" cho du khách, ABC Hotel không chỉ cung cấp nơi lưu trú, mà còn là nơi lưu giữ những khoảnh khắc hạnh phúc của bạn bên gia đình và người thân.</p>
            </div>
        </div>
    </section>

    <section class="services-section">
        <div class="container">
            <h2 class="tieu-de-muc" style="margin-bottom: 50px;">Dịch Vụ Đẳng Cấp</h2>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-img-box">
                        <img src="../images/massage.jpg" class="service-img" alt="Spa">
                    </div>
                    <div class="service-content">
                        <h3 class="service-title">Spa & Sức khỏe</h3>
                        <p class="service-desc">Tái tạo năng lượng tại ABC Spa với các liệu trình massage trị liệu cổ truyền kết hợp thảo dược thiên nhiên.</p>
                    </div>
                </div>

                <div class="service-card">
                    <div class="service-img-box">
                        <img src="../images/amthucaau.jpg" class="service-img" alt="Nhà hàng">
                    </div>
                    <div class="service-content">
                        <h3 class="service-title">Ẩm thực Á - Âu</h3>
                        <p class="service-desc">Thưởng thức tinh hoa ẩm thực tại nhà hàng The Ocean View với nguyên liệu tươi ngon nhất trong ngày.</p>
                    </div>
                </div>

                <div class="service-card">
                    <div class="service-img-box">
                        <img src="../images/phonggym.jpg" class="service-img" alt="Phòng Gym">
                    </div>
                    <div class="service-content">
                        <h3 class="service-title">Giải trí & Thể thao</h3>
                        <p class="service-desc">Đắm mình trong làn nước xanh mát tại hồ bơi vô cực và rèn luyện sức khỏe tại phòng Gym hiện đại.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container page-padding">
        <div class="zigzag-wrapper">
            <div class="col-info">
                <h2 style="color: #333; margin-bottom: 15px;">Tinh Hoa Ẩm Thực</h2>
                <p style="color: #555; line-height: 1.8;">Không chỉ là nơi nghỉ dưỡng, ABC Hotel còn là điểm đến lý tưởng cho những tín đồ ẩm thực. Nhà hàng buffet sáng phục vụ hơn 50 món ăn đa dạng.</p>
            </div>
            <div class="col-image">
                <img src="../images/amthuc.jpg" class="detail-img">
            </div>
        </div>

        <div class="zigzag-wrapper reverse">
            <div class="col-info">
                <h2 style="color: #333; margin-bottom: 15px;">Hội Nghị & Sự Kiện</h2>
                <p style="color: #555; line-height: 1.8; margin-bottom: 20px;">Hệ thống phòng họp Grand Ballroom sức chứa 500 khách, trang bị hiện đại, phù hợp cho mọi sự kiện quan trọng.</p>
                <a href="lien_he.php" class="nut-xem-phong" style="width:auto; display:inline-block; padding:10px 30px;">Liên hệ ngay</a>
            </div>
            <div class="col-image">
                <img src="../images/hoinghi.jpg" class="detail-img">
            </div>
        </div>
    </section>
    
    <section class="stats-section">
        <div class="container features-grid">
            <div class="icon-box">
                <h3 class="stat-number">15+</h3>
                <p class="stat-label">Năm kinh nghiệm</p>
            </div>
            <div class="icon-box">
                <h3 class="stat-number">80+</h3>
                <p class="stat-label">Phòng nghỉ & Suite</p>
            </div>
            <div class="icon-box">
                <h3 class="stat-number">20k</h3>
                <p class="stat-label">Khách hàng hài lòng</p>
            </div>
            <div class="icon-box">
                <h3 class="stat-number">4.9</h3>
                <p class="stat-label">Điểm đánh giá</p>
            </div>
        </div>
    </section>
</main>

<?php 
$ketNoiDb->close();
include '../includes/footerkhachhang.php'; 
?>