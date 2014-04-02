<?php
/**
 * User: fri13th
 * Date: 12/05/30 10:40
 */

date_default_timezone_set("Asia/Seoul");

class UsaConfig {
    public $app;
    public $theme;
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
    public $data;
}

abstract class UsaSession {
    abstract public function init();
    abstract public function session($key, $value = NULL);
    abstract public function auth($criteria);
}

abstract class UsaHttpRedirect {
    public function redirectTo($url) {
        header("Location: $url");
        exit();
    }
    abstract public function redirectWith($url,$message);
    abstract public function goBackWith($message);
}


class Usa {
    private $basePath;
    private $session;
    private $redirect;
    private $pdo;
    public $config;
    public $debug;
    public $controller;

    /* start */
    function __construct(UsaConfig $config, UsaSession $session, UsaHttpRedirect $redirect) {
        error_log($_SERVER["PHP_SELF"]);
        $this->config = $config;
        $this->session = $session;
        $this->session->init();
        $this->redirect = $redirect;
        $this->debug = $config->debug;
    }

    public function setBase($basePath) {
        $this->basePath = $basePath . "/usa/";
    }

    /* include classes */
    private function base($file) {include($this->basePath . $file . ".php"); }
    public function model($name) { $this->base("models/" . $name . "Model"); }
    public function form($name) { $this->base("forms/" . $name . "Form"); }
    public function util($name) { $this->base("utils/" . $name . "Util"); }
    public function controller($name) { $this->controller = $name; $this->base("controllers/" . $name . "Controller"); }
    public function template($name) { if ($this->config("theme")) $this->base("templates/" . $this->config("app") . "/" . ($this->config("theme") ?  $this->config("theme") . "/" : "") .$name); }
    public function view($name) {$this->base("views/" . $this->config("app") . "/" . $name . "View"); }

    public function auth($criteria = null) {
        $this->session->auth($criteria);
    }
    public function config($key, $value = NULL) {
        return (func_num_args() < 2) ? $this->config->data[$key] : ($this->config->data[$key] = $value) && false;
    }
    public function session($key, $value = NULL)  {
        return $this->session->session($key, $value);
    }
    public function redirectTo($url) {
        $this->redirect->redirectTo($url);
    }
    public function redirectWith($url,$message) {
        $this->redirect->redirectWith($url, $message);
    }
    public function goBackWith($message) {
        $this->redirect->goBackWith($message);
    }
    public function getPdo() {
        if (!$this->pdo) {
            $this->pdo = new PDO($this->config->db_url, $this->config->db_userid, $this->config->db_password, $this->config->db_options);
        }
        return $this->pdo;
    }
    public function jsonResponse($jsonObj) {
        header('Content-type: application/json');
        exit(json_encode($jsonObj));

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



/**
 * DB and model
 */
class BaseModel {
    public $rowNumber; // for pagination
    public $totalCount; // for pagination

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
            array_push($plainObjects, $obj->plainObject($exclusive));
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

    public function json($exclusives = null) {
        return json_encode($this->plainObject($exclusives));
    }

    public function plainObject($exclusives) {
        $obj = array();
        foreach ($this->columns as $key => $value) {
            if (!$exclusives || !in_array($key, $exclusives)) $obj[$key] = $this->$key;
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
