<?

function mainAction() {
    $usa = getUsa();

    $usa->model("board");

    $usa->view("main");
    mainView();
}