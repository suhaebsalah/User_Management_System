<?php
require_once "classes/init.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$image_name = '';
$image_query = $db->query("SELECT image_name FROM users WHERE id = '" . $db->escape($id) . "' LIMIT 1");
if ($image_query && $image_query->num_rows > 0) {
    $row = $image_query->fetch_assoc();
    $image_name = $row['image_name'] ?? '';
}

$result = $user->delete_data($id);

if ($result === true) {
    if (!empty($image_name)) {
        $image_path = "assets/uploads/" . basename($image_name);
        if (file_exists($image_path) && is_file($image_path)) {
            unlink($image_path);
        }
    }

    $_SESSION['flash_message'] = 'User deleted successfully.';
    $_SESSION['flash_type'] = 'success';
    header("Location: index.php");
    exit;
}

$_SESSION['flash_message'] = 'Error deleting user.';
$_SESSION['flash_type'] = 'error';
header("Location: index.php");
exit;