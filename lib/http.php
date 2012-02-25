<?php
// http v0.1
// micro-routing framework for php
// @author Nijiko Yonskai <http://blog.nexua.org>
// @license AOL <http://aol.nexua.org/#!/http.php/Nijiko Yonskai/nijikokun@gmail.com/nijikokun>

namespace {
    class http {
        static $request;
        static $method;
        static $script;
        static $session;
        private static $routes = array();

        static function setup () {
            self::$session = new \http\Session('http_session');
            self::$request = $_SERVER['REQUEST_URI'];
            self::$method = $_SERVER['REQUEST_METHOD'];
            self::$script = $_SERVER['SCRIPT_NAME'];
            self::sanitize();
        }

        private static function sanitize () {
            $uri = explode('/', self::$request);
            $name = explode('/', self::$script);
            for($i = 0; $i < count($name); $i++)
                if($uri[$i] == $name[$i])
                    unset($uri[$i]);
            self::$request = '/' . implode('/', $uri);
        }

        static function add ($rule, $method, $callback, $cond = array()) {
            self::$routes[] = new \http\Route(
                self::$request, $rule, $method, $callback, $cond
            );
            self::run();
        }

        static function ajax () {
            return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        }

        static function run () {
            foreach(self::$routes as $i => $r) {
                if($r->match && !$r->ran) {
                    call_user_func_array($r->callback, $r->params);
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
            session_name($name);
            session_start();
        }

        public function __get($key) {
            global $_SESSION;
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        }

        public function __set($key, $value) {
            global $_SESSION;
            $_SESSION[$key] = $value;
            return $value;
        }
    }

    class Route {
        public $url;
        public $match = false;
        public $ran = false;
        public $params = array();
        public $callback;
        private $conditions, $request;

        function __construct ($request, $url, $method, $callback, $cond = array()) {
            if(empty($request) || empty($url) || empty($callback)) return;
            if(is_callable($callback)) $this->callback = $callback;

            $this->url = $url;
            $this->method = is_array($method) ? $method : array( $method );
            $this->conditions = $cond;
            $this->request = $request;
            $this->compile();
        }

        function compile () {
            $names = $values = array(); 
            preg_match_all('@:([\w]+)@', $this->url, $names, PREG_PATTERN_ORDER);
            $names = $names[0];
            $regex  = preg_replace_callback('@:[\w]+@', array($this, 'regex'), $this->url);
            $regex .= '/?';

            if (
                preg_match('@^' . $regex . '$@', $this->request, $values) && 
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
            if (array_key_exists($key, $this->conditions)) {
                return '('.$this->conditions[$key].')';
            } else {
                return '([a-zA-Z0-9_\+\-%]+)';
            }
        }
    }

    function get ($rule, $callback, $cond = array()) { \http::add($rule, 'GET', $callback, $cond); }
    function post ($rule, $callback, $cond = array()) { \http::add($rule, 'POST', $callback, $cond); }
    function put ($rule, $callback, $cond = array()) { \http::add($rule, 'PUT', $callback, $cond); }
    function delete ($rule, $callback, $cond = array()) { \http::add($rule, 'DELETE', $callback, $cond); }
}