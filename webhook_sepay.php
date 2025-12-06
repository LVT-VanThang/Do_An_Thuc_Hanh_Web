<?php
// File: webhook_sepay.php
include 'includes/ketnoidb.php';

// --- THƯ VIỆN EMAIL ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

function guiEmailThanhCong($email, $ten, $maDon, $dsPhong, $in, $out, $tien) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thangkkt112@gmail.com'; 
        $mail->Password   = 'weul pqoa abxy wamo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('thangkkt112@gmail.com', 'Khách Sạn ABC');
        $mail->addAddress($email, $ten);
        $mail->isHTML(true);
        $mail->Subject = "Đặt phòng thành công - Đơn #$maDon";
        $mail->Body    = "
            <h3>Thanh toán thành công!</h3>
            <p>Xin chào <b>$ten</b>,</p>
            <p>Phòng của bạn đã được chuyển sang trạng thái <b>ĐÃ ĐẶT</b>.</p>
            <ul>
                <li><b>Mã đơn:</b> #$maDon</li>
                <li><b>Phòng:</b> $dsPhong</li>
                <li><b>Ngày nhận:</b> $in</li>
                <li><b>Ngày trả:</b> $out</li>
            </ul>
            <p>Hẹn gặp lại quý khách!</p>
        ";
        $mail->send();
    } catch (Exception $e) {}
}

// XỬ LÝ WEBHOOK
$dataJson = file_get_contents('php://input');
$data = json_decode($dataJson, true);
if (!isset($data['id'])) die('Access Denied');
$noiDungCk = $data['content']; 
$soTienCk = $data['transferAmount'];

if (preg_match('/(DH|DON)\s*(\d+)/i', $noiDungCk, $matches)) {
    $idDon = (int)$matches[2];

    // Lấy đơn hàng
    $sqlOrder = "SELECT * FROM dat_phong WHERE id = $idDon AND trang_thai = 'Chờ xác nhận'";
    $order = $ketNoiDb->query($sqlOrder)->fetch_assoc();

    if ($order && $soTienCk >= $order['tien_coc']) { // So sánh với tiền cọc hoặc tổng tiền
        $idLoaiPhong = $order['loai_phong_id'];
        $soLuongCan = $order['so_luong'];

        // A. Tìm phòng trống
        $sqlTim = "SELECT id, so_phong FROM phong 
                   WHERE loai_phong_id = $idLoaiPhong AND trang_thai = 'Sẵn sàng' LIMIT $soLuongCan";
        $resTim = $ketNoiDb->query($sqlTim);

        if ($resTim->num_rows >= $soLuongCan) {
            $dsTenPhong = [];
            
            while($r = $resTim->fetch_assoc()) {
                $pid = $r['id'];
                $dsTenPhong[] = "P." . $r['so_phong'];

                // 1. Gán phòng vào chi tiết
                $ketNoiDb->query("INSERT INTO chi_tiet_dat_phong (dat_phong_id, phong_id) VALUES ($idDon, $pid)");
                
                // 2. Đổi trạng thái phòng -> 'Đã đặt'
                $ketNoiDb->query("UPDATE phong SET trang_thai = 'Đã đặt' WHERE id = $pid");
            }

            // 3. Đổi trạng thái đơn -> 'Đã đặt' (Thay vì Đã duyệt)
            $ketNoiDb->query("UPDATE dat_phong SET trang_thai = 'Đã đặt' WHERE id = $idDon");

            // 4. Gửi Email
            $strPhong = implode(", ", $dsTenPhong);
            guiEmailThanhCong($order['email_khach'], $order['ten_khach'], $idDon, $strPhong, date('d/m/Y', strtotime($order['ngay_nhan'])), date('d/m/Y', strtotime($order['ngay_tra'])), $soTienCk);

            echo json_encode(["status" => "success", "message" => "Đơn #$idDon đã chuyển sang Đã đặt"]);
        }
    }
}
?>