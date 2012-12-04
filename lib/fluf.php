<?php
/**
 * (fluf) php
 *
 * A Micro-Routing / Web Application Framework for PHP(5) - Without the excess "fluf"
 *
 * @version 1.0
 * @author Nijiko Yonskai <http://nexua.org>
 * @package fluf
 * @license AOL <http://aol.nexua.org/#!/fluf.php/Nijiko Yonskai/nijikokun@gmail.com/nijikokun>
 */

namespace {
  class fluf {
    static $server, $env, $files, $request, $get, $post, $session, $cookie, $uri, $method, $script, $autorun = true;
    private static $routes = array();

    static function setup () {
      self::$session = new \fluf\Session('fluf_session');
      foreach (array('request','post','get','server', 'env', 'files') as $method)
        self::${$method} = new \fluf\Arrays($GLOBALS['_'.strtoupper($method)]);
      self::$uri = preg_replace('/\?.+/', '', $_SERVER['REQUEST_URI']);
      self::$method = $_SERVER['REQUEST_METHOD'];
      self::$script = $_SERVER['SCRIPT_NAME'];
      self::sanitize();
    }

    private static function sanitize () {
      $uri = explode('/', self::$uri);
      $name = explode('/', self::$script);
      for ($i = 0; $i < count($name); $i++) if ($uri[$i] == $name[$i]) unset($uri[$i]);
      self::$uri = '/' . implode('/', $uri);
    }

    static function add ($rule, $method, $callback, $cond = array()) {
      self::$routes[] = new \fluf\Route(self::$uri, $rule, $method, $callback, $cond);
      if (self::$autorun) self::run();
    }

    static function ajax ($x = 'HTTP_X_REQUESTED_WITH') {
      return isset($_SERVER[$x]) &&  $_SERVER[$x] === 'XMLHttpRequest';
    }

    static function redirect($path, $exit = true) {
      $uri = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
      header('Location: ' . ((preg_match('%^http://|https://%', $path) > 0) ? $path : $uri . $path)); if($exit) exit;
    }

    static function run () {
      foreach (self::$routes as $i => $r) {
        if ($r->match && !$r->ran) {
          $result = call_user_func_array($r->callback, $r->params); $r->ran = true;
          if (is_array($result)) { header('Content-type: application/json; charset=utf-8'); echo json_encode($result); }
        } else unset(self::$routes[$i]);
      }
    }
  }

  fluf::setup();
}

namespace fluf {
  class Session {
    private $a;
    public function __construct ($name) { session_name($name); session_start(); $this->a = $GLOBALS['_SESSION']; }
    public function __get($k) { return isset($this->a[$k]) ? $this->a[$k] : null; }
    public function __set($k, $v) { $this->a[$k] = $v; return $v; }
    public function unset($k) { if ($this->a[$k]) unset($this->a[$K]); }
    public function destroy($unset = false) { if($unset) session_unset(); return session_destroy(); }
  }

  class Cookie {
    private $a;
    public function __construct () { $this->a = $GLOBALS['_COOKIE']; }
    public function __invoke($k, $v, $timeout = time() + 3600 * 60 * 60, $path = null, $domain = null, $secure = false, $httponly = false) { return $this->set($k, $v, $timeout, $path, $domain, $secure, $httponly); }
    public function __get($k) { return isset($this->a[$k]) ? $this->a[$k] : null; }
    public function set($k, $v, $timeout = time() + 3600 * 60 * 60, $path = null, $domain = null, $secure = false, $httponly = false) { return setcookie($k, $v, $timeout, $path, $domain, $secure, $httponly); }
    public function unset($k) { if ($this->a[$K]) unset($this->a[$K]); return setcookie($k, null, -1); }
  }

  class Arrays {
    private $a;
    public function __construct(&$a) { $this->a = $a; }
    public function __invoke($k, $v) { if(isset($v)) { $this->a[$k] = $v; return $v; } else return isset($this->a[$k]) ? $this->a[$k] : null; }
    public function __get($k) { return isset($this->a[$k]) ? $this->a[$k] : null; }
    public function __set($k, $v) { $this->a[$k] = $v; return $v; }
  }

  class Route {
    public $url, $callback, $match = false, $ran = false, $params = array();
    private $conditions, $uri;

    function __construct ($uri, $url, $method, $callback, $cond = array()) {
      if (empty($uri) || empty($url) || empty($method) || empty($callback)) return;
      if (is_callable($callback)) $this->callback = $callback;
      $this->method = is_array($method) ? $method : array( $method );
      $this->conditions = $cond; $this->url = $url; $this->uri = $uri;
      $this->compile();
    }

    function compile () {
      $names = $values = array(); 
      preg_match_all('@:([\w]+)@', $this->url, $names, PREG_PATTERN_ORDER);
      $names = $names[0];
      $regex = (preg_replace_callback('@:[\w]+@', array($this, 'regex'), $this->url)) . '/?';
      
      if (preg_match('@^' . $regex . '$@', $this->uri, $values) && in_array(\fluf::$method, $this->method) && array_shift($values)) {
        foreach ($names as $i => $v) $this->params[substr($v,1)] = urldecode($values[$i]);
        $this->match = true;
      }
    }

    function regex ($matches) {
      $key = str_replace(':', '', $matches[0]);
      return array_key_exists($key, $this->conditions) ?  '('.$this->conditions[$key].')' : '([a-zA-Z0-9_\+\-%]+)';
    }
  }

  function map ($rule, $callback, $cond = array()) { 
    return new \fluf\Map($rule, $callback, $cond); 
  }
  
  foreach (array('get','post','put','delete','patch','head','options') as $method) 
    eval('namespace fluf; function '.$method.' ($rule, $callback, $cond = array()) { \fluf::add($rule, "'.strtoupper($method).'", $callback, $cond); }');

  class Map {
    function __construct ($rule, $callback, $cond = array()) { $this->r = $rule; $this->cb = $callback; $this->cd = $cond; return $this; }
    public function via () { if (func_num_args() !== 0) foreach (func_get_args() as $a) \fluf::add($this->r, strtoupper($a), $this->cb, $this->cd); }
  }
}