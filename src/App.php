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

	class App {

		private static $instance;

		protected $profile;
		protected $request;
		protected $response;
		protected $db;

		public static function getInstance() {

			static::$instance = static::$instance ?? new static();
			return static::$instance;
		}

		/**
		 * Private constructor to prevent creating a new instance of the *App* via the `new` operator from outside of this class.
		 *
		 * @return void
		 */
		protected function __construct() {
		}

		function init($settings) {

			$this->profile = $settings[PROFILE];
			$this->request = new Request();
			$this->response = new Response();

			$this->router = new Router();
			$this->router->add('*', 'App::routeRequest');

			$this->database = new Dabbie($this->profile['database']);
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
	}
?>