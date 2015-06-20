<?php
include "../../../core/usa.php";
include "../configs/web.php";
$usa = getUsa();
$usa->model("board");

class DbTest extends PHPUnit_Framework_TestCase {
    protected $usa;

    protected function setUp() {
    }


    public function testSelectTables() {
//        $usa = getUsa();
//        $boardDao = new BoardModel();
//        $boards = $boardDao->selectAll();
//        $this->assertEquals(2, count($boards));
//
//        $usa->model("fileDetail");
//        $fileDetailDao = new FileDetailModel();
//        $fileDetails = $fileDetailDao->selectAll();
//        $this->assertEquals(4, count($fileDetails));
//
//        $usa->model("file");
//        $fileDao = new FileModel();
//        $files = $fileDao->selectAll();
//        $this->assertEquals(2, count($files));
//
//        $usa->model("type");
//        $typeDao = new TypeModel();
//        $types = $typeDao->selectAll();
//        $this->assertEquals(4, count($types));
//
//        $usa->model("user");
//        $userDao = new UserModel();
//        $users = $userDao->selectAll();
//        $this->assertEquals(1, count($users));
    }

    // may we use insert, update, delete

    public function testJoin() {
        $boardJoinDao = new BoardModel();
        $typeJoin = new BaseJoin("LEFT", "b", "u_type", array("typeCode" => "type_code", "typeTitle" => "title"));
        $typeJoin->onEq("boardType", "typeCode");
        $boardJoinDao->join($typeJoin);

        $boardJoinDao->selectAll();

    }
}