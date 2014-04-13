<?php
/**
 * User: fri13th
 * Date: 2013/02/17 10:27
 */

$config = new UsaConfig();
$config->db_type = "mssql";
if (strstr($config->domain, "localhost") || preg_match('/^[\d\.:]*$/', $config->domain) || (php_sapi_name() == "cli")) {
    $config->debug = true;
    $config->debug_mode = "local";
    $config->db_url = "mysql:host=localhost;dbname=usagidb;charset=utf8";
    $config->db_userid = "root";
    $config->db_password = "usagi";
    $config->db_options = array();
}
else {
    $config->debug = false;
    $config->debug_mode = "real";
    $config->db_url = "mysql:host=localhost;dbname=usagidb;charset=utf8";
    $config->db_userid = "root";
    $config->db_password = "usagi";
    $config->db_options = array(PDO::ATTR_PERSISTENT => false,);
}

$auth_options = array(
    "ROLE_ANONYMOUS" => array("#/secure/auth/.*#"),
    "ROLE_USER" => array("#/manage/.*#"),
    "ROLE_ADMIN" => array("#/manage/.*#")
);

$GLOBALS["USA_FRAMEWORK"] = new Usa($config);

/**
 * @return Usa
 */
function getUsa() {
    return $GLOBALS["USA_FRAMEWORK"];
}
$usa = getUsa();
$usa->setBase(substr(dirname(__FILE__), 0, -11));
$usa->middleware("simpleAuth", $auth_options);

// add paginate utils here
$usa->config("PAGINATE_DEFAULT_CURRENT_PAGE", 1);
$usa->config("PAGINATE_DEFAULT_LIST_SIZE", 10);
$usa->config("PAGINATE_DEFAULT_PAGINATION_SIZE", 10);