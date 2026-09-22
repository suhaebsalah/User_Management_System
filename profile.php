<?php
require_once 'classes/init.php';

checking(2);

$flash_message = '';
$flash_type = '';

if (!function_exists('isEmptyInput')) {
    function isEmptyInput($value): bool
    {
        return trim((string) $value) === '';
    }
}

if (isset($_POST['update_admin'])) {

    $admin_id = (int) ($_SESSION['id_admin'] ?? 0);
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));

    if (isEmptyInput($username)) {
        $flash_message = 'Please, the input is empty.';
        $flash_type = 'error';
    } elseif (isEmptyInput($email)) {
        $flash_message = 'Please, the input is empty.';
        $flash_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash_message = 'Invalid email format.';
        $flash_type = 'error';
    } else {
        $current = $admin->get_by_id($admin_id);

        $admin->id = $admin_id;
        $admin->username = $username;
        $admin->email = $email;
        $admin->role = $current->role;

        if ($password !== '') {
            $admin->password = password_hash($password, PASSWORD_BCRYPT);
        } else {
            $admin->password = $current->password;
        }

        $result = $admin->update_data();
        if ($result === true) {
            $flash_message = 'Profile updated successfully!';
            $flash_type = 'success';
        } else {
            $flash_message = is_string($result) ? $result : 'Failed to update profile.';
            $flash_type = 'error';
        }
    }
}

if (isset($_POST['add_role'])) {
    $role_name = trim((string) ($_POST['role_name'] ?? ''));
    if (isEmptyInput($role_name)) {
        $flash_message = 'Please, the input is empty.';
        $flash_type = 'error';
    } else {
    $r = new role();
    $r->name = $role_name;
    if ($r->insert_data() === true) {
        $flash_message = "Role added"; $flash_type = "success";
    }
    }
}
if (isset($_POST['update_role'])) {
    $role_name = trim((string) ($_POST['role_name'] ?? ''));
    if (isEmptyInput($role_name)) {
        $flash_message = 'Please, the input is empty.';
        $flash_type = 'error';
    } else {
    $r = new role();
    $r->id = $_POST['role_id'];
    $r->name = $role_name;
    if ($r->update_data($r->id) === true) {
        $flash_message = "Role updated"; $flash_type = "success";
    }
    }
}
if (isset($_POST['delete_role'])) {
    $r = new role();
    $result = $r->delete_data($_POST['role_id']);
    if ($result === true) {
        $flash_message = "Role deleted"; $flash_type = "success";
    } else {
        $flash_message = is_string($result) ? $result : "Failed to delete role.";
        if (stripos($flash_message, 'foreign key constraint fails') !== false) {
            $flash_message = "Cannot delete this role because it is assigned to existing users.";
        }
        $flash_type = "error";
    }
}

if (isset($_POST['add_dept'])) {
    $dept_name = trim((string) ($_POST['dept_name'] ?? ''));
    if (isEmptyInput($dept_name)) {
        $flash_message = 'Please, the input is empty.';
        $flash_type = 'error';
    } else {
    $d = new department();
    $d->name = $dept_name;
    if ($d->insert_data() === true) {
        $flash_message = "Department added"; $flash_type = "success";
    }
    }
}
if (isset($_POST['update_dept'])) {
    $dept_name = trim((string) ($_POST['dept_name'] ?? ''));
    if (isEmptyInput($dept_name)) {
        $flash_message = 'Please, the input is empty.';
        $flash_type = 'error';
    } else {
    $d = new department();
    $d->id = $_POST['dept_id'];
    $d->name = $dept_name;
    if ($d->update_data($d->id) === true) {
        $flash_message = "Department updated"; $flash_type = "success";
    }
    }
}
if (isset($_POST['delete_dept'])) {
    $d = new department();
    $result = $d->delete_data($_POST['dept_id']);
    if ($result === true) {
        $flash_message = "Department deleted"; $flash_type = "success";
    } else {
        $flash_message = is_string($result) ? $result : "Failed to delete department.";
        if (stripos($flash_message, 'foreign key constraint fails') !== false) {
            $flash_message = "Cannot delete this department because it is assigned to existing users.";
        }
        $flash_type = "error";
    }
}

$current_admin = clone $admin;
$current_admin = $current_admin->get_by_id($_SESSION['id_admin']);

/** @var role[] $roles */
$roles = $role->get_all(0);
/** @var department[] $depts */
$depts = $department->get_all(0);

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/profile.css?v=<?php echo time(); ?>">

<main class="main profile-shell">
    <?php if (!empty($flash_message)): ?>
        <div class="app-alert profile-notice <?php echo ($flash_type === 'success') ? 'app-alert-success' : 'app-alert-error'; ?>">
            <?php echo htmlspecialchars($flash_message); ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">
        <section class="identity-panel">
            <div class="identity-header">
                <div class="identity-avatar-wrap">
                    <div class="identity-avatar">
                        <span><?php echo strtoupper(substr($current_admin->username ?? 'A', 0, 1)); ?></span>
                    </div>
                </div>
                <div class="identity-copy">
                    <p class="identity-eyebrow">Admin Profile</p>
                    <h1 class="identity-name"><?php echo htmlspecialchars($current_admin->username ?? ''); ?></h1>
                    <p class="identity-subtitle">Manage account credentials, roles, and departments from one workspace.</p>
                    <p class="identity-mail"><?php echo htmlspecialchars($current_admin->email ?? ''); ?></p>
                    <div class="identity-pill">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <?php echo isset($current_admin->role) && $current_admin->role == 1 ? 'Super Admin' : 'Admin'; ?>
                    </div>
                </div>
            </div>

            <div class="identity-metrics">
                <div class="metric-box">
                    <span class="metric-label">Current Roles</span>
                    <span class="metric-value"><?php echo count($roles); ?></span>
                </div>

                <div class="metric-box">
                    <span class="metric-label">Departments</span>
                    <span class="metric-value"><?php echo count($depts); ?></span>
                </div>
            </div>

            <div class="account-head">
                <h2>Account Settings</h2>
                <small>Keep your credentials up to date. Leave password empty to keep the current one.</small>
               
            </div>

            <form method="POST" class="account-form">
                <div class="field-grid">
                    <div class="field-block">
                        <label>Username</label>
                        <input type="text" name="username" class="input" value="<?php echo htmlspecialchars($current_admin->username ?? ''); ?>">
                        <p class="account-inline-error username-empty-error" style="color:red;display:none;">Please, the input is empty.</p>
                    </div>

                    <div class="field-block">
                        <label>Email Address</label>
                        <input type="email" name="email" class="input" value="<?php echo htmlspecialchars($current_admin->email ?? ''); ?>">
                        <p class="account-inline-error email-empty-error" style="color:red;display:none;">Please, the input is empty.</p>
                        <p class="account-inline-error email-format-error" style="color:red;display:none;">Invalid email format.</p>
                    </div>
                </div>

                <div class="field-block">
                    <label>Password</label>
                    <input type="password" name="password" class="input" placeholder="Leave blank to keep existing password">
                </div>

                <div class="form-toolbar">
                    <button type="submit" name="update_admin" class="btn btn-primary form-save">Update Profile</button>
                  
                </div>
            </form>
        </section>

        <section class="management-area">
            <article class="manager-card">
                <div class="manager-head">
                    <div>
                        <h2>Role Manager</h2>
                        <p>Define role labels used across the system.</p>
                    </div>
                    <span class="manager-count"><?php echo count($roles); ?></span>
                </div>
                <div class="manager-body">
                    <form method="POST" class="manager-add-form">
                        <input type="text" name="role_name" class="input" placeholder="Create new role">
                        <button type="submit" name="add_role" class="btn btn-primary btn-sm">Add</button>
                        <p class="add-inline-empty-error" style="color:red;display:none;">Please, the input is empty.</p>
                    </form>

                    <div class="manager-list">
                        <?php if (!empty($roles)): ?>
                            <?php foreach($roles as $r): ?>
                            <?php /** @var role $r */ ?>
                            <form method="POST" class="manager-row">
                                <input type="hidden" name="role_id" value="<?php echo $r->id; ?>">
                                <input type="text" name="role_name" value="<?php echo htmlspecialchars($r->name); ?>" class="input">
                                <button type="submit" name="update_role" class="btn btn-ghost btn-sm">Save</button>
                                <button type="submit" name="delete_role" class="btn btn-danger-outline btn-sm">Delete</button>
                                <p class="inline-empty-error" style="color:red;display:none;">Please, the input is empty.</p>
                            </form>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="manager-empty">
                                <span class="manager-empty-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8z"/>
                                    </svg>
                                </span>
                                <strong>No roles found</strong>
                                <span>Add your first role to get started.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

            <article class="manager-card">
                <div class="manager-head">
                    <div>
                        <h2>Department Manager</h2>
                        <p>Organize teams and assign users efficiently.</p>
                    </div>
                    <span class="manager-count"><?php echo count($depts); ?></span>
                </div>
                <div class="manager-body">
                    <form method="POST" class="manager-add-form">
                        <input type="text" name="dept_name" class="input" placeholder="Create new department">
                        <button type="submit" name="add_dept" class="btn btn-primary btn-sm">Add</button>
                        <p class="add-inline-empty-error" style="color:red;display:none;">Please, the input is empty.</p>
                    </form>

                    <div class="manager-list">
                        <?php if (!empty($depts)): ?>
                            <?php foreach($depts as $d): ?>
                            <?php /** @var department $d */ ?>
                            <form method="POST" class="manager-row">
                                <input type="hidden" name="dept_id" value="<?php echo $d->id; ?>">
                                <input type="text" name="dept_name" value="<?php echo htmlspecialchars($d->name); ?>" class="input">
                                <button type="submit" name="update_dept" class="btn btn-ghost btn-sm">Save</button>
                                <button type="submit" name="delete_dept" class="btn btn-danger-outline btn-sm">Delete</button>
                                <p class="inline-empty-error" style="color:red;display:none;">Please, the input is empty.</p>
                            </form>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="manager-empty">
                                <span class="manager-empty-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L12 3l9 4.5M4.5 9.75v7.5L12 21l7.5-3.75v-7.5M12 12l9-4.5M12 12L3 7.5"/>
                                    </svg>
                                </span>
                                <strong>No departments found</strong>
                                <span>Create a department to organize users.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </section>
    </div>
</main>

<script>
    setTimeout(function() {
        const alert = document.querySelector('.app-alert');
        if (alert) {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }
    }, 3000);

    document.querySelectorAll('.manager-add-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const input = form.querySelector('input[name="role_name"], input[name="dept_name"]');
            const errorText = form.querySelector('.add-inline-empty-error');

            if (input && input.value.trim() === '') {
                e.preventDefault();
                if (errorText) {
                    errorText.style.display = 'block';
                }
                input.focus();
                return;
            }

            if (errorText) {
                errorText.style.display = 'none';
            }
        });

        const input = form.querySelector('input[name="role_name"], input[name="dept_name"]');
        const errorText = form.querySelector('.add-inline-empty-error');
        if (input && errorText) {
            input.addEventListener('input', function() {
                if (input.value.trim() !== '') {
                    errorText.style.display = 'none';
                }
            });
        }
    });

    document.querySelectorAll('.manager-row').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const clickedButton = e.submitter;
            const isDelete = clickedButton && (clickedButton.name === 'delete_role' || clickedButton.name === 'delete_dept');
            const input = form.querySelector('input[name="role_name"], input[name="dept_name"]');
            const errorText = form.querySelector('.inline-empty-error');

            if (!isDelete && input) {
                if (input.value.trim() === '') {
                    e.preventDefault();
                    if (errorText) {
                        errorText.style.display = 'block';
                    }
                    input.focus();
                    return;
                }
            }

            if (errorText) {
                errorText.style.display = 'none';
            }
        });

        const input = form.querySelector('input[name="role_name"], input[name="dept_name"]');
        const errorText = form.querySelector('.inline-empty-error');
        if (input && errorText) {
            input.addEventListener('input', function() {
                if (input.value.trim() !== '') {
                    errorText.style.display = 'none';
                }
            });
        }
    });

    (function() {
        const accountForm = document.querySelector('.account-form');
        if (!accountForm) return;

        const usernameInput = accountForm.querySelector('input[name="username"]');
        const emailInput = accountForm.querySelector('input[name="email"]');
        const usernameEmptyError = accountForm.querySelector('.username-empty-error');
        const emailEmptyError = accountForm.querySelector('.email-empty-error');
        const emailFormatError = accountForm.querySelector('.email-format-error');

        function show(el) {
            if (el) el.style.display = 'block';
        }

        function hide(el) {
            if (el) el.style.display = 'none';
        }

        function isValidEmail(value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        accountForm.addEventListener('submit', function(e) {
            const clickedButton = e.submitter;
            if (!clickedButton || clickedButton.name !== 'update_admin') return;

            const username = usernameInput ? usernameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            let hasError = false;

            hide(usernameEmptyError);
            hide(emailEmptyError);
            hide(emailFormatError);

            if (username === '') {
                show(usernameEmptyError);
                if (!hasError && usernameInput) {
                    usernameInput.focus();
                }
                hasError = true;
            }

            if (email === '') {
                show(emailEmptyError);
                if (!hasError && emailInput) {
                    emailInput.focus();
                }
                hasError = true;
            } else if (!isValidEmail(email)) {
                show(emailFormatError);
                if (!hasError && emailInput) {
                    emailInput.focus();
                }
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
            }
        });

        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                if (usernameInput.value.trim() !== '') {
                    hide(usernameEmptyError);
                }
            });
        }

        if (emailInput) {
            emailInput.addEventListener('input', function() {
                const value = emailInput.value.trim();
                if (value !== '') {
                    hide(emailEmptyError);
                }
                if (value === '' || isValidEmail(value)) {
                    hide(emailFormatError);
                }
            });
        }
    })();
</script>

<?php require_once 'includes/footer.php'; ?>