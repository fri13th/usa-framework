<?

function mainAction() {
    $usa = getUsa();

    $usa->view("main");
    mainView();
}