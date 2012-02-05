<?php
namespace LazyRecord;

use PDO,
    Exception,
    Iterator;

use LazyRecord\OperationResult\OperationSuccess,
    LazyRecord\OperationResult\OperationError;
use SQLBuilder\QueryBuilder;

class BaseCollection
    implements Iterator
{

    /**
     * SQLBuilder\QueryBuilder
     */
    protected $currentQuery;

    /**
     * @var PDOStatement handle
     */
    protected $handle;


    /**
     * handle data for items
     */ 
    protected $itemData = null;


    /**
     * current data item cursor position
     */
    protected $itemCursor = null;




    public function __get( $key ) 
    {
        if( $key == '_schema' ) {
            return SchemaLoader::load( static::schema_proxy_class );
        }
        elseif( $key == '_handle' ) {
            return $this->handle ?: $this->fetch();
        }
        elseif( $key == '_query' ) {
            return $this->currentQuery ?: $this->createQuery();
        }
    }

    public function __call($m,$a)
    {
        $q = $this->_query;
        if( method_exists($q,$m) ) {
            return call_user_func_array(array($q,$m),$a);
        }

    }

    public function createQuery()
    {
        $q = new QueryBuilder;
        $q->driver = QueryDriver::getInstance();
        $q->table( $this->_schema->table );
        $q->select('*');
        return $this->currentQuery = $q;
    }


    public function fetch($force = false)
    {
        if( $this->handle == null || $force ) {
            $ret = $this->doFetch();
        }
        return $this->handle;
    }



    public function doFetch()
    {
        /* fetch by current query */
        $sql = $this->currentQuery->build();
        try {
            $this->handle = $this->dbQuery($sql);

            /*
            foreach( $this->handle as $row ) {
                var_dump( $row ); 
            }
            */
        }
        catch ( PDOException $e )
        {
            return new OperationError( 'Collection fetch failed: ' , array( 
                'sql' => $sql,
                'exception' => $e,
            ) );
        }
        return new OperationSuccess('Updated', array( 'sql' => $sql ));
    }

    public function size()
    {
        if( ! $this->itemData )
            $this->_readRows();
        return count($this->itemData);
    }


    protected function _readRows()
    {
        $h = $this->_handle;
        $this->itemData = array();
        while( $o = $h->fetchObject( static::model_class ) ) {
            $o->deflate();
            $this->itemData[] = $o;
        }
        return $this->itemData;
    }


    /**
     * Implements Iterator methods 
     */
    function rewind()
    { 
        $this->itemCursor = 0;
    }

    /* is current row a valid row ? */
    function valid()
    {
        if( $this->itemData == null )
            $this->_readRows();
        return isset($this->itemData[ $this->itemCursor ] );
    }

    function current() 
    { 
        return $this->itemData[ $this->itemCursor ];
    }

    function next() 
    {
        return $this->itemData[ $this->itemCursor++ ];
    }

    function key()
    {
        return $this->itemCursor;
    }


    /*******************************************
     * duplicate methods from BaseModel 
     * *****************************************/

    /**
     * get pdo connetion and make a query
     *
     * @param string $sql SQL statement
     */
    public function dbQuery($sql)
    {
        $conn = $this->getConnection();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // $conn->setAttribute(PDO::ATTR_AUTOCOMMIT,true);
        $stm = $conn->prepare( $sql );
        $stm->execute();
        return $stm;
    }


    /**
     * get default connection object (PDO) from connection manager
     *
     * @return PDO
     */
    public function getConnection()
    {
        // xxx: process for read/write source
        $sourceId = 'default';
        $connManager = ConnectionManager::getInstance();
        return $connManager->getDefault(); // xxx: support read/write connection later
    }


}

