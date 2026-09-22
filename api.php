<?php
require_once 'classes/init.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(string $status, string $message, array $extra = []): void
{
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra));
    exit;
}

function not_empty_value($value): bool
{
    return trim((string) $value) !== '';
}

function is_duplicate_email_error(string $message): bool
{
    $normalized = strtolower($message);
    return strpos($normalized, 'email already exists') !== false
        || strpos($normalized, 'duplicate') !== false
        || strpos($normalized, '1062') !== false;
}

function ensure_upload_dirs(string $uploadsDir, string $thumbsDir): bool
{
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
        return false;
    }
    if (!is_dir($thumbsDir) && !mkdir($thumbsDir, 0755, true)) {
        return false;
    }
    return true;
}

function save_uploaded_image(array $file): array
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 2 * 1024 * 1024;

    if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [false, '', ''];
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return [false, '', 'Image upload failed.'];
    }

    $mime = (string) ($file['type'] ?? '');
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

    if (!in_array($mime, $allowedTypes, true) || !in_array($ext, $allowedExt, true)) {
        return [false, '', 'Invalid image type. Use JPG, PNG, GIF, or WEBP.'];
    }

    if ((int) ($file['size'] ?? 0) > $maxSize) {
        return [false, '', 'Image size must be 2MB or less.'];
    }

    $uploadsDir = 'assets/uploads/';
    $thumbsDir = 'assets/uploads/thumbs/';

    if (!ensure_upload_dirs($uploadsDir, $thumbsDir)) {
        return [false, '', 'Could not prepare upload directories.'];
    }

    $fileName = uniqid('img_', true) . '.' . $ext;
    $targetPath = $uploadsDir . $fileName;
    $thumbPath = $thumbsDir . $fileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        return [false, '', 'Failed to save uploaded image.'];
    }

    @copy($targetPath, $thumbPath);

    return [true, $fileName, ''];
}

function save_pending_avatar_data(string $dataUrl): array
{
    if (!not_empty_value($dataUrl)) {
        return [false, '', ''];
    }

    if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $dataUrl)) {
        return [false, '', 'Invalid image data.'];
    }

    $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
    $binary = base64_decode($base64, true);
    if ($binary === false) {
        return [false, '', 'Invalid image encoding.'];
    }

    if (strlen($binary) > (2 * 1024 * 1024)) {
        return [false, '', 'Image size must be 2MB or less.'];
    }

    $imageInfo = @getimagesizefromstring($binary);
    $mime = (string) ($imageInfo['mime'] ?? '');
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!isset($extMap[$mime])) {
        return [false, '', 'Invalid image type. Use JPG, PNG, GIF, or WEBP.'];
    }

    $uploadsDir = 'assets/uploads/';
    $thumbsDir = 'assets/uploads/thumbs/';

    if (!ensure_upload_dirs($uploadsDir, $thumbsDir)) {
        return [false, '', 'Could not prepare upload directories.'];
    }

    $fileName = uniqid('img_', true) . '.' . $extMap[$mime];
    $targetPath = $uploadsDir . $fileName;
    $thumbPath = $thumbsDir . $fileName;

    if (file_put_contents($targetPath, $binary) === false) {
        return [false, '', 'Failed to save image file.'];
    }

    @copy($targetPath, $thumbPath);

    return [true, $fileName, ''];
}

function delete_user_image(string $fileName): void
{
    if ($fileName === '') {
        return;
    }

    $main = 'assets/uploads/' . basename($fileName);
    $thumb = 'assets/uploads/thumbs/' . basename($fileName);

    if (is_file($main)) {
        @unlink($main);
    }
    if (is_file($thumb)) {
        @unlink($thumb);
    }
}

if (!$session->get_log_in()) {
    json_response('error', 'Unauthorized request.');
}

$action = strtolower(trim((string) ($_POST['action'] ?? $_GET['action'] ?? '')));
if ($action === '') {
    json_response('error', 'Missing action.');
}

if ($action === 'get_stats') {
    $statsSql = "SELECT 
                    COUNT(*) AS total_users,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_users,
                    SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) AS inactive_users,
                    COALESCE(SUM(salary), 0) AS total_salary
                 FROM users";

    $statsResult = $db->query($statsSql);
    if (!$statsResult || $statsResult->num_rows === 0) {
        json_response('error', 'Could not load stats.');
    }

    $stats = $statsResult->fetch_assoc();
    json_response('success', 'Stats loaded successfully.', [
        'stats' => [
            'total' => (int) ($stats['total_users'] ?? 0),
            'active' => (int) ($stats['active_users'] ?? 0),
            'inactive' => (int) ($stats['inactive_users'] ?? 0),
            'salary_sum' => (int) ($stats['total_salary'] ?? 0)
        ]
    ]);
}

if ($action === 'get_user') {
    $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        json_response('error', 'Invalid user id.');
    }

    $sql = "SELECT u.*, d.name AS department_name, r.name AS role_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = '" . $db->escape((string) $id) . "' LIMIT 1";
    $result = $db->query($sql);

    if (!$result || $result->num_rows === 0) {
        json_response('error', 'User not found.');
    }

    $row = $result->fetch_assoc();
    json_response('success', 'User loaded successfully.', ['user' => $row]);
}

if ($action === 'delete_user') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response('error', 'Invalid user id.');
    }

    $imageName = '';
    $imgQuery = $db->query("SELECT image_name FROM users WHERE id = '" . $db->escape((string) $id) . "' LIMIT 1");
    if ($imgQuery && $imgQuery->num_rows > 0) {
        $imgRow = $imgQuery->fetch_assoc();
        $imageName = (string) ($imgRow['image_name'] ?? '');
    }

    $result = $user->delete_data($id);
    if ($result === true) {
        delete_user_image($imageName);
        json_response('success', 'User deleted successfully.');
    }

    json_response('error', (string) $result);
}

if ($action === 'insert_user' || $action === 'update_user') {
    $isUpdate = $action === 'update_user';

    $id = (int) ($_POST['id'] ?? 0);
    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $departmentId = trim((string) ($_POST['department_id'] ?? ''));
    $roleId = trim((string) ($_POST['role_id'] ?? ''));
    $salaryRaw = trim((string) ($_POST['salary'] ?? ''));
    $salary = (int) $salaryRaw;
    $status = trim((string) ($_POST['status'] ?? 'Inactive'));
    $info = trim((string) ($_POST['info'] ?? ''));
    $pendingAvatar = (string) ($_POST['pending_avatar_data'] ?? '');
    $removeImage = ((string) ($_POST['remove_image'] ?? '0')) === '1';
    $uploadImage = $_FILES['upload_image'] ?? null;

    if ($isUpdate && $id <= 0) {
        json_response('error', 'Invalid user id.');
    }

    $errors = [];

    if (!not_empty_value($firstName)) {
        $errors['first_name'] = 'Please, the input is empty.';
    }
    if (!not_empty_value($lastName)) {
        $errors['last_name'] = 'Please, the input is empty.';
    }
    if (!not_empty_value($email)) {
        $errors['email'] = 'Please, the input is empty.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    if (!not_empty_value($salaryRaw)) {
        $errors['salary'] = 'Please, the input is empty.';
    } elseif ($salary <= 0) {
        $errors['salary'] = 'Salary must be greater than 0';
    }
    if (!not_empty_value($departmentId)) {
        $errors['department_id'] = 'Please, the input is empty.';
    }
    if (!not_empty_value($roleId)) {
        $errors['role_id'] = 'Please, the input is empty.';
    }
    if (!in_array($status, ['Active', 'Inactive'], true)) {
        $errors['status'] = 'Please select a valid status';
    }
    if (not_empty_value($info) && strlen($info) > 255) {
        $errors['info'] = 'Info must be less than 255 characters';
    }

    if (!empty($errors)) {
        json_response('error', 'Please fix the required fields.', ['errors' => $errors]);
    }

    $oldImage = '';
    if ($isUpdate) {
        $oldImage = trim((string) ($_POST['old_image'] ?? ''));
        if ($oldImage === '') {
            $oldQuery = $db->query("SELECT image_name FROM users WHERE id = '" . $db->escape((string) $id) . "' LIMIT 1");
            if ($oldQuery && $oldQuery->num_rows > 0) {
                $oldRow = $oldQuery->fetch_assoc();
                $oldImage = (string) ($oldRow['image_name'] ?? '');
            }
        }
    }

    $newImageName = $oldImage;

    if (is_array($uploadImage) && isset($uploadImage['error']) && (int) $uploadImage['error'] !== UPLOAD_ERR_NO_FILE) {
        [$okUpload, $fileName, $uploadError] = save_uploaded_image($uploadImage);
        if (!$okUpload) {
            json_response('error', $uploadError !== '' ? $uploadError : 'Image upload failed.');
        }
        $newImageName = $fileName;
    } elseif (not_empty_value($pendingAvatar)) {
        [$okPending, $fileName, $pendingError] = save_pending_avatar_data($pendingAvatar);
        if (!$okPending) {
            json_response('error', $pendingError !== '' ? $pendingError : 'Image upload failed.');
        }
        $newImageName = $fileName;
    } elseif ($isUpdate && $removeImage) {
        $newImageName = '';
    }

    $user->first_name = $firstName;
    $user->last_name = $lastName;
    $user->email = $email;
    $user->department_id = $departmentId;
    $user->role_id = $roleId;
    $user->salary = $salary;
    $user->status = $status;
    $user->info = $info;
    $user->image_name = $newImageName;

    if ($isUpdate) {
        $user->id = $id;
        $result = $user->update_data();
        if ($result === true) {
            if ($newImageName !== $oldImage && $oldImage !== '') {
                delete_user_image($oldImage);
            }
            json_response('success', 'User updated successfully.');
        }
        if (is_duplicate_email_error((string) $result)) {
            json_response('error', 'Please, this email is already used. Change email.', [
                'errors' => ['email' => 'Please, this email is already used. Change email.']
            ]);
        }
        json_response('error', (string) $result);
    }

    $result = $user->insert_data();
    if ($result === true) {
        json_response('success', 'User added successfully.');
    }

    if (is_duplicate_email_error((string) $result)) {
        json_response('error', 'Please, this email is already used. Change email.', [
            'errors' => ['email' => 'Please, this email is already used. Change email.']
        ]);
    }

    json_response('error', (string) $result);
}

json_response('error', 'Invalid action.');
