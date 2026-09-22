<?php
require_once 'classes/init.php';

if (!function_exists('notEmpty')) {
	function notEmpty($value): bool
	{
		return trim((string) $value) !== '';
	}
}

$errors = [];
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';

if (isset($_SESSION['flash_message'], $_SESSION['flash_type'])) {
	unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

if (isset($_POST['save_user'])) {
	$first_name = trim($_POST['first_name'] ?? '');
	$last_name = trim($_POST['last_name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$department_id = trim((string) ($_POST['department_id'] ?? ''));
	$role_id = trim((string) ($_POST['role_id'] ?? ''));
	$salary_raw = trim((string) ($_POST['salary'] ?? ''));
	$salary = (int) $salary_raw;
	$status = $_POST['status'] ?? 'Inactive';
	$upload_image = $_FILES['upload_image'] ?? null;
	$pending_avatar_data = $_POST['pending_avatar_data'] ?? '';
	$info = trim($_POST['info'] ?? '');

	if (!notEmpty($first_name)) {
		$errors['first_name'] = 'Please, the input is empty.';
	}
	if (!notEmpty($last_name)) {
		$errors['last_name'] = 'Please, the input is empty.';
	}
	if (!notEmpty($email)) {
		$errors['email'] = 'Please, the input is empty.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors['email'] = 'Invalid email format';
	}
	if (!notEmpty($salary_raw)) {
		$errors['salary'] = 'Please, the input is empty.';
	} elseif ($salary <= 0) {
		$errors['salary'] = 'Salary must be greater than 0';
	}
	if (!notEmpty($department_id)) {
		$errors['department_id'] = 'Please, the input is empty.';
	}
	if (!notEmpty($role_id)) {
		$errors['role_id'] = 'Please, the input is empty.';
	}
	if (!in_array($status, ['Active', 'Inactive'], true)) {
		$errors['status'] = 'Please select a valid status';
	}
	if (notEmpty($info) && strlen($info) > 255) {
		$errors['info'] = 'Info must be less than 255 characters';
	}

	if (empty($errors)) {
		$user->first_name = $first_name;
		$user->last_name = $last_name;
		$user->email = $email;
		$user->department_id = $department_id;
		$user->role_id = $role_id;
		$user->salary = $salary;
		$user->status = $status;
		$user->info = $info;

		$insert_result = false;
		$has_uploaded_image = is_array($upload_image)
			&& isset($upload_image['error'])
			&& (int) $upload_image['error'] !== UPLOAD_ERR_NO_FILE;

		if ($has_uploaded_image) {
			if (!$user->set_image($upload_image)) {
				$errors['upload_image'] = $user->image_error ?: 'Invalid image upload.';
			} else {
				$insert_result = $user->save_image();
			}
		} elseif (notEmpty($pending_avatar_data)) {
			if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $pending_avatar_data)) {
				$errors['upload_image'] = 'Invalid image data.';
			} else {
				$base64 = substr($pending_avatar_data, strpos($pending_avatar_data, ',') + 1);
				$binary = base64_decode($base64, true);

				if ($binary === false) {
					$errors['upload_image'] = 'Invalid image encoding.';
				} elseif (strlen($binary) > (2 * 1024 * 1024)) {
					$errors['upload_image'] = 'Image size must be 2MB or less.';
				} else {
					$insert_result = $user->save_image_from_binary($binary);
					if ($insert_result !== true) {
						$errors['upload_image'] = $user->image_error ?: 'Failed to save image.';
					}
				}
			}
		} else {
			$insert_result = $user->insert_data();
		}

		if ($insert_result === true) {
			$_SESSION['flash_message'] = 'User added successfully.';
			$_SESSION['flash_type'] = 'success';
			unset($_POST['pending_avatar_data']);
			header('Location:index.php');
			exit;
		}

		if (is_string($insert_result) && (stripos($insert_result, 'email already exists') !== false || stripos($insert_result, 'duplicate') !== false || stripos($insert_result, '1062') !== false)) {
			$errors['email'] = 'Please, this email is already used. Change email.';
			$flash_message = 'Please, this email is already used. Change email.';
			$flash_type = 'error';
		} elseif (isset($errors['upload_image'])) {
			$flash_message = $errors['upload_image'];
			$flash_type = 'error';
		} else {
			$errors['email'] = 'Could not add user. Please try again.';
			$flash_message = 'Error inserting user data.';
			$flash_type = 'error';
		}
	}
}

?>