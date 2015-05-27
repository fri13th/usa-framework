<?php
/**
 * Created by IntelliJ IDEA.
 * User: fri13th
 * Date: 15. 2. 9.
 * Time: 14:01
 */

class FileModel extends BaseModel {

    public $seq;
    public $boardSeq;
    public $fileDetailSeq;
    public $caption;
    public $deleteYn;
    public $createdDate;

    public $columns = array("seq" => "seq", "boardSeq" => "board_seq", "fileDetailSeq" => "file_detail_seq", "caption" => "caption",
        "deleteYn" => "delete_yn", "createdDate" => "create_dt");
    public $table = "u_file";
    public $pk = "seq";
    protected $insertDateField = "createdDate";
}