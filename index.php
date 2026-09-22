<?php

require_once 'classes/init.php';

checking(2);

require_once 'insert_data.php';
require_once 'edit.php';


$get_users_with_join = $user->get_all_with_join();
$users_count = count($get_users_with_join);


$salary_values = array_map(function ($u) {
	return (int) ($u->salary ?? 0);
}, $get_users_with_join);

$max_salary_in_data = !empty($salary_values) ? max($salary_values) : 10000;
$salary_filter_max = max(10000, (int) (ceil($max_salary_in_data / 100) * 100));
$users_page_limit = 10;
$initial_users = array_slice($get_users_with_join, 0, $users_page_limit);
$initial_loaded_count = count($initial_users);
?>

<?php
require_once 'includes/header.php';
?>

<!-- ========== MAIN ========== -->
<main class="main">
	<?php if (!empty($flash_message)): ?>
		<div id="global-alert"
			class="app-alert <?php echo ($flash_type === 'success') ? 'app-alert-success' : 'app-alert-error'; ?>">
			<?php echo htmlspecialchars($flash_message); ?>
		</div>
	<?php endif; ?>
	<div class="stats-grid">
		<div class="stat-card">
			<div class="stat-label">Total Users</div>
			<div class="stat-value total"><?php echo $users_count; ?></div>
		</div>
		<div class="stat-card">
			<div class="stat-label">Active</div>
			<div class="stat-value active">
				<?php echo count(array_filter($get_users_with_join, 
				function ($u) {
					return $u->status === 'Active'; })); ?>
			</div>
		</div>

		<div class="stat-card">
			<div class="stat-label">Inactive</div>
			<div class="stat-value inactive">
				<?php echo count(array_filter($get_users_with_join, function ($u) {
					return $u->status === 'Inactive'; })); ?>
			</div>
		</div>
		<div class="stat-card">
			<div class="stat-label">Avg Salary</div>
			<div class="stat-value salary">
				<?php echo $users_count > 0 ? '$' . number_format(array_sum(array_column($get_users_with_join, 'salary')), 0) : '$0'; ?>
			</div>
		</div>
	</div>

	<!-- ========== Filters ========== -->
	<div class="filters-panel">
		<div class="filters-row">
			<div class="filter-group flex-1">
				<label class="filter-label" for="search">Search</label>
				<div class="input-with-icon">
					<svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<circle cx="11" cy="11" r="8" />
						<path stroke-linecap="round" d="m21 21-4.35-4.35" />
					</svg>
					<input id="search" type="text" class="input" placeholder="Name, email, role...">
				</div>
			</div>

			<div class="filter-group min-150">
				<label class="filter-label" for="dept-filter">Department</label>
				<select id="dept-filter" class="select" onchange="render()">
					<option value="">All</option>
					<?php
					$departments_list = $department->get_all(0);
					
					foreach ($departments_list as $row) {
						echo '<option value="' . htmlspecialchars($row->name) . '">' . htmlspecialchars($row->name) . '</option>';
					}
					?>
				</select>
			</div>

			<div class="filter-group min-130">
				<label class="filter-label" for="status-filter">Status</label>
				<select id="status-filter" class="select" onchange="render()">
					<option value="">All</option>
					<option value="Active">Active</option>
					<option value="Inactive">Inactive</option>
				</select>
			</div>

			<div class="filter-group salary-group">
				<label class="filter-label">
					Salary range: <span id="sal-range-lbl" class="accent">0 - <?php echo number_format($salary_filter_max, 0, '.', ' '); ?>$</span>
				</label>
				<div class="range-row">
					<input type="range" id="sal-min" class="range-slider" min="0" max="<?php echo $salary_filter_max; ?>" step="100" value="0"
						oninput="render();updateSalLabel()">
					<input type="range" id="sal-max" class="range-slider" min="0" max="<?php echo $salary_filter_max; ?>" step="100" value="<?php echo $salary_filter_max; ?>"
						oninput="render();updateSalLabel()">
				</div>
			</div>

			<div style="display:flex;align-items:flex-end">
				<button type="button" class="btn btn-ghost" onclick="resetFilters()">Reset</button>
			</div>
		</div>
	</div>

		<!-- ========== Users table ========== -->
	<div class="table-card">
		<div class="table-header-bar">
			<span class="result-count">Showing <?php echo $initial_loaded_count; ?> of <?php echo $users_count; ?> users</span>
		</div>
		<div class="table-wrap" <?php echo ($users_count === 0) ? 'style="display:none"' : ''; ?>>
			<table>
				<thead>
					<tr>
						<th>User</th>
						<th>Department</th>
						<th>Role</th>
						<th>Salary</th>
						<th>Status</th>
						<th>Info</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody id="table-body" data-total-users="<?php echo $users_count; ?>" data-offset="<?php echo $initial_loaded_count; ?>" data-limit="<?php echo $users_page_limit; ?>">


					<?php
					foreach ($initial_users as $row) {
						$dept_name = strtolower(trim((string) ($row->department_name ?? '')));
						$dept_palette = [
							'engineering' => 'background:#dbeafe;color:#1d4ed8;',
							'design' => 'background:#ede9fe;color:#7c3aed;',
							'product' => 'background:#fef3c7;color:#92400e;',
							'hr' => 'background:#fce7f3;color:#9d174d;',
							'data' => 'background:#ccfbf1;color:#0f766e;'
						];

						if (isset($dept_palette[$dept_name])) {
							$dept_style = $dept_palette[$dept_name];
						} else {
							$fallback_styles = [
								'background:#e0f2fe;color:#0369a1;',
								'background:#ecfccb;color:#3f6212;',
								'background:#fef3c7;color:#92400e;',
								'background:#fee2e2;color:#b91c1c;',
								'background:#ede9fe;color:#6d28d9;'
							];
							$idx = abs(crc32($dept_name)) % count($fallback_styles);
							$dept_style = $fallback_styles[$idx];
						}

						$avatar_html = strtoupper(htmlspecialchars($row->first_name[0] ?? '?') . htmlspecialchars($row->last_name[0] ?? '?'));
						if (!empty($row->image_name)) {
							$safe_image = htmlspecialchars($row->image_name);
							$safe_name = htmlspecialchars($row->first_name . ' ' . $row->last_name);
							$avatar_html = '<img src="assets/uploads/thumbs/' . $safe_image . '" alt="' . $safe_name . '" width="38" height="38" loading="lazy" onerror="this.onerror=null;this.src=\'assets/uploads/' . $safe_image . '\';">';
						}
						echo '<tr class="fade-in" data-id="' . $row->id . '" data-department="' . htmlspecialchars(strtolower((string) $row->department_name)) . '" data-status="' . htmlspecialchars(strtolower((string) $row->status)) . '" data-salary="' . (int) $row->salary . '">
        <td>
        <div class="user-cell">
            <div class="avatar av-blue">
				' . $avatar_html . '
			</div>
            <div>
                <div class="user-name">' . htmlspecialchars($row->first_name) . ' ' . htmlspecialchars($row->last_name) . '</div>
                <div class="user-email">' . htmlspecialchars($row->email) . '</div>
            </div>
        </div></td>
		<td><span class="tag" style="' . $dept_style . '">' . htmlspecialchars($row->department_name) . '</span></td>

        <td style="color:var(--text-muted);font-size:13px">' . htmlspecialchars($row->role_name) . '</td>
        <td><span class="salary-val"><span style="color:var(--text-muted);font-size:14px"> $</span>' . htmlspecialchars(number_format($row->salary, 0)) . '</span></td>
        <td><span class="tag ' . (htmlspecialchars($row->status) === 'Active' ? 'tag-active' : 'tag-inactive') . '">' . htmlspecialchars($row->status) . '</span></td>
        <td><span class="info-cell">' . htmlspecialchars($row->info) . '</span></td>
        <td>
            <div class="actions-cell">
				<a href="index.php?edit_id=' . $row->id . '" 
	class="btn btn-ghost btn-sm" data-action="edit" data-id="' . $row->id . '">
   Edit
</a>
				<a href="#" class="btn btn-danger-outline btn-sm"  data-action="delete" 	data-id="' . $row->id . '">Delete</a>
            </div>
        </td>
    </tr>';
					}
					?>

				</tbody>
			</table>
		</div>
		<div id="empty-state" class="empty-state <?php echo ($users_count === 0) ? 'visible' : ''; ?>">
			<svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
					d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8z" />
			</svg>
			<p>No data found</p>
			<span>Please add user data.</span>
		</div>
	</div>
</main>




<!-- ============ Add user form ============ -->
<div id="modal-bg" class="modal-bg <?php echo !empty($errors) ? 'open' : ''; ?>">
	<div class="modal">
		<form id="add-user-form" action="index.php" method="POST" enctype="multipart/form-data">

			<div class="modal-head">
				<div>
					<h2 id="modal-title" class="modal-title">Add user</h2>
					<p class="modal-subtitle">Create a team profile with role and department details.</p>
				</div>
				<button class="btn btn-icon" type="button" onclick="closeModal()">
					<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</button>
			</div>

			<div class="modal-body">
				<div class="modal-section modal-section-profile">
					<div class="modal-section-title">Profile</div>
					<div class="av-upload-row">
						<div id="av-preview" class="av-preview">
							<span id="av-initials">?</span>
						</div>
						<div class="av-upload-info">
							<input type="file" id="img-input" name="upload_image" accept="image/*" style="display:none"
								onchange="handleImg(this)">
							<input type="hidden" id="pending-avatar-data" name="pending_avatar_data"
								value="<?php echo htmlspecialchars($_POST['pending_avatar_data'] ?? '', ENT_QUOTES); ?>">
							<input class="btn btn-ghost btn-sm" type="button"
								onclick="document.getElementById('img-input').click()" value="Upload photo">
							<button type="button" class="btn btn-danger-outline btn-sm" style="display:none;"
								onclick="removeSelectedImage('add')" id="btn-remove-add-img">Remove</button>
							<span class="av-hint">JPG, PNG up to 2MB</span>

							<small id="img-error"
								class="form-error"><?php echo isset($errors['upload_image']) ? htmlspecialchars($errors['upload_image']) : ''; ?></small>
						</div>
					</div>
				</div>

				<div class="modal-section">
					<div class="modal-section-title">Basic information</div>
					<div class="form-grid-2">
						<div class="form-field">
							<label class="form-label" for="f-first">First name <span
									class="field-required">*</span></label>
							<input id="f-first" class="input form-input" placeholder="Sara" name="first_name"
								value="<?php echo htmlspecialchars($first_name ?? ''); ?>">
							<small class="form-error"><?php echo isset($errors['first_name']) ? htmlspecialchars($errors['first_name']) : ''; ?></small>
						</div>
						<div class="form-field">
							<label class="form-label" for="f-last">Last name <span
									class="field-required">*</span></label>
							<input id="f-last" class="input form-input" placeholder="Ahmed" name="last_name"
								value="<?php echo htmlspecialchars($last_name ?? ''); ?>">
							<small class="form-error"><?php echo isset($errors['last_name']) ? htmlspecialchars($errors['last_name']) : ''; ?></small>
						</div>
					</div>

					<div class="form-field">
						<label class="form-label" for="f-email">Email <span class="field-required">*</span></label>
						<input id="f-email" name="email" type="email" class="input form-input"
							placeholder="sara@company.com" value="<?php echo htmlspecialchars($email ?? ''); ?>">
						<small class="form-error"><?php echo isset($errors['email']) ? htmlspecialchars($errors['email']) : ''; ?></small>
					</div>
				</div>

				<div class="modal-section">
					<div class="modal-section-title">Work details</div>
					<div class="form-grid-2">
						<div class="form-field">
							<label class="form-label" for="f-dept">Department</label>
							<select id="f-dept" class="select form-select" name="department_id">
								<option value="" disabled <?php echo empty($department_id) ? 'selected' : ''; ?>>select
									the department</option>
								<?php
								$get_departments = $department->get_all(0);
								foreach ($get_departments as $row) {
									$selected_dept = ((string) ($department_id ?? '') === (string) $row->id) ? ' selected' : '';
									echo '<option value="' . $row->id . '"' . $selected_dept . '>' . htmlspecialchars($row->name) . '</option>';
								}
								?>
							</select>
							<small class="form-error"><?php echo isset($errors['department_id']) ? htmlspecialchars($errors['department_id']) : ''; ?></small>
						</div>

						<div class="form-field">
							<label class="form-label" for="f-role">Role</label>
							<select id="f-role" class="select form-select" name="role_id">
								<option value="" disabled <?php echo empty($role_id) ? 'selected' : ''; ?>>select the
									role</option>
								<?php
								$rolea = $role->get_all(0);
								foreach ($rolea as $row) {
									$selected_role = ((string) ($role_id ?? '') === (string) $row->id) ? ' selected' : '';
									echo '<option value="' . $row->id . '"' . $selected_role . '>' . htmlspecialchars($row->name) . '</option>';
								}
								?>
							</select>
							<small class="form-error"><?php echo isset($errors['role_id']) ? htmlspecialchars($errors['role_id']) : ''; ?></small>
						</div>
					</div>

					<div class="form-grid-2">
						<div class="form-field">
							<label class="form-label" for="f-salary">Salary (USD/year)</label>
							<input id="f-salary" name="salary" type="number" class="input form-input"
								placeholder="85000" value="<?php echo htmlspecialchars((string) ($salary ?? '')); ?>">
							<small class="form-error"><?php echo isset($errors['salary']) ? htmlspecialchars($errors['salary']) : ''; ?></small>
						</div>
						<div class="form-field">
							<label class="form-label" for="f-status">Status</label>
							<select id="f-status" name="status" class="select form-select">
								<option value="Active" <?php echo (($status ?? '') === 'Active') ? 'selected' : ''; ?>>
									Active</option>
								<option value="Inactive" <?php echo (($status ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
							</select>
							<small class="form-error"><?php echo isset($errors['status']) ? htmlspecialchars($errors['status']) : ''; ?></small>
						</div>
					</div>

					<div class="form-field">
						<label class="form-label" for="f-info">Additional info</label>
						<input id="f-info" class="input form-input" name="info"
							placeholder="Location, notes, experience..."
							value="<?php echo htmlspecialchars($info ?? ''); ?>">
						<small class="form-error"><?php echo isset($errors['info']) ? htmlspecialchars($errors['info']) : ''; ?></small>
					</div>
				</div>
			</div>

			<div class="modal-foot">
				<input class="btn btn-ghost" onclick="closeModal()" type="button" value="Cancel">
				<input class="btn btn-primary" name="save_user" type="submit" value="Save">
			</div>
		</form>
	</div>
</div>
</div>

<!-- ============ Edit user form ============ -->

<div id="edit-modal-bg" class="modal-bg <?php echo $edit_user ? 'open' : ''; ?>">


	<div class="modal">
		<form id="edit-user-form" action="index.php" method="POST" enctype="multipart/form-data">
			<input type="hidden" id="e-id" name="id"
				value="<?php echo htmlspecialchars($edit_user ? $edit_user->id : ''); ?>">
			<input type="hidden" name="old_image" id="e-old-image"
				value="<?php echo htmlspecialchars($edit_user ? $edit_user->image_name : ''); ?>">
			<div class="modal-head">
				<div>
					<h2 id="edit-modal-title" class="modal-title">Edit user</h2>
					<p class="modal-subtitle">Update user profile, role, and department details.</p>
				</div>
				<button class="btn btn-icon" type="button" onclick="closeEditModal()">
					<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</button>
			</div>

			<div class="modal-body">
				<div class="modal-section modal-section-profile">
					<div class="modal-section-title">Profile</div>
					<div class="av-upload-row">
						<div id="e-av-preview" class="av-preview">
							<?php if (!empty($_POST['update_user']) && !empty($_POST['pending_avatar_data'])): ?>
								<img src="<?php echo htmlspecialchars($_POST['pending_avatar_data']); ?>" alt="Avatar" width="70" height="70" loading="lazy">
							<?php elseif ($edit_user && !empty($edit_user->image_name)): ?>
								<img src="assets/uploads/thumbs/<?php echo htmlspecialchars($edit_user->image_name); ?>"
									alt="Avatar" width="70" height="70" loading="lazy"
									onerror="this.onerror=null;this.src='assets/uploads/<?php echo htmlspecialchars($edit_user->image_name); ?>';">
							<?php else: ?>
								<span
									id="e-av-initials"><?php echo htmlspecialchars($edit_user ? ($edit_user->first_name[0] ?? '?') . ($edit_user->last_name[0] ?? '?') : '?'); ?></span>
							<?php endif; ?>
						</div>
						<div class="av-upload-info">
							<input type="file" id="e-img-input" name="upload_image" accept="image/*"
								style="display:none" onchange="handleImgEdit(this)">

							<input type="hidden" id="e-pending-avatar-data" name="pending_avatar_data"
								value="<?php echo htmlspecialchars($_POST["pending_avatar_data"] ?? "", ENT_QUOTES); ?>">
							<input class="btn btn-ghost btn-sm" type="button"
								onclick="document.getElementById('e-img-input').click()" value="Upload photo">
							<button type="button" class="btn btn-danger-outline btn-sm"
								onclick="removeSelectedImage('edit')" id="btn-remove-edit-img" <?php echo (($edit_user && !empty($edit_user->image_name)) || (!empty($_POST['update_user']) && !empty($_POST['pending_avatar_data']))) ? '' : 'style="display:none;"'; ?>>Remove</button>
							<input type="hidden" name="remove_image" id="e-remove-image" value="0">
							<span class="av-hint">JPG, PNG up to 2MB</span>
							<small id="e-img-error"
								class="form-error"><?php echo (isset($_POST["update_user"]) && isset($errors["upload_image"])) ? htmlspecialchars($errors["upload_image"]) : ""; ?></small>
						</div>
					</div>
				</div>

				<div class="modal-section">
					<div class="modal-section-title">Basic information</div>
					<div class="form-grid-2">
						<div class="form-field">
							<label class="form-label" for="e-first">First name <span
									class="field-required">*</span></label>
							<input id="e-first" class="input form-input" name="first_name"
								value="<?php echo htmlspecialchars($edit_user ? $edit_user->first_name : ''); ?>">
							<small class="form-error"><?php echo (isset($_POST['update_user']) && isset($errors['first_name'])) ? htmlspecialchars($errors['first_name']) : ''; ?></small>

						</div>
						<div class="form-field">
							<label class="form-label" for="e-last">Last name <span
									class="field-required">*</span></label>
							<input id="e-last" class="input form-input" placeholder="Ahmed" name="last_name"
								value="<?php echo htmlspecialchars($edit_user ? $edit_user->last_name : ''); ?>">
							<small class="form-error"><?php echo (isset($_POST['update_user']) && isset($errors['last_name'])) ? htmlspecialchars($errors['last_name']) : ''; ?></small>
						</div>
					</div>

					<div class="form-field">
						<label class="form-label" for="e-email">Email <span class="field-required">*</span></label>
						<input id="e-email" name="email" type="email" class="input form-input"
							placeholder="sara@company.com"
							value="<?php echo htmlspecialchars($edit_user ? $edit_user->email : ''); ?>">
						<small class="form-error"><?php echo (isset($_POST['update_user']) && isset($errors['email'])) ? htmlspecialchars($errors['email']) : ''; ?></small>
					</div>
				</div>

				<div class="modal-section">
					<div class="modal-section-title">Work details</div>
					<div class="form-grid-2">
						<div class="form-field">
							<label class="form-label" for="e-dept">Department</label>
							<select id="e-dept" class="select form-select" name="department_id">
								<option value="" disabled <?php echo (!$edit_user || empty($edit_user->department_id)) ? 'selected' : ''; ?>>select the department</option>
								<?php
								foreach ($department->get_all(0) as $row) {
									echo "<option value=\"" . $row->id . "\"" . ($edit_user && $edit_user->department_id == $row->id ? " selected" : "") . ">" . htmlspecialchars($row->name) . "</option>";
								}
								?>
							</select>
							<small class="form-error"><?php echo (isset($_POST['update_user']) && isset($errors['department_id'])) ? htmlspecialchars($errors['department_id']) : ''; ?></small>
						</div>

						<div class="form-field">
							<label class="form-label" for="e-role">Role</label>
							<select id="e-role" class="select form-select" name="role_id">
								<option value="" disabled <?php echo (!$edit_user || empty($edit_user->role_id)) ? 'selected' : ''; ?>>select the role</option>
								<?php
								foreach ($role->get_all(0) as $row) {
									echo "<option value=\"" . $row->id . "\"" . ($edit_user && $edit_user->role_id == $row->id ? " selected" : "") . ">" . htmlspecialchars($row->name) . "</option>";
								}
								?>
							</select>
							<small class="form-error"><?php echo (isset($_POST['update_user']) && isset($errors['role_id'])) ? htmlspecialchars($errors['role_id']) : ''; ?></small>
						</div>
					</div>

					<div class="form-grid-2">
						<div class="form-field">
							<label class="form-label" for="e-salary">Salary (USD/year)</label>
							<input id="e-salary" name="salary" type="number" class="input form-input"
								placeholder="85000"
								value="<?php echo htmlspecialchars($edit_user ? $edit_user->salary : ''); ?>">
							<small class="form-error"><?php echo (isset($_POST['update_user']) && isset($errors['salary'])) ? htmlspecialchars($errors['salary']) : ''; ?></small>
						</div>
						<div class="form-field">
							<label class="form-label" for="e-status">Status</label>
							<select name="status" class="select form-select">
								<option value="Active" <?php echo ($edit_user && $edit_user->status === 'Active') ? 'selected' : ''; ?>>
									Active</option>

								<option value="Inactive" <?php echo ($edit_user && $edit_user->status === 'Inactive') ? 'selected' : ''; ?>>
									Inactive</option>
							</select>
						</div>
					</div>

					<div class="form-field">
						<label class="form-label" for="e-info">Additional info</label>
						<input id="e-info" class="input form-input" name="info"
							placeholder="Location, notes, experience..."
							value="<?php echo htmlspecialchars($edit_user ? $edit_user->info : ''); ?>">
					</div>
				</div>
			</div>

			<div class="modal-foot">
				<input class="btn btn-ghost" onclick="closeEditModal()" type="button" value="Cancel">
				<input class="btn btn-primary" name="update_user" type="submit" value="Save">
			</div>
		</form>
	</div>
</div>

<!-- ============ Delete user  ============ -->
<div id="confirm-bg" class="modal-bg" onclick="if(event.target===this)closeConfirm()">
	<div class="modal confirm-modal">
		<div class="confirm-icon">
			<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round"
					d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
			</svg>
		</div>
		<div class="confirm-title">Delete user?</div>
		<div class="confirm-text">This action cannot be undone.</div>
		<div class="confirm-btns">
			<button class="btn btn-ghost" onclick="closeConfirm()">Cancel</button>
			<button id="confirm-btn" class="btn btn-danger">Delete</button>
		</div>
	</div>
</div>

<div id="toast" class="toast">
	<svg class="toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
		<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
	</svg>
	<span id="toast-msg">Saved</span>
</div>
<?php
// Load layout footer (JS scripts)
require_once "includes/footer.php";
?>