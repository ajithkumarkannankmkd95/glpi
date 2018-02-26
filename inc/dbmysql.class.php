<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  Database class for Mysql
**/
class DBmysql {

   //! Database Host - string or Array of string (round robin)
   public $dbhost             = "";
   //! Database User
   public $dbuser             = "";
   //! Database Password
   public $dbpassword         = "";
   //! Default Database
   public $dbdefault          = "";
   //! Database Handler
   private $dbh;
   //! Database Error
   public $error              = 0;

   // Slave management
   public $slave              = false;
   /** Is it a first connection ?
    * Indicates if the first connection attempt is successful or not
    * if first attempt fail -> display a warning which indicates that glpi is in readonly
   **/
   public $first_connection   = true;
   // Is connected to the DB ?
   public $connected          = false;

   //to calculate execution time
   public $execution_time          = false;

   //to simulate transactions (for tests)
   public $objcreated = [];

   private $cache_disabled = false;

   /**
    * Constructor / Connect to the MySQL Database
    *
    * @param integer $choice host number (default NULL)
    *
    * @return void
    */
   function __construct($choice = null) {
      $this->connect($choice);
   }

   /**
    * Connect using current database settings
    * Use dbhost, dbuser, dbpassword and dbdefault
    *
    * @param integer $choice host number (default NULL)
    *
    * @return void
    */
   function connect($choice = null) {
      $this->connected = false;

      if (is_array($this->dbhost)) {
         // Round robin choice
         $i    = (isset($choice) ? $choice : mt_rand(0, count($this->dbhost)-1));
         $host = $this->dbhost[$i];

      } else {
         $host = $this->dbhost;
      }

      $hostport = explode(":", $host);
      if (count($hostport) < 2) {
         // Host
         $dsn = "mysql:host=$host";
      } else if (intval($hostport[1])>0) {
         // Host:port
         $dsn = "mysql:host={$hostport[0]}:{$hostport[1]}";
      } else {
         // :Socket
         $dsn = "mysql:unix_socket={$hostport[1]}";
      }

      try {
         $charset = isset($this->dbenc) ? $this->dbenc : "utf8";
         $this->dbh = new PDO(
            "$dsn;dbname={$this->dbdefault};charset=$charset",
            $this->dbuser,
            rawurldecode($this->dbpassword)
         );
         $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         if (GLPI_FORCE_EMPTY_SQL_MODE) {
            $this->dbh->query("SET SESSION sql_mode = ''");
         }
         $this->connected = true;
      } catch (\Exception $e) {
         $this->connected = false;
         $this->error     = 1;
         //FIXME: drop or handle
         throw $e;
      }
   }

   /**
    * Escapes special characters in a string for use in an SQL statement,
    * taking into account the current charset of the connection and quote it.
    *
    * @since 9.3
    *
    * @param string $string String to quote
    *
    * @return string quoted string
    */
   function quote($string) {
      return $this->dbh->quote($string);
   }


   /**
    * Escapes special characters in a string for use in an SQL statement,
    * taking into account the current charset of the connection
    *
    * @since 0.84
    *
    * @param string $string String to escape
    *
    * @return string escaped string
    */
   function escape($string) {
      $quoted = $this->quote($string);
      //TODO: this is OK for MySQL; but probably not for PostgreSQL
      return trim($quoted, "'");
   }

   /**
    * Executes a PDO prepared query
    *
    * @param string $query  The query string to execute
    * @param array  $params Query parameters; if any
    *
    * @return PDOStatement
    */
   public function execute($query, array $params = []) {
      $stmt = $this->dbh->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
      $stmt->execute($params);
      return $stmt;
   }

   /**
    * Execute a MySQL query
    *
    * @param string $query Query to execute
    * @param array  $params Query parameters; if any
    *
    * @var array   $CFG_GLPI
    * @var array   $DEBUG_SQL
    * @var integer $SQL_TOTAL_REQUEST
    *
    * @return PDOStatement|boolean Query result handler
    *
    * @throws GlpitestSQLError
    */
   function query($query, $params = []) {
      global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && $CFG_GLPI["debug_sql"]) {
         $SQL_TOTAL_REQUEST++;
         $DEBUG_SQL["queries"][$SQL_TOTAL_REQUEST] = $query;
      }
      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
         && $CFG_GLPI["debug_sql"] || $this->execution_time === true) {
         $TIMER                                    = new Timer();
         $TIMER->start();
      }

      try {
         $res = $this->execute($query, $params);

         if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
            && $CFG_GLPI["debug_sql"]) {
            $TIME                                   = $TIMER->getTime();
            $DEBUG_SQL["times"][$SQL_TOTAL_REQUEST] = $TIME;
         }
         if ($this->execution_time === true) {
            $this->execution_time = $TIMER->getTime(0, true);
         }
         return $res;
      } catch (\Exception $e) {
         // no translation for error logs
         $error = "  *** MySQL query error:\n  SQL: ".$query."\n  Error: ".
                   $e->getMessage()."\n";
         $error .= print_r($params, true) . "\n";
         $error .= $e->getTraceAsString();

         Toolbox::logSqlError($error);

         if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
                || isAPI())
             && $CFG_GLPI["debug_sql"]) {
            $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $e->getMessage();
         }
      }
      return false;
   }

   /**
    * Execute a MySQL query and die
    * (optionnaly with a message) if it fails
    *
    * @since 0.84
    *
    * @param string $query   Query to execute
    * @param string $message Explanation of query (default '')
    * @param array  $params  Query parameters; if any
    *
    * @return PDOStatement
    */
   function queryOrDie($query, $message = '', $params = []) {
      $res = $this->query($query, $params);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $query,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }
      }
      return $res;
   }

   /**
    * Prepare a MySQL query
    *
    * @param string $query Query to prepare
    *
    * @return PDOStatement|boolean statement object or FALSE if an error occurred.
    *
    * @throws GlpitestSQLError
    */
   function prepare($query) {
      global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

      try {
         $res = $this->dbh->prepare($query);
         return $res;
      } catch (\Exception $e) {
         // no translation for error logs
         $error = "  *** MySQL prepare error:\n  SQL: ".$query."\n  Error: ".
                  $e->getMessage()."\n";
         $error .= $e->getTraceAsString();

         Toolbox::logInFile("sql-errors", $error);
         if (class_exists('GlpitestSQLError')) { // For unit test
            throw new GlpitestSQLError($error, 0, $e);
         }

         if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
             && $CFG_GLPI["debug_sql"]) {
            $SQL_TOTAL_REQUEST++;
            $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $e->getMessage();
         }
      }
      return false;
   }

   /**
    * Give result from a sql result
    *
    * @param PDOStatement $result MySQL result handler
    * @param int          $i      Row offset to give
    * @param type         $field  Field to give
    *
    * @return mixed Value of the Row $i and the Field $field of the Mysql $result
    */
   function result($result, $i, $field) {
      $seek_mode = (is_int($field) ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
      if ($result) {
         $data = $this->data_seek($result, $i, $seek_mode);
         if (isset($data[$field])) {
            return $data[$field];
         }
      }
      return null;
   }

   /**
    * Number of rows
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return integer number of rows
    */
   function numrows($result) {
      return $result->rowCount();
   }

   /**
    * Fetch array of the next row of a Mysql query
    * Please prefer fetch_row or fetch_assoc
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return string[]|null array results
    */
   function fetch_array($result) {
      $result->setFetchMode(PDO::FETCH_NUM);
      return $result->fetch();
   }

   /**
    * Fetch row of the next row of a Mysql query
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return mixed|null result row
    */
   function fetch_row($result) {
      return $result->fetch();
   }

   /**
    * Fetch assoc of the next row of a Mysql query
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return string[]|null result associative array
    */
   function fetch_assoc($result) {
      $result->setFetchMode(PDO::FETCH_ASSOC);
      return $result->fetch();
   }

   /**
    * Fetch object of the next row of an SQL query
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return object|null
    */
   function fetch_object($result) {
      $result->setFetchMode(PDO::FETCH_OBJ);
      return $result->fetch();
   }

   /**
    * Move current pointer of a Mysql result to the specific row
    *
    * @param PDOStatement $result MySQL result handler
    * @param integer      $num    Row to move current pointer
    *
    * @return boolean
    */
   function data_seek($result, $num, $seek_mode = PDO::FETCH_ASSOC) {
      return $result->fetch($seek_mode, PDO::FETCH_ORI_ABS, $num);
   }

   /**
    * Give ID of the last inserted item by Mysql
    *
    * @return mixed
    */
   function insert_id() {
      return (int)$this->dbh->lastInsertID();
   }

   /**
    * Give number of fields of a Mysql result
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return int number of fields
    */
   function num_fields($result) {
      return $result->field_count;
   }

   /**
    * Give name of a field of a Mysql result
    *
    * @param PDOStatement $result MySQL result handler
    * @param integer       $nb     ID of the field
    *
    * @return string name of the field
    */
   function field_name($result, $nb) {
      $finfo = $result->fetch_fields();
      return $finfo[$nb]->name;
   }


   /**
    * List tables in database
    *
    * @param string $table table name condition (glpi_% as default to retrieve only glpi tables)
    *
    * @return PDOStatement list of tables
    *
    * @deprecated 9.3
    */
   function list_tables($table = "glpi_%") {
      Toolbox::deprecated('list_tables is deprecated, use listTables');
      return $this->query(
         "SELECT TABLE_NAME FROM information_schema.`TABLES`
             WHERE TABLE_SCHEMA = '{$this->dbdefault}'
                AND TABLE_TYPE = 'BASE TABLE'
                AND TABLE_NAME LIKE '$table'"
      );
   }

   /**
    * List tables in database
    *
    * @param string $table Table name condition (glpi_% as default to retrieve only glpi tables)
    * @param array  $where Where clause to append
    *
    * @return DBmysqlIterator
    */
   function listTables($table = 'glpi_%', array $where = []) {
      $iterator = $this->request([
         'SELECT' => 'TABLE_NAME',
         'FROM'   => 'information_schema.TABLES',
         'WHERE'  => [
            'TABLE_SCHEMA' => $this->dbdefault,
            'TABLE_TYPE'   => 'BASE TABLE',
            'TABLE_NAME'   => ['LIKE', $table]
         ] + $where
      ]);
      return $iterator;
   }

   public function getMyIsamTables() {
      $iterator = $this->listTables('glpi_%', ['engine' => 'MyIsam']);
      return $iterator;
   }


   /**
    * List fields of a table
    *
    * @param string  $table    Table name condition
    * @param boolean $usecache If use field list cache (default true)
    *
    * @return mixed list of fields
    */
   function list_fields($table, $usecache = true) {
      static $cache = [];

      if (!$this->cache_disabled && $usecache && isset($cache[$table])) {
         return $cache[$table];
      }
      $result = $this->query("SHOW COLUMNS FROM `$table`");
      if ($result) {
         if ($this->numrows($result) > 0) {
            $cache[$table] = [];
            while ($data = $this->fetch_assoc($result)) {
               $cache[$table][$data["Field"]] = $data;
            }
            return $cache[$table];
         }
         return [];
      }
      return false;
   }

   /**
    * Get number of affected rows in previous MySQL operation
    *
    * @return int number of affected rows on success, and -1 if the last query failed.
    */
   function affected_rows() {
      throw new \RuntimeException('affected_rows method could not be used... Use PDOStatement::rowCount instead.');
   }

   /**
    * Free result memory
    *
    * @param PDOStatement $result PDO statement
    *
    * @return boolean TRUE on success or FALSE on failure.
    */
   function free_result($result) {
      return $result->closeCursor();
   }

   /**
    * Returns the numerical value of the error message from previous MySQL operation
    *
    * @return int error number from the last MySQL function, or 0 (zero) if no error occurred.
    */
   function errno() {
      return $this->dbh->errno;
   }

   /**
    * Returns the text of the error message from previous MySQL operation
    *
    * @return string error text from the last MySQL function, or '' (empty string) if no error occurred.
    */
   function error() {
      $error = $this->dbh->errorInfo();
      if (isset($error[2])) {
         return $error[2];
      } else {
         return '';
      }
   }

   /**
    * Close MySQL connection
    *
    * @return boolean TRUE on success or FALSE on failure.
    */
   function close() {
      if ($this->dbh) {
         $this->dbh = null;
         return true;
      }
      return false;
   }

   /**
    * is a slave database ?
    *
    * @return boolean
    */
   function isSlave() {
      return $this->slave;
   }

   /**
    * Execute all the request in a file
    *
    * @param string $path with file full path
    *
    * @return boolean true if all query are successfull
    */
   function runFile($path) {
      $DBf_handle = fopen($path, "rt");
      if (!$DBf_handle) {
         return false;
      }

      $formattedQuery = "";
      $lastresult     = false;
      while (!feof($DBf_handle)) {
         // specify read length to be able to read long lines
         $buffer = fgets($DBf_handle, 102400);

         // do not strip comments due to problems when # in begin of a data line
         $formattedQuery .= $buffer;
         if ((substr(rtrim($formattedQuery), -1) == ";")
             && (substr(rtrim($formattedQuery), -4) != "&gt;")
             && (substr(rtrim($formattedQuery), -4) != "160;")) {

            $formattedQuerytorun = $formattedQuery;

            // Do not use the $DB->query
            if ($this->query($formattedQuerytorun)) { //if no success continue to concatenate
               $formattedQuery = "";
               $lastresult     = true;
            } else {
               $lastresult = false;
            }
         }
      }

      return $lastresult;
   }

   /**
    * Instanciate a Simple DBIterator
    *
    * Examples =
    *  foreach ($DB->request("select * from glpi_states") as $data) { ... }
    *  foreach ($DB->request("glpi_states") as $ID => $data) { ... }
    *  foreach ($DB->request("glpi_states", "ID=1") as $ID => $data) { ... }
    *  foreach ($DB->request("glpi_states", "", "name") as $ID => $data) { ... }
    *  foreach ($DB->request("glpi_computers",array("name"=>"SBEI003W","entities_id"=>1),array("serial","otherserial")) { ... }
    *
    * Examples =
    *   array("id"=>NULL)
    *   array("OR"=>array("id"=>1, "NOT"=>array("state"=>3)));
    *   array("AND"=>array("id"=>1, array("NOT"=>array("state"=>array(3,4,5),"toto"=>2))))
    *
    * FIELDS name or array of field names
    * ORDER name or array of field names
    * LIMIT max of row to retrieve
    * START first row to retrieve
    *
    * @param string|string[] $tableorsql Table name, array of names or SQL query
    * @param string|string[] $crit       String or array of filed/values, ex array("id"=>1), if empty => all rows
    *                                    (default '')
    * @param boolean         $debug      To log the request (default false)
    *
    * @return DBmysqlIterator
    */
   public function request ($tableorsql, $crit = "", $debug = false) {
      $iterator = new DBmysqlIterator($this);
      $iterator->execute($tableorsql, $crit, $debug);
      return $iterator;
   }

    /**
     *  Optimize sql table
     *
     * @var DB $DB
     *
     * @param mixed   $migration Migration class (default NULL)
     * @param boolean $cron      To know if optimize must be done (false by default)
     *
     * @deprecated 9.2.2
     *
     * @return int number of tables
     */
   static function optimize_tables($migration = null, $cron = false) {
      global $DB;

      Toolbox::deprecated();

      $crashed_tables = self::checkForCrashedTables();
      if (!empty($crashed_tables)) {
         Toolbox::logError("Cannot launch automatic action : crashed tables detected");
         return -1;
      }

      if (!is_null($migration) && method_exists($migration, 'displayMessage')) {
         $migration->displayTitle(__('Optimizing tables'));
         $migration->addNewMessageArea('optimize_table'); // to force new ajax zone
         $migration->displayMessage(sprintf(__('%1$s - %2$s'), __('optimize'), __('Start')));
      }
      $result = $DB->listTables();
      $nb     = 0;

      while ($line = $result->next()) {
         $table = $line[0];

         // For big database to reduce delay of migration
         if ($cron
             || (countElementsInTable($table) < 15000000)) {

            if (!is_null($migration) && method_exists($migration, 'displayMessage')) {
               $migration->displayMessage(sprintf(__('%1$s - %2$s'), __('optimize'), $table));
            }

            $query = "OPTIMIZE TABLE `".$table."`;";
            $DB->query($query);
            $nb++;
         }
      }
      $DB->free_result($result);

      if (!is_null($migration)
          && method_exists($migration, 'displayMessage') ) {
         $migration->displayMessage(sprintf(__('%1$s - %2$s'), __('optimize'), __('End')));
      }

      return $nb;
   }

   /**
    * Get information about DB connection for showSystemInformations
    *
    * @since 0.84
    *
    * @return string[] Array of label / value
    */
   public function getInfo() {
      // No translation, used in sysinfo
      $ret = [];
      $req = $this->request("SELECT @@sql_mode as mode, @@version AS vers, @@version_comment AS stype");

      if (($data = $req->next())) {
         if ($data['stype']) {
            $ret['Server Software'] = $data['stype'];
         }
         if ($data['vers']) {
            $ret['Server Version'] = $data['vers'];
         } else {
            $ret['Server Version'] = $this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
         }
         if ($data['mode']) {
            $ret['Server SQL Mode'] = $data['mode'];
         } else {
            $ret['Server SQL Mode'] = '';
         }
      }
      $ret['Parameters'] = $this->dbuser."@".$this->dbhost."/".$this->dbdefault;
      $ret['Host info']  = $this->dbh->getAttribute(PDO::ATTR_SERVER_INFO);

      return $ret;
   }

   /**
    * Is MySQL strict mode ?
    * @since 0.90
    *
    * @var DB $DB
    *
    * @param string $msg Mode
    *
    * @return boolean
    */
   static function isMySQLStrictMode(&$msg) {
      global $DB;

      $msg = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY,NO_AUTO_CREATE_USER';
      $req = $DB->request("SELECT @@sql_mode as mode");
      if (($data = $req->next())) {
         return (preg_match("/STRICT_TRANS/", $data['mode'])
                 && preg_match("/NO_ZERO_/", $data['mode'])
                 && preg_match("/ONLY_FULL_GROUP_BY/", $data['mode']));
      }
      return false;
   }

   /**
    * Get a global DB lock
    *
    * @since 0.84
    *
    * @param string $name lock's name
    *
    * @return boolean
    */
   public function getLock($name) {
      $name          = addslashes($this->dbdefault.'.'.$name);
      $query         = "SELECT GET_LOCK('$name', 0)";
      $result        = $this->query($query);
      list($lock_ok) = $this->fetch_row($result);

      return $lock_ok;
   }

   /**
    * Release a global DB lock
    *
    * @since 0.84
    *
    * @param string $name lock's name
    *
    * @return boolean
    */
   public function releaseLock($name) {
      $name          = addslashes($this->dbdefault.'.'.$name);
      $query         = "SELECT RELEASE_LOCK('$name')";
      $result        = $this->query($query);
      list($lock_ok) = $this->fetch_row($result);

      return $lock_ok;
   }

   /**
   * Check for crashed MySQL Tables
   *
   * @since 0.90.2
   *
   * @var DB $DB
    *
   * @return string[] array with supposed crashed table and check message
   */
   static public function checkForCrashedTables() {
      global $DB;
      $crashed_tables = [];

      $result_tables = $DB->listTables();

      while ($line = $result_tables->next()) {
         $query  = "CHECK TABLE `".$line['TABLE_NAME']."` FAST";
         $result  = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            $row = $DB->fetch_assoc($result);
            if ($row['Msg_type'] != 'status' && $row['Msg_type'] != 'note') {
               $crashed_tables[] = ['table'    => $row[0],
                                    'Msg_type' => $row['Msg_type'],
                                    'Msg_text' => $row['Msg_text']];
            }
         }
      }
      return $crashed_tables;
   }

   /**
    * Check if a table exists
    *
    * @since 9.2
    *
    * @param string $tablename Table name
    *
    * @return boolean
    **/
   public function tableExists($tablename) {
      // Get a list of tables contained within the database.
      $result = $this->listTables("%$tablename%");

      if (count($result)) {
         while ($data = $result->next()) {
            if ($data['TABLE_NAME'] === $tablename) {
               return true;
            }
         }
      }

      return false;
   }

   /**
    * Check if a field exists
    *
    * @since 9.2
    *
    * @param string  $table    Table name for the field we're looking for
    * @param string  $field    Field name
    * @param Boolean $usecache Use cache; @see DBmysql::list_fields(), defaults to true
    *
    * @return boolean
    **/
   public function fieldExists($table, $field, $usecache = true) {
      if (!$this->tableExists($table)) {
         trigger_error("Table $table does not exists", E_USER_WARNING);
         return false;
      }

      if ($fields = $this->list_fields($table, $usecache)) {
         if (isset($fields[$field])) {
            return true;
         }
         return false;
      }
      return false;
   }

   /**
    * Disable table cache globally; usefull for migrations
    *
    * @return void
    */
   public function disableTableCaching() {
      $this->cache_disabled = true;
   }

   /**
    * Quote field name
    *
    * @since 9.3
    *
    * @param string $name of field to quote (or table.field)
    *
    * @return string
    */
   public static function quoteName($name) {
      //handle aliases
      $names = preg_split('/ AS /i', $name);
      if (count($names) > 2) {
         throw new \RuntimeException(
            'Invalid field name ' . $name
         );
      }
      if (count($names) == 2) {
         $name = self::quoteName($names[0]);
         if (count($names) == 2) {
            $name .= ' AS ' . self::quoteName($names[1]);
         }
         return $name;
      } else {
         if (strpos($name, '.')) {
            $n = explode('.', $name, 2);
            $table = self::quoteName($n[0]);
            $field = ($n[1] === '*') ? $n[1] : self::quoteName($n[1]);
            return "$table.$field";
         }
         return ($name[0]=='`' ? $name : ($name === '*') ? $name : "`$name`");
      }
   }

   /**
    * Starts a PDO transaction
    *
    * @return boolean
    */
   public function beginTransaction() {
      return $this->dbh->beginTransaction();
   }

   /**
    * Commits a PDO transaction
    *
    * @return boolean
    */
   public function commit() {
      return $this->dbh->commit();
   }

   /**
    * Roolbacks a PDO transaction
    *
    * @return boolean
    */
   public function rollBack() {
      return $this->dbh->rollBack();
   }

   /**
    * Is into a PDO transaction?
    *
    * @return boolean
    */
   public function inTransaction() {
      return $this->dbh->inTransaction();
   }
   /**
    * Quote value for insert/update
    *
    * @param mixed $value Value
    *
    * @return mixed
    */
   public static function quoteValue($value) {
      if ($value instanceof QueryParam || $value instanceof QueryExpression) {
         //no quote for query parameters nor expressions
         $value = $value->getValue();
      } else if ($value === null || $value === 'NULL' || $value === 'null') {
         $value = 'NULL';
      } else if (!preg_match("/^`.*?`$/", $value)) { //`field` is valid only for mysql :/
         //phone numbers may start with '+' and will be considered as numeric
         $value = "'$value'";
      }
      return $value;
   }

   /**
    * Builds an insert statement
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([field name => field value)
    *
    * @return string
    */
   public function buildInsert($table, &$params) {
      $query = "INSERT INTO " . self::quoteName($table) . " (";

      $fields  = [];
      $keys    = [];
      foreach ($params as $key => $value) {
         $fields[] = $this->quoteName($key);
         if ($value instanceof QueryExpression) {
            $keys[] = $value->getValue();
            unset($params[$key]);
         } else {
            $keys[]   = ":$key";
         }
      }

      $query .= implode(', ', $fields);
      $query .= ") VALUES (";
      $query .= implode(", ", $keys);
      $query .= ")";

      return $query;
   }

   /**
    * Insert a row in the database
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([field name => field value)
    *
    * @return PDOStatement
    */
   public function insert($table, $params) {
      $result = $this->query(
         $this->buildInsert($table, $params),
         $params
      );
      return $result;
   }

   /**
    * Insert a row in the database and die
    * (optionnaly with a message) if it fails
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params  Query parameters ([field name => field value)
    * @param string $message Explanation of query (default '')
    *
    * @return PDOStatement
    */
   function insertOrDie($table, $params, $message = '') {
      $insert = $this->buildInsert($table, $params);
      $res = $this->query($insert, $params);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $insert,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }
      }
      return $res;
   }

   /**
    * Builds an update statement
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([field name => field value)
    * @param array  $where  WHERE clause (@see DBmysqlIterator capabilities)
    *
    * @return string
    */
   public function buildUpdate($table, &$params, $where) {

      if (!count($where)) {
         throw new \RuntimeException('Cannot run an UPDATE query without WHERE clause!');
      }

      $query  = "UPDATE ". self::quoteName($table) ." SET ";

      foreach ($params as $field => $value) {
         $subq = self::quoteName($field) . ' = ?, ';
         if ($value instanceof QueryExpression) {
            $subq = str_replace('?', $value->getValue(), $subq);
            unset($params[$field]);
         }
         $query .= $subq;
      }
      $query = rtrim($query, ', ');

      $it = new DBmysqlIterator($this);
      $query .= " WHERE " . $it->analyseCrit($where);
      $params = array_merge(array_values($params), $it->getParameters());

      return $query;
   }

   /**
    * Update a row in the database
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([:field name => field value)
    * @param array  $where  WHERE clause
    *
    * @return PDOStatement
    */
   public function update($table, $params, $where) {
      $query = $this->buildUpdate($table, $params, $where);
      $result = $this->query($query, $params);
      return $result;
   }

   /**
    * Update a row in the database or die
    * (optionnaly with a message) if it fails
    *
    * @since 9.3
    *
    * @param string $table   Table name
    * @param array  $params  Query parameters ([:field name => field value)
    * @param array  $where   WHERE clause
    * @param string $message Explanation of query (default '')
    *
    * @return mysqli_result|boolean Query result handler
    */
   function updateOrDie($table, $params, $where, $message = '') {
      $update = $this->buildUpdate($table, $params, $where);
      $res = $this->query($update, $params);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $update,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }
      }
      return $res;
   }

   /**
    * Builds a delete statement
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([field name => field value)
    * @param array  $where  WHERE clause (@see DBmysqlIterator capabilities)
    *
    * @return string
    */
   public function buildDelete($table, $where) {

      if (!count($where)) {
         throw new \RuntimeException('Cannot run an DELETE query without WHERE clause!');
      }

      $query  = "DELETE FROM ". self::quoteName($table);

      $it = new DBmysqlIterator($this);
      $query .= " WHERE " . $it->analyseCrit($where);

      return $query;
   }

   /**
    * Delete rows in the database
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $where  WHERE clause
    *
    * @return mysqli_result|boolean Query result handler
    */
   public function delete($table, $where) {
      $query = $this->buildDelete($table, $where);
      $result = $this->query($query, array_values($where));
      return $result;
   }

   /**
    * Delete a row in the database and die
    * (optionnaly with a message) if it fails
    *
    * @since 9.3
    *
    * @param string $table   Table name
    * @param array  $where   WHERE clause
    * @param string $message Explanation of query (default '')
    *
    * @return mysqli_result|boolean Query result handler
    */
   function deleteOrDie($table, $where, $message = '') {
      $update = $this->buildDelete($table, $where);
      $res = $this->query($update, $where);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $update,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }

      }
      return $res;
   }


   /**
    * Get table schema
    *
    * @param string $table Table name,
    * @param string|null $structure Raw table structure
    *
    * @return array
    */
   public function getTableSchema($table, $structure = null) {
      if ($structure === null) {
         $structure = $this->query("SHOW CREATE TABLE `$table`")->fetch();
         $structure = $structure[1];
      }

      //get table index
      $index = preg_grep(
         "/^\s\s+?KEY/",
         array_map(
            function($idx) { return rtrim($idx, ','); },
            explode("\n", $structure)
         )
      );
      //get table schema, without index, without AUTO_INCREMENT
      $structure = preg_replace(
         [
            "/\s\s+KEY .*/",
            "/AUTO_INCREMENT=\d+ /"
         ],
         "",
         $structure
      );
      $structure = preg_replace('/,(\s)?$/m', '', $structure);
      $structure = preg_replace('/ COMMENT \'(.+)\'/', '', $structure);

      $structure = str_replace(
         [
            " COLLATE utf8_unicode_ci",
            " CHARACTER SET utf8",
            ', ',
         ], [
            '',
            '',
            ',',
         ],
         trim($structure)
      );

      //do not check engine nor collation
      $structure = preg_replace(
         '/\) ENGINE.*$/',
         '',
         $structure
      );

      //Mariadb 10.2 will return current_timestamp()
      //while older retuns CURRENT_TIMESTAMP...
      $structure = preg_replace(
         '/ CURRENT_TIMESTAMP\(\)/i',
         ' CURRENT_TIMESTAMP',
         $structure
      );

      //Mariadb 10.2 allow default values on longblob, text and longtext
      preg_match_all(
         '/^.+ (longblob|text|longtext) .+$/m',
         $structure,
         $defaults
      );
      if (count($defaults[0])) {
         foreach ($defaults[0] as $line) {
               $structure = str_replace(
                  $line,
                  str_replace(' DEFAULT NULL', '', $line),
                  $structure
               );
         }
      }

      $structure = preg_replace("/(DEFAULT) ([-|+]?\d+)(\.\d+)?/", "$1 '$2$3'", $structure);
      //$structure = preg_replace("/(DEFAULT) (')?([-|+]?\d+)(\.\d+)(')?/", "$1 '$3'", $structure);
      $structure = preg_replace('/(BIGINT)\(\d+\)/i', '$1', $structure);
      $structure = preg_replace('/(TINYINT) /i', '$1(4) ', $structure);

      return [
         'schema' => strtolower($structure),
         'index'  => $index
      ];

   }
}
