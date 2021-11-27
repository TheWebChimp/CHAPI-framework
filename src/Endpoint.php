<?php

	/**
	 * Endpoint class
	 *
	 * A simple wrapper for endpoints.
	 * You must override the init() method.
	 */

	namespace CHAPI;

	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;

	abstract class Endpoint {

		/**
		 * Constructor
		 */
		function __construct() {

			global $app;

			$this->message = '';
			$this->data = [];
			$this->properties = [];
			$this->status = 404;
			$this->result = 'error';

			$this->router = $app->router;
			$this->request = $app->router->getRequest();
			$this->response = $app->router->getResponse();

			$called_class =  get_called_class();

			$this->plural = str_replace('Endpoint', '', $called_class);
			$this->singular = rtrim($this->plural, 's');

			$this->setupRoutes();

			$this->init();
		}

		function setupRoutes() {

			$called_class =  get_called_class();
			$endpoint = strtolower(str_replace('Endpoint', '', $called_class));
			$router = $this->router;
			$endpoint_instance = $this;

			$router->all("/{$endpoint}[/{route}]", function($route = null) use ($router, $endpoint_instance) {

				$parts = [];
				if($route) $parts = explode('/', $route);

				/*
				 * GET /plural
				 * List all elements of class
				 */

				if($router->getRequest()->type == 'get' && !count($parts)) {

					call_user_func([$endpoint_instance, 'list']);
				}

				/*
				 * GET /plural/:id
				 * List a single element of class
				 */

				$method = !!count($parts) ? $parts[0] . 'Action' : '';

				if($router->getRequest()->type == 'get' && count($parts) == 1 && !method_exists($endpoint_instance, $method)) {

					call_user_func([$endpoint_instance, 'single'], $parts[0]);
				}

				/*else {


					if(method_exists($endpoint_instance, $method)) {

						array_shift($parts);
						call_user_func_array([$endpoint_instance, $method], [ $parts ]);
					}
				}*/

				$router->getResponse()->setStatus($endpoint_instance->status);
				$router->getResponse()->ajaxRespond($endpoint_instance->result, $endpoint_instance->data, $endpoint_instance->message, $endpoint_instance->properties);
			});
		}

		function requireJWT() {

			global $app;
			$token = $this->request->getBearerToken();

			$message = '';
			$ret = false;

			if($token) {
				try {

					$payload = JWT::decode($token, new Key($app->getGlobal('jwt_secret'), 'HS256'));

					if($payload->exp >= time()) {
						$user = \Users::getById($payload->uid);
						if($user) {
							if($user->status == 'Active') {
								$ret = $user->id;
							} else {
								$message = "User is {$user->status}";
							}
						} else {
							$message = 'User not found';
						}
					} else {
						$message = 'Token expired';
					}

				} catch (\Exception $e) {

					$message = "Exception caught: " . $e->getMessage();
					error_log($e->getMessage());
				}
			} else {
				$message = "No bearer token present";
			}
			if(!$ret) {
				$this->response->setStatus(403);
				$this->response->ajaxRespond('error', null, $message);
				exit;
			}
			return $ret;
		}

		function addRoute($route, $functName, $method = '*') {
			$this->router->prepend('/' . strtolower($this->plural) . '/' . $route, $functName, $method);
		}

		function respond() {
			$this->response->ajaxRespond($this->result, $this->data, $this->message, $this->properties);
			exit;
		}

		function getItemById($id) {

			$item = $this->plural::getById($id);
			return $item;
		}

		/**
		 * Initialization callback, must be overriden in your extended classes
		 */
		abstract function init();

		/**
		 * Lists all elements from a class
		 */
		function list() {

			//Items to show, we can define the number of items via the show query param
			$show = $this->request->get('show', 100);
			$show = $show ?: 100;

			//Pages to show, we can define the page number via the page query param
			$page = $this->request->get('page', 1);
			$page = $page ?: 1;

			//Sorting defined via the sort query param
			$sort = $this->request->get('sort', 'asc');
			$sort = $sort ?: 'asc';

			//Sort by parameter defined via the by query param
			$by = $this->request->get('by', 'id');
			$by = $by ?: 'id';

			$args = [ 'show' => $show, 'page' => $page, 'sort' => $sort, 'by' => $by, 'args' => [] ];

			//Defines if we want to fetch metas or not
			$fetch_metas = $this->request->get('fetch_metas');
			if($fetch_metas) {

				if($fetch_metas != 1) $fetch_metas = explode(',', $fetch_metas);
				$args['args']['fetch_metas'] = $fetch_metas;
			}

			//Automagically add conditions based on get params
			foreach($_GET as $key => $value) {

				if(in_array($key, $this->plural::getTableFields())) {
					$args['conditions'][] = "`{$key}` = '{$value}'";
				}
			}

			$items = $this->plural::all($args);

			$this->data = $items;

			$count = $this->plural::count($args['conditions']);
			$pages = ceil($count / $show);

			$this->properties['current_page'] = (int) $page;
			$this->properties['per_page'] = (int) $show;
			$this->properties['last_page'] = (int) $pages;
			$this->properties['count'] = (int) $count;
			$this->properties['sort'] = $sort;
			$this->properties['by'] = $by;
			$this->properties['total'] = (int) $pages;

			if(count($items)) {

				$this->result = 'success';
			}
		}

		function single($id) {

			$args = ['args' => []];

			//Defines if we want to fetch metas or not
			$fetch_metas = $this->request->get('fetch_metas');
			if($fetch_metas) {

				if($fetch_metas != 1) $fetch_metas = explode(',', $fetch_metas);
				$args['args']['fetch_metas'] = $fetch_metas;
			}

			$item = $this->getItemById($id, $args);

			if($item) {

				$this->data = $item;
				$this->result = 'success';
			}
		}
	}
?>