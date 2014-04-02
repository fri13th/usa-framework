<?php
/**
 * User: fri13th
 * Date: 2013/02/17 10:27
 */



define("LEVEL_GUEST", 60);      // not logged
define("LEVEL_DROP", 50);       // block login
define("LEVEL_CLOSED", 40);     // block login
define("LEVEL_FREE", 30);       // only login
define("LEVEL_PREMIUM", 20);     // expired by one year
define("LEVEL_ADMIN", 10);       // admin mode, restaurant manage

class WebConfig extends UsaConfig {
    function __construct() {
        $this->domain = filter_input(INPUT_SERVER, "HTTP_HOST");
        $this->app = substr(basename(__FILE__), 0, -4);
        $this->db_type = "mssql";
        $this->data = array(
            "url.login" => "/secure/auth/login",
            "url.block.admin" => false,
            "url.block.premium" => false,
            "message.block.admin" => "접근하실수 없는 서비스 입니다.",
            "message.block.premium" => "유료회원 가입이 필요한 서비스입니다."
        );

        if (strstr($this->domain, "localhost") || preg_match('/^[\d\.:]*$/', $this->domain)  || (php_sapi_name() == "cli")) {
            $this->debug = true;
            $this->debug_mode = "local";
            $this->db_url = "mysql:host=localhost;dbname=usagidb;charset=utf8";
            $this->db_userid = "root";
            $this->db_password = "usagi";
            $this->db_options = array();
        }
        else {
            $this->debug = false;
            $this->debug_mode = "real";
            $this->db_url = "mysql:host=localhost;dbname=usagidb;charset=utf8";
            $this->db_userid = "root";
            $this->db_password = "usagi";
            $this->db_options = array(PDO::ATTR_PERSISTENT => false,);
        }
    }

}

class WebHttpRedirect extends UsaHttpRedirect {
    public function redirectWith($url,$message) {
        echo "<!DOCTYPE html>
            <html lang='ko'>
            <head><meta charset='UTF-8'/></head>
            <body>
                <script>
                    alert('$message');
                    location.href='$url';
                </script>
            </body>
            </html>
        ";
        exit();
    }
    public function goBackWith($message) {
        $usa = getUsa();
        echo "<!DOCTYPE html>
            <html lang='ko'>
            <head><meta charset='UTF-8'/></head>
            <body>
                <script>
                    alert('$message');
                    var d = document.domain;
                    if (d.indexOf('{$usa->config("domain")}') < 0) location.href='/';
                    else history.back();
                </script>
            </body>
            </html>
        ";
        exit();
    }
}

class WebSession extends UsaSession{
    function init() {
        if(!isset($_SESSION["session.usa"])) {
            $_SESSION["session.usa"]["level"] = LEVEL_GUEST;
            $_SESSION["session.usa"]["username"] = "Guest";
        }
    }
    function session($key, $value = NULL) {
        return ($value == NULL) ? $_SESSION["session.usa"][$key] : ($_SESSION["session.usa"][$key] = $value) && false;
    }
    function auth($limitLevel) {
        $usa = getUsa();
        if ($limitLevel <= LEVEL_ADMIN && $usa->session("level") > LEVEL_ADMIN) {
            if ($usa->config("url.block.admin")) $usa->redirectTo($usa->config("url.block.admin"));
            else if ($usa->config("message.block.admin")) $usa->goBackWith($usa->config("message.block.admin"));
            else $usa->redirectTo($usa->config("url.login"));
        }
        if ($limitLevel <= LEVEL_ALL_PREMIUM && $usa->session("level") > LEVEL_ALL_PREMIUM) {
            if ($usa->config("url.block.premium")) $usa->redirectTo($usa->config("url.block.premium"));
            else if ($usa->config("message.block.premium")) $usa->goBackWith($usa->config("message.block.premium"));
            else $usa->redirectTo($usa->config("url.login"));
        }
        if ($limitLevel <= LEVEL_FREE && $usa->session("level") > LEVEL_FREE) {
            $usa->session("referrer", filter_input(INPUT_SERVER, "REQUEST_URI"));
            $usa->redirectTo($usa->config("url.login"));
        }
    }
}

class BootstrapPaginate extends BasePaginate {

    function setTotalCount($totalCount) {
        $this->totalCount = $totalCount;

        if ($this->currentPage < 1 || $this->totalCount < 1 || $this->totalCount < (($this->currentPage - 1)*$this->paginationSize)) { // we don't need to search
            $this->searchable = false;
            return;
        }
        $this->searchable = true;
        $this->totalPage = (int)($this->totalCount/$this->paginationSize) + (($this->totalCount % $this->paginationSize == 0) ? 0 : 1);

        if ($this->endAt > $this->totalCount)
            $this->endAt = $this->totalCount;

        $this->prev = $this->currentPage - 1;
        $this->next = $this->currentPage + 1;

        $this->startPage = (($this->currentPage - $this->listSize) < 1) ? 1 : ($this->currentPage - $this->listSize);
        if (($this->totalPage > ($this->listSize*2 + 3)) && ($this->startPage > $this->totalPage - $this->listSize*2 - 1)) {
            $this->startPage = $this->totalPage - $this->listSize*2 - 1;
        }
        $this->endPage = (($this->currentPage + $this->listSize) > $this->totalPage) ? $this->totalPage : ($this->currentPage + $this->listSize);
        if ($this->totalPage > ($this->listSize*2 + 3) && $this->endPage < ($this->listSize*2 + 1)) {
            $this->endPage = $this->listSize*2 + 1;
        }

        $this->useFirstPage = ($this->startPage > 1);
        $this->useFirstSkip = ($this->startPage > 2);
        $this->useLastPage = (($this->totalPage - $this->endPage) > 0 && $this->totalPage > $this->startPage);
        $this->useLastSkip = (($this->totalPage - $this->endPage) > 1 && $this->totalPage > $this->startPage + 1);
    }

    function html() {

        $empty = "<span class=\"empty\">&nbsp;</span>";

        if (!$this->searchable)
            return $empty;

        if ($this->startPage == $this->endPage) {
            return $empty;
        }

        $html = "<ul>";

        if ($this->useFirstPage) {
            $html .= "<li class=\"first\"><a href=\"?p=1\"></a></li>";
        }
        if ($this->prev > 0) {
            $html .= " <li class='prev'><a href=\"?p={$this->prev}\" ></a></li> ";
        }
//        if ($this->useFirstSkip) {
//            if ($this->startPage == 3) {
//                $html .= "<li><a href=\"?p=2\">2</a></li>";
//            }
//            else {
//                $html .= "<li class=\"disabled\"><a href=\"#\">...</a></li>";
//            }
//        }
        for ($i = $this->startPage; $i <= $this->endPage; $i++) {
            if ($i == $this->currentPage){
                $html .= " <li class='active'><a href=\"?p={$i}\">{$i}</a></li> ";
            }
            else {
                $html .= " <li><a href=\"?p={$i}\">{$i}</a></li> ";
            }
        }
//        if ($this->useLastSkip) {
//            if ($this->endPage == $this->totalPage - 2) {
//                $html .= "<li><a href=\"?p=" . ($this->endPage + 1) . "\">" . ($this->endPage + 1) . "</a></li> ";
//            }
//            else {
//                $html .= "<li class=\"disabled\"><a href=\"#\">...</a></li>";
//            }
//        }
        if ($this->next <= $this->totalPage) {
            $html .= " <li class='next'><a href=\"?p=" . $this->next . "\"></a></li> ";
        }
        if ($this->useLastPage) {
            $html .= "<li class=\"end\"><a href=\"?p=" . $this->totalPage . "\"></a></li>";
        }

        $html .= "</ul>";
        return $html;
    }

}

class FlatPaginate extends BasePaginate {

    function setTotalCount($totalCount) {
        $this->totalCount = $totalCount;

        if ($this->currentPage < 1 || $this->totalCount < 1 || $this->totalCount < (($this->currentPage - 1)*$this->paginationSize)) { // we don't need to search
            $this->searchable = false;
            return;
        }
        $this->searchable = true;
        $this->totalPage = (int)($this->totalCount/$this->paginationSize) + (($this->totalCount % $this->paginationSize == 0) ? 0 : 1);

        if ($this->endAt > $this->totalCount)
            $this->endAt = $this->totalCount;

        $this->prev = $this->currentPage - 1;
        $this->next = $this->currentPage + 1;

        $this->startPage = (($this->currentPage - $this->listSize) < 1) ? 1 : ($this->currentPage - $this->listSize);
        if (($this->totalPage > ($this->listSize*2 + 3)) && ($this->startPage > $this->totalPage - $this->listSize*2 - 1)) {
            $this->startPage = $this->totalPage - $this->listSize*2 - 1;
        }
        $this->endPage = (($this->currentPage + $this->listSize) > $this->totalPage) ? $this->totalPage : ($this->currentPage + $this->listSize);
        if ($this->totalPage > ($this->listSize*2 + 3) && $this->endPage < ($this->listSize*2 + 1)) {
            $this->endPage = $this->listSize*2 + 1;
        }

        $this->useFirstPage = ($this->startPage > 1);
        $this->useFirstSkip = ($this->startPage > 2);
        $this->useLastPage = (($this->totalPage - $this->endPage) > 0 && $this->totalPage > $this->startPage);
        $this->useLastSkip = (($this->totalPage - $this->endPage) > 1 && $this->totalPage > $this->startPage + 1);
    }

    function html() {

        $empty = "<span class=\"empty\">&nbsp;</span>";

        if (!$this->searchable)
            return $empty;

        if ($this->startPage == $this->endPage) {
            return $empty;
        }

        $html = "<ul>";

        if ($this->prev > 0) {
            $html .= " <li class='previous'><a href=\"?p={$this->prev}\"  class=\"fui-arrow-left\"></a></li> ";
        }

        if ($this->useFirstPage) {
            $html .= "<li><a href=\"?p=1\">1</a></li>";
        }
        if ($this->useFirstSkip) {
            if ($this->startPage == 3) {
                $html .= "<li><a href=\"?p=2\">2</a></li>";
            }
            else {
                $html .= "<li class=\"disabled\"><a href=\"#\">...</a></li>";
            }
        }
        for ($i = $this->startPage; $i <= $this->endPage; $i++) {
            if ($i == $this->currentPage){
                $html .= " <li class='active'><a href=\"?p={$i}\">{$i}</a></li> ";
            }
            else {
                $html .= " <li><a href=\"?p={$i}\">{$i}</a></li> ";
            }
        }
        if ($this->useLastSkip) {
            if ($this->endPage == $this->totalPage - 2) {
                $html .= "<li><a href=\"?p=" . ($this->endPage + 1) . "\">" . ($this->endPage + 1) . "</a></li> ";
            }
            else {
                $html .= "<li class=\"disabled\"><a href=\"#\">...</a></li>";
            }
        }
        if ($this->useLastPage) {
            $html .= "<li><a href=\"?p=" . $this->totalPage . "\">" . $this->totalPage . "</a></li>";
        }
        if ($this->next <= $this->totalPage) {
            $html .= " <li class='next'><a href=\"?p=" . $this->next . "\" class='fui-arrow-right'></a></li> ";
        }

        $html .= "</ul>";
        return $html;
    }

}



$webConfig = new WebConfig();
$webSession = new WebSession();
$webRedirect = new WebHttpRedirect();
$GLOBALS["USA_FRAMEWORK"] = new Usa($webConfig, $webSession, $webRedirect);
/**
 * @return Usa
 */
function getUsa() {
    return $GLOBALS["USA_FRAMEWORK"];
}
$usaError = new UsaError();
$usa = getUsa();
$usa->config("PAGINATE_DEFAULT_CURRENT_PAGE", 1);
$usa->config("PAGINATE_DEFAULT_LIST_SIZE", 10);
$usa->config("PAGINATE_DEFAULT_PAGINATION_SIZE", 10);