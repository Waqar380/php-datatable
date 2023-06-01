<?php

namespace Waqar\Datatable;


/**
 *  A Datable Class class
 *
 *  This is the Class to interact with database to fech data for datatabke jQuery plugin server side processing.
 *
 * @author Syed Waqar Ali
 */
class datatables
{

    /** @var string $_table_name hold the name of the table */
    private $_table_name = '';

    /** @var string $_primary_key hold the name of the primary key */
    private $_primary_key = '';

    /** @var mix $_colums hold the list of the columns */
    private $_colums = array();

    /** @var mix $_join_table hold the list of the columns */
    private $_join_table = array();

    private $database;

    private $where;

    private $_distinct;

    private $_debug = false;

    private $_group_by = null;



    public function __construct($host, $port, $db_name, $user_name, $password)
    {
        $this->database = new database($host, $port, $db_name, $user_name, $password);

    }

    /**
     * Add Column
     *
     * Add column for datatable
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function addColumn($param)
    {
        $this->_colums[] = $param;
    }

    /**
     * Add Column
     *
     * Add column for datatable
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function setTable($param)
    {
        $this->_table_name = $param;
    }

    /**
     * Add Join
     *
     * Add Joins for datatable
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function joinTable($param)
    {
        if(isset($param['type']) && isset($param['table']) && isset($param['primary']) && isset($param['secondary'])){
            $this->_join_table[] = $param;
            return true;
        }else{
            return false;
        }
    }

    /**
     * Add Column
     *
     * Add column for datatable
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function setPrimaryKey($param)
    {
        $this->_primary_key = $param;
    }

    /**
     * Add WHERE
     *
     * Add Where for datatable
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function setWhere($param)
    {
        $this->where = $param;
    }

    /**
     * Add WHERE
     *
     * Add Where for datatable
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function setDistinct($param)
    {
        $this->_distinct = $param;
    }


    /**
     * Add GROUP BY
     *
     * Add Where for datatable
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function setGroupBy($param)
    {
        $this->_group_by = $param;
    }


    /**
     * Set Debugger
     *
     * Set if its debug mode
     *
     * @param mix $param1 An array containing the parameter
     *
     * @return null
     */
    public function setDebug($param)
    {
        $this->_debug = $param;
    }

    /**
     * Fetch Data
     *
     * Get Data for datatable
     *
     * @return mix
     */
    public function getData(){

        return $this->database->fetchData($_GET, $this->_table_name, $this->_primary_key, $this->_colums, $this->_join_table, $this->where, $this->_distinct, $this->_group_by, $this->_debug);
    }



}