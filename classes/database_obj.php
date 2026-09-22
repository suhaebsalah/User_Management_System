<?php

class database_obj{

    protected static $table_name = "users";

    public function get_all($condition){
        if($condition === 0){
         return static::query_process("SELECT * FROM " . static::$table_name);
        }
        else {
            return static::query_process("SELECT * FROM " . static::$table_name . " WHERE department = '" . $condition . "'");
        }
       
    }

    public static function query_process($sql){

        global $db;

        $result = $db->query($sql);

        if(!$result){
            die("Database Query Failed");
        }

        $all_objects = [];

        while($row = $result->fetch_assoc()){
            $all_objects[] = static::instance($row);
        }

        return $all_objects;
    }

    public function get_by_id($id){

        $sql = "SELECT * FROM ".static::$table_name . " WHERE id = '" .$id. "' LIMIT 1";

        $result = static::query_process($sql);

        return !empty($result) ? array_shift($result) : false;
    }



    public static function instance($columns){

        $object = new static();

        foreach($columns as $property=>$value){

            if($object->has_the_property($property)){
                $object->$property = $value;
            }

        }

        return $object;
    }

    private function has_the_property($property){

        $object_properties = get_object_vars($this);
        return array_key_exists($property,$object_properties);

    }

 public function property(){
    global $db;
    $property = [];

    foreach (static::$columns as $column) {
        if(property_exists($this, $column)){
            $property[$column] = "'".$db->escape($this->$column)."'";
        }
    }
    return $property;
}




        public function insert_data() {
        global $db;

       
        $property = $this->property();

        if(empty($property)) return false;

        $columns = array_keys($property);
        $values  = array_values($property);

        $sql = "INSERT INTO ".
        static::$table_name."(".implode(", ",$columns).") VALUES (".implode(", ", $values). ")";

        $execute = $db->query($sql);

        if($execute){
            return true;
        } else {
            if((int)$db->errno === 1062){
                return "This email already exists.";
            }
            return "Error inserting data: " . $db->error;
        }
    }

    public function delete_data($id){
        global $db;

        $sql = "DELETE FROM ".static::$table_name." WHERE id = '".$db->escape($id)."'";

        $execute = $db->query($sql);

        if($execute){
            return true;
        } else {
            return "Error deleting data: " . $db->error;
        }
    }


   
    public function update_data() {
        global $db;
        $properties = $this->property();
        $property_pairs = [];

        foreach ($properties as $key => $value) {
          
          
            $property_pairs[] = "{$key} = {$value}";
        }
        $safe_id = $db->escape($this->id);

        $sql = "UPDATE " . static::$table_name . " SET " . implode(", ", $property_pairs);
        $sql .= " WHERE id = {$safe_id}";

        $result = $db->query($sql);

        if ($result) {
            return true;
        } else {
            if (isset($db->db->errno) && $db->db->errno === 1062) {
                return "This email already exists. Please choose another one.";
            }
            return "Error updating data: " . ($db->db->error ?? '');
        }
    }





}

?>