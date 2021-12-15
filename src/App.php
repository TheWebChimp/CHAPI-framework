<?php
	/**
	 * app.inc.php
	 * This class is the core of CHAPI
	 *
	 * Version: 	1.0
	 * Author(s):	Rodrigo Tejero <github.com/webchimp>
	 */

	namespace CHAPI;

	use CHAPI\Request;
	use CHAPI\Response;
	use CHAPI\Router;
	use CHAPI\DotEnv;

	use Dabbie\Dabbie;

	use NORM\NORM;
	use NORM\CROOD;

	include('utilities.inc.php');

	class App {

		private static $instance;

		protected $globals;
		protected $profile;
		protected $request;
		protected $response;

		protected $base_dir;
		protected $config_dir;

		protected $db;

		protected $app_title;
		protected $pass_salt;
		protected $token_salt;

		public static function getInstance() {

			static::$instance = static::$instance ?? new static();
			return static::$instance;
		}

		/**
		 * Private constructor to prevent creating a new instance of the *App* via the `new` operator from outside of this class.
		 *
		 * @return void
		 */
		protected function __construct() {}

		function init() {

			if(!$this->base_dir) {
				throw new \InvalidArgumentException(sprintf('CHAPI Error: base directory not defined'));
			}

			// Getting app profile
			(new DotEnv($this->base_dir . '/.env'))->load();
			define('PROFILE', getenv('PROFILE') ?? 'development');

			$config_dir = $this->config_dir ?? $this->base_dir . '/config';

			if(!file_exists($config_dir . '/config.shared.ini')) {
				throw new \InvalidArgumentException(sprintf('CHAPI Error: Shared Config file missing'));
			}

			if(!file_exists($config_dir . '/config.' . PROFILE . '.ini')) {
				throw new \InvalidArgumentException(sprintf('CHAPI Error: ' . PROFILE . ' Config file missing'));
			}

			$settings['shared'] = parse_ini_file($config_dir . '/config.shared.ini', true, INI_SCANNER_TYPED);
			$this->globals = $settings['shared'];

			$settings[PROFILE] =  @parse_ini_file($config_dir . '/config.' . PROFILE . '.ini', true, INI_SCANNER_TYPED);
			$this->profile = $settings[PROFILE];

			$this->request = new Request();
			$this->response = new Response();

			if(!$this->profile['app_url']) {
				throw new \InvalidArgumentException(sprintf('CHAPI Error: PROFILE app_url not defined'));
			}

			$this->router = new Router($this->profile['app_url']);

			$router = $this->router;
			$this->router->set404(function() use ($router) {
				$router->getResponse()->ajaxRespond('error', [], 'Error: Route not found');
			});

			$router->all('/', function() use ($router) {
				$router->getResponse()->ajaxRespond('success', [], 'App running, okey dokey 🐵🐵🐵');
			});

			//$this->router->all('.*', 'CHAPI\App::routeRequest');

			$this->db = new Dabbie($this->profile['database']);

			$this->pass_salt = $this->globals['pass_salt'];
			$this->token_salt = $this->globals['token_salt'];
			$this->app_title = $this->globals['app_name'];

			NORM::setDBHandler($this->db);
			CROOD::setDBHandler($this->db);

			//Including all the models and endpoints registered

			foreach (glob($this->baseDir() . '/app/{model,endpoint}/*.php', GLOB_BRACE) as $filename) {
				include $filename;

				//Check each endpoint
				if(strpos(basename($filename), '.endpoint') !== false) {

					//getting the endpoint route
					$endpoint = explode('.', basename($filename));
					$endpoint = get_item($endpoint, 0);

					if($endpoint) {

						$endpoint_class = ucfirst($endpoint) . 'Endpoint';

						if(class_exists($endpoint_class)) {

							$endpoint_instance = new $endpoint_class();
						}
					}
				}
			}
		}

		function getRequest() {
			return $this->request;
		}

		function getResponse() {
			return $this->response;
		}

		function getRouter() {
			return $this->router;
		}

		function setBaseDir($base_dir) {
			$this->base_dir = $base_dir;
		}

		/**
		 * Get base folder
		 * @param  string  $path Path to append
		 * @param  boolean $echo Whether to print the resulting string or not
		 * @return string        The well-formed path
		 */
		function baseDir($path = '', $echo = false) {
			$ret = sprintf('%s%s', $this->base_dir, $path);
			if($echo) {
				echo $ret;
			}
			return $ret;
		}

		/**
		 * Log something to file
		 * @param  mixed  $data     What to log
		 * @param  string $log_file Log name, without extension
		 * @return nothing
		 */
		public static function log_to_file($data, $log_file = '') {
			$app = App::getInstance();

			if (!file_exists($app->baseDir('/log'))) {
				mkdir($app->baseDir('/log'), 0777, true);
			}

			$log_file = $log_file ? $log_file : date('Y-m-d');
			$file = fopen( $app->baseDir("/log/{$log_file}.log"), 'a');
			$date = date('Y-m-d H:i:s');
			if(is_array($data) || is_object($data)) {
				$data = json_encode($data);
			}
			fwrite($file, "{$date} - {$data}\n");
			fclose($file);
		}

		/**
		 * Sanitize the given string (slugify it)
		 * @param  string $str       The string to sanitize
		 * @param  array  $replace   Optional, an array of characters to replace
		 * @param  string $delimiter Optional, specify a custom delimiter
		 * @return string            Sanitized string
		 */
		function toAscii($str, $replace = [], $delimiter = '-') {
			setlocale(LC_ALL, 'en_US.UTF8');
			# Remove spaces
			if( !empty($replace) ) {
				$str = str_replace((array)$replace, ' ', $str);
			}
			# Remove non-ascii characters
			$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
			# Remove non alphanumeric characters and lowercase the result
			$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
			$clean = strtolower(trim($clean, '-'));
			# Remove other unwanted characters
			$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
			return $clean;
		}

		function slugify($str, $replace = [], $delimiter = '-') {
			return $this->toAscii($str, $replace, $delimiter);
		}

		/**
		 * Hash the specified token
		 * @param  mixed  $action  Action name(s), maybe a single string or an array of strings
		 * @param  boolean $echo   Whether to output the resulting string or not
		 * @return string          The hashed token
		 */
		function hashToken($action, $echo = false) {
			if(is_array($action)) {
				$action_str = '';
				foreach ($action as $item) {
					$action_str .= $item;
				}
				$ret = md5($this->token_salt.$action_str);
			} else {
				$ret = md5($this->token_salt.$action);
			}
			if($echo) echo $ret;
			return $ret;
		}

		/**
		 * Hash the specified password
		 * @param  string  $password 	Plain-text password
		 * @param  boolean $echo   		Whether to output the resulting string or not
		 * @return string          		The hashed password
		 */
		function hashPassword($password, $echo = false) {
			$ret = md5($this->pass_salt.$password);
			if($echo) echo $ret;
			return $ret;
		}

		/**
		 * Validate the given token with the specified action
		 * @param  string $token  Hashed token
		 * @param  string $action Action name
		 * @return boolean        True if the token is valid, False otherwise
		 */
		function validateToken($token, $action) {
			$check = $this->hashToken($action);
			return ($token == $check);
		}

		/**
		 * Register a hook listener
		 * @param  string  $hook      Hook name
		 * @param  string  $functName Callback function name
		 * @param  boolean $prepend   Whether to add the listener at the beginning or the end
		 */
		function registerHook($hook, $functName, $prepend = false) {
			if(!isset( $this->hooks[$hook] )) {
				$this->hooks[$hook] = [];
			}
			if($prepend) {
				array_unshift($this->hooks[$hook], $functName);
			} else {
				array_push($this->hooks[$hook], $functName);
			}
		}

		/**
		 * Execute a hook (run each listener incrementally)
		 * @param  string $hook   	Hook name
		 * @param  mixed  $params 	Parameters to pass to each callback function
		 * @return mixed          	The processed data or the same data if no callbacks were found
		 */
		function executeHook($hook, $params = '') {
			if(isset( $this->hooks[$hook] )) {
				$hooks = $this->hooks[$hook];
				$ret = [];
				foreach ($hooks as $hook) {
					$ret[$hook] = call_user_func($hook, $params);
				}
				return $ret;
			}
			return false;
		}

		/**
		 * Get the specified option from the current profile
		 * @param  string $key     Option name
		 * @param  string $default Default value
		 * @return mixed           The option value (array, string, integer, boolean, etc)
		 */
		function getOption($key, $default = '') {
			$ret = $default;
			if(isset( $this->profile[$key] )) {
				$ret = $this->profile[$key];
			}
			return $ret;
		}

		/**
		 * Get the specified option from the global profile
		 * @param  string $key     Option name
		 * @param  string $default Default value
		 * @return mixed           The option value (array, string, integer, boolean, etc)
		 */
		function getGlobal($key, $default = '') {
			$ret = $default;
			if(isset( $this->globals[$key] )) {
				$ret = $this->globals[$key];
			}
			return $ret;
		}

		/**
		 * Get the app name
		 * @param  boolean $echo Print the result?
		 * @return string        App name
		 */
		function getAppTitle($echo = false) {
			$ret = $this->app_title;
			if($echo) echo $ret;

			return $ret;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the *App* instance.
		 *
		 * @return void
		 */
		private function __clone() {}

		/**
		 * Private unserialize method to prevent unserializing of the *App* instance.
		 *
		 * @return void
		 */
		private function __wakeup() {}
	}
?>