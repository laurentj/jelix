<?php
/**
 * @package     jelix
 * @subpackage  dao
 * @author      Laurent Jouanneau
 * @contributor Loic Mathaud
 * @contributor Julien Issler
 * @contributor Thomas
 * @contributor Yoan Blanc
 * @contributor Mickael Fradin
 * @contributor Christophe Thiriot
 * @contributor Yannick Le Guédart
 * @contributor Steven Jehannet, Didier Huguet
 * @contributor Philippe Villiers
 * @copyright   2005-2011 Laurent Jouanneau
 * @copyright   2007 Loic Mathaud
 * @copyright   2007-2009 Julien Issler
 * @copyright   2008 Thomas
 * @copyright   2008 Yoan Blanc
 * @copyright   2009 Mickael Fradin
 * @copyright   2009 Christophe Thiriot
 * @copyright   2010 Yannick Le Guédart
 * @copyright   2010 Steven Jehannet, 2010 Didier Huguet
 * @copyright   2013 Philippe Villiers
 * @link        http://www.jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

/**
 * base class for all factory classes generated by the dao compiler
 * @package  jelix
 * @subpackage dao
 */
abstract class jDaoFactoryBase  {
    /**
     * informations on tables
     *
     * Keys of elements are the alias of the table. values are arrays like that :
     * <pre> array (
     *   'name' => ' the table alias',
     *   'realname' => 'the real name of the table',
     *   'pk' => array ( list of primary keys name ),
     *   'fields' => array ( list of property name attached to this table )
     * )
     * </pre>
     * @var array
     */
    protected $_tables;
    /**
     * the id of the primary table
     * @var string
     */
    protected $_primaryTable;
    /**
     * the database connector
     * @var jDbConnection
     */
    protected $_conn;
    /**
     * the select clause you can reuse for a specific SELECT query
     * @var string
     */
    protected $_selectClause;
    /**
     * the from clause you can reuse for a specific SELECT query
     * @var string
     */
    protected $_fromClause;
    /**
     * the where clause you can reuse for a specific SELECT query
     * @var string
     */
    protected $_whereClause;
    /**
     * the class name of a dao record for this dao factory
     * @var string
     */
    protected $_DaoRecordClassName;

    /**
     * the selector of the dao, to be sent with events
     * @var string
     */
    protected $_daoSelector;

    /**
     * @since 1.0
     */
    protected $_deleteBeforeEvent = false;
    /**
     * @since 1.0
     */
    protected $_deleteAfterEvent = false;
    /**
     * @since 1.0
     */
    protected $_deleteByBeforeEvent = false;
    /**
     * @since 1.0
     */
    protected $_deleteByAfterEvent = false;

    /**
     * @since 1.0
     */
    protected $trueValue = 1;
    /**
     * @since 1.0
     */
    protected $falseValue = 0;
    /**
     * @param jDbConnection $conn the database connection
     */
    function  __construct($conn){
        $this->_conn = $conn;

        if($this->_conn->hasTablePrefix()){
            foreach($this->_tables as $table_name=>$table){
                $this->_tables[$table_name]['realname'] = $this->_conn->prefixTable($table['realname']);
            }
        }
    }

    /**
     * @since 1.3.2
     * @return array informations on tables
     * @see $_tables
     */
    public function getTables() {
        return $this->_tables;
    }

    /**
     * @since 1.3.2
     * @return string the id (alias or realname) of the primary table
     */
    public function getPrimaryTable() {
        return $this->_primaryTable;
    }

    /**
     * informations on all properties
     *
     * keys are property name, and values are an array like that :
     * <pre> array (
     *  'name' => 'name of property',
     *  'fieldName' => 'name of fieldname',
     *  'regExp' => NULL, // or the regular expression to test the value
     *  'required' => true/false,
     *  'isPK' => true/false, //says if it is a primary key
     *  'isFK' => true/false, //says if it is a foreign key
     *  'datatype' => '', // type of data : string
     *  'unifiedType'=> '' // the corresponding unified type
     *  'table' => 'grp', // alias of the table the property is attached to
     *  'updatePattern' => '%s',
     *  'insertPattern' => '%s',
     *  'selectPattern' => '%s',
     *  'sequenceName' => '', // name of the sequence when field is autoincrement
     *  'maxlength' => NULL, // or a number
     *  'minlength' => NULL, // or a number
     *  'ofPrimaryTable' => true/false
     *  'autoIncrement'=> true/false
     * ) </pre>
     * @return array informations on all properties
     * @since 1.0beta3
     */
    public function getProperties() { return static::$_properties; }

    /**
     * list of id of primary properties
     * @return array list of properties name which contains primary keys
     * @since 1.0beta3
     */
    public function getPrimaryKeyNames() { return static::$_pkFields; }

    /**
     * creates a record object for the dao
     *
     * @return jDaoRecordBase
     */
    public function createRecord() {
        $c = $this->_DaoRecordClassName;
        $obj = new $c();
        return $obj;
    }

    /**
     * return all records
     * @return jDbResultSet
     */
    public function findAll(){
        $rs = $this->_conn->query ($this->_selectClause.$this->_fromClause.$this->_whereClause);
        $this->finishInitResultSet($rs);
        return $rs;
    }

    /**
     * return the number of all records
     * @return int the count
     */
    public function countAll(){
        $oracle = ($this->_conn->dbms == 'oci');
        if (!$oracle) {
            $query = 'SELECT COUNT(*) as c '.$this->_fromClause.$this->_whereClause;
        } else { // specific query for oracle to make sure the alias has the correct case
            $query = 'SELECT COUNT(*) as "c" '.$this->_fromClause.$this->_whereClause;
        }
        $rs  = $this->_conn->query ($query);
        $res = $rs->fetch ();
        return intval($res->c);
    }

    /**
     * return the record corresponding to the given key
     * @return jDaoRecordBase
     * @throws jException
     * @internal param string $key one or more primary key
     */
    final public function get(){
        $args=func_get_args();
        if(count($args)==1 && is_array($args[0])){
            $args=$args[0];
        }
        $keys = @array_combine(static::$_pkFields, $args );

        if($keys === false){
            throw new jException('jelix~dao.error.keys.missing');
        }

        $q = $this->_selectClause.$this->_fromClause.$this->_whereClause;
        $q .= $this->_getPkWhereClauseForSelect($keys);

        $rs = $this->_conn->query ($q);
        $this->finishInitResultSet($rs);
        $record =  $rs->fetch ();
        return $record;
    }

    /**
     * delete a record corresponding to the given key
     * @return int the number of deleted record
     * @throws jException
     * @internal param string $key one or more primary key
     */
    final public function delete(){
        $args=func_get_args();
        if(count($args)==1 && is_array($args[0])){
            $args=$args[0];
        }
        $keys = array_combine(static::$_pkFields, $args);
        if($keys === false){
            throw new jException('jelix~dao.error.keys.missing');
        }
        $q = 'DELETE FROM '.$this->_conn->encloseName($this->_tables[$this->_primaryTable]['realname']).' ';
        $q.= $this->_getPkWhereClauseForNonSelect($keys);

        if ($this->_deleteBeforeEvent) {
            jEvent::notify("daoDeleteBefore", array('dao'=>$this->_daoSelector, 'keys'=>$keys));
        }
        $result = $this->_conn->exec ($q);
        if ($this->_deleteAfterEvent) {
            jEvent::notify("daoDeleteAfter", array('dao'=>$this->_daoSelector, 'keys'=>$keys, 'result'=>$result));
        }
        return $result;
    }

    /**
     * save a new record into the database
     * if the dao record has an autoincrement key, its corresponding property is updated
     * @param jDaoRecordBase $record the record to save
     * @return integer  1 if success (the number of affected rows). False if the query has failed. 
     */
    abstract public function insert ($record);

    /**
     * save a modified record into the database
     * @param jDaoRecordBase $record the record to save
     * @return integer  1 if success (the number of affected rows). False if the query has failed. 
     */
    abstract public function update ($record);

    /**
     * return all record corresponding to the conditions stored into the
     * jDaoConditions object.
     * you can limit the number of results by given an offset and a count
     * @param jDaoConditions $searchcond
     * @param int $limitOffset
     * @param int $limitCount
     * @return jDbResultSet
     */
    final public function findBy (jDaoConditions $searchcond, $limitOffset=0, $limitCount=null){
        $query = $this->_selectClause.$this->_fromClause.$this->_whereClause;
        if ($searchcond->hasConditions ()){
            $query .= ($this->_whereClause !='' ? ' AND ' : ' WHERE ');
            $query .= $this->_createConditionsClause($searchcond);
        }
        $query.= $this->_createOrderClause($searchcond);

        if($limitCount !== null){
            $rs = $this->_conn->limitQuery ($query, $limitOffset, $limitCount);
        }else{
            $rs = $this->_conn->query ($query);
        }
        $this->finishInitResultSet($rs);
        return $rs;
    }

    /**
     * return the number of records corresponding to the conditions stored into the
     * jDaoConditions object.
     * @author Loic Mathaud
     * @contributor Steven Jehannet
     * @copyright 2007 Loic Mathaud
     * @since 1.0b2
     * @param jDaoConditions $searchcond
     * @return int the count
     */
    final public function countBy($searchcond, $distinct=null) {
        $count = '*';
        $sqlite = false;

        if ($distinct !== null) {
            $props = static::$_properties;
            if (isset($props[$distinct]))
                $count = 'DISTINCT '.$this->_tables[$props[$distinct]['table']]['name'].'.'.$props[$distinct]['fieldName'];
            $sqlite = ($this->_conn->dbms == 'sqlite');
        }
        $oracle = ($this->_conn->dbms == 'oci');

        if (!$sqlite) {
            if(!$oracle) {
                $query = 'SELECT COUNT('.$count.') as c '.$this->_fromClause.$this->_whereClause;
            } else {
                $query = 'SELECT COUNT('.$count.') as "c" '.$this->_fromClause.$this->_whereClause;
            }
        } else { // specific query for sqlite, which doesn't support COUNT+DISTINCT
            $query = 'SELECT COUNT(*) as c FROM (SELECT '.$count.' '.$this->_fromClause.$this->_whereClause;
        }

        if ($searchcond->hasConditions ()){
            $query .= ($this->_whereClause !='' ? ' AND ' : ' WHERE ');
            $query .= $this->_createConditionsClause($searchcond);
        }
        if($sqlite) $query .= ')';
        $rs  = $this->_conn->query ($query);
        $res = $rs->fetch();
        return intval($res->c);
    }

    /**
     * delete all record corresponding to the conditions stored into the
     * jDaoConditions object.
     * @param jDaoConditions $searchcond
     * @return number of deleted rows
     * @since 1.0beta3
     */
    final public function deleteBy ($searchcond){
        if ($searchcond->isEmpty ()){
            return 0;
        }

        $query = 'DELETE FROM '.$this->_conn->encloseName($this->_tables[$this->_primaryTable]['realname']).' WHERE ';
        $query .= $this->_createConditionsClause($searchcond, false);

        if ($this->_deleteByBeforeEvent) {
            jEvent::notify("daoDeleteByBefore", array('dao'=>$this->_daoSelector, 'criterias'=>$searchcond));
        }
        $result = $this->_conn->exec($query);
        if ($this->_deleteByAfterEvent) {
            jEvent::notify("daoDeleteByAfter", array('dao'=>$this->_daoSelector, 'criterias'=>$searchcond, 'result'=>$result));
        }
        return $result;
    }

    /**
     * create a WHERE clause with conditions on primary keys with given value. This method
     * should be used for SELECT queries. You haven't to escape values.
     *
     * @param array $pk  associated array : keys = primary key name, values : value of a primary key
     * @return string a 'where' clause (WHERE mypk = 'myvalue' ...)
     */
    abstract protected function _getPkWhereClauseForSelect($pk);

    /**
     * create a WHERE clause with conditions on primary keys with given value. This method
     * should be used for DELETE and UPDATE queries.
     * @param array $pk  associated array : keys = primary key name, values : value of a primary key
     * @return string a 'where' clause (WHERE mypk = 'myvalue' ...)
     */
    abstract protected function _getPkWhereClauseForNonSelect($pk);

    /**
    * @internal
    */
    final protected function _createConditionsClause($daocond, $forSelect=true){
        return $this->_generateCondition ($daocond->condition, static::$_properties, $forSelect, true);
    }

    /**
     * @internal
     */
    final protected function _createOrderClause($daocond) {
        $order = array ();
        $isOci = ($this->_conn->dbms == 'oci');
        foreach ($daocond->order as $name => $way){
            if (isset(static::$_properties[$name])) {
                // SqlServer needs only name/aliases, Oci needs only table.field. Sqlite, Pgsql, Mysql accept both syntax.
                if ($isOci) {
                    $order[] = $this->_conn->encloseName(static::$_properties[$name]['table']).'.'.$this->_conn->encloseName(static::$_properties[$name]['fieldName']).' '.$way;
                }
                else {
                    $order[] = $this->_conn->encloseName(static::$_properties[$name]['name']).' '.$way;
                }
            }
        }

        if(count ($order)){
            return ' ORDER BY '.implode (', ', $order);
        }
        return '';
    }

    /**
     * @internal it don't support isExpr property of a condition because of security issue (SQL injection)
     * because the value could be provided by a form, it is escaped in any case
     */
    final protected function _generateCondition(jDaoCondition $condition, &$fields, $forSelect, $principal=true){
        $r = ' ';
        $notfirst = false;
        foreach ($condition->conditions as $cond){
            if ($notfirst){
                $r .= ' '.$condition->glueOp.' ';
            }else
                $notfirst = true;

            if (!isset($fields[$cond['field_id']])) {
                throw new jException('jelix~dao.error.property.unknown', $cond['field_id']);
            }

            $prop=$fields[$cond['field_id']];

            // Check if pattern is set
            $pattern = isset($cond['field_pattern']) ? $cond['field_pattern'] : '%s';

            if($forSelect) {
                if($pattern == '%s' || empty($pattern)) {
                    $prefixNoCondition = $this->_conn->encloseName($this->_tables[$prop['table']]['name']).'.'.$this->_conn->encloseName($prop['fieldName']);
                } else {
                    $prefixNoCondition = str_replace("%s", $this->_conn->encloseName($this->_tables[$prop['table']]['name']).'.'.$this->_conn->encloseName($prop['fieldName']), $pattern);
                }
            }
            else {
                if($pattern == '%s' || empty($pattern)) {
                    $prefixNoCondition = $this->_conn->encloseName($prop['fieldName']);
                } else {
                    $prefixNoCondition = str_replace("%s", $this->_conn->encloseName($prop['fieldName']), $pattern);
                } 
            }

            $op = strtoupper($cond['operator']);
            $prefix = $prefixNoCondition.' '.$op.' '; // ' ' for LIKE

            if ($op == 'IN' || $op == 'NOT IN'){
                if(is_array($cond['value'])){
                    $values = array();
                    foreach($cond['value'] as $value)
                        $values[] = $this->_prepareValue($value,$prop['unifiedType']);
                    $values = join(',', $values);
                }
                else
                    $values = $cond['value'];

                $r .= $prefix.'('.$values.')';
            }
            else {
                if ($op == 'LIKE' || $op == 'NOT LIKE') {
                    $type = 'varchar';
                }
                else {
                    $type = $prop['unifiedType'];
                }

                if (!is_array($cond['value'])) {
                    $value = $this->_prepareValue($cond['value'], $type);
                    if ($cond['value'] === null) {
                        if (in_array($op, array('=','LIKE','IS','IS NULL'))) {
                            $r .= $prefixNoCondition.' IS NULL';
                        } else {
                            $r .= $prefixNoCondition.' IS NOT NULL';
                        }
                    } else {
                        $r .= $prefix.$value;
                    }
                } else {
                    $r .= ' ( ';
                    $firstCV = true;
                    foreach ($cond['value'] as $conditionValue){
                        if (!$firstCV) {
                            $r .= ' or ';
                        }
                        $value = $this->_prepareValue($conditionValue, $type);
                        if ($conditionValue === null) {
                            if (in_array($op, array('=','LIKE','IS','IS NULL'))) {
                                $r .= $prefixNoCondition.' IS NULL';
                            } else {
                                $r .= $prefixNoCondition.' IS NOT NULL';
                            }
                        } else {
                            $r .= $prefix.$value;
                        }
                        $firstCV = false;
                    }
                    $r .= ' ) ';
                }
            }
        }
        //sub conditions
        foreach ($condition->group as $conditionDetail){
            if ($notfirst){
                $r .= ' '.$condition->glueOp.' ';
            }else{
                $notfirst=true;
            }
            $r .= $this->_generateCondition($conditionDetail, $fields, $forSelect, false);
        }

        //adds parenthesis around the sql if needed (non empty)
        if (strlen (trim ($r)) > 0 && !$principal){
            $r = '('.$r.')';
        }
        return $r;
    }

    /**
     * prepare the value ready to be used in a dynamic evaluation
     */
    final protected function _prepareValue($value, $fieldType, $notNull = false){
        if (!$notNull && $value === null)
            return 'NULL';
        
        switch(strtolower($fieldType)){
            case 'integer':
                return intval($value);
            case 'double':
            case 'float':
            case 'numeric':
            case 'decimal':
                return jDb::floatToStr($value);
            case 'boolean':
                if ($value === true|| strtolower($value)=='true'|| intval($value) === 1 || $value ==='t' || $value ==='on')
                    return $this->trueValue;
                else
                    return $this->falseValue;
                break;
            default:
                return $this->_conn->quote2 ($value, true, ($fieldType == 'binary'));
        }
    }

    /**
     * finish to initialise a record set. Could be redefined in child class
     * to do additionnal processes
     * @param jDbResultSet $rs the record set
     */
    protected function finishInitResultSet($rs) {
        $rs->setFetchMode(8, $this->_DaoRecordClassName);
    }

    /**
     * a callback function for some array_map call in generated methods
     * @since 1.2
     */
    protected function _callbackQuote($value) {
        return $this->_conn->quote2($value);
    }

    /**
     * a callback function for some array_map call in generated methods
     * @since 1.2
     */
    protected function _callbackQuoteBin($value) {
        return $this->_conn->quote2($value, true, true);
    }

    /**
     * a callback function for some array_map call in generated methods
     */
    protected function _callbackBool($value) {
        if ($value === true|| strtolower($value)=='true'|| intval($value) === 1 || $value ==='t' || $value ==='on')
            return $this->trueValue;
        else
            return $this->falseValue;
    }
}
