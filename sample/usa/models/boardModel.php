<?php

class BoardModel extends BaseModel {

    public $seq;
    public $boardType;
    public $title;
    public $content;
    public $author;
    public $thumbnailSeq;
    public $deleteYn;
    public $createdDate;
    public $modifyDate;

    public $columns = array("seq" => "seq", "boardType" => "board_type", "title" => "title", "content" => "content",
        "author" => "author", "thumbnailSeq" => "thumbnail_seq", "deleteYn" => "delete_yn", "createdDate" => "create_dt",
        "modifyDate" => "modify_dt");

    public $table = "u_board";
    public $pk = "seq";
    protected $insertDateField = "createdDate";
    protected $updateDateField = "modifyDate";

}