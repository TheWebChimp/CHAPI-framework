<?php

	namespace CHAPI;

	include 'utilities.inc.php';

	class Request {

		/**
		 * HTTP method used to make the current request (get, post, etc.)
		 * @var string
		 */
		public string $type;

		/**
		 * Request parts (controller, action, id and extra fragments)
		 * @var string
		 */
		public $parts;
		public string $uri;

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->type = strtolower($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? get_item($_SERVER, 'REQUEST_METHOD'));
			$this->parts = [];
		}

		/**
		 * Check whether the current request was made via POST or not
		 * @return boolean True if the request was made via POST, False otherwise
		 */
		public function isPost(): bool {
			return !!$_POST;
		}

		/**
		 * Return the HTTP method
		 * @return string HTTP method
		 */
		public function getMethod(): string {
			$method = strtolower(get_item($_SERVER, 'REQUEST_METHOD', 'GET'));
			$method = strtolower(get_item($_SERVER, 'X-HTTP-METHOD-OVERRIDE', $method));
			return strtolower(get_item($_SERVER, 'X_HTTP_METHOD_OVERRIDE', $method));
		}

		/**
		 * Reads the input buffer
		 * @return string Input stream contents
		 */
		public function readInput(): string {
			return file_get_contents('php://input');
		}

		/**
		 * Get a variable from the $_REQUEST super global
		 * @param string $name    Variable name
		 * @param mixed  $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		public function param(string $name = '', $default = '') {
			return $name ? ($_REQUEST[$name] ?? $default) : $_REQUEST;
		}

		/**
		 * Get a variable from the $_GET super global
		 * @param string $name    Variable name
		 * @param mixed  $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		public function get(string $name = '', $default = '') {
			return $name ? ($_GET[$name] ?? $default) : $_GET;
		}

		/**
		 * Get a variable from the $_POST super global
		 * @param string $name    Variable name
		 * @param mixed  $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		public function post(string $name = '', $default = '') {
			return $name ? ($_POST[$name] ?? $default) : $_POST;
		}

		/**
		 * Get a variable from the PUT stream
		 * @param string $name    Variable name
		 * @param mixed  $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		public function put(string $name = '', $default = '') {
			parse_str($this->readInput(), $put_vars);
			return $name ? ($put_vars[$name] ?? $default) : $put_vars;
		}

		/**
		 * Get a variable from the $_SESSION super global
		 * @param string $name    Variable name
		 * @param mixed  $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		public function session(string $name = '', $default = '') {
			return $name ? ($_SESSION[$name] ?? $default) : $_SESSION;
		}

		/**
		 * Get a file from the $_FILES super global
		 * @param string $name File key
		 * @return mixed        Array with file properties or Null
		 */
		public function files(string $name = '') {
			return $name ? ($_FILES[$name] ?? null) : $_FILES;
		}

		/**
		 * Get a variable from the $_SERVER super global
		 * @param string $name    Variable name
		 * @param string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		public function server(string $name, string $default = '') {
			return $_SERVER[$name] ?? $default;
		}

		/**
		 * Get a variable from the $_COOKIE super global
		 * @param string $name    Variable name
		 * @param string $default Default value to return if the variable is not set
		 * @return mixed           Variable value or $default
		 */
		public function cookie(string $name, string $default = '') {
			return $_COOKIE[$name] ?? $default;
		}

		/**
		 * Check the $_REQUEST super global, with or without a specific item
		 * @param string|null $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		public function hasParam(string $name = null): bool {
			return $name === null ? !!$_REQUEST : isset($_REQUEST[$name]);
		}

		/**
		 * Check the $_GET super global, with or without a specific item
		 * @param string|null $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		public function hasGet(string $name = null): bool {
			return $name === null ? !!$_GET : isset($_GET[$name]);
		}

		/**
		 * Check the $_POST super global, with or without a specific item
		 * @param string|null $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		public function hasPost(string $name = null): bool {
			return $name === null ? !!$_POST : isset($_POST[$name]);
		}

		/**
		 * Check the $_SESSION super global, with or without a specific item
		 * @param string|null $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		public function hasSession(string $name = null): bool {
			return $name === null ? !!$_SESSION : isset($_SESSION[$name]);
		}

		/**
		 * Check the $_FILES super global, with or without a specific item
		 * @param string|null $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		public function hasFiles(string $name = null): bool {
			return $name === null ? !!$_FILES : isset($_FILES[$name]);
		}

		/**
		 * Check the $_SERVER super global, with or without a specific item
		 * @param string|null $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		public function hasServer(string $name = null): bool {
			return $name === null ? !!$_SERVER : isset($_SERVER[$name]);
		}

		/**
		 * Check the $_COOKIE super global, with or without a specific item
		 * @param string|null $name Item name
		 * @return boolean       True if the item was found (or the array is not empty), False otherwise
		 */
		public function hasCookie(string $name = null): bool {
			return $name === null ? !!$_COOKIE : isset($_COOKIE[$name]);
		}

		/**
		 * Get authorization token from headers in different ways
		 * @return string       Bearer Token if found
		 */
		public function getAuthorizationHeader(): ?string {
			$headers = null;
			if(isset($_SERVER['Authorization'])) {
				$headers = trim($_SERVER['Authorization']);
			} else if(isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
				$headers = trim($_SERVER['HTTP_AUTHORIZATION']);
			} elseif(function_exists('apache_request_headers')) {
				$requestHeaders = apache_request_headers();
				// Server-side fix for bug in old Android versions (a nice side effect of this fix means we don't care about capitalization for Authorization)
				$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
				//print_r($requestHeaders);
				if(isset($requestHeaders['Authorization'])) {
					$headers = trim($requestHeaders['Authorization']);
				}
			}

			return $headers;
		}

		/**
		 * Sanitizes authorization header for use
		 * @return string       Clean bearer or null if not found
		 */
		public function getBearerToken(): ?string {
			$headers = $this->getAuthorizationHeader();
			// HEADER: Get the access token from the header
			if(!empty($headers)) {
				if(preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
					return $matches[1];
				}
			}
			return null;
		}
	}