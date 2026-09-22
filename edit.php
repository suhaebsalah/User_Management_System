<?php
require_once "classes/init.php";

if (!function_exists('notEmpty')) {
	function notEmpty($value): bool
	{
		return trim((string) $value) !== '';
	}
}

if (!function_exists('deleteUserImage')) {
	function deleteUserImage(string $fileName): void
	{
		if ($fileName === '') {
			return;
		}

		$uploadPath = 'assets/uploads/' . $fileName;
		$thumbPath = 'assets/uploads/thumbs/' . $fileName;

		if (file_exists($uploadPath)) {
			unlink($uploadPath);
		}
		if (file_exists($thumbPath)) {
			unlink($thumbPath);
		}
	}
}

$edit_user = null;
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
	$edit_id = (int) $_GET['edit_id'];
	$edit_user = $user->get_by_id($edit_id);
	echo "<script>document.addEventListener('DOMContentLoaded', () => openEditModal());</script>";
}
if (isset($_POST['update_user'])) {
	$id = (int) ($_POST['id'] ?? 0);
	$first_name = trim($_POST['first_name'] ?? '');
	$last_name = trim($_POST['last_name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$department_id = trim((string) ($_POST['department_id'] ?? ''));
	$role_id = trim((string) ($_POST['role_id'] ?? ''));
	$salary_raw = trim((string) ($_POST['salary'] ?? ''));
	$salary = (int) $salary_raw;
	$status = $_POST['status'] ?? '';
	$info = trim($_POST['info'] ?? '');
	$old_image = trim((string) ($_POST['old_image'] ?? ''));
	$remove_image = ($_POST['remove_image'] ?? '0') === '1';
	$upload_image = $_FILES['upload_image'] ?? null;

	if (!notEmpty($first_name))
		$errors['first_name'] = 'Please, the input is empty.';
	if (!notEmpty($last_name))
		$errors['last_name'] = 'Please, the input is empty.';
	if (!notEmpty($email))
		$errors['email'] = 'Please, the input is empty.';
	if (notEmpty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
		$errors['email'] = 'Invalid email format';
	if (!notEmpty($salary_raw)) {
		$errors['salary'] = 'Please, the input is empty.';
	} elseif ($salary <= 0) {
		$errors['salary'] = 'Salary must be greater than 0';
	}
	if (!notEmpty($department_id))
		$errors['department_id'] = 'Please, the input is empty.';
	if (!notEmpty($role_id))
		$errors['role_id'] = 'Please, the input is empty.';
	if (!in_array($status, ['Active', 'Inactive'], true))
		$errors['status'] = 'Please select a valid status';
	if (notEmpty($info) && strlen($info) > 255)
		$errors['info'] = 'Info must be less than 255 characters';

	if (empty($errors)) {
		$user->id = $id;
		$user->first_name = $first_name;
		$user->last_name = $last_name;
		$user->email = $email;
		$user->department_id = $department_id;
		$user->role_id = $role_id;
		$user->salary = $salary;
		$user->status = $status;
		$user->info = $info;
		$user->image_name = $old_image;

		$has_uploaded_image = is_array($upload_image)
			&& isset($upload_image['error'])
			&& (int) $upload_image['error'] !== UPLOAD_ERR_NO_FILE;

		if ($has_uploaded_image) {
			if ($user->set_image($upload_image)) {
				$user->save_image();
				deleteUserImage($old_image);
			} else {
				$errors['upload_image'] = $user->image_error ?: 'Invalid image upload.';
			}
		} elseif ($remove_image) {
			deleteUserImage($old_image);
				$user->image_name = '';
		}

		if (!empty($errors)) {
			$edit_user = new stdClass();
			$edit_user->id = $id;
			$edit_user->first_name = $first_name;
			$edit_user->last_name = $last_name;
			$edit_user->email = $email;
			$edit_user->department_id = $department_id;
			$edit_user->role_id = $role_id;
			$edit_user->salary = $salary_raw;
			$edit_user->status = $status;
			$edit_user->info = $info;
			$edit_user->image_name = $old_image;
			$flash_message = 'Please fix the required fields.';
			$flash_type = 'error';
			return;
		}

		$result = $user->update_data();
		if ($result === true) {
			$_SESSION['flash_message'] = "User updated successfully";
			$_SESSION['flash_type'] = "success";
		} else {
			if (is_string($result) && (stripos($result, 'email already exists') !== false || stripos($result, 'duplicate') !== false || stripos($result, '1062') !== false)) {
				$_SESSION['flash_message'] = 'Please, this email is already used. Change email.';
				$_SESSION['flash_type'] = "error";
				$errors['email'] = 'Please, this email is already used. Change email.';
				$edit_user = (object) [
					'id' => $id,
					'first_name' => $first_name,
					'last_name' => $last_name,
					'email' => $email,
					'department_id' => $department_id,
					'role_id' => $role_id,
					'salary' => $salary_raw,
					'status' => $status,
					'info' => $info,
					'image_name' => $old_image,
				];
				return;
			}
			$_SESSION['flash_message'] = $result;
			$_SESSION['flash_type'] = "error";
		}
		header("Location: index.php");
		exit;
	}

	// Keep modal open with current values when validation fails.
	$edit_user = new stdClass();
	$edit_user->id = $id;
	$edit_user->first_name = $first_name;
	$edit_user->last_name = $last_name;
	$edit_user->email = $email;
	$edit_user->department_id = $department_id;
	$edit_user->role_id = $role_id;
	$edit_user->salary = $salary_raw;
	$edit_user->status = $status;
	$edit_user->info = $info;
	$edit_user->image_name = $old_image;
	$flash_message = 'Please fix the required fields.';
	$flash_type = 'error';
}
?>