<?php
/**
 * User: fri13th
 * Date: 12/05/30 10:40
 */

date_default_timezone_set("Asia/Seoul");

class UsaConfig {
    public $domain;
    public $debug;
    public $debug_mode;
    public $locale;
    public $encoding;
    public $db_type;
    public $db_url;
    public $db_userid;
    public $db_password;
    public $db_options;
    public $theme;
    public $data;
    public $middlewares;

    function __construct() {
        $this->domain = filter_input(INPUT_SERVER, "HTTP_HOST");
        $this->db_type = "mysql";
        $this->data = array();
        $this->debug = true;
        $this->debug_mode = "local";
        $this->db_url = "mysql:host=localhost;dbname=usagidb;charset=utf8";
        $this->db_userid = "root";
        $this->db_password = "";
        $this->db_options = array(PDO::ATTR_PERSISTENT => false, PDO::ERRMODE_EXCEPTION);
        $this->middlewares = array();
    }
}

abstract class UsaMiddleware {
    public function __construct($config){
        $this->setup($config);
    }
    abstract function setup($config);
    abstract function process_request();
    abstract function process_response();
}


class Usa {
    private $basePath;
    private $pdo;
    public $config;
    public $debug;
    public $controller;
    public $middlewares;

    /* start */
    function __construct(UsaConfig $config) {
        //error_log($_SERVER["PHP_SELF"]);
        $this->config = $config;
        $this->debug = $config->debug;
        if (!isset($_SESSION["session.usa"])) $_SESSION["session.usa"]["username"] = "Guest";
        $this->middlewares = array();
    }

    public function setBase($basePath) {
        $this->basePath = $basePath . "/usa/";
    }

    /* include classes */
    private function base($file) {include($this->basePath . $file . ".php"); }
    public function model($name) { $this->base("models/" . $name . "Model"); }
    public function middleware($name, $options) {
        $name .= "Middleware";
        $this->base("middlewares/" . $name);
        $name = ucwords($name);
        array_push($this->middlewares, new $name($options));
    }
    public function form($name) { $this->base("forms/" . $name . "Form"); }
    public function util($name) { $this->base("utils/" . $name . "Util"); }
    public function controller($name) { $this->controller = $name; $this->base("controllers/" . $name . "Controller"); }
    public function controllerString($name) {
        ob_start();
        $this->controller($name);
        $view = ob_get_contents();
        ob_end_clean();
        return $view;
    }
    public function template($name) { if ($this->config("theme")) $this->base("templates/" . $this->config("app") . "/" . ($this->config("theme") ?  $this->config("theme") . "/" : "") .$name); }
    public function view($name) {$this->base("views/" . $this->config("app") . "/" . $name . "View"); }

    public function config($key, $value = NULL) {
        return (func_num_args() < 2) ? $this->config->data[$key] : ($this->config->data[$key] = $value) && false;
    }

    public function session($key, $value = NULL)  {
        return ($value == NULL) ? $_SESSION["session.usa"][$key] : ($_SESSION["session.usa"][$key] = $value) && false;
    }
    public function redirectTo($url) {
        header("Location: $url");
        exit();
    }
    public function redirectWith($url,$message) {
        echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'/></head><body><script>alert('$message');location.href='$url';</script></body></html>";
        exit();
    }
    public function goBackWith($message) {
        echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'/></head><body><script>alert('$message');history.back();</script></body></html>";
        exit();
    }
    public function process_request() {
        foreach($this->middlewares as $m) $m->process_request();
    }
    public function process_response() {
        foreach($this->middlewares as $m) $m->process_response();
    }

    public function getPdo() {
        if (!$this->pdo) {
            $this->pdo = new PDO($this->config->db_url, $this->config->db_userid, $this->config->db_password, $this->config->db_options);
        }
        return $this->pdo;
    }
    public function jsonResponse($obj) {
        if (is_array($obj) && is_a($obj[0], "BaseModel")) $obj = json_encode(array_map(function($i){return $i->plainObject();}, $obj));
        else if (is_a($obj, "BaseModel")) $obj = $obj->json();
        else if (is_a($obj, "BasePaginate")) $obj = $obj->json();
        else if (!is_string($obj)) $obj = json_encode($obj);
        header('Content-type: application/json');
        exit($obj);
    }
}

class UsaError {
    function __construct() {
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));
        register_shutdown_function(array($this, 'shutdownHandler'));
    }

    public function errorHandler($no, $str, $file, $line){
        $this->errorPrint($no, $str, $file, $line, debug_backtrace());
    }

    public function exceptionHandler(Exception $exception) {
        $this->errorPrint("EXCEPTION", $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace());
    }

    public function shutdownHandler() {
        if ($error = error_get_last()){
            if ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)) {
                $this->errorPrint($error['type'], $error['message'], $error['file'], $error['line'], debug_backtrace());
            }
        }
    }

    private function errorPrint($no, $str, $file, $line, $traces) {
        if ($no == E_NOTICE) return;
        $usa = getUsa();
        $error_type = array(E_WARNING=>'WARNING', E_NOTICE => 'NOTICE', E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING', E_USER_NOTICE => 'USER NOTICE', E_STRICT => 'STRICT',
            E_ERROR => 'ERROR', E_PARSE => 'PARSE', E_CORE_ERROR => 'CORE ERROR', E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR', E_COMPILE_WARNING => 'COMPILE WARNING',
            E_RECOVERABLE_ERROR => 'FATAL ERROR', "EXCEPTION" => 'EXCEPTION');

        if (!$usa->debug) return; # 404, or error we need to show some error message
        else if ($usa->config->debug_mode == "local" && $no != E_STRICT && $no != E_WARNING) {
            error_log("[" . $error_type[$no] . "] " . $str . " at " . $file . "(" . $line . ")");
            return;
        }

        $error = "<div style='border:1px solid #CCC;padding:10px;background:#DDD'>[" .
            //$error_type[$no], "] " . iconv("CP949", "UTF-8", $str) . " at " . $file . "(" . $line . ")<br />" .
            $error_type[$no] . "] " . $str . " at " . $file . "(" . $line . ")<br />" .
            "<br />Trace log:<br />";
        foreach($traces as $trace) {
            if(isset($trace["file"]))
                $error .= "[" . $trace["file"] . "(" . $trace["line"] . ")] ";
            if (isset($trace["class"]))
                $error .= $trace["class"] . "::";
            $error .= $trace["function"] . "<br />";
        }
        $error .= "</div>";
        echo $error;
    }
}
$usaError = new UsaError();


/**
 * DB and model
 */
class BaseModel {
    public $rowNumber; // for pagination
    public $totalCount; // for pagination

    protected $pdo;
    /** @var $statement PDOStatement */
    protected $statement;
    protected $dbType;
    protected $paramSuffix;

    protected $jsonExclusives = array("pdo", "statement", "dbType", "paramSuffix", "jsonExclusive", "jsonIncludes", "table", "pk",
        "columns", "prefixedColumns", "insertDateField", "updateDateField", "vars", "varsPrev", "joins"); // exclude model specific fields
    protected $jsonIncludes = array();

    protected $table; // we don't use foreign key, use pure sql when you need it
    protected $pk;
    protected $columns = array();
    protected $prefixedColumns = array();

    protected $insertDateField = "";
    protected $updateDateField = "";

    protected $vars = array();
    protected $varsPrev = array(); // save prev variables, so don't reset
    /** @var BaseJoin[]  */
    protected $joins = array();

    private function initVars() {
        $this->vars = array(
            "fields" => array(), // custom fields (max(*) as maxVal, )
            "wheres" => array(),
            "orderBys" => array(),
            "groupBys" => array(),
            "limit" => array(),
            "params" => array(),
            "joins" => array(),
        );
    }

    function __construct() {
        /** $usa Usa */$usa = getUsa();
        $this->pdo = $usa->getPdo();
        $this->dbType = $usa->config->db_type;
        $this->paramSuffix = 0;
        foreach($this->columns as $key => $val) {
            $this->prefixedColumns[$key] = "a." . $val;
        }
        $this->initVars();
    }

    public function fetch($sql, $params) {
        $this->statement = $this->pdo->prepare($sql);
        $this->statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, get_class($this));
        $this->statement->execute($params);
        $row = $this->statement->fetch();
        $this->statement->closeCursor();
        return $row;
    }

    public function fetchAll($sql, $params) {
        $this->statement = $this->pdo->prepare($sql);
        $this->statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, get_class($this));
        $this->statement->execute($params);
        $rows = $this->statement->fetchAll();
        $this->statement->closeCursor();
        return $rows;
    }

    public function exec($sql, $params) {
        $this->statement = $this->pdo->prepare($sql);
        return $this->statement->execute($params);
    }

    public function lastInsertId($name) {
        return $this->pdo->lastInsertId($name);
    }

    public function rowCount() {
        return $this->statement->rowCount();
    }

    public function debugDumpParams() {
        return $this->statement->debugDumpParams();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    public function whereAndBegin() {
        array_push($this->vars["wheres"], "AND ( 1 != 1 ");
        return $this;
    }

    public function whereAndEnd() {
        array_push($this->vars["wheres"], ") ");
        return $this;
    }

    public function whereOrBegin() {
        array_push($this->vars["wheres"], "OR ( 1 = 1 ");
        return $this;
    }

    public function whereOrEnd() {
        array_push($this->vars["wheres"], ") ");
        return $this;
    }

    public function eq($column, $value) {
        return $this->where($column, "=", $value);
    }

    public function neq($column, $value) {
        return $this->where($column, "<>", $value);
    }

    public function isNotNull($column) {
        array_push($this->vars["wheres"], " AND " . $this->vars["columns"][$column] . " IS NOT NULL ");
        return $this;
    }

    public function isNull($column) {
        array_push($this->vars["wheres"], " AND " . $this->vars["columns"][$column] . " IS NULL ");
        return $this;
    }

    public function search($keyword, $columns) {
        if ($keyword) {
            $where = " AND (1 != 1";
            foreach ($columns as $column) {
                $where .= " OR " . $this->vars["columns"][$column] . " LIKE '%" . $keyword . "%' ";
            }
            $where .= ") ";
            //$this->params["keyword"] = $keyword; // we must use pdo for preventing sql injection
            array_push($this->vars["wheres"], $where);
        }
        return $this;
    }

    public function where($column, $comparator, $value, $static = false) {
        // if $comparator is IN
        $this->paramSuffix++;
        $valueKey = $column . "___" . $this->paramSuffix;
        if ($comparator == "IN" || $comparator == "IS" || $static) {
            if ($this->dbType == "mssql" && $value == "NOW()") $value = "GETDATE()";
            array_push($this->vars["wheres"], " AND " . $this->getPrefixedColumn($column) . " " . $comparator . " " . $this->getPrefixedColumn($value));
        }
        else {
            array_push($this->vars["wheres"], " AND " . $this->getPrefixedColumn($column) . " " . $comparator . " :" . $valueKey);
            $this->vars["params"][$valueKey] = $value; // we must use pdo for preventing sql injection
        }
        return $this;
    }

    private function getPrefixedColumn($column) {
        return $this->prefixedColumns[$column] ? $this->prefixedColumns[$column] : $column;
    }
    public function orderBy($column, $order = "DESC") {
        array_push($this->vars["orderBys"], array($this->getPrefixedColumn($column), $order));
        return $this;
    }

    public function groupBy($column) {
        array_push($this->vars["groupBys"], $this->getPrefixedColumn($column));
        return $this;
    }

    public function limit($start, $length) {
        $this->vars["limit"] = array($start, $length);
        return $this;
    }

    private function getFromsSql(){
        $sqlFroms = array($this->table . " as a ");
        if (count($this->joins) > 0) {
            foreach($this->joins as $join) {
                $ons = array();
                foreach ($join->conditions as $cond) {
                    array_push($ons, $this->getPrefixedColumn($cond[0]) . " " . $cond[1] . " " . $this->getPrefixedColumn($cond[2]));
                }
                array_push($sqlFroms, $join->joinType . " JOIN " . $join->table . " AS " . $join->prefix . " ON " . join(" AND ", $ons));
            }
        }
        return join(" ", $sqlFroms);
    }

    private function getColumnsSql() {
        $sqlColumns = array();
        $columns = array();
        if ($this->vars["fields"]) array_push($sqlColumns, join(", ", $this->vars["fields"]));
        foreach ($this->prefixedColumns as $key => $val) {
            array_push($columns, $val . " AS " . $key);
        }

        array_push($sqlColumns, join(", ", $columns));
        return join(", ", $sqlColumns);
    }

    private function getGroupBysSql() {
        $sql = "";
        if (sizeof($this->vars["groupBys"]) > 0) { // group by doesn't require prefix
            $sql = " GROUP BY " . join(", ", $this->vars["groupBys"]);
        }
        return $sql;
    }

    private function getOrderBysSql() {
        $sqlOrderBy = "";
        if (sizeof($this->vars["orderBys"]) > 0) { // order by doesn't require prefix
            $orderBys = array();
            foreach ($this->vars["orderBys"] as $orderBy) {
                array_push($orderBys, join(" " , $orderBy));
            }
            $sqlOrderBy = " ORDER BY " . join(", ", $orderBys);
        }
        return $sqlOrderBy;
    }

    private function getWheresSql() {
        $sqlWhere = "";
        if (sizeof($this->vars["wheres"]) > 0) {
            $sqlWhere = " WHERE 1=1 " . join(" ", $this->vars["wheres"]);
        }
        return $sqlWhere;
    }

    private function getLimitSql() {
        $sqlLimit = "";
//        $selectTop = "";
        if (sizeof($this->vars["limit"]) > 0) { // only for sql server 2012+
            if ($this->dbType == "mysql") $sqlLimit = " LIMIT " . $this->vars["limit"][0] . ", " . $this->vars["limit"][1];
//            else if ($this->dbType == "mssql") { // not tested from modified
//                if ($this->vars["limit"][0] == 0) {
//                    $selectTop = "TOP " . $this->vars["limit"][1] . " ";
//                }
//                else {
//                    if (!$sqlOrderBy) $sqlOrderBy = " ORDER BY " . $this->pk . " DESC ";
//                    $sql = "SELECT TOP {$this->vars["limit"][1]} * FROM (SELECT ROW_NUMBER() OVER ($sqlOrderBy) AS rowNumber," .
//                        $sqlColumns . " FROM " . join(" ", $sqlFroms) . $sqlWhere .
//                        ") _tmpInlineView WHERE rowNumber > {$this->vars["limit"][0]} " . $sqlGroupBy . $sqlOrderByMSSQL;
//                    //$sqlLimit = " OFFSET " . $this->limit[0] . " ROWS FETCH NEXT " . $this->limit[1] . " ROWS ONLY";
//                    return $sql;
//                }
//            }
        }
        return $sqlLimit;

    }

    private function generateSelectSql() {

        $sqlFroms = $this->getFromsSql();
        $sqlColumns = $this->getColumnsSql();
        $sqlGroupBy = $this->getGroupBysSql();
        $sqlOrderBy = $this->getOrderBysSql();
        $sqlWhere = $this->getWheresSql();
        $sqlLimit = $this->getLimitSql();

        $sql = "SELECT " . $sqlColumns . " FROM " . $sqlFroms . $sqlWhere . $sqlGroupBy . $sqlOrderBy . $sqlLimit;

#        echo $sql;
#        error_log($sql);
        return $sql;
    }

    private function resetVariables() {
        $this->varsPrev = $this->vars;
        $this->initVars();
    }

    public function paginateMultiple(BasePaginate $paginate){
        $this->limit($paginate->startAt, $paginate->paginationSize);
        $this->criteria($paginate->criteria);
        $this->keyword($paginate->keyword);
        $totalCount = $this->selectCount();
        $paginate->setTotalCount($totalCount);
        $sql = $this->generateSelectSql();
        $paginate->setList($this->fetchAll($sql, $this->vars["params"]));
        $this->resetVariables();
        return $paginate;
    }

    public function paginate(BasePaginate $paginate){
        $this->limit($paginate->startAt, $paginate->paginationSize);
        $totalCount = $this->selectCount();
        $paginate->setTotalCount($totalCount);
        $sql = $this->generateSelectSql();
        $paginate->setList($this->fetchAll($sql, $this->vars["params"]));
        return $paginate;
    }

    /**
     * @param null $column
     * @return BaseModel
     */
    public function select() {
        $sql = $this->generateSelectSql();
        $results = $this->fetch($sql, $this->vars["params"]);        // reset variables for next use
        $this->resetVariables();
        return $results;
    }
    /**
     * @param null $column
     * @return BaseModel
     */
    public function selectCount() {
        $sqlFroms = $this->getFromsSql();
        $sqlWhere = $this->getWheresSql();
        $sql = "SELECT count(*) AS totalCount " . " FROM " . $sqlFroms . $sqlWhere;
        $result = $this->fetch($sql, $this->vars["params"]);        // reset variables for next use
        return $result->totalCount;
    }

    public function selectFieldAll($field) { // field doesn't support group by order by where... it may be changed
        $sqlFroms = $this->getFromsSql();
        $sqlGroupBy = $this->getGroupBysSql();
        $sqlOrderBy = $this->getOrderBysSql();
        $sqlWhere = $this->getWheresSql();
        $sqlLimit = $this->getLimitSql();

        $sql = "SELECT " . $field . " FROM " . $sqlFroms . $sqlWhere . $sqlGroupBy . $sqlOrderBy . $sqlLimit;
        $result = $this->fetchAll($sql, $this->vars["params"]);        // reset variables for next use
        $this->resetVariables();
        return $result;
    }

    /**
     * @return BaseModel[]
     */
    public function selectAll() {
        $sql = $this->generateSelectSql();
        $results = $this->fetchAll($sql, $this->vars["params"]);        // reset variables for next use
        $this->resetVariables();
        return $results;
    }

    public function selectAllPlainObjects() {
        $results = $this->selectAll();

        $plainObjects = array();
        foreach ($results as $obj) {
            array_push($plainObjects, $obj->plainObject());
        }
        return $plainObjects;
    }

    public function save() {
        $columns = array();
        $values = array();
        $sets = array();
        $params = array();

        if ($this->pk && $this->{$this->pk}) { // update
            foreach ($this->columns as $column => $original_column) {
                if ($column == $this->pk) continue;
                if ($column == $this->updateDateField) array_push($sets, $original_column . (($this->dbType == "mssql") ? "=GETDATE()" : "=NOW()"));
                else {
                    array_push($sets, $original_column . "=:" . $column);
                    $params[$column] = $this->{$column};
                }
            }
            $params[$this->pk] = $this->{$this->pk};

            $sql = "UPDATE " . $this->table . " SET " . join(", ", $sets) . " WHERE " . $this->pk . "=:" . $this->pk;
            $this->exec($sql, $params);
        }
        else {
            foreach ($this->columns as $column => $original_column) {
                if ($column == $this->pk || is_null($this->{$column}) && $column != $this->insertDateField) continue;
                array_push($columns, $original_column);
                if ($column == $this->insertDateField) array_push($values, (($this->dbType == "mssql") ? "GETDATE()" : "NOW()"));
                else {
                    array_push($values, ":" . $column);
                    $params[$column] = $this->{$column};
                }
            }
            $sql = "INSERT " . "INTO "  . $this->table . " (" . join(", ", $columns). ") VALUES (" . join(", ", $values). ")";
            #error_log($sql);
            #error_log(join(", ", $params));
            $this->exec($sql, $params);
            if ($this->pk) $this->{$this->pk} = $this->lastInsertId($this->pk);
        }
        return $this;
    }

    public function delete() {
        $sql = "DELETE " . "FROM " . $this->table . " WHERE " . $this->pk . " = :" . $this->pk;
        $this->exec($sql, array( $this->pk => $this->{$this->pk}));
    }

    public function json() {
        return json_encode($this->plainObject());
    }

    public function plainObject() {
        $obj = array();
        $columns = $this->prefixedColumns + $this->jsonIncludes;
        foreach ($columns as $key => $value) {
            if (!in_array($key, $this->jsonExclusives)) $obj[$key] = $this->$key;
        }
        return (object)$obj;
    }
    public function criteria($criteria){} // almost abstract
    public function keyword($keyword){} // almost abstract

    public function copy($obj){
        foreach ($this->prefixedColumns as $key => $val) {
            if (property_exists($obj, $key)) $this->$key = $obj->$key;
        }
    }

    /**
     * @param $join BaseJoin
     */
    public function join($join){
        foreach ($join->columns as $key => $val) {
            $this->prefixedColumns[$key] = $join->prefix . "." . $val;
        }
        array_push($this->joins, $join);
        return $this;
    }

}

class BaseJoin {
    public $prefix = ""; // from b...
    public $table = ""; // table or inner select
    public $joinType = ""; // EMPTY, LEFT, RIGHT, INNER
    public $columns = array();
    public $conditions = array();

    public function __construct($joinType, $prefix, $table, $columns, $onEqConds = null) {
        $this->joinType = $joinType;
        $this->prefix = $prefix;
        $this->table = $table;
        $this->columns = $columns;
        foreach($this->columns as $key => $val) {
            $this->prefixedColumns[$key] = $this->prefix . "." . $val;
        }

        if ($onEqConds) $this->onEq($onEqConds[0], $onEqConds[1]);
    }

    public function onEq($cond1, $cond2) {
        $this->on($cond1, "=", $cond2);
        return $this;
    }

    public function on($cond1, $comparator,  $cond2) {
        array_push($this->conditions, array($cond1, $comparator, $cond2));
        return $this;
    }

}

/** class for pagination */
abstract class BasePaginate { // provide improved pagination
    // input for pagination
    public $currentPage;
    public $listSize;
    public $paginationSize;
    public $criteria;

    // output
    public $totalCount;
    public $searchable;
    public $list;

    // temporary variables
    public $startAt;
    public $endAt;

    protected $prev;
    protected $next;

    protected $startPage;
    protected $endPage;

    protected $useFirstPage;
    protected $useFirstSkip;
    protected $useLastPage;
    protected $useLastSkip;

    protected $totalPage;


    function __construct($currentPage, $keyword = NULL, $criteria = NULL, $listSize = 0, $paginationSize = 0){
        $usa = getUsa();
        $this->keyword = $keyword; // legacy
        $this->criteria = $criteria; // legacy
        $this->currentPage = (!$currentPage) ? $usa->config("PAGINATE_DEFAULT_CURRENT_PAGE") : $currentPage ;
        $this->listSize = $listSize ? $listSize : $usa->config("PAGINATE_DEFAULT_LIST_SIZE");
        $this->paginationSize = $paginationSize ? $paginationSize : $usa->config("PAGINATE_DEFAULT_PAGINATION_SIZE");

        $this->startAt = ($this->currentPage - 1)*$this->paginationSize;
        $this->endAt = $this->currentPage * $this->paginationSize;

        $this->totalCount = 0;
        $this->list = array();
    }

    function setList($list){$this->list = $list;}

    abstract function setTotalCount($totalCount);
    public function json() {
        return json_encode($this->plainObject());
    }

    public function plainObject() {
        $obj = array();

        $obj["currentPage"] = $this->currentPage;
        $obj["listSize"] = $this->listSize;
        $obj["paginationSize"] = $this->paginationSize;
        $obj["criteria"] = $this->criteria;
        $obj["totalCount"] = $this->totalCount;
        $obj["searchable"] = $this->searchable;

        $obj["list"] = array();

        foreach ($this->list as $item) {
            array_push($obj["list"], $item->plainObject());
        }
        return (object)$obj;
    }
}


/**
 * sanitize and validate inputs.
 * we don't use php filter validation.
 */
abstract class BaseForm {
    public $sanitizeRules = array();
    public $errors = array();
    public $error = false;

    function __construct(){
        $result = null;
        if (strstr($_SERVER["CONTENT_TYPE"], "application/json")) $result = json_decode(file_get_contents("php://input"));
        $this->sanitizeAndRequiredCheck($result);
        $this->validation();
    }

    function sanitizeAndRequiredCheck($result) {
        if ($result) {
            foreach ($this->sanitizeRules as $param => $rule) {
                $result->$param = (is_string($result->$param)) ? trim($result->$param) : $result->$param;
                $this->$param = ($result->$param != null && $result->$param != "") ? $result->$param : $rule["default"];
                $rule = $this->sanitizeRules[$param];
                if ($rule["required"] && !isset($result->$param)) $this->errors[$param] = array("required");
            }
        }
        else {
            $result = filter_var_array($_GET + $_POST, $this->sanitizeRules);
            foreach ($this->sanitizeRules as $param => $rule) {
                $result[$param] = (is_string($result[$param])) ? trim($result[$param]) : $result[$param];
                $this->$param = ($result[$param] != null && $result[$param] != "") ? $result[$param] : $rule["default"];
                $rule = $this->sanitizeRules[$param];
                if ($rule["required"] && !isset($result[$param])) $this->errors[$param] = array("required");
            }
        }
        $this->error = (count($this->errors) > 0);
    }

    function addError($param, $errorCode, $message){
        $this->errors[$param] = array($errorCode, $message);
        $this->error = true;
    }

    abstract function validation();
}

abstract class PaginateForm extends BaseForm {
    public $p;
    public $paginateRules =
        array(
            "p" => array(
                'filter' => FILTER_SANITIZE_NUMBER_INT,
                "default" => 1
            )
        );

    function __construct(){
        $this->sanitizeRules += $this->paginateRules;
        $this->sanitizeAndRequiredCheck(false);
        $this->validation();
    }
}

/**
 * @return Usa
 */
function getUsa() {
    return $GLOBALS["USA_FRAMEWORK"];
}