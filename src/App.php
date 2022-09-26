<?php
	/**
	 * app.inc.php
	 * This class is the core of CHAPI
	 *
	 * Version:    1.0
	 * Author(s):  Rodrigo Tejero <github.com/webchimp>
	 */

	namespace CHAPI;

	use Dabbie\Dabbie;

	use Dabbie\DabbieException;
	use InvalidArgumentException;
	use NORM\NORM;
	use NORM\CROOD;

	include('utilities.inc.php');

	/**
	 *
	 */
	class App {

		/**
		 * @var App
		 */
		private static App $instance;

		/**
		 * @var array
		 */
		protected array $globals;
		/**
		 * @var array
		 */
		protected array $profile;
		/**
		 * @var
		 */
		protected $request;
		/**
		 * @var
		 */
		protected $response;

		/**
		 * @var string
		 */
		protected string $base_dir;
		/**
		 * @var string
		 */
		protected string $config_dir;

		/**
		 * @var Dabbie
		 */
		protected Dabbie $db;

		/**
		 * @var string
		 */
		protected string $app_title;
		/**
		 * @var string
		 */
		protected string $pass_salt;
		/**
		 * @var string
		 */
		protected string $token_salt;

		/**
		 * @var Router
		 */
		public Router $router;

		/**
		 * @return App
		 */
		public static function getInstance(): App {

			static::$instance = static::$instance ?? new static();
			return static::$instance;
		}

		/**
		 * Private constructor to prevent creating a new instance of the *App* via the `new` operator from outside this class.
		 *
		 * @return void
		 */
		protected function __construct() {}

		/**
		 * @throws DabbieException
		 */
		function init() {

			if(!$this->base_dir) {
				throw new InvalidArgumentException('CHAPI Error: base directory not defined');
			}

			// Getting app profile
			(new DotEnv($this->base_dir . '/.env'))->load();
			define('PROFILE', getenv('PROFILE') ?? 'development');

			$config_dir = $this->config_dir ?? $this->base_dir . '/config';

			if(!file_exists($config_dir . '/config.shared.ini')) {
				throw new InvalidArgumentException('CHAPI Error: Shared Config file missing');
			}

			if(!file_exists($config_dir . '/config.' . PROFILE . '.ini')) {
				throw new InvalidArgumentException('CHAPI Error: ' . PROFILE . ' Config file missing');
			}

			$settings['shared'] = parse_ini_file($config_dir . '/config.shared.ini', true, INI_SCANNER_TYPED);
			$this->globals = $settings['shared'];

			$settings[PROFILE] = @parse_ini_file($config_dir . '/config.' . PROFILE . '.ini', true, INI_SCANNER_TYPED);
			$this->profile = $settings[PROFILE];

			$this->request = new Request();
			$this->response = new Response();

			if(!$this->profile['app_url']) {
				throw new InvalidArgumentException('CHAPI Error: PROFILE app_url not defined');
			}

			$this->router = new Router($this->profile['app_url']);

			$router = $this->router;
			$this->router->set404(function() use ($router) {
				$router->getResponse()->ajaxRespond('error', [], 'Error: Route not found');
			});

			$router->all('/', function() use ($router) {
				$router->getResponse()->ajaxRespond('success', [], 'App running, okay dokey 🐵🐵🐵');
			});

			//$this->router->all('.*', 'CHAPI\App::routeRequest');

			$this->db = new Dabbie($this->profile['database']);

			$this->pass_salt = $this->globals['pass_salt'];
			$this->token_salt = $this->globals['token_salt'];
			$this->app_title = $this->globals['app_name'];

			NORM::setDBHandler($this->db);
			CROOD::setDBHandler($this->db);

			//Including extra files

			if(file_exists($this->baseDir() . '/app/traits.inc.php')) {

				include_once($this->baseDir() . '/app/traits.inc.php');
			}

			//Including all the models and endpoints registered

			foreach(glob($this->baseDir() . '/app/{model,endpoint}/*.php', GLOB_BRACE) as $filename) {
				include_once $filename;
			}

			foreach(glob($this->baseDir() . '/app/{model,endpoint}/*.php', GLOB_BRACE) as $filename) {
				//Check each endpoint
				if(strpos(basename($filename), '.endpoint') !== false) {

					//getting the endpoint route
					$endpoint = explode('.', basename($filename));
					$endpoint = get_item($endpoint, 0);

					if($endpoint) {

						$endpoint_class = ucfirst(dash_to_camel($endpoint)) . 'Endpoint';

						if(class_exists($endpoint_class)) {

							$endpoint_instance = new $endpoint_class();
						}
					}
				}
			}
		}

		/**
		 * @return Dabbie
		 */
		function db(): Dabbie {
			return $this->db;
		}

		/**
		 * @return mixed
		 */
		function getRequest() {
			return $this->request;
		}

		/**
		 * @return mixed
		 */
		function getResponse() {
			return $this->response;
		}

		/**
		 * @return Router
		 */
		function getRouter(): Router {
			return $this->router;
		}

		/**
		 * @param $base_dir
		 * @return void
		 */
		function setBaseDir($base_dir) {
			$this->base_dir = $base_dir;
		}

		/**
		 * Get base folder
		 * @param string  $path Path to append
		 * @param boolean $echo Whether to print the resulting string or not
		 * @return string       The well-formed path
		 */
		function baseDir(string $path = '', bool $echo = false): string {
			$ret = sprintf('%s%s', $this->base_dir, $path);
			if($echo) {
				echo $ret;
			}
			return $ret;
		}

		/**
		 * Log something to file
		 * @param mixed  $data     What to log
		 * @param string $log_file Log name, without extension
		 * @return void
		 */
		public static function log_to_file($data, string $log_file = '') {
			$app = App::getInstance();

			if (!file_exists($app->baseDir('/log'))) {
				mkdir($app->baseDir('/log'), 0777, true);
			}

			$log_file = $log_file ?: date('Y-m-d');
			$file = fopen( $app->baseDir("/log/{$log_file}.log"), 'a');
			$date = date('Y-m-d H:i:s');
			if(is_array($data) || is_object($data)) $data = json_encode($data);
			fwrite($file, "{$date} - {$data}\n");
			fclose($file);
		}

		/**
		 * Sanitize the given string (slugify it)
		 * @param string $str       The string to sanitize
		 * @param array  $replace   Optional, an array of characters to replace
		 * @param string $delimiter Optional, specify a custom delimiter
		 * @return string            Sanitized string
		 */
		function toAscii(string $str, array $replace = [], string $delimiter = '-'): string {
			setlocale(LC_ALL, 'en_US.UTF8');
			# Remove spaces
			if(!empty($replace)) {
				$str = str_replace($replace, ' ', $str);
			}
			# Remove non-ascii characters
			$clean = iconv('UTF-8', 'ASCII//TRANSIT', $str);
			# Remove non-alphanumeric characters and lowercase the result
			$clean = preg_replace("/[^a-zA-Z\d\/_|+ -]/", '', $clean);
			$clean = strtolower(trim($clean, '-'));
			# Remove other unwanted characters
			return preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
		}

		/**
		 * @param string $str
		 * @param array  $replace
		 * @param string $delimiter
		 * @return string
		 */
		function slugify(string $str, array $replace = [], string $delimiter = '-'): string {
			return $this->toAscii($str, $replace, $delimiter);
		}

		/**
		 * Hash the specified token
		 * @param mixed   $action Action name(s), maybe a single string or an array of strings
		 * @param boolean $echo   Whether to output the resulting string or not
		 * @return string         The hashed token
		 */
		function hashToken($action, bool $echo = false): string {
			if(is_array($action)) {
				$action_str = implode('', $action);
				$ret = md5($this->token_salt . $action_str);
			} else {
				$ret = md5($this->token_salt . $action);
			}
			if($echo) echo $ret;
			return $ret;
		}

		/**
		 * Hash the specified password
		 * @param string  $password Plain-text password
		 * @param boolean $echo     Whether to output the resulting string or not
		 * @return string           The hashed password
		 */
		function hashPassword(string $password, bool $echo = false): string {
			$ret = md5($this->pass_salt . $password);
			if($echo) echo $ret;
			return $ret;
		}

		/**
		 * Validate the given token with the specified action
		 * @param string $token  Hashed token
		 * @param string $action Action name
		 * @return boolean       True if the token is valid, False otherwise
		 */
		function validateToken(string $token, string $action): bool {
			$check = $this->hashToken($action);
			return ($token == $check);
		}

		/**
		 * Register a hook listener
		 * @param string  $hook         Hook name
		 * @param string  $functionName Callback function name
		 * @param boolean $prepend      Whether to add the listener at the beginning or the end
		 */
		function registerHook(string $hook, string $functionName, bool $prepend = false) {

			if(!isset($this->hooks)) $this->hooks = [];
			if(!isset($this->hooks[$hook])) $this->hooks[$hook] = [];
			if($prepend) array_unshift($this->hooks[$hook], $functionName); else {
				$this->hooks[$hook][] = $functionName;
			}
		}

		/**
		 * Execute a hook (run each listener incrementally)
		 * @param string $hook   Hook name
		 * @param mixed  $params Parameters to pass to each callback function
		 * @return array|false   The processed data or the same data if no callbacks were found
		 */
		function executeHook(string $hook, $params = '') {
			if(isset($this->hooks[$hook])) {
				$hooks = $this->hooks[$hook];
				$ret = [];
				foreach($hooks as $hook) {
					$ret[$hook] = call_user_func($hook, $params);
				}
				return $ret;
			}
			return false;
		}

		/**
		 * Get the specified option from the current profile
		 * @param string $key     Option name
		 * @param string $default Default value
		 * @return mixed           The option value (array, string, integer, boolean, etc)
		 */
		function getOption(string $key, string $default = '') {
			$ret = $default;
			if(isset( $this->profile[$key] )) {
				$ret = $this->profile[$key];
			}
			return $ret;
		}

		/**
		 * Get the specified option from the global profile
		 * @param string $key     Option name
		 * @param string $default Default value
		 * @return mixed           The option value (array, string, integer, boolean, etc)
		 */
		function getGlobal(string $key, string $default = '') {
			$ret = $default;
			if(isset($this->globals[$key])) {
				$ret = $this->globals[$key];
			}
			return $ret;
		}

		/**
		 * Get the app name
		 * @param boolean $echo Print the result?
		 * @return string        App name
		 */
		function getAppTitle(bool $echo = false): string {
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
		 * Private serialize method to prevent serializing of the *App* instance.
		 *
		 * @return void
		 */
		private function __wakeup() {}
	}