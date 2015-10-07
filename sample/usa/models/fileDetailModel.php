<?php

class UsaFileDetailModel extends BaseModel {

    public $seq;
    public $saveName;
    public $originalName;
    public $createdDate;

    public $columns = array("seq" => "seq", "saveName" => "save_nm", "originalName" => "org_nm",
        "deleteYn" => "delete_yn", "createdDate" => "create_dt");
    public $table = "u_file_detail";
    public $pk = "seq";
    protected $insertDateField = "createdDate";
}