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
        $this->db_url = "mysql:host=localhost;dbname=usa;charset=utf8";
        $this->db_userid = "root";
        $this->db_password = "";
        $this->db_options = array(PDO::ATTR_PERSISTENT => false);
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
    public $jsonExclusives = array(); // exclude personal information field
    public $jsonIncludes = array();

    protected $table; // we don't use foreign key, use pure sql when you need it
    protected $pk;
    protected $columns = array();
    protected $customColumns = array();
    protected $fields = array();
    protected $wheres = array();
    protected $orderBys = array();
    protected $orderBysMSSQL = array();
    protected $groupBys = array();
    protected $joins = array();
    protected $limit = array();
    protected $params = array();
    protected $prefixes = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o");//...
    protected $insertDateField = "";
    protected $updateDateField = "";
    private   $onlySelectedField = false;

    protected $pdo;
    /** @var $statement PDOStatement */
    protected $statement;
    protected $dbType;


    function __construct() {
        /** $usa Usa */$usa = getUsa();
        $this->pdo = $usa->getPdo();
        $this->dbType = $usa->config->db_type;
        if ($this->dbType == "mysql") $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function fetch($sql, $params, $returnArray = false) {
        $this->statement = $this->pdo->prepare($sql);
        $this->statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, get_class($this));
        $this->statement->execute($params);
        $result = call_user_func(array($this->statement, $returnArray ? "fetchAll" : "fetch"));
        $this->statement->closeCursor();
        return $result;
    }

    public function fetchAll($sql, $params) {
        return $this->fetch($sql, $params, true);
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

    public function whereOrBegin() {
        array_push($this->wheres, "OR ( 1 = 1 ");
        return $this;
    }

    public function whereOrEnd() {
        array_push($this->wheres, ") ");
        return $this;
    }

    public function eq($column, $value) {
        return $this->where($column, "=", $value);
    }

    public function neq($column, $value) {
        return $this->where($column, "<>", $value);
    }

    public function isNotNull($column) {
        array_push($this->wheres, " AND " . $this->columns[$column] . " IS NOT NULL ");
        return $this;
    }

    public function isNull($column) {
        array_push($this->wheres, " AND " . $this->columns[$column] . " IS NULL ");
        return $this;
    }

    public function field($field) {
        array_push($this->fields, $field);
        return $this;
    }

    public function search($keyword, $columns) {
        if ($keyword) {
            $where = " AND (1 != 1";
            foreach ($columns as $column) $where .= " OR " . $this->columns[$column] . " LIKE '%" . $keyword . "%' " ;
            $where .= ") ";
            //$this->params["keyword"] = $keyword; // we must use pdo for preventing sql injection
            array_push($this->wheres, $where);
        }
        return $this;
    }

    public function where($column, $comparator, $value, $static = false) {
        // if $comparator is IN
        if ($comparator == "IN" || $comparator == "IS" || $static) {
            if ($this->dbType == "mssql" && $value == "NOW()") $value = "GETDATE()";
            array_push($this->wheres, " AND " . $this->columns[$column] . " $comparator " . $value);
        }
        else {
            array_push($this->wheres, " AND " . $this->columns[$column] . " " . $comparator . " :" . $column);
            $this->params[$column] = $value; // we must use pdo for preventing sql injection
        }
        return $this;
    }
    public function orderBy($column, $order = "DESC") {
        array_push($this->orderBysMSSQL, array($column, $order));
        array_push($this->orderBys, array($this->columns[$column], $order));
        return $this;
    }

    public function groupBy($column) {
        array_push($this->orderBys, $this->columns[$column]);
        return $this;
    }

    public function limit($start, $length) {
        $this->limit = array($start, $length);
        return $this;
    }

    public function column($columns) {
        $this->customColumns = $columns;
        return $this;
    }

    private function generateSelectSql() {
        // generate sql here
        #when there's foreign key, add one more prefix
        $tables = array();
        $columns = array();

        array_push($tables, array("name" => $this->table, "column" => $this->columns)); // add foreign keys, one to many or many to one

        $prefixIndex = 0;
        $sqlColumns = array();
        $sqlTableNames = array();


        if ($this->onlySelectedField) {
            $sqlColumns = $this->fields;
            array_push($sqlTableNames, $this->table);
        }
        else {
            foreach ($tables as $table) {
                $prefix = $this->prefixes[$prefixIndex];
                foreach ($table["column"] as $key => $val) {
                    if (count($this->customColumns) == 0 || in_array($key, $this->customColumns)) {
                        array_push($columns, $prefix . "." . $val . " as " . $key);
                    }
                }

                array_push($sqlTableNames, $table["name"] . " as " . $prefix);
                array_push($sqlColumns, join(", ", $columns));
                if ($this->fields) array_push($sqlColumns, join(", ", $this->fields));
                $prefixIndex++;
            }
        }

        $sqlGroupBy = "";
        if (sizeof($this->groupBys) > 0) {
            $groupBys = array();
            foreach ($this->groupBys as $groupBy) {
                array_push($groupBys, join(" " , $groupBy));
            }
            $sqlGroupBy = " GROUP BY " . join(", ", $groupBys);
        }

        $sqlOrderBy = "";
        $sqlOrderByMSSQL = "";
        if (sizeof($this->orderBys) > 0) {
            $orderBys = array();
            $orderBysMSSQL = array();
            foreach ($this->orderBys as $orderBy) {
                array_push($orderBys, join(" " , $orderBy));
            }
            foreach ($this->orderBysMSSQL as $orderBy) {
                array_push($orderBysMSSQL, join(" " , $orderBy));
            }
            $sqlOrderBy = " ORDER BY " . join(", ", $orderBys);
            $sqlOrderByMSSQL = " ORDER BY " . join(", ", $orderBysMSSQL);
        }

        $sqlWhere = "";
        if (sizeof($this->wheres) > 0) {
            $sqlWhere = " WHERE 1=1 " . join(" ", $this->wheres);
        }

        $sqlLimit = "";
        $selectTop = "";
        if (sizeof($this->limit) > 0) { // only for sql server 2012+
            if ($this->dbType == "mssql") {
                if ($this->limit[0] == 0) {
                    $selectTop = "TOP " . $this->limit[1] . " ";
                }
                else if(!$this->onlySelectedField){
                    if (!$sqlOrderBy) $sqlOrderBy = " ORDER BY " . $this->pk . " DESC ";
                    $sql = "SELECT TOP {$this->limit[1]} * FROM (SELECT ROW_NUMBER() OVER ($sqlOrderBy) AS rowNumber," .
                        join(",", $sqlColumns) . " FROM " . join(",", $sqlTableNames) . $sqlWhere .
                        ") _tmpInlineView WHERE rowNumber > {$this->limit[0]} " . $sqlGroupBy . $sqlOrderByMSSQL;
                    //$sqlLimit = " OFFSET " . $this->limit[0] . " ROWS FETCH NEXT " . $this->limit[1] . " ROWS ONLY";
                    return $sql;
                }
            }
            if ($this->dbType == "mysql") $sqlLimit = " LIMIT " . $this->limit[0] . ", " . $this->limit[1];
        }

        $sql = "SELECT " . $selectTop . join(",", $sqlColumns) . " FROM " .  join(",", $sqlTableNames) . $sqlWhere;
        if(!$this->onlySelectedField) $sql .= $sqlGroupBy . $sqlOrderBy . $sqlLimit; #add where and order by and limit
#        echo $sql;
#        error_log($sql);
        return $sql;
    }

    private function resetVariables() {
        $this->params = array();
        $this->customColumns = array();
        $this->fields = array();
        $this->wheres = array();
        $this->orderBys = array();
        $this->groupBys = array();
        $this->limit = array();
        $this->onlySelectedField = false;
    }

    public function paginate(BasePaginate $paginate){
        $this->limit($paginate->startAt, $paginate->paginationSize);
        $totalCount = $this->selectCount();
        $paginate->setTotalCount($totalCount);
        $sql = $this->generateSelectSql(null);
        $paginate->setList($this->fetchAll($sql, $this->params));
        $this->resetVariables();
        return $paginate;
    }

    /**
     * @param null $column
     * @return BaseModel
     */
    public function select() {
        $sql = $this->generateSelectSql();
        $results = $this->fetch($sql, $this->params);        // reset variables for next use
        $this->resetVariables();
        return $results;
    }
    /**
     * @param null $column
     * @return BaseModel
     */
    public function selectCount() {
        $fields = $this->fields;
        $this->fields = array("count(*) as totalCount");
        $this->onlySelectedField = true;
        $sql = $this->generateSelectSql();
        $result = $this->fetch($sql, $this->params);        // reset variables for next use
        $this->onlySelectedField = false;
        $this->fields = $fields;
        return $result->totalCount;
    }

    public function selectField() {
        $this->onlySelectedField = true;
        return $this->select();
    }

    public function selectAll() {
        $sql = $this->generateSelectSql();
        $results = $this->fetchAll($sql, $this->params);        // reset variables for next use
        $this->resetVariables();
        return $results;
    }

    public function selectAllPlainObjects($exclusive = null) {
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
            $sql = "INSERT INTO "  . $this->table . " (" . join(", ", $columns). ") VALUES (" . join(", ", $values). ")";
            error_log($sql);
            #error_log(join(", ", $params));
            $this->exec($sql, $params);
            if ($this->pk) $this->{$this->pk} = $this->lastInsertId($this->pk);
        }
        return $this;
    }

    public function delete() {
        $sql = "DELETE FROM " . $this->table . " WHERE " . $this->pk . " = :" . $this->pk;
        $this->exec($sql, array( $this->pk => $this->{$this->pk}));
    }

    public function json() {
        return json_encode($this->plainObject());
    }

    public function plainObject() {
        $obj = array();
        $columns = $this->columns + $this->jsonIncludes;
        foreach ( $columns as $key => $value) {
            if (!$this->jsonExclusives || !in_array($key, $this->jsonExclusives) && $key != "table") $obj[$key] = $this->$key;
        }
        return (object)$obj;
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
        $this->sanitizeAndRequiredCheck();
        $this->validation();
    }

    function sanitizeAndRequiredCheck() {
        $result = filter_var_array($_GET + $_POST, $this->sanitizeRules);
        foreach ($this->sanitizeRules as $param => $rule) {
            $this->$param = $result[$param] or $rule["default"];
            $rule = $this->sanitizeRules[$param];
            if ($rule["required"] && !isset($result[$param])) $this->errors[$param] = array("required");
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
        $this->sanitizeAndRequiredCheck();
        $this->validation();
    }

}

/**
 * @return Usa
 */
function getUsa() {
    return $GLOBALS["USA_FRAMEWORK"];
}
