<?php
// File: khachhang/dat_phong.php

// 1. Kết nối DB và Header
include '../includes/ketnoidb.php';
$tieuDeTrang = "Hoàn tất đặt phòng - Khách sạn ABC";
include '../includes/headerkhachhang.php';

// --- KHAI BÁO THƯ VIỆN PHPMAILER ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

// --- HÀM GỬI EMAIL ---
function guiEmailXacNhan($emailKhach, $tenKhach, $maDon, $tenPhong, $ngayNhan, $ngayTra, $tongTien, $tienCoc) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thangkkt112@gmail.com'; // Email của bạn
        $mail->Password   = 'weul pqoa abxy wamo';       // Mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('thangkkt112@gmail.com', 'Khách Sạn ABC');
        $mail->addAddress($emailKhach, $tenKhach);

        $mail->isHTML(true);
        $mail->Subject = "Xác nhận đặt phòng #$maDon - Khách sạn ABC";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h3 style='color: #27ae60;'>Cảm ơn quý khách đã đặt phòng!</h3>
                <p>Xin chào <b>$tenKhach</b>,</p>
                <p>Đơn đặt phòng của quý khách đã được ghi nhận. Thông tin chi tiết:</p>
                <ul>
                    <li><b>Mã đơn:</b> #$maDon</li>
                    <li><b>Loại phòng:</b> $tenPhong</li>
                    <li><b>Ngày nhận:</b> $ngayNhan</li>
                    <li><b>Ngày trả:</b> $ngayTra</li>
                    <li><b>Tổng tiền:</b> " . number_format($tongTien) . " VNĐ</li>
                    <li style='color:red;'><b>Đã đặt cọc:</b> " . number_format($tienCoc) . " VNĐ</li>
                </ul>
                <p>Vui lòng thanh toán số tiền còn lại khi nhận phòng.</p>
                <p>Trân trọng,<br>Khách sạn ABC</p>
            </div>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// 2. NHẬN DỮ LIỆU TỪ URL
if (!isset($_GET['id']) || empty($_GET['checkin']) || empty($_GET['checkout'])) {
    header("Location: index.php");
    exit;
}

$idLoai = (int)$_GET['id'];
$checkin = $_GET['checkin'];
$checkout = $_GET['checkout'];
$soLuongDefault = isset($_GET['soluong']) ? (int)$_GET['soluong'] : 1;

// 3. LẤY THÔNG TIN PHÒNG & TÍNH TOÁN
$sql = "SELECT * FROM loai_phong WHERE id = $idLoai";
$phongInfo = $ketNoiDb->query($sql)->fetch_assoc();
if (!$phongInfo) die("Lỗi: Không tìm thấy loại phòng.");

$anh = !empty($phongInfo['anh_dai_dien']) ? 'data:image/jpeg;base64,' . base64_encode($phongInfo['anh_dai_dien']) : '../images/no-image.jpg';

// Tính số đêm
$d1 = new DateTime($checkin);
$d2 = new DateTime($checkout);
$soDem = $d1->diff($d2)->days;
if($soDem < 1) $soDem = 1;

// --- TÍNH SỐ PHÒNG TRỐNG THỰC TẾ ---
$dkBaoTri = (strtotime($checkin) <= time()) ? "AND trang_thai != 'Bảo trì'" : "";

$sqlTong = "SELECT COUNT(*) as total FROM phong WHERE loai_phong_id = $idLoai $dkBaoTri";
$tongPhong = $ketNoiDb->query($sqlTong)->fetch_assoc()['total'];

$sqlDaDat = "SELECT COUNT(DISTINCT ct.phong_id) as booked 
             FROM chi_tiet_dat_phong ct
             JOIN dat_phong dp ON ct.dat_phong_id = dp.id
             WHERE ct.phong_id IN (SELECT id FROM phong WHERE loai_phong_id = $idLoai)
             AND dp.trang_thai IN ('Đã duyệt', 'Đang ở')
             AND (dp.ngay_nhan < '$checkout' AND dp.ngay_tra > '$checkin')";

$phongDaDat = $ketNoiDb->query($sqlDaDat)->fetch_assoc()['booked'];
$phongTrong = $tongPhong - $phongDaDat;

if ($phongTrong <= 0) {
    echo "<script>alert('Rất tiếc, loại phòng này vừa hết chỗ!'); window.location.href='index.php';</script>";
    exit;
}

// Tính tổng tiền mặc định để hiển thị
$tongTienHienThi = $phongInfo['gia_tien'] * $soDem * $soLuongDefault;


// 4. XỬ LÝ ĐẶT PHÒNG (POST)
$datThanhCong = false;
$maDonHang = 0;
$tienCocFinal = 0;
$thongBaoLoi = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten = $_POST['ten_khach'];
    $sdt = $_POST['sdt_khach'];
    $email = $_POST['email_khach'];
    $slDat = (int)$_POST['so_luong_chot']; // Lấy số lượng khách chọn
    
    if ($slDat > $phongTrong) {
        $thongBaoLoi = "<div class='alert alert-danger'>Lỗi: Chỉ còn $phongTrong phòng trống. Vui lòng chọn lại.</div>";
    } else {
        // Tính tiền server-side
        $tongTienFinal = $phongInfo['gia_tien'] * $soDem * $slDat;
        $tienCocFinal = $tongTienFinal * 0.3; // Cọc 30%

        // Lưu vào DB (Nhớ là bảng dat_phong phải có cột tien_coc nha)
        $sqlInsert = "INSERT INTO dat_phong (loai_phong_id, so_luong, ten_khach, email_khach, sdt_khach, ngay_nhan, ngay_tra, tong_tien, tien_coc, trang_thai) 
                      VALUES ('$idLoai', '$slDat', '$ten', '$email', '$sdt', '$checkin', '$checkout', '$tongTienFinal', '$tienCocFinal', 'Chờ xác nhận')";
        
        if ($ketNoiDb->query($sqlInsert)) {
            $datThanhCong = true;
            $maDonHang = $ketNoiDb->insert_id;
            
            // Gửi Email
            guiEmailXacNhan($email, $ten, $maDonHang, $phongInfo['ten_loai'], date('d/m/Y', strtotime($checkin)), date('d/m/Y', strtotime($checkout)), $tongTienFinal, $tienCocFinal);
        } else {
            $thongBaoLoi = "<div class='alert alert-danger'>Lỗi hệ thống: " . $ketNoiDb->error . "</div>";
        }
    }
}
?>

<main class="container page-padding" style="margin-top: 40px; margin-bottom: 60px;">
    
    <?php if ($datThanhCong): ?>
        <div style="max-width: 900px; margin: 0 auto;">
            
            <div style="background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 40px;">
                
                <div style="background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 40px 20px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-check" style="font-size: 40px; color: white;"></i>
                    </div>
                    <h2 style="margin: 0; font-size: 2rem;">Đặt phòng thành công!</h2>
                    <p style="opacity: 0.9; margin-top: 10px;">Cảm ơn <strong><?php echo htmlspecialchars($ten); ?></strong>, đơn hàng của bạn đã được khởi tạo.</p>
                </div>

                <div id="payment-box" style="padding: 40px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 40px;">
                        
                        <div style="flex: 1; min-width: 280px; text-align: center; border-right: 1px dashed #eee;">
                            <h3 style="color: #2c3e50; margin-top: 0;">Quét mã để thanh toán</h3>
                            <p style="color: #7f8c8d; font-size: 0.9rem;">Sử dụng ứng dụng ngân hàng hoặc ví điện tử</p>
                            
                            <?php
                                $nganHangId = 'MB'; 
                                $soTaiKhoan = '0372036292'; 
                                $tenChuTaiKhoan = 'LUONG VAN THANG'; 
                                $noiDungCk = "DH" . $maDonHang;
                                $qrUrl = "https://img.vietqr.io/image/$nganHangId-$soTaiKhoan-compact2.jpg?amount=$tienCocFinal&addInfo=$noiDungCk&accountName=$tenChuTaiKhoan";
                            ?>
                            
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: inline-block; border: 1px solid #ddd;">
                                <img src="<?php echo $qrUrl; ?>" alt="QR Payment" style="width: 100%; max-width: 220px; display: block;">
                            </div>
                            
                            <div style="margin-top: 20px; color: #27ae60; font-weight: 600; background: #e8f5e9; padding: 10px; border-radius: 20px; font-size: 0.9rem; display: inline-block;">
                                <i class="fas fa-sync fa-spin"></i> Đang chờ xác nhận tự động...
                            </div>
                        </div>

                        <div style="flex: 1.5; min-width: 280px;">
                            <h3 style="color: #2c3e50; margin-top: 0;">Thông tin chuyển khoản</h3>
                            
                            <div style="background: #fff8e1; border-left: 4px solid #f1c40f; padding: 15px; margin-bottom: 25px;">
                                <p style="margin: 0; color: #d35400; font-weight: bold;">
                                    <i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng:
                                </p>
                                <p style="margin: 5px 0 0; font-size: 0.9rem; color: #555;">
                                    Vui lòng nhập chính xác <strong>Nội dung chuyển khoản</strong> bên dưới để hệ thống tự động kích hoạt đơn hàng.
                                </p>
                            </div>

                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px 0; color: #666; border-bottom: 1px solid #eee;">Ngân hàng:</td>
                                    <td style="padding: 12px 0; font-weight: bold; text-align: right; border-bottom: 1px solid #eee;">MB Bank</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; color: #666; border-bottom: 1px solid #eee;">Số tài khoản:</td>
                                    <td style="padding: 12px 0; font-weight: bold; text-align: right; border-bottom: 1px solid #eee; font-size: 1.1rem; letter-spacing: 1px; color: #2980b9;"><?php echo $soTaiKhoan; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; color: #666; border-bottom: 1px solid #eee;">Chủ tài khoản:</td>
                                    <td style="padding: 12px 0; font-weight: bold; text-align: right; border-bottom: 1px solid #eee; text-transform: uppercase;"><?php echo $tenChuTaiKhoan; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; color: #666; border-bottom: 1px solid #eee;">Số tiền cọc (30%):</td>
                                    <td style="padding: 12px 0; font-weight: bold; text-align: right; border-bottom: 1px solid #eee; color: #c0392b; font-size: 1.2rem;"><?php echo number_format($tienCocFinal); ?> đ</td>
                                </tr>
                                <tr>
                                    <td style="padding: 15px 0; color: #666;">Nội dung CK:</td>
                                    <td style="padding: 15px 0; text-align: right;">
                                        <span style="background: #e0e0e0; color: #333; padding: 8px 15px; border-radius: 4px; font-weight: bold; font-family: monospace; font-size: 1.1rem; border: 1px solid #ccc;"><?php echo $noiDungCk; ?></span>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top: 30px; text-align: right;">
                                <a href="index.php" style="color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">
                                    <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="success-box" style="display: none; padding: 50px; text-align: center;">
                    <div style="width: 100px; height: 100px; background: #d4edda; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                         <i class="fas fa-check-circle" style="font-size: 50px; color: #27ae60;"></i>
                    </div>
                    <h2 style="color: #27ae60; margin-bottom: 10px;">THANH TOÁN THÀNH CÔNG!</h2>
                    <p style="color: #555; font-size: 1.1rem;">Hệ thống đã nhận được tiền cọc. Phòng của bạn đã được giữ.</p>
                    
                    <div style="background: #f9f9f9; border-radius: 8px; padding: 20px; max-width: 500px; margin: 30px auto; text-align: left; border: 1px solid #eee;">
                        <h4 style="margin: 0 0 15px 0; border-bottom: 2px solid #ddd; padding-bottom: 10px;">Chi tiết đặt phòng</h4>
                        <p style="margin: 5px 0;"><strong>Mã đơn:</strong> #<?php echo $maDonHang; ?></p>
                        <p style="margin: 5px 0;"><strong>Phòng:</strong> <span id="room-name" style="color: #2980b9; font-weight: bold;">...</span></p>
                        <p style="margin: 5px 0;"><strong>Check-in:</strong> <span id="check-in-date">...</span></p>
                        <p style="margin: 5px 0;"><strong>Check-out:</strong> <span id="check-out-date">...</span></p>
                    </div>

                    <a href="index.php" class="btn btn-primary" style="padding: 12px 30px; background: #2c3e50; color: white; border: none; border-radius: 5px; text-decoration: none; font-weight: bold; transition: 0.3s;">
                        Về Trang Chủ
                    </a>
                </div>

            </div>
        </div>

        <script>
            const orderId = <?php echo $maDonHang; ?>;
            let checkInterval;

            function checkPaymentStatus() {
                fetch('check_status.php?id=' + orderId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Ẩn phần thanh toán, hiện phần thành công
                            document.getElementById('payment-box').style.display = 'none';
                            document.getElementById('success-box').style.display = 'block';
                            
                            // Điền dữ liệu vào bảng thành công
                            document.getElementById('room-name').innerText = data.phong;
                            document.getElementById('check-in-date').innerText = data.checkin;
                            document.getElementById('check-out-date').innerText = data.checkout;

                            // Dừng kiểm tra
                            clearInterval(checkInterval);
                        }
                    })
                    .catch(error => console.error('Lỗi kiểm tra trạng thái:', error));
            }

            // Kiểm tra mỗi 3 giây
            checkInterval = setInterval(checkPaymentStatus, 3000);
        </script>
    
    <?php else: ?>
        <h1 class="tieu-de-muc" style="margin-bottom: 30px;">Xác nhận thông tin đặt phòng</h1>
        
        <?php echo $thongBaoLoi; ?>

        <div class="row" style="display: flex; gap: 40px; flex-wrap: wrap;">
            
            <div class="col-form" style="flex: 2; min-width: 300px;">
                <div class="card-box" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                    <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; color: #2c3e50;">Thông tin liên hệ</h3>
                    
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                            <label style="font-weight: bold; display: block; margin-bottom: 5px;">Số lượng phòng muốn đặt:</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="number" name="so_luong_chot" id="so_luong" 
                                       class="form-control" 
                                       value="<?php echo ($soLuongDefault <= $phongTrong) ? $soLuongDefault : 1; ?>" 
                                       min="1" max="<?php echo $phongTrong; ?>" 
                                       required 
                                       style="width: 100px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-weight: bold; font-size: 1.1rem;">
                                <span style="color: green; font-size: 0.9rem;">(Còn <?php echo $phongTrong; ?> phòng trống)</span>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: bold;">Họ và tên:</label>
                            <input type="text" name="ten_khach" class="form-control" required placeholder="Nhập họ tên..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: bold;">Số điện thoại:</label>
                            <input type="text" name="sdt_khach" class="form-control" required placeholder="Nhập SĐT..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 30px;">
                            <label style="font-weight: bold;">Email:</label>
                            <input type="email" name="email_khach" class="form-control" required placeholder="Nhập email..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>

                        <button type="submit" style="background: #27ae60; color: white; border: none; padding: 15px; font-size: 1.1rem; font-weight: bold; border-radius: 5px; cursor: pointer; width: 100%;">
                            XÁC NHẬN & THANH TOÁN CỌC <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <p style="text-align: center; margin-top: 15px; color: #777; font-size: 0.9rem;">
                            <i class="fas fa-lock"></i> Thông tin được bảo mật an toàn.
                        </p>
                    </form>
                </div>
            </div>

            <div class="col-info" style="flex: 1; min-width: 300px;">
                <div class="card-box" style="background: #fdfdfd; padding: 0; border-radius: 8px; border: 1px solid #ddd; overflow: hidden; position: sticky; top: 20px;">
                    <img src="<?php echo $anh; ?>" style="width: 100%; height: 180px; object-fit: cover;">
                    
                    <div style="padding: 20px;">
                        <h3 style="margin-top: 0; color: #2980b9; font-size: 1.3rem;"><?php echo $phongInfo['ten_loai']; ?></h3>
                        
                        <div style="background: #fff; padding: 15px; border-radius: 5px; border: 1px dashed #ccc; margin: 15px 0;">
                            <p style="margin: 5px 0;"><strong>Nhận:</strong> <?php echo date('d/m/Y', strtotime($checkin)); ?></p>
                            <p style="margin: 5px 0;"><strong>Trả:</strong> <?php echo date('d/m/Y', strtotime($checkout)); ?></p>
                            <p style="margin: 5px 0; color: #666;">(<?php echo $soDem; ?> đêm)</p>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 10px;">
                            <span>Đơn giá:</span>
                            <span><?php echo number_format($phongInfo['gia_tien'], 0, ',', '.'); ?> ₫</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 10px;">
                            <span>Số lượng:</span>
                            <strong id="display_sl">x <?php echo $soLuongDefault; ?></strong>
                        </div>

                        <hr>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; margin-top: 15px;">
                            <span>Tổng cộng:</span>
                            <span style="color: #333; font-weight: bold;" id="display_total">
                                <?php echo number_format($tongTienHienThi, 0, ',', '.'); ?> ₫
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem; margin-top: 10px; color: #c0392b;">
                            <span>Cọc trước (30%):</span>
                            <span style="font-weight: bold;" id="display_deposit">
                                <?php echo number_format($tongTienHienThi * 0.3, 0, ',', '.'); ?> ₫
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>

</main>

<script>
    const inputSL = document.getElementById('so_luong');
    const displaySL = document.getElementById('display_sl');
    const displayTotal = document.getElementById('display_total');
    const displayDeposit = document.getElementById('display_deposit');
    
    const giaPhong = <?php echo $phongInfo['gia_tien']; ?>;
    const soDem = <?php echo $soDem; ?>;

    if(inputSL){
        inputSL.addEventListener('input', function() {
            let sl = parseInt(this.value);
            if (isNaN(sl) || sl < 1) sl = 1;
            if (sl > <?php echo $phongTrong; ?>) sl = <?php echo $phongTrong; ?>;
            
            displaySL.innerText = "x " + sl;
            let total = giaPhong * soDem * sl;
            let deposit = total * 0.3;
            
            displayTotal.innerText = new Intl.NumberFormat('vi-VN').format(total) + " ₫";
            displayDeposit.innerText = new Intl.NumberFormat('vi-VN').format(deposit) + " ₫";
        });
    }
</script>

<?php 
$ketNoiDb->close();
include '../includes/footerkhachhang.php'; 
?>