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

	include('utilities.inc.php');

	class App {

		private static $instance;

		protected $profile;
		protected $request;
		protected $response;
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

			if(!BASE_DIR) {
				throw new \InvalidArgumentException(sprintf('CHAPI Error: BASE_DIR constant not defined'));
			}

			// Getting app profile
			(new DotEnv(BASE_DIR . '/.env'))->load();
			define('PROFILE', getenv('PROFILE') ?? 'development');

			if(!file_exists(BASE_DIR . '/config.shared.ini')) {
				throw new \InvalidArgumentException(sprintf('CHAPI Error: Shared Config file missing'));
			}

			if(!file_exists(BASE_DIR . '/config.' . PROFILE . '.ini')) {
				throw new \InvalidArgumentException(sprintf('CHAPI Error: ' . PROFILE . ' Config file missing'));
			}

			$settings['shared'] = parse_ini_file(BASE_DIR . '/config.shared.ini', true, INI_SCANNER_TYPED);
			$this->globals = $settings['shared'];

			$settings[PROFILE] =  @parse_ini_file(BASE_DIR . '/config.' . PROFILE . '.ini', true, INI_SCANNER_TYPED);
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


			//$this->router->all('.*', 'CHAPI\App::routeRequest');

			$this->db = new Dabbie($this->profile['database']);

			$this->pass_salt = $this->globals['pass_salt'];
			$this->token_salt = $this->globals['token_salt'];
			$this->app_title = $this->globals['app_name'];
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

		/**
		 * Log something to file
		 * @param  mixed  $data     What to log
		 * @param  string $log_file Log name, without extension
		 * @return nothing
		 */
		public static function log_to_file($data, $log_file = '') {
			global $app;
			$log_file = $log_file ? $log_file : date('Y-m');
			$file = fopen( $app->baseDir("/log/{$log_file}.log"), 'a');
			$date = date('Y-m-d H:i:s');
			if ( is_array($data) || is_object($data) ) {
				$data = json_encode($data);
			}
			fwrite($file, "{$date} - {$data}\n");
			fclose($file);
		}

		static function routeRequest() {

			echo "WAX";
		}
	}
?>