<?php


class TypeModel extends BaseModel {

    public $typeCode;
    public $title;
    public $deleteYn;
    public $createdDate;
    public $modifyDate;

    public $columns = array("typeCode" => "type_code", "title" => "title", "deleteYn" => "delete_yn",
        "createdDate" => "create_dt", "modifyDate" => "modify_dt");
    public $table = "u_type";
    public $pk = "typeCode";
    protected $insertDateField = "createdDate";
    protected $updateDateField = "modifyDate";

}