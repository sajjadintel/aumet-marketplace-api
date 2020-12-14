<?php

class GenericModel extends DB\SQL\Mapper
{

    private $table_name;
    public $exception;

    public function __construct(DB\SQL $db, $table_name)
    {
        $this->table_name = $table_name;
        parent::__construct($db, $table_name);
    }

    public static function getTableNames(DB\SQL $db)
    {
        $query = "SELECT table_name FROM information_schema.tables where table_type='BASE TABLE' and table_schema='" . $db->name() . "'";
        return $db->exec($query);
    }

    public static function getTablesAndViews(DB\SQL $db)
    {
        $query = "SELECT table_name, table_type FROM information_schema.tables where table_schema='" . $db->name() . "'";
        return $db->exec($query);
    }

    public function getFullSchema()
    {
        return $this->schema();
    }

    public function getContraints()
    {

        $query = "SELECT COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME 
           FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
           WHERE TABLE_SCHEMA = '360vuz' AND TABLE_NAME = '$this->table_name'";

        return $this->db->exec($query);
    }

    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {

        $arr = [];
        foreach ($this->fields + $this->adhoc as $key => $field)
            $arr[$key] = $field['value'];

        return $arr;
    }

    public function toFieldsArray()
    {
        return (array) ($this->fields + $this->adhoc);
    }

    public function all($order = false, $limit = 0, $offset = 0)
    {
        if (!$order && $limit == 0) {
            $this->load();
        } else if ($order && $limit == 0) {
            $this->load(array(), array('order' => $order, 'offset' => $offset));
        } else if (!$order && $limit >= 0) {
            $this->load(array(), array('limit' => $limit, 'offset' => $offset));
        } else {
            $this->load(array(), array('order' => $order, 'limit' => $limit, 'offset' => $offset));
        }

        return $this->query;
    }

    public function findAll($order = false, $limit = 0, $offset = 0)
    {
        $result = null;

        if (!$order && $limit == 0) {
            $result = $this->find();
        } else if ($order && $limit == 0) {
            $result = $this->find(array(), array('order' => $order, 'offset' => $offset));
        } else if (!$order && $limit >= 0) {
            $result = $this->find(array(), array('limit' => $limit, 'offset' => $offset));
        } else {
            $result = $this->find(array(), array('order' => $order, 'limit' => $limit, 'offset' => $offset));
        }
        $result = array_map(array($this, 'cast'), $result);

        return $result;
    }

    public function getByField($name, $value, $order = false)
    {
        try {
            if ($order) {
                $this->load(array("$name=?", $value), array('order' => $order));
            } else {
                $this->load(array("$name=?", $value));
            }

            return $this->query;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public function getWhere($where, $order = "", $limit = 0)
    {
        try {
            if ($order == "") {
                $this->load(array($where));
            } else if ($limit == 0) {
                $this->load(array($where), array('order' => $order));
            } else {
                $this->load(array($where), array('order' => $order, 'limit' => $limit));
            }
            return $this->query;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public function findWhere($where, $order = "", $limit = 0, $offset = 0)
    {
        $result = null;
        if ($order == "") {
            $result = $this->find(array($where));
        } else if ($limit == 0) {
            $result = $this->find(array($where), array('order' => $order, 'offset' => $offset));
        } else {
            $result = $this->find(array($where), array('order' => $order, 'limit' => $limit, 'offset' => $offset));
        }
        $result = array_map(array($this, 'cast'), $result);

        return $result;
    }

    public function allCursor()
    {
        $this->load();
        return true;
    }

    public function getByFieldCursor($name, $value, $order = false)
    {
        try {
            if ($order) {
                $this->load(array("$name=?", $value), array('order' => $order));
            } else {
                $this->load(array("$name=?", $value));
            }

            return true;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public function getWhereCursor($where, $order = "", $limit = 0)
    {
        try {
            if ($order == "") {
                $this->load(array($where));
            } else if ($limit == 0) {
                $this->load(array($where), array('order' => $order));
            } else {
                $this->load(array($where), array('order' => $order, 'limit' => $limit));
            }
            return true;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public function getPage($pageIndex = 0, $pageSize = 25, $where = false, $order = false)
    {
        return $this->paginate($pageIndex, $pageSize, $where ? array($where) : null,  $order ? array('order' => $order) : null);
    }

    public function getDataTablePage($pageIndex = 0, $pageSize = 25, $where = false, $arrSort = false)
    {
        $objDTResponse = new KTDataTableResponse();

        $order = null;

        if ($arrSort) {
            $objDTResponse->meta->sort = $arrSort["sort"];
            $objDTResponse->meta->field = $arrSort["field"];

            $order = trim($arrSort["field"] . " " . $arrSort["sort"]);

            if ($order == "") {
                $order = null;
            } else {
                $order = array('order' => $order);
            }
        }

        $arrListPage = $this->paginate($pageIndex, $pageSize, $where ? array($where) : null,  $order);

        $objDTResponse->meta->page = $arrListPage["pos"] + 1; // pos start from 0
        $objDTResponse->meta->pages = $arrListPage["count"];
        $objDTResponse->meta->perpage = $arrListPage["limit"];
        $objDTResponse->meta->total = $arrListPage["total"];

        $objDTResponse->data = [];

        foreach ($arrListPage["subset"] as $row) {
            $objDTResponse->data[] = $row->toArray();
        }

        return $objDTResponse;
    }

    public function addReturnID($withInsertDateTime = false)
    {
        try {
            if ($withInsertDateTime) {
                $this->insertDateTime = date('Y-m-d H:i:s');
            }
            $this->insert();
            $this->id = $this->get('_id');
            return TRUE;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public function add()
    {
        try {
            $this->insert();
            $this->id = $this->get('_id');
            return TRUE;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public function edit()
    {
        try {
            $this->update();
            return TRUE;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public function delete()
    {
        try {
            if (isset($this->isActive)) {
                if ($this->isActive == 0) {
                    $this->exception = "Already deleted";
                    return false;
                }
                $this->isActive = 0;
                $this->update();
            } else {
                $this->erase();
            }
            return TRUE;
        } catch (Exception $ex) {
            $this->exception = $ex->getMessage() . " - " . $ex->getTraceAsString();
            return false;
        }
    }

    public static function escapeMySQL($inp)
    {
        if (is_array($inp))
            return array_map(__METHOD__, $inp);

        if (!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
        }

        return $inp;
    }
}
