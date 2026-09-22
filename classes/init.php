<?php

// ==================== Base Path ====================
define('BASE_PATH', __DIR__);

// ==================== Require Files ====================

require_once BASE_PATH . '/../classes/db.php';
require_once BASE_PATH . '/../classes/database_obj.php';
require_once BASE_PATH . '/../classes/department.php';
require_once BASE_PATH . '/../classes/role.php';
require_once BASE_PATH . '/../classes/user.php';
require_once BASE_PATH . '/../classes/session.php';
require_once BASE_PATH . '/../classes/admin.php';

// ==================== Initialize Objects ====================
$db=new Config();
$department=new department();
$role=new role();
$user=new user();
$session=new Session();
$admin=new admin();

require_once BASE_PATH . '/../classes/function.php';


?>
