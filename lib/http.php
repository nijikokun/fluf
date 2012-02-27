<?php
// http v0.1
// micro-routing framework for php
// @author Nijiko Yonskai <http://blog.nexua.org>
// @license AOL <http://aol.nexua.org/#!/http.php/Nijiko Yonskai/nijikokun@gmail.com/nijikokun>

namespace {
    class http {
        static $request, $get, $post, $session;
        static $uri, $method, $script;
        private static $routes = array();

        static function setup () {
            self::$session = new \http\Session('http_session');
            self::$request = new \http\Arrays($_REQUEST);
            self::$post = new \http\Arrays($_POST);
            self::$get = new \http\Arrays($_GET);
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

        static function ajax () {
            return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        }

        static function run () {
            foreach(self::$routes as $i => $r) {
                if($r->match && !$r->ran) {
                    $result = call_user_func_array($r->callback, $r->params);
                    if(is_array($result)) echo json_encode($result);
                    $r->ran = true;
                }
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
        public $url;
        public $match = false;
        public $ran = false;
        public $params = array();
        public $callback;
        private $conditions, $uri;

        function __construct ($uri, $url, $method, $callback, $cond = array()) {
            if(empty($uri) || empty($url) || empty($callback)) return;
            if(is_callable($callback)) $this->callback = $callback;
            $this->url = $url;
            $this->method = is_array($method) ? $method : array( $method );
            $this->conditions = $cond;
            $this->uri = $uri;
            $this->compile();
        }

        function compile () {
            $names = $values = array(); 
            preg_match_all('@:([\w]+)@', $this->url, $names, PREG_PATTERN_ORDER);
            $names = $names[0];
            $regex  = preg_replace_callback('@:[\w]+@', array($this, 'regex'), $this->url);
            $regex .= '/?';

            if (
                preg_match('@^' . $regex . '$@', $this->uri, $values) && 
                in_array($_SERVER['REQUEST_METHOD'], $this->method)
            ) {
                array_shift($values);
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

    function get ($rule, $callback, $cond = array()) { \http::add($rule, 'GET', $callback, $cond); }
    function post ($rule, $callback, $cond = array()) { \http::add($rule, 'POST', $callback, $cond); }
    function put ($rule, $callback, $cond = array()) { \http::add($rule, 'PUT', $callback, $cond); }
    function delete ($rule, $callback, $cond = array()) { \http::add($rule, 'DELETE', $callback, $cond); }
}