<?php
// File: khachhang/check_status.php
// File này trả về JSON để JavaScript gọi AJAX
include '../includes/ketnoidb.php';

// Tắt hiển thị lỗi HTML để không làm hỏng JSON
error_reporting(0); 
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Lấy trạng thái hiện tại của đơn
    $sql = "SELECT trang_thai, ngay_nhan, ngay_tra FROM dat_phong WHERE id = $id";
    $result = $ketNoiDb->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $trangThai = trim($row['trang_thai']); // Xóa khoảng trắng thừa
        
        // Kiểm tra nếu trạng thái đã chuyển sang thành công
        // (Hỗ trợ cả 'Đã duyệt', 'Đã đặt', 'Đang ở' đề phòng Admin đổi tên trạng thái)
        if (in_array($trangThai, ['Đã duyệt', 'Đã đặt', 'Đang ở'])) {
            
            // Lấy danh sách phòng đã được hệ thống xếp tự động (từ bảng chi tiết)
            $sqlPhong = "SELECT p.so_phong 
                         FROM chi_tiet_dat_phong ct 
                         JOIN phong p ON ct.phong_id = p.id 
                         WHERE ct.dat_phong_id = $id
                         ORDER BY p.so_phong ASC";
                         
            $resPhong = $ketNoiDb->query($sqlPhong);
            $dsPhong = [];
            while($r = $resPhong->fetch_assoc()) {
                $dsPhong[] = "P." . $r['so_phong'];
            }
            
            // Trả về JSON thành công
            echo json_encode([
                'status' => 'success',
                'message' => 'Thanh toán thành công!',
                'phong' => !empty($dsPhong) ? implode(', ', $dsPhong) : 'Đang cập nhật...',
                'checkin' => date('d/m/Y', strtotime($row['ngay_nhan'])),
                'checkout' => date('d/m/Y', strtotime($row['ngay_tra']))
            ]);
        } else {
            // Vẫn đang chờ
            echo json_encode(['status' => 'waiting']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID']);
}
?>