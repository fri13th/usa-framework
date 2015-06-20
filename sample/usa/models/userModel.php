<?php
/**
 * Created by IntelliJ IDEA.
 * User: fri13th
 * Date: 15. 2. 9.
 * Time: 14:01
 */

class UserModel extends BaseModel {

    public $seq;
    public $userid;
    public $password;
    public $nickname;
    public $deleteYn;
    public $createdDate;
    public $lastLoginDate;

    public $columns = array("seq" => "seq", "userid" => "userid", "password" => "userpass", "nickname" => "nickname",
        "deleteYn" => "delete_yn", "createdDate" => "create_dt", "lastLoginDate" => "last_login_dt");
    public $table = "u_user";
    public $pk = "seq";
    protected $insertDateField = "createdDate";
}