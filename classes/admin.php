<?php
class admin extends database_obj {
        protected static $table_name = "admin";
        protected static $columns = ['id','username','email','password','role'];
        public $id;
        public $username;
        public $email;
        public $password;
        public $role;

        public static function verify_admin($username,$password){
            global $db;

            $safe_username = $db->escape($username);
            $sql = "SELECT * FROM admin WHERE username='" . $safe_username . "' LIMIT 1";
            $result = $db->query($sql);

            if (!$result || $result->num_rows !== 1) {
                return false;
            }

            $row = $result->fetch_assoc();
            $stored_password = $row['password'] ?? '';

            // Support bcrypt-hashed passwords and keep a fallback for legacy plain-text rows.
            $is_valid = password_verify($password, $stored_password) || hash_equals((string)$stored_password, (string)$password);
            if (!$is_valid) {
                return false;
            }

            return static::instance($row);
        }

        public static function username_exists($username) {
            global $db;

            $safe_username = $db->escape($username);
            $sql = "SELECT id FROM admin WHERE username='" . $safe_username . "' LIMIT 1";
            $result = $db->query($sql);

            return ($result && $result->num_rows === 1);
        }
}



?>