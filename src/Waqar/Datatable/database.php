<?php

namespace SyedWaqar\Datatable;

/**
 *  A Datable Class class
 *
 *  This is the Class to interact with database to fech data for datatabke jQuery plugin server side processing.
 *
 * @author Syed Waqar Ali
 */

use function Couchbase\defaultDecoder;
use \PDO;
class database
{
    private static $_db;
    public static $_dns;

    public function __construct($host, $port, $db_name, $user_name, $password)
    {
        self::$_dns = "pgsql:host=$host;port=$port;dbname=$db_name;user=$user_name;password=$password";
        try {
            self::$_db = new PDO(self::$_dns);
            if (!self::$_db)
            {
                throw new Exception('Could not connect to database');
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Create the data output array for the DataTables rows
     *
     *  @param  array $columns Column information array
     *  @param  array $data    Data from the SQL get
     *  @return array          Formatted data in a row based format
     */
    static function data_output ( $columns, $data )
    {

        $out = array();
        for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
            $row = array();
            for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
                $column = $columns[$j];
                // Is there a formatter?
                if ( isset( $column['formatter'] ) ) {

                    if($column['alies'])
                        $row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['alies'] ], $data[$i] );
                    else
                        $row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['db'] ], $data[$i] );
                }
                else {
                    if($column['alies'])
                        $row[ $column['dt'] ] = $data[$i][ $columns[$j]['alies'] ];
                    else
                        $row[ $column['dt'] ] = $data[$i][ $columns[$j]['db'] ];
                }
            }
            $out[] = $row;
        }
        return $out;
    }
    /**
     * Paging
     *
     * Construct the LIMIT clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @return string SQL limit clause
     */
    static function limit ( $request, $columns )
    {
        $limit = '';
        if ( isset($request['start']) && $request['length'] != -1 ) {
            $limit = "OFFSET ".intval($request['start'])." LIMIT ".intval($request['length']);
        }
        return $limit;
    }
    /**
     * Ordering
     *
     * Construct the ORDER BY clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @return string SQL order by clause
     */
    static function order ( $request, $columns , $order_by_distinct)
    {
        $order = '';
        if ( isset($request['order']) && count($request['order']) ) {
            $orderBy = array();
            $dtColumns = self::pluck( $columns, 'dt' );
            for ( $i=0, $ien=count($request['order']) ; $i<$ien ; $i++ ) {
                // Convert the column index into the column data property
                $columnIdx = intval($request['order'][$i]['column']);
                $requestColumn = $request['columns'][$columnIdx];
                $columnIdx = array_search( $requestColumn['data'], $dtColumns );
                $column = $columns[ $columnIdx ];
                if ( $requestColumn['orderable'] == 'true' ) {
                    $dir = $request['order'][$i]['dir'] === 'asc' ?
                        'ASC' :
                        'DESC';
                    $orderBy[] = ''.$column['db'].' '.$dir;
                }
            }
            $order = 'ORDER BY ' . implode(', ', $orderBy)   ;


        }

        return $order;
    }
    /**
     * Searching / Filtering
     *
     * Construct the WHERE clause for server-side processing SQL query.
     *
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here performance on large
     * databases would be very poor
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @param  array $bindings Array of values for PDO bindings, used in the
     *    sql_exec() function
     *  @return string SQL where clause
     */
    static function filter ( $request, $columns, &$bindings )
    {
        $globalSearch = array();
        $columnSearch = array();
        $dtColumns = self::pluck( $columns, 'dt' );

        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str = $request['search']['value'];
            for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
                $requestColumn = $request['columns'][$i];
                $columnIdx = array_search( $requestColumn['data'], $dtColumns );
                $column = $columns[ $columnIdx ];
                if ( $requestColumn['searchable'] == 'true' ) {

                    switch($column['type']){

                        case 1:
                            if(is_int($str)){
                                $binding = self::bind( $bindings, $str, $column['type'] );
                                $globalSearch[] = "".$column['db']." = ".$binding;
                            }
                            break;
                        case 2:
                            if(strpos($str,"|")){
                                $pices = explode("|", trim($str,"|"));
                                foreach ($pices as $p){
                                    $binding = self::bind( $bindings, '%'.$p.'%', $column['type'] );
                                    $globalSearch[] = "".$column['db']." ilike ".$binding;
                                }
                            }else{
                                $binding = self::bind( $bindings, '%'.$str.'%', $column['type'] );
                                $globalSearch[] = "".$column['db']." ilike ".$binding;
                            }

                            break;
                        case 98:
                            //Date Type
                            $binding = self::bind( $bindings, '%'.$str.'%', 2 );
                            $globalSearch[] = "to_char(".$column['db'].", 'dd-mm-YYYY')  ilike ".$binding;
                            break;
                            break;
                        case 99:
                            //Date Type
                            $binding = self::bind( $bindings, '%'.$str.'%', 2 );
                            $globalSearch[] = "to_char(".$column['db'].", 'dd-mm-YYYY HH:ii:ss')  ilike ".$binding;
                            break;

                        case 100:
                            //Date custom format Type
                            $binding = self::bind( $bindings, '%'.$str.'%', 2 );
                            $globalSearch[] = "to_char(".$column['db'].", '" . $column['format'] ."')  ilike ".$binding;
                            break;
                        default:
                            break;

                    }
                    if($column['type'] == 1){

                        if(is_int($str)){
                        }
                    }else{
                    }

                }
            }
        }
        // Individual column filtering
        for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
            $requestColumn = $request['columns'][$i];
            $columnIdx = array_search( $requestColumn['data'], $dtColumns );
            $column = $columns[ $columnIdx ];
            $str = $requestColumn['search']['value'];
            if ( $requestColumn['searchable'] == 'true' &&
                $str != '' ) {
                if(strpos($str,"|")){
                    $pices = explode("|", trim($str,"|"));
                    $columnMultipleSearch = array();
                    foreach ($pices as $p){
                        $binding = self::bind( $bindings, ''.$p.'', $column['type'] );
                        $columnMultipleSearch[] = "".$column['db']." ilike ".$binding;
                    }
                    $columnSearch[] = $columnMultipleSearch;
                }else{
                    $binding = self::bind( $bindings, ''.$str.'', $column['type'] );
                    $columnSearch[] = "".$column['db']." ilike ".$binding;
                }
            }
        }
        // Combine the filters into a single string
        $where = '';
        if ( count( $globalSearch ) ) {
            $where = '('.implode(' OR ', $globalSearch).')';
        }
        if ( count( $columnSearch ) ) {
            //Added Mutivalue search feature
            foreach($columnSearch as $column){
                if($where != '')
                    $where .= ' AND ';
                if(is_array($column)){
                    $where .= " (" . implode(" OR ", $column) . " ) ";
                }else{
                    $where .= $column ;
                }
            }
        }
        if ( $where !== '' ) {
            $where = 'WHERE '.$where;
        }
        return $where;
    }

    /**
     *
     * Join
     *
     * Create SQL for joining tables
     *
     * @param $join
     * @return string
     */

    static function join($joins){
        $sqlQuery = "";
        if(count($joins)){
            foreach ($joins as $join){
                $operator = isset($join['operator']) ? $join['operator'] : "=";
                $sqlQuery .=  $join['type'] . " JOIN " . $join['table'] . " ON " . $join['primary'] . " $operator " . $join['secondary'] . " ";
            }
        }
        return $sqlQuery;
    }

    /**
     *
     * columns
     *
     * Create SQL for joining tables
     *
     * @param $join
     * @return string
     */

    static function columns($columns){
        $sqlQuery = "";
        foreach ($columns as $column){

            if($column['alies']){
                $sqlQuery .= ", " . $column['db'] . " " . $column['alies'] . " ";
            }else{
                $sqlQuery .= ", " . $column['db'] . " ";
            }
        }
        return trim($sqlQuery,",");
    }

    static function group($columns){
        $fileds = array_column($columns , "db");
        return implode(", ", $fileds);
    }



    /**
     * Perform the SQL queries needed for an server-side processing requested,
     * utilising the helper functions of this class, limit(), order() and
     * filter() among others. The returned array is ready to be encoded as JSON
     * in response to an self request, or can be modified if needed before
     * sending back to the client.
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $sql_details SQL connection details - see sql_connect()
     *  @param  string $table SQL table to query
     *  @param  string $primaryKey Primary key of the table
     *  @param  array $columns Column information array
     *  @return array          Server-side processing response array
     */
    static function fetchData ( $request, $table, $primaryKey, $columns, $joins, $added_where  = null, $distinct = null, $group_by =  null , $debug = FALSE)
    {
        $bindings = array();
        $db = self::$_db;
        // Build the SQL query string from the request
        $limit = self::limit( $request, $columns );
        $order = self::order( $request, $columns, $distinct );
        $where = self::filter( $request, $columns, $bindings );
        $join = self::join( $joins );
        $fileds = self::columns($columns);


        if($added_where)
            $where = trim($where) ? $where . " AND " . $added_where : ' WHERE ' . $added_where;

        if($group_by)
            $group_by = " GROUP BY " . $group_by;

        if($distinct)
            $distinct = "DISTINCT ON ( " . $distinct . ") ";

        if($distinct)
            $group_by = " GROUP BY " . self::group($columns) . " ";

        $count_sql  = " SELECT COUNT (DISTINCT {$primaryKey}) as total_count
			 FROM   $table 
			    $join 
			    $where
			    $group_by
			    ";

        // Main query to actually get the data
        $psql = "SELECT $distinct $fileds FROM $table  $join $where $group_by $order $limit";
        $data = self::sql_exec( $db, $bindings,$psql);


        $total_count = self::sql_exec( $db, $bindings,$count_sql);

        // Data set length after filtering
        $recordsFiltered = @$total_count[0]["total_count"];

        if(count($total_count) > 1){
            $recordsFiltered = count($total_count);
        }else{
            $recordsFiltered = @$total_count[0]['total_count'];
        }

        // Total data set length
        $length_psql = "SELECT COUNT ( {$primaryKey}) as total_count
			 FROM   $table 
			    $join
              ";
        if($added_where)
            $length_psql .= " WHERE $added_where";

        if($group_by)
            $length_psql .= " $group_by";

        $resTotalLength = self::sql_exec( $db,$length_psql);

        if(count($resTotalLength) > 1){
            $recordsTotal = count($resTotalLength);
        }else{
            $recordsTotal = @$resTotalLength[0]['total_count'];
        }

        /*
         * Output
         */
        return array(
            "draw"              =>  intval( $request['draw'] ),
            "recordsTotal"      =>  intval( $recordsTotal ),
            "recordsFiltered"   =>  intval( $recordsFiltered ),
            "data"              =>  self::data_output( $columns, $data ),
            "sql"			    =>  $debug ? $psql : '',
            "count_sql"		    =>  $debug ? $count_sql : "",
            "length"            =>  $debug ? $length_psql : ""
        );
    }
    /**
     * Connect to the database
     *
     * @param  array $sql_details SQL server connection details array, with the
     *   properties:
     *     * host - host name
     *     * db   - database name
     *     * user - user name
     *     * pass - user password
     * @return resource Database connection handle
     */
    static function sql_connect ( $sql_details )
    {
        try {
            $db = @new PDO(
                "pgsql:host={$sql_details['host']};dbname={$sql_details['db']}",
                $sql_details['user'],
                $sql_details['pass'],
                array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION )
            );
        }
        catch (PDOException $e) {
            self::fatal(
                "An error occurred while connecting to the database. ".
                "The error reported by the server was: ".$e->getMessage()
            );
        }
        return $db;
    }
    /**
     * Execute an SQL query on the database
     *
     * @param  resource $db  Database handler
     * @param  array    $bindings Array of PDO binding values from bind() to be
     *   used for safely escaping strings. Note that this can be given as the
     *   SQL query string if no bindings are required.
     * @param  string   $sql SQL query to execute.
     * @return array         Result from the query (all rows)
     */
    static function sql_exec ( $db, $bindings, $sql=null )
    {
        // Argument shifting
        if ( $sql === null ) {
            $sql = $bindings;
        }
        $stmt = $db->prepare( $sql );
        //echo $sql;
        // Bind parameters
        if ( is_array( $bindings ) ) {
            for ( $i=0, $ien=count($bindings) ; $i<$ien ; $i++ ) {
                $binding = $bindings[$i];

                $stmt->bindValue( $binding['key'], $binding['val'], $binding['type'] );
            }
        }
        // Execute
        try {
            $stmt->execute();
        }
        catch (PDOException $e) {
            self::fatal( "An SQL error occurred: ".$e->getMessage()." [with] '".$sql."'" );
        }
        // Return all
        return $stmt->fetchAll();
    }
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Internal methods
     */
    /**
     * Throw a fatal error.
     *
     * This writes out an error message in a JSON string which DataTables will
     * see and show to the user in the browser.
     *
     * @param  string $msg Message to send to the client
     */
    static function fatal ( $msg )
    {
        echo json_encode( array(
            "error" => $msg
        ) );
        exit(0);
    }
    /**
     * Create a PDO binding key which can be used for escaping variables safely
     * when executing a query with sql_exec()
     *
     * @param  array &$a    Array of bindings
     * @param  *      $val  Value to bind
     * @param  int    $type PDO field type
     * @return string       Bound key to be used in the SQL where this parameter
     *   would be used.
     */
    static function bind ( &$a, $val, $type )
    {
        $key = ':binding_'.count( $a );
        $a[] = array(
            'key' => $key,
            'val' => $val,
            'type' => $type
        );
        return $key;
    }
    /**
     * Pull a particular property from each assoc. array in a numeric array,
     * returning and array of the property values from each item.
     *
     *  @param  array  $a    Array to get data from
     *  @param  string $prop Property to read
     *  @return array        Array of property values
     */
    static function pluck ( $a, $prop )
    {
        $out = array();
        for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
            $out[] = $a[$i][$prop];
        }
        return $out;
    }
}
?>