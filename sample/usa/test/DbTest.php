<?php

class DbTest extends PHPUnit_Framework_TestCase {
    protected $usa;

    protected function setUp() {
        include "../../../core/usa.php";
        include "../configs/web.php";
        /** @var Usa */$this->usa = getUsa();
    }


    public function testSelectTables() {
        $usa = getUsa();
        $usa->model("board");
        $boardDao = new BoardModel();
        $boards = $boardDao->selectAll();
        $this->assertEquals(2, count($boards));

        $usa->model("fileDetail");
        $fileDetailDao = new FileDetailModel();
        $fileDetails = $fileDetailDao->selectAll();
        $this->assertEquals(4, count($fileDetails));

        $usa->model("file");
        $fileDao = new FileModel();
        $files = $fileDao->selectAll();
        $this->assertEquals(2, count($files));

        $usa->model("type");
        $typeDao = new TypeModel();
        $types = $typeDao->selectAll();
        $this->assertEquals(4, count($types));

        $usa->model("user");
        $userDao = new UserModel();
        $users = $userDao->selectAll();
        $this->assertEquals(1, count($users));
    }

    // may we use insert, update, delete

    public function testJoin() {
        $usa = getUsa();
        $usa->model("board");
        $boardJoinDao = new BoardModel();

    }
}