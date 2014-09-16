<?

function mainAction() {
    global $usa;

    $usa->model("board");

    $usa->view("main");
    mainView();
}