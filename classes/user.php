<?php
class user extends database_obj {

    protected static $table_name = "users";

    protected static $columns = [
        'id','first_name','last_name','email','department_id','role_id','salary','status','info','image_name'
    ];

    //Properties for users table
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $department_id;
    public $role_id;
    public $salary;
    public $status;
    public $info;

    //Properties for image upload
    public $image_name;
    public $image_size;
    public $image_type;
    public $image_tmp_name;
    public $directory = "assets/uploads/";
    public $thumb_directory = "assets/uploads/thumbs/";
    public $image_error;
    public $array_of_errors =array(
        UPLOAD_ERR_OK => "No error, the file uploaded with success.",
        UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
        UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
        UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
        UPLOAD_ERR_NO_FILE => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
    );


    //Properties for joined data
    public $department_name;
    public $role_name;


    //Methods
    public function get_all_with_join() {
        global $db;
        $sql = "SELECT u.*, 
                       d.name AS department_name, 
                       r.name AS role_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN roles r ON u.role_id = r.id";

        $result = $db->query($sql);

        $all_users = [];
        while($row = $result->fetch_assoc()){
            $all_users[] = static::instance($row);
        }
        return $all_users;
    }






    public function set_image($image){
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if(empty($image) || !is_array($image) || !isset($image['name'])){
            $this->image_error = $this->array_of_errors[UPLOAD_ERR_NO_FILE];
            return false;
        }

        if((int)$image['error'] === UPLOAD_ERR_NO_FILE){
            $this->image_error = $this->array_of_errors[UPLOAD_ERR_NO_FILE];
            return false;
        }

        if((int)$image['error'] !== UPLOAD_ERR_OK){
            $this->image_error = $this->array_of_errors[$image['error']] ?? 'Image upload error.';
            return false;
        }

        $image_actual_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        $image_type = $image['type'] ?? '';

        if(!in_array($image_actual_ext, $allowed_extensions, true) || !in_array($image_type, $allowed_mimes, true)){
            $this->image_error = 'Invalid image type. Allowed: JPG, JPEG, PNG, GIF, WEBP.';
            return false;
        }

        $this->image_name = uniqid('img_', true) . '.' . $image_actual_ext;
        $this->image_tmp_name = $image['tmp_name'];
        $this->image_size = $image['size'] ?? 0;
        $this->image_type = $image_type;
        $this->image_error = '';
        return true;
    }

    private function ensure_directory($path){
        if(is_dir($path)){
            return true;
        }
        return mkdir($path, 0755, true);
    }

    private function create_image_resource_from_path($source_path, $mime){
        switch($mime){
            case 'image/jpeg':
                return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($source_path) : false;
            case 'image/png':
                return function_exists('imagecreatefrompng') ? @imagecreatefrompng($source_path) : false;
            case 'image/gif':
                return function_exists('imagecreatefromgif') ? @imagecreatefromgif($source_path) : false;
            case 'image/webp':
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source_path) : false;
            default:
                return false;
        }
    }

    private function extension_from_mime($mime){
        switch($mime){
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            default:
                return 'jpg';
        }
    }

    private function save_original_with_thumb_copy($source_path, $mime){
        if(!$this->ensure_directory($this->directory) || !$this->ensure_directory($this->thumb_directory)){
            $this->image_error = $this->array_of_errors[UPLOAD_ERR_CANT_WRITE];
            return false;
        }

        $ext = $this->extension_from_mime($mime);
        $base_name = uniqid('img_', true) . '.' . $ext;
        $main_path = $this->directory . $base_name;
        $thumb_path = $this->thumb_directory . $base_name;

        if(!@copy($source_path, $main_path)){
            $this->image_error = 'Failed to save image file.';
            return false;
        }

        @copy($source_path, $thumb_path);

        $this->image_name = $base_name;
        return true;
    }

    private function create_webp_thumbnail($source_image, $target_path, $target_width = 96, $target_height = 96, $quality = 60){
        if(!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')){
            return false;
        }

        $src_w = imagesx($source_image);
        $src_h = imagesy($source_image);
        if($src_w <= 0 || $src_h <= 0){
            return false;
        }

        $thumb = imagecreatetruecolor($target_width, $target_height);
        if(!$thumb){
            return false;
        }

        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);

        $scale = min($target_width / $src_w, $target_height / $src_h);
        $new_w = max(1, (int) floor($src_w * $scale));
        $new_h = max(1, (int) floor($src_h * $scale));
        $dst_x = (int) floor(($target_width - $new_w) / 2);
        $dst_y = (int) floor(($target_height - $new_h) / 2);

        imagecopyresampled($thumb, $source_image, $dst_x, $dst_y, 0, 0, $new_w, $new_h, $src_w, $src_h);
        $saved = imagewebp($thumb, $target_path, $quality);
        imagedestroy($thumb);
        return $saved;
    }

    private function save_webp_and_thumb_from_path($source_path, $mime){
        if(!function_exists('imagewebp') || !function_exists('imagecreatetruecolor')){
            return $this->save_original_with_thumb_copy($source_path, $mime);
        }

        if(!$this->ensure_directory($this->directory) || !$this->ensure_directory($this->thumb_directory)){
            $this->image_error = $this->array_of_errors[UPLOAD_ERR_CANT_WRITE];
            return false;
        }

        $source_image = $this->create_image_resource_from_path($source_path, $mime);
        if(!$source_image){
            $this->image_error = 'Invalid source image.';
            return false;
        }

        $base_name = uniqid('img_', true) . '.webp';
        $main_path = $this->directory . $base_name;
        $thumb_path = $this->thumb_directory . $base_name;

        $saved_main = imagewebp($source_image, $main_path, 75);
        $saved_thumb = $this->create_webp_thumbnail($source_image, $thumb_path, 96, 96, 60);
        imagedestroy($source_image);

        if(!$saved_main || !$saved_thumb){
            if(file_exists($main_path)) unlink($main_path);
            if(file_exists($thumb_path)) unlink($thumb_path);
            $this->image_error = 'Failed to save optimized image.';
            return false;
        }

        $this->image_name = $base_name;
        return true;
    }

    public function save_image_from_binary($binary){
        if(empty($binary)){
            $this->image_error = 'Invalid image data.';
            return false;
        }

        $tmp_path = tempnam(sys_get_temp_dir(), 'img_');
        if($tmp_path === false){
            $this->image_error = 'Failed to create temp image.';
            return false;
        }

        file_put_contents($tmp_path, $binary);
        $image_info = @getimagesize($tmp_path);
        $mime = $image_info['mime'] ?? '';
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if(!in_array($mime, $allowed_mimes, true)){
            @unlink($tmp_path);
            $this->image_error = 'Invalid image type. Allowed: JPG, JPEG, PNG, GIF, WEBP.';
            return false;
        }

        $saved = $this->save_webp_and_thumb_from_path($tmp_path, $mime);
        @unlink($tmp_path);

        if(!$saved){
            return false;
        }

        return $this->insert_data();
    }

    public function save_image(){
        /* if($this->id){
            $this->update();
        } */

        if(!empty($this->image_error)){
            return false;
        }
        if(empty($this->image_name) || empty($this->image_tmp_name)){
            $this->image_error = $this->array_of_errors[UPLOAD_ERR_NO_FILE];
            return false;
        }
        $directory = $this->directory . $this->image_name;
        if(file_exists($directory)){
            $this->image_error = $this->array_of_errors[UPLOAD_ERR_EXTENSION];
            return false;
        }

        $saved = $this->save_webp_and_thumb_from_path($this->image_tmp_name, $this->image_type);
        if($saved){
            if($this->insert_data()){
                unset($this->image_tmp_name);
                return true;
            }
        } else {
            $this->image_error = $this->array_of_errors[UPLOAD_ERR_CANT_WRITE];
            return false;
        }
        

    }

}

?>