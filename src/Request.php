<?php

	namespace CHAPI;

	class Request {

		/**
		 * HTTP method used to make the current request (get, post, etc.)
		 * @var string
		 */
		public $type;

		/**
		 * Request parts (controller, action, id and extra fragments)
		 * @var string
		 */
		public $parts;

		/**
		 * Constructor
		 */
		function __construct() {
			$this->type = strtolower( isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : get_item($_SERVER, 'REQUEST_METHOD') );
			$this->parts = [];
		}

		/**
		 * Check whether the current request was made via POST or not
		 * @return boolean True if the request was made via POST, False otherwise
		 */
		function isPost() {
			return !!$_POST;
		}

		/**
		 * Return the HTTP method
		 * @return string HTTP method
		 */
		function getMethod() {
			$method = strtolower( get_item($_SERVER, 'REQUEST_METHOD', 'GET') );
			$method = strtolower( get_item($_SERVER, 'X-HTTP-METHOD-OVERRIDE', $method) );
			$method = strtolower( get_item($_SERVER, 'X_HTTP_METHOD_OVERRIDE', $method) );
			return $method;
		}

		/**
		 * Read the input buffer
		 * @return string Input stream contents
		 */
		function readInput() {
			return file_get_contents('php://input');
		}

		/**
		 * Get a variable from the $_REQUEST superglobal
		 * @param  string $name    Variable name
		 * @param  string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		function param($name = '', $default = '') {
			$ret = $name ? ( isset( $_REQUEST[$name] ) ? $_REQUEST[$name] : $default ) : $_REQUEST;
			return $ret;
		}

		/**
		 * Get a variable from the $_GET superglobal
		 * @param  string $name    Variable name
		 * @param  string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		function get($name = '', $default = '') {
			$ret = $name ? ( isset( $_GET[$name] ) ? $_GET[$name] : $default ) : $_GET;
			return $ret;
		}

		/**
		 * Get a variable from the $_POST superglobal
		 * @param  string $name    Variable name
		 * @param  string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		function post($name = '', $default = '') {
			$ret = $name ? ( isset( $_POST[$name] ) ? $_POST[$name] : $default ) : $_POST;
			return $ret;
		}

		/**
		 * Get a variable from the PUT stream
		 * @param  string $name    Variable name
		 * @param  string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		function put($name = '', $default = '') {
			parse_str($this->readInput(), $put_vars);
			$ret = $name ? ( isset( $put_vars[$name] ) ? $put_vars[$name] : $default ) : $put_vars;
			return $ret;
		}

		/**
		 * Get a variable from the $_SESSION superglobal
		 * @param  string $name    Variable name
		 * @param  string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		function session($name = '', $default = '') {
			$ret = $name ? ( isset( $_SESSION[$name] ) ? $_SESSION[$name] : $default ) : $_SESSION;
			return $ret;
		}

		/**
		 * Get a file from the $_FILES superglobal
		 * @param  string $name File key
		 * @return mixed        Array with file properties or Null
		 */
		function files($name = '') {
			$ret = $name ? ( isset( $_FILES[$name] ) ? $_FILES[$name] : null ) : $_FILES;
			return $ret;
		}

		/**
		 * Get a variable from the $_SERVER superglobal
		 * @param  string $name    Variable name
		 * @param  string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		function server($name, $default = '') {
			return isset( $_SERVER[$name] ) ? $_SERVER[$name] : $default;
		}

		/**
		 * Get a variable from the $_COOKIE superglobal
		 * @param  string $name    Variable name
		 * @param  string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		function cookie($name, $default = '') {
			return isset( $_COOKIE[$name] ) ? $_COOKIE[$name] : $default;
		}

		/**
		 * Check the $_REQUEST superglobal, with or without a specific item
		 * @param  string  $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		function hasParam($name = null) {
			return $name === null ? !!$_REQUEST : isset( $_REQUEST[$name] );
		}

		/**
		 * Check the $_GET superglobal, with or without a specific item
		 * @param  string  $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		function hasGet($name = null) {
			return $name === null ? !!$_GET : isset( $_GET[$name] );
		}

		/**
		 * Check the $_POST superglobal, with or without a specific item
		 * @param  string  $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		function hasPost($name = null) {
			return $name === null ? !!$_POST : isset( $_POST[$name] );
		}

		/**
		 * Check the $_SESSION superglobal, with or without a specific item
		 * @param  string  $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		function hasSession($name = null) {
			return $name === null ? !!$_SESSION : isset( $_SESSION[$name] );
		}

		/**
		 * Check the $_FILES superglobal, with or without a specific item
		 * @param  string  $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		function hasFiles($name = null) {
			return $name === null ? !!$_FILES : isset( $_FILES[$name] );
		}

		/**
		 * Check the $_SERVER superglobal, with or without a specific item
		 * @param  string  $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		function hasServer($name = null) {
			return $name === null ? !!$_SERVER : isset( $_SERVER[$name] );
		}

		/**
		 * Check the $_COOKIE superglobal, with or without a specific item
		 * @param  string  $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		function hasCookie($name = null) {
			return $name === null ? !!$_COOKIE : isset( $_COOKIE[$name] );
		}

		/**
		 * Get authorization token from headers in different ways
		 * @return string       Bearer Token if found
		 */
		function getAuthorizationHeader() {
			$headers = null;
			if (isset($_SERVER['Authorization'])) {
				$headers = trim($_SERVER['Authorization']);
			}
			else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
				$headers = trim($_SERVER['HTTP_AUTHORIZATION']);
			} elseif (function_exists('apache_request_headers')) {
				$requestHeaders = apache_request_headers();
				// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
				$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
				//print_r($requestHeaders);
				if (isset($requestHeaders['Authorization'])) {
					$headers = trim($requestHeaders['Authorization']);
				}
			}

			return $headers;
		}

		/**
		 * Sanitizes authorization header for use
		 * @return string       Clean Bearer
		 */
		function getBearerToken() {
			$headers = $this->getAuthorizationHeader();
			// HEADER: Get the access token from the header
			if (!empty($headers)) {
				if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
					return $matches[1];
				}
			}
			return null;
		}
	}
?>