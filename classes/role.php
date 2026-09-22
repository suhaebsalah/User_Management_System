<?php

class role extends database_obj{

    protected static $table_name = "roles";
    protected static $columns = ['id', 'name'];
    public $id;
    public $name;

}


?>