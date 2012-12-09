<?php
/**
 * Base Database Exception Class
 */
class flufDBException extends flufException {}

/**
 * Database Query Exception Class
 */
class flufDBQueryException extends flufDBException {}

/**
 * ## Excess Plugin for fluf(php)
 *
 *   Database class utilizing PDO and static methods for an easy interface to database control.
 *
 * ## Usage
 *
 *   Move file from `excesses` directory into `lib` directory and add the following code to your 
 *   bootstrap file or `index.php` file:
 *
 * ```
 * fluf::load('db');
 * flufDB::setup('type', 'table', 'host', 'username', 'password');
 * ```
 *
 * @version 1.0
 * @author Nijiko Yonskai <http://nexua.org>
 * @package fluf
 * @license AOL <http://aol.nexua.org/#!/fluf.php/Nijiko+Yonskai/nijikokun@gmail.com/nijikokun>
 */
class flufDB {
  const MYSQL = 'mysql';
  public $dbh;
  private static $instances = array(), $type, $name, $host, $user, $pass;
  private $_type, $_name, $_host, $_user, $_pass;
  
  public function __construct ($type, $name, $host = 'localhost', $user = 'root', $pass = '') {
    $this->_type = $type;
    $this->_name = $name;
    $this->_host = $host;
    $this->_user = $user;
    $this->_pass = $pass;
  }

  public static function getInstance ($type, $name, $host = 'localhost', $user = 'root', $pass = '') {
    $args = func_get_args();
    $hash = md5(implode('~', $args));
    if(isset(self::$instances[$hash])) return self::$instances[$hash];
    self::$instances[$hash] = new flufDB($type, $name, $host, $user, $pass);
    return self::$instances[$hash];
  }
  
  public function execute ($sql = false, $params = array()) {
    $this->init();
    try {
      $sth = $this->prepare($sql, $params);
      return (preg_match('/insert/i', $sql)) ? $this->dbh->lastInsertId() : $sth->rowCount();
    } catch(PDOException $e) {
      flufException::raise(new flufDBQueryException("Query error: {$e->getMessage()} - {$sql}"));
      return false;
    }
  }
  
  public function insertId () {
    $this->init();
    $id = $this->dbh->lastInsertId();
    return ($id > 0) ? $id : false;
  }
  
  public function all ($sql = false, $params = array()) {
    $this->init();
    try {
      $sth = $this->prepare($sql, $params);
      return $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
      flufException::raise(new flufDBQueryException("Query error: {$e->getMessage()} - {$sql}"));
      return false;
    }
  }
  
  public function one ($sql = false, $params = array()) {
    $this->init();
    try {
      $sth = $this->prepare($sql, $params);
      return $sth->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
      flufException::raise(new flufDBQueryException("Query error: {$e->getMessage()} - {$sql}"));
      return false;
    }
  }

  public static function setup ($type = null, $name = null, $host = 'localhost', $user = 'root', $pass = '') {
    if (!empty($type) && !empty($name)) {
      self::$type = $type;
      self::$name = $name;
      self::$host = $host;
      self::$user = $user;
      self::$pass = $pass;
    }

    return array(
      'type' => self::$type, 'name' => self::$name, 'host' => self::$host, 'user' => self::$user, 'pass' => self::$pass
    );
  }

  private function prepare ($sql, $params = array()) {
    try {
      $sth = $this->dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      $sth->execute($params);
      return $sth;
    } catch(PDOException $e) {
      flufException::raise(new flufDBQueryException("Query error: {$e->getMessage()} - {$sql}"));
      return false;
    }
  }

  private function init () {
    if ($this->dbh) return;

    try {
      $this->dbh = new PDO("{$this->_type}:host={$this->_host};dbname={$this->_name}", $this->_user, $this->_pass);
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(Exception $e) {
      flufException::raise(new flufDBException('Could not connect to database'));
    }
  }

  public static function get () {
    $employ = extract(self::setup());

    if (empty($type) || empty($name) || empty($host) || empty($user)) return flufException::raise(
      new flufDBException('Could not determine which database module to load', 404)
    );

    return self::getInstance($type, $name, $host, $user, $pass);
  }
}