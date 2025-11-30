<?php
$tenMayChu = "localhost";
$tenNguoiDung = "root";
$matKhau = ""; 
$tenDatabase = "khachsan"; 

$ketNoiDb = new mysqli($tenMayChu, $tenNguoiDung, $matKhau, $tenDatabase);

if ($ketNoiDb->connect_error) {
    die("Kết nối thất bại: " . $ketNoiDb->connect_error);
}
$ketNoiDb->set_charset("utf8");