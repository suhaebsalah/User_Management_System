<?php

class department extends database_obj{

    protected static $table_name = "departments";
    protected static $columns = ['id', 'name'];
    public $id;
    public $name;

}
?>