<?php
/**
 * User: fri13th
 * Date: 13/02/17 2:38
 */

$base_path = dirname(__FILE__);
$script_path = $base_path . $_SERVER['SCRIPT_NAME']; // not real uri
if ((php_sapi_name() == 'cli-server') && file_exists($script_path) && !endsWith($script_path, "/index.php")) { # skip existing file
    return false;
}
include $base_path . "/../core/usa.php";

$uri = parse_url($_SERVER['REQUEST_URI']); # uri path
$uri = $uri["path"];
$method =  $_SERVER["REQUEST_METHOD"];
$parsed = explode("/", $uri);
$uri_locale = $parsed[1];
$uri_header = "/" . $parsed[1];
<<<<<<< HEAD
$middleware = array(); // use as aop like way
=======
>>>>>>> 85186e416d894173c4ad1bfd73a65b9d54152e8d

$modules = array(
    "/" => array(
        "config" => "web",
        "theme" => "front",
        "controller" => "main",
        "middleware" => array(),
        "patterns" =>  array(
            array("GET", "|^/$|", function($uri){mainAction();}),
        ),
    ),
    "/upload" => array(
        "config" => "web",
        "controller" => "front",
        "patterns" => array(
            array("POST", "|^/upload/image$|", function(){uploadAction();})
        )
    ),
    "/board" => array(
        "config" => "web",
        "controller" => "board",
        "theme" => "front",
        "patterns" => array(
            array("GET", "|^/board/([0-9a-zA-Z]+)/view/([0-9]+)$|", function($uri, $board_name, $idx){boardViewAction($board_name, $idx);}),
            array("GET", "|^/board/([0-9a-zA-Z]+)/list/?$|", function($uri, $board_name){boardListAction($board_name, 1);}),
            array("GET", "|^/board/([0-9a-zA-Z]+)/list/([0-9]+)/$|", function($uri, $board_name, $page){boardListAction($board_name, $page);}),
            array("GET", "|^/board/([0-9a-zA-Z]+)/edit/([0-9]*)$|", function($uri, $board_name, $idx){boardEditAction($board_name, $idx);}), # get list
            array("POST NO_THEME", "|^/board/([0-9a-zA-Z]+)/save$|", function($uri, $board_name){boardSaveAction($board_name);}), # get list
            array("POST NO_THEME", "|^/board/([0-9a-zA-Z]+)/delete$|", function($uri, $board_name){boardDeleteAction($board_name);}), # get list
        ),
    ),
    "/secure" => array(
        "config" => "web",
        "controller" => "auth",
        "theme" => "front",
        "patterns" => array(
            array("GET", "|^/secure/auth/login$|", function(){authLoginAction();}),
            array("GET", "|^/secure/auth/logout$|", function(){authLogoutAction();}),
            array("POST", "|^/secure/auth/loginProcess$|", function(){authLoginProcessAction();}),
            array("POST NO_THEME", "|^/secure/auth/check_google.json$|", function(){authCheckGoogleAccountAction();}),
            array("GET", "|^/secure/auth/socialSubscription$|", function(){authSocialSubscriptionAction();}),
            array("POST", "|^/secure/auth/socialAccountLogin$|", function(){authSocialAccountLoginAction();}),
            array("POST", "|^/secure/auth/socialAccountCreate$|", function(){authSocialAccountCreateAction();}),
            array("POST NO_THEME", "|^/secure/auth/passwordLogin.json$|", function(){authPasswordLoginJsonAction();}),
        ),
    ),
    "/manage" => array(
        "config" => "manage",
        "controller" => "manage",
        "theme" => "admin",
        "patterns" => array(
            array("GET NO_THEME", "|^/manage/?$|", function($uri){header("Location: /manage/main"); exit();}),
        )
    ),
);


$matched = false;
$module = dict($modules, $uri_header);
if ($module) {
    include $base_path . "/usa/configs/{$module["config"]}.php";
    # start routing
    foreach ($module["patterns"] as $pattern) {
        if (strstr($pattern[0], $method) and preg_match($pattern[1], $uri, $matches)){
            $usa = getUsa();
            $usa->config("app", $module["config"]);
            $usa->config("root", dirname(__FILE__));
            $usa->config("uri", $uri);
            $usa->config("uri_header", $uri_header);
            $usa->process_request(); // middleware
            if ($module["controller"]) $usa->controller($module["controller"]);
            if ($module["theme"] && !stristr($pattern[0], "NO_THEME")) $usa->config("theme", $module["theme"]);
            $callFunc = (is_array($matches)) ? "call_user_func_array" : "call_user_func";

            ob_start();
            if (is_callable($pattern[2])) {
                $usa->template("header");
                $callFunc($pattern[2], $matches);
                $usa->template("footer");
            }
            else { // 5.2 fallback
                $usa->template("header");
                include $base_path . $pattern[2];
                $usa->template("footer");
            }
            ob_end_flush();
            $usa->process_response(); // middleware
            return exit();
        }
    }
}
if (!$matched) {
#    error_log("404 Not Found: " . $uri);
    header("HTTP/1.0 404 Not Found");
    include $base_path . "/404.html";
}
else return false;



// utils
function startsWith($haystack, $needle) {
    return !strncmp($haystack, $needle, strlen($needle));
}

function endsWith($haystack, $needle){
    return substr($haystack, -strlen($needle)) == $needle;
}

function dict($array, $key, $default = NULL) {
    return isset($array[$key]) ? $array[$key] : $default;
}

function getRealIPAddress(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
    else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else $ip = $_SERVER['REMOTE_ADDR'];
    if ($ip == "::1") $ip = "127.0.0.1";
    return $ip;
}