<?php
// http v0.6
// micro-routing framework for php
// @author Nijiko Yonskai <http://blog.nexua.org>
// @license AOL <http://aol.nexua.org/#!/http.php/Nijiko Yonskai/nijikokun@gmail.com/nijikokun>

namespace {
    class http {
        static $server, $request, $get, $post, $session, $uri, $method, $script;
        private static $routes = array();

        static function setup () {
            self::$session = new \http\Session('http_session');
            foreach(array('request','post','get','server') as $method) {
                $v = '_'.strtoupper($method); self::${$method} = new \http\Arrays($_GLOBALS[$v]); }
            self::$uri = preg_replace('/\?.+/', '', $_SERVER['REQUEST_URI']);
            self::$method = $_SERVER['REQUEST_METHOD'];
            self::$script = $_SERVER['SCRIPT_NAME'];
            self::sanitize();
        }

        private static function sanitize () {
            $uri = explode('/', self::$uri);
            $name = explode('/', self::$script);
            for($i = 0; $i < count($name); $i++)
                if($uri[$i] == $name[$i])
                    unset($uri[$i]);
            self::$uri = '/' . implode('/', $uri);
        }

        static function add ($rule, $method, $callback, $cond = array()) {
            self::$routes[] = new \http\Route(self::$uri, $rule, $method, $callback, $cond);
            self::run();
        }

        static function ajax ($x = 'HTTP_X_REQUESTED_WITH') {
            return isset($_SERVER[$x]) &&  $_SERVER[$x] === 'XMLHttpRequest';
        }

        static function redirect($path, $exit = true) {
            $uri = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
            header('Location: ' . ((preg_match('%^http://|https://%', $path) > 0) ? $path : $uri . $path)); if($exit) exit;
        }

        static function run () {
            foreach(self::$routes as $i => $r) {
                if($r->match && !$r->ran) {
                    $result = call_user_func_array($r->callback, $r->params); $r->ran = true;
                    if(is_array($result)) echo json_encode($result);
                } else unset(self::$routes[$i]);
            }
        }
    }

    http::setup();
}

namespace http {
    class Session {
        public function __construct ($name) {
            session_name($name); session_start();
        }

        public function __get($k) {
            global $_SESSION; return isset($_SESSION[$k]) ? $_SESSION[$k] : null;
        }

        public function __set($k, $v) {
            global $_SESSION; $_SESSION[$k] = $v; return $v;
        }
    }

    class Arrays {
        private $a;
        public function __construct(&$a) { $this->a = $a; }
        public function __get($k) { return isset($this->a[$k]) ? $this->a[$k] : null; }
        public function __set($k, $v) { $this->a[$k] = $v; return $v; }
    }

    class Route {
        public $url, $callback, $match = false, $ran = false, $params = array();
        private $conditions, $uri;

        function __construct ($uri, $url, $method, $callback, $cond = array()) {
            if(empty($uri) || empty($url) || empty($method) || empty($callback)) return;
            if(is_callable($callback)) $this->callback = $callback;
            $this->method = is_array($method) ? $method : array( $method );
            $this->conditions = $cond;
            $this->url = $url;
            $this->uri = $uri;
            $this->compile();
        }

        function compile () {
            $names = $values = array(); 
            preg_match_all('@:([\w]+)@', $this->url, $names, PREG_PATTERN_ORDER);
            $names = $names[0];
            $regex = (preg_replace_callback('@:[\w]+@', array($this, 'regex'), $this->url)) . '/?';
            
            if (preg_match('@^' . $regex . '$@', $this->uri, $values) && in_array(\http::$method, $this->method) && array_shift($values)) {
                foreach($names as $i => $v) 
                    $this->params[substr($v,1)] = urldecode($values[$i]);
                $this->match = true;
            }
        }

        function regex ($matches) {
            $key = str_replace(':', '', $matches[0]);
            return array_key_exists($key, $this->conditions) ?  '('.$this->conditions[$key].')' : '([a-zA-Z0-9_\+\-%]+)';
        }
    }
    
    foreach(array('get','post','put','delete','patch','head','options') as $method) 
        eval('namespace http; function '.$method.' ($rule, $callback, $cond = array()) { \http::add($rule, "'.strtoupper($method).'", $callback, $cond); }');

    function map ($rule, $callback, $cond = array()) { return new Map($rule, $callback, $cond); }
    class Map {
        function __construct ($rule, $callback, $cond = array()) {
            $this->r = $rule; $this->cb = $callback; $this->cd = $cond; return $this;
        }
        
        public function via () { 
            if (func_num_args() !== 0) foreach(func_get_args() as $a) \http::add($this->r, strtoupper($a), $this->cb, $this->cd);
        }
    }
}