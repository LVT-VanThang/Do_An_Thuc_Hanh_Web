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
$so_phong = ""; $loai_phong_id = ""; $tang = ""; $trang_thai = "Sẵn sàng";
$isEdit = false;
$errorMsg = "";

// 3. Lấy ID từ GET hoặc POST
if (isset($_GET['id'])) $id = (int)$_GET['id'];
if (isset($_POST['id'])) $id = (int)$_POST['id'];

// --- LOGIC KHI MỚI VÀO TRANG (GET) ---
// Đây là phần lấy dữ liệu cũ lên form
if ($id > 0 && $_SERVER['REQUEST_METHOD'] != 'POST') {
    
    // Kiểm tra trạng thái phòng (Bảo vệ dữ liệu)
    $checkStatus = $ketNoiDb->query("SELECT trang_thai FROM phong WHERE id = $id")->fetch_assoc();
    
    if ($checkStatus['trang_thai'] == 'Đang ở') {
        echo "<script>
                alert('CẢNH BÁO: Phòng này đang có khách! \\nBạn không được phép chỉnh sửa lúc này.'); 
                window.location.href='quan_ly_so_phong.php';
              </script>";
        exit;
    }

    $stmt = $ketNoiDb->prepare("SELECT * FROM phong WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // --- GÁN DỮ LIỆU CŨ VÀO BIẾN ---
        $so_phong = $row['so_phong'];
        $loai_phong_id = $row['loai_phong_id'];
        $tang = $row['tang']; // <--- QUAN TRỌNG: Lấy số tầng từ DB
        $trang_thai = $row['trang_thai'];
        
        $isEdit = true;
    }
    $stmt->close();
}

// --- LOGIC KHI BẤM LƯU (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $so = trim($_POST['so_phong']);
    $loai = (int)$_POST['loai_phong_id'];
    $tang = (int)$_POST['tang']; // Lấy số tầng từ form
    $tt = $_POST['trang_thai'];

    // Validate: Kiểm tra trùng số phòng (chỉ khi số phòng thay đổi hoặc thêm mới)
    // Logic: Tìm xem có phòng nào KHÁC phòng hiện tại mà có cùng số phòng không
    $sqlCheck = "SELECT COUNT(*) as cnt FROM phong WHERE so_phong = '$so' AND id != $id";
    $checkDup = $ketNoiDb->query($sqlCheck);
    
    if ($checkDup && $checkDup->fetch_assoc()['cnt'] > 0) {
        $errorMsg = "Lỗi: Số phòng '$so' đã tồn tại! Vui lòng đặt tên khác.";
    }

    if (empty($errorMsg)) {
        if ($id > 0) {
            // CẬP NHẬT
            $stmt = $ketNoiDb->prepare("UPDATE phong SET so_phong=?, loai_phong_id=?, tang=?, trang_thai=? WHERE id=?");
            $stmt->bind_param("siisi", $so, $loai, $tang, $tt, $id);
        } else {
            // THÊM MỚI
            $stmt = $ketNoiDb->prepare("INSERT INTO phong (so_phong, loai_phong_id, tang, trang_thai) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siis", $so, $loai, $tang, $tt);
        }

        if ($stmt->execute()) {
            echo "<script>alert('Lưu thành công!'); window.location.href='quan_ly_so_phong.php';</script>";
            exit;
        } else {
            $errorMsg = "Lỗi Database: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Lấy danh sách loại phòng cho thẻ Select
$listLoai = $ketNoiDb->query("SELECT * FROM loai_phong");
?>

<main class="container page-padding">
    
    <div style="margin-bottom: 20px;">
        <a href="quan_ly_so_phong.php" style="color: #666; font-size: 0.9rem; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
    </div>

    <div class="form-box" style="max-width: 600px; margin: 0 auto; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; background: #fff;">
        <h2 class="form-header" style="text-align: center; margin-bottom: 25px; color: #2c3e50;">
            <?php echo $isEdit ? "<i class='fas fa-edit'></i> Sửa phòng $so_phong" : "<i class='fas fa-plus-circle'></i> Thêm phòng mới"; ?>
        </h2>
        
        <?php if($errorMsg): ?>
            <div class="alert alert-danger text-center"><?php echo $errorMsg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="form-group mb-3">
                <label class="form-label font-weight-bold">Số phòng (Ví dụ: 101):</label>
                <input type="text" name="so_phong" class="form-control" value="<?php echo htmlspecialchars($so_phong); ?>" required placeholder="Nhập số phòng...">
            </div>

            <div class="form-group mb-3">
                <label class="form-label font-weight-bold">Thuộc loại phòng:</label>
                <select name="loai_phong_id" class="form-control" required>
                    <option value="">-- Chọn loại phòng --</option>
                    <?php 
                    $listLoai->data_seek(0); 
                    while($l = $listLoai->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $l['id']; ?>" <?php if($loai_phong_id == $l['id']) echo 'selected'; ?>>
                            <?php echo $l['ten_loai']; ?> (Giá: <?php echo number_format($l['gia_tien']); ?>đ)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label class="form-label font-weight-bold">Tầng:</label>
                <input type="number" name="tang" class="form-control" value="<?php echo $tang; ?>" required placeholder="Nhập số tầng..." min="0">
            </div>

            <div class="form-group mb-4">
                <label class="form-label font-weight-bold">Trạng thái:</label>
                <select name="trang_thai" class="form-control">
                    <option value="Sẵn sàng" <?php if($trang_thai=='Sẵn sàng') echo 'selected'; ?>>✅ Sẵn sàng</option>
                    <option value="Đang ở" <?php if($trang_thai=='Đang ở') echo 'selected'; ?>>👤 Đang ở</option>
                    <option value="Đang dọn" <?php if($trang_thai=='Đang dọn') echo 'selected'; ?>>🧹 Đang dọn</option>
                    <option value="Bảo trì" <?php if($trang_thai=='Bảo trì') echo 'selected'; ?>>🔧 Bảo trì</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">
                <?php echo $isEdit ? "CẬP NHẬT" : "LƯU LẠI"; ?>
            </button>
        </form>
    </div>
</main>

<?php include '../includes/footeradmin.php'; ?>
