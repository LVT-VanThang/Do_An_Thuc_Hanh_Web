<?php
session_start();
// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit; 
}

include '../includes/ketnoidb.php';
include '../includes/headeradmin.php';

// 2. Khởi tạo biến mặc định
$id = 0;
$ten_loai = ""; $gia_tien = ""; $suc_chua = ""; 
$so_giuong = ""; $huong_nhin = "Biển"; $mo_ta = "";
$isEdit = false;
$row = []; 
$errorMsg = "";

// 3. Lấy ID từ GET hoặc POST (để giữ ID khi submit form)
if (isset($_GET['id'])) $id = (int)$_GET['id'];
if (isset($_POST['id'])) $id = (int)$_POST['id'];

// --- LOGIC KHI MỚI VÀO TRANG (GET) ---
if ($id > 0 && $_SERVER['REQUEST_METHOD'] != 'POST') {
    // Kiểm tra xem phòng có đang bận không (An toàn dữ liệu)
    $checkBusy = $ketNoiDb->query("SELECT COUNT(*) as cnt FROM phong WHERE loai_phong_id = $id AND trang_thai = 'Đang ở'")->fetch_assoc();
    
    if ($checkBusy['cnt'] > 0) {
        echo "<script>
                alert('CẢNH BÁO: Loại phòng này đang có khách ở! \\nBạn không được phép chỉnh sửa thông tin quan trọng lúc này.'); 
                window.location.href='quan_ly_phong.php';
              </script>";
        exit;
    }

    // Lấy dữ liệu cũ lên form
    $stmt = $ketNoiDb->prepare("SELECT * FROM loai_phong WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ten_loai = $row['ten_loai'];
        $gia_tien = $row['gia_tien'];
        $suc_chua = $row['suc_chua'];
        $so_giuong = $row['so_giuong'];
        $huong_nhin = $row['huong_nhin'];
        $mo_ta = $row['mo_ta'];
        $isEdit = true;
    } else {
        $errorMsg = "Không tìm thấy loại phòng này!";
    }
    $stmt->close();
}

// --- LOGIC KHI BẤM LƯU (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten = $_POST['ten_loai'];
    $gia = $_POST['gia_tien'];
    $suc = $_POST['suc_chua'];
    $giuong = $_POST['so_giuong'];
    $view = $_POST['huong_nhin'];
    $mota = $_POST['mo_ta'];

    // Xử lý ảnh (BLOB)
    $hasImage = false;
    $fileData = null;
    if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == 0) {
        $fileData = file_get_contents($_FILES['anh_dai_dien']['tmp_name']);
        $hasImage = true;
    }

    if ($id > 0) {
        // --- CẬP NHẬT (UPDATE) ---
        if ($hasImage) {
            // Có upload ảnh mới
            $stmt = $ketNoiDb->prepare("UPDATE loai_phong SET ten_loai=?, gia_tien=?, suc_chua=?, so_giuong=?, huong_nhin=?, mo_ta=?, anh_dai_dien=? WHERE id=?");
            $stmt->bind_param("siiissbi", $ten, $gia, $suc, $giuong, $view, $mota, $null, $id);
            $stmt->send_long_data(6, $fileData); // Gửi dữ liệu ảnh BLOB
        } else {
            // Giữ nguyên ảnh cũ
            $stmt = $ketNoiDb->prepare("UPDATE loai_phong SET ten_loai=?, gia_tien=?, suc_chua=?, so_giuong=?, huong_nhin=?, mo_ta=? WHERE id=?");
            $stmt->bind_param("siiissi", $ten, $gia, $suc, $giuong, $view, $mota, $id);
        }
    } else {
        // --- THÊM MỚI (INSERT) ---
        if ($hasImage) {
            $stmt = $ketNoiDb->prepare("INSERT INTO loai_phong (ten_loai, gia_tien, suc_chua, so_giuong, huong_nhin, mo_ta, anh_dai_dien) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiissb", $ten, $gia, $suc, $giuong, $view, $mota, $null);
            $stmt->send_long_data(6, $fileData);
        } else {
            $stmt = $ketNoiDb->prepare("INSERT INTO loai_phong (ten_loai, gia_tien, suc_chua, so_giuong, huong_nhin, mo_ta) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiiss", $ten, $gia, $suc, $giuong, $view, $mota);
        }
    }

    if ($stmt->execute()) {
        echo "<script>alert('Lưu dữ liệu thành công!'); window.location.href='quan_ly_phong.php';</script>";
        exit;
    } else {
        $errorMsg = "Lỗi Database: " . $stmt->error;
    }
    $stmt->close();
}
?>

<main class="container page-padding">
    <div class="form-box" style="max-width: 800px; margin: 30px auto; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-radius: 12px; background: #fff;">
        
        <h2 class="form-header" style="text-align: center; margin-bottom: 30px; color: #2c3e50; font-weight: 700; text-transform: uppercase;">
            <?php echo $isEdit ? "CẬP NHẬT: " . htmlspecialchars($ten_loai) : "THÊM LOẠI PHÒNG MỚI"; ?>
        </h2>

        <?php if($errorMsg): ?>
            <div class="alert alert-danger text-center"><?php echo $errorMsg; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data"> 
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="form-group mb-3">
                <label class="form-label font-weight-bold">Tên loại phòng:</label>
                <input type="text" name="ten_loai" class="form-control" value="<?php echo htmlspecialchars($ten_loai); ?>" required placeholder="Ví dụ: Phòng Deluxe Hướng Biển">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label font-weight-bold">Giá tiền (VNĐ/Đêm):</label>
                    <input type="number" name="gia_tien" class="form-control" value="<?php echo $gia_tien; ?>" required min="0">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label font-weight-bold">Sức chứa (Người):</label>
                    <input type="number" name="suc_chua" class="form-control" value="<?php echo $suc_chua; ?>" required min="1">
                </div>
                <div class="form-group">
                    <label class="form-label font-weight-bold">Số giường:</label>
                    <input type="number" name="so_giuong" class="form-control" value="<?php echo $so_giuong; ?>" required min="1">
                </div>
                <div class="form-group">
                    <label class="form-label font-weight-bold">Hướng nhìn:</label>
                    <select name="huong_nhin" class="form-control">
                        <option value="Biển" <?php if($huong_nhin=='Biển') echo 'selected'; ?>>Biển</option>
                        <option value="Thành phố" <?php if($huong_nhin=='Thành phố') echo 'selected'; ?>>Thành phố</option>
                        <option value="Sân vườn" <?php if($huong_nhin=='Sân vườn') echo 'selected'; ?>>Sân vườn</option>
                        <option value="Hồ bơi" <?php if($huong_nhin=='Hồ bơi') echo 'selected'; ?>>Hồ bơi</option>
                        <option value="Khác" <?php if($huong_nhin=='Khác') echo 'selected'; ?>>Khác</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label font-weight-bold">Hình ảnh đại diện:</label>
                
                <?php if($isEdit && !empty($row['anh_dai_dien'])): ?>
                    <div style="margin-bottom:15px; border: 1px solid #eee; padding: 10px; border-radius: 8px; display: inline-block; background: #f9f9f9;">
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row['anh_dai_dien']); ?>" style="height:120px; object-fit:cover; border-radius: 4px;">
                        <br><small class="text-muted"><i class="fas fa-info-circle"></i> Ảnh hiện tại (Chọn ảnh mới để thay thế)</small>
                    </div>
                <?php endif; ?>
                
                <input type="file" name="anh_dai_dien" class="form-control" accept="image/*" style="padding: 10px;">
            </div>

            <div class="form-group mb-4">
                <label class="form-label font-weight-bold">Mô tả chi tiết:</label>
                <textarea name="mo_ta" class="form-control" rows="5" placeholder="Mô tả tiện nghi, diện tích,..." style="resize: vertical;"><?php echo htmlspecialchars($mo_ta); ?></textarea>
            </div>

            <div style="display:flex; gap:15px; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="quan_ly_phong.php" class="btn" style="background:#95a5a6; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                    <i class="fas fa-times"></i> HỦY BỎ
                </a>
                <button type="submit" class="btn" style="background:#27ae60; color: white; padding: 12px 40px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2);">
                    <i class="fas fa-save"></i> <?php echo $isEdit ? "CẬP NHẬT" : "THÊM MỚI"; ?>
                </button>
            </div>

        </form>
    </div>
</main>

<?php include '../includes/footeradmin.php'; ?>