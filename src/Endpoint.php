<?php

	/**
	 * Endpoint class
	 *
	 * A simple wrapper for endpoints.
	 * You must override the init() method.
	 */

	namespace CHAPI;

	use Exception;
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;

	/**
	 *
	 */
	abstract class Endpoint {
		/**
		 * @var string
		 */
		public string $message;
		/**
		 * @var mixed
		 */
		public $data;
		/**
		 * @var array
		 */
		public array $properties;
		/**
		 * @var int
		 */
		public int $status;
		/**
		 * @var string
		 */
		public string $result;
		/**
		 * @var Router
		 */
		public $router;
		/**
		 * @var Request
		 */
		public $request;
		/**
		 * @var
		 */
		public $response;
		/**
		 * @var mixed
		 */
		public $plural;
		/**
		 * @var string
		 */
		public string $singular;
		/**
		 * @var mixed
		 */
		public $endpoint;
		/**
		 * @var bool
		 */
		public bool $listJWT;
		/**
		 * @var bool
		 */
		public bool $createJWT;
		/**
		 * @var bool
		 */
		public bool $updateJWT;
		/**
		 * @var bool
		 */
		public bool $deleteJWT;
		/**
		 * @var bool
		 */
		public bool $singleJWT;

		/**
		 * Constructor
		 */
		function __construct() {
			global $app;

			$this->message = '';
			$this->data = [];
			$this->properties = [];
			$this->status = 200;
			$this->result = 'error';

			$this->router = $app->router;
			$this->request = $app->router->getRequest();
			$this->response = $app->router->getResponse();

			$called_class = get_called_class();

			if(!(isset($this->isModel) && $this->isModel === false)) {
				$this->plural = str_replace('Endpoint', '', $called_class);
				$this->singular = rtrim($this->plural, 's');
				$this->endpoint = $this->plural;

				// Setup Routes

				$this->setupRoutes();

				// JWT

				$this->listJWT = true;
				$this->createJWT = true;
				$this->updateJWT = true;
				$this->deleteJWT = true;
				$this->singleJWT = true;
			} else {
				$this->endpoint = strtolower($called_class);
			}

			$this->init();
		}

		/**
		 * @param $obj_array
		 * @return array
		 */
		function getIds($obj_array): array {

			$ids = [];
			foreach($obj_array as $obj) {
				if(isset($obj->id)) {
					$ids[] = $obj->id;
				} else if(isset($obj['id'])) {
					$ids[] = $obj['id'];
				}
			}

			return $ids;
		}

		/**
		 * Adds a condition to based on the key given
		 * @param $key
		 * @param string|bool $condition
		 * @return false|string
		 */
		static function addCondition($key, $condition = false) {
			global $app;
			$request = $app->getRequest();

			$val = $request->get($key);
			if($val) {
				$condition = $condition ?: "{$key} = '%s'";
				return $val != '' ? sprintf($condition, $val) : '';
			}

			return false;
		}

		/**
		 * Sets up the basic routes for a magic endpoint
		 * @return void
		 */
		function setupRoutes() {
			$called_class = get_called_class();
			$endpoint = strtolower(str_replace('-endpoint', '', camel_to_dash($called_class)));
			$router = $this->router;

			if($router->getRequest()->type == 'post' || $router->getRequest()->type == 'put') {
				$raw_input = $router->getRequest()->readInput();

				//Check for json
				$php_input = @json_decode($raw_input, true);

				if(!$php_input) {
					$php_input = $router->getRequest()->put();
				}

				if($php_input) {
					$_POST = $php_input;
				}
			}

			//Metas
			$router->get("/{$endpoint}/{id}/meta/{value}", [$this, 'meta']);
			$router->put("/{$endpoint}/{id}/meta/{value}", [$this, 'meta']);

			/*
			 * GET /plural
			 * Lists all elements of class
			 */
			$router->get("/{$endpoint}", [$this, 'list']);

			/*
			 * POST /plural/
			 * Creates a single element of class
			 */
			$router->post("/{$endpoint}", [$this, 'create']);

			/*
			 * PUT /plural/:id
			 * Updates a single element of class
			 */
			$router->put("/{$endpoint}/{id}", [$this, 'update']);

			/*
			 * DELETE /plural/:id
			 * Deletes a single or multiple elements of class
			 */
			$router->delete("/{$endpoint}/{id}", [$this, 'delete']);

			/*
			 * GET /plural/:id
			 * Lists a single element of class
			 */
			$router->get("/{$endpoint}/{id}", [$this, 'single']);
		}

		/**
		 * Checks if the bearer token is present and if its present checks for user existence
		 * @return void
		 */
		function requireJWT() {
			global $app;
			$token = $this->request->getBearerToken();

			$message = '';
			$ret = false;

			if($token) {
				try {
					$payload = JWT::decode($token, new Key($app->getGlobal('jwt_secret'), 'HS256'));

					if($payload->exp >= time()) {

						$user = class_exists('User') ? \Users::getById($payload->uid) : false;

						if($user) {
							if($user->status == 'Active') {
								$ret = $user->id;
							} else {
								$this->response->setStatus(401);
								$message = "User is {$user->status}";
							}
						} else {
							$this->response->setStatus(404);
							$message = 'User not found';
						}
					} else {
						$this->response->setStatus(401);
						$message = 'Token expired';
					}
				} catch(Exception $e) {
					$this->response->setStatus(401);
					$message = "Exception caught: " . $e->getMessage();
					error_log($e->getMessage());
				}
			} else {
				$this->response->setStatus(401);
				$message = "No bearer token present";
			}
			if(!$ret) {
				$this->response->ajaxRespond('error', null, $message);
				exit;
			}
			return $ret;
		}

		/**
		 * Adds a route to the global router
		 * @param $route
		 * @param $functionName
		 * @param string $method
		 * @return void
		 */
		function addRoute($route, $functionName, string $method = '*') {
			$this->router->prepend('/' . camel_to_dash($this->plural) . '/' . $route, $functionName, $method);
		}

		/**
		 * Respond the endpoint as ajax
		 * @return void
		 */
		function respond() {
			$this->response->ajaxRespond($this->result, $this->data, $this->message, $this->properties);
			exit;
		}

		/**
		 * Gets an item bases on id, function to be overridden by child classes
		 * @param $id
		 * @param array $args
		 * @return mixed
		 */
		function getItemById($id, array $args = []) {
			return $this->plural::getById($id, $args);
		}

		/**
		 * Gets all items in an array of ids
		 * @param $ids
		 * @param $args
		 * @return mixed
		 */
		function allItemInId($ids, $args) {
			return $this->plural::allInId($ids, $args);
		}

		/**
		 * @param $item
		 * @return mixed
		 * @throws Exception
		 */
		function upsert($item) {
			$metas = $this->request->post('metas');

			try {

				$fields_whitelist = $this->plural::getTableFields();

				foreach($_POST as $key => $value) {

					if(in_array($key, $fields_whitelist)) {
						$item->{$key} = $value;
					}
				}

				$save = $item->save();

				if($save) {

					// Metas
					if($metas) {
						foreach($metas as $name => $value) {
							$item->updateMeta($name, $value);
						}
					}

					return $item;
				}

			} catch(Exception $e) { throw new Exception('CHAPI Endpoint: Error at saving through upsert: ' . $e->getMessage()); }

			return false;
		}

		/**
		 * @param $conditions
		 * @return mixed
		 */
		function filterListConditions($conditions) {

			return $conditions;
		}

		/**
		 * Initialization callback, must be overridden in your extended classes
		 */
		abstract function init();

		/**
		 * Lists all elements from a class
		 */
		function list() {

			if($this->listJWT) $this->requireJWT();

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

			//Query Fields
			$query_fields = $this->request->get('query_fields');
			if($query_fields && is_string($query_fields)) $query_fields = explode(',', $query_fields);
			if($query_fields) $args['query_fields'] = $query_fields;

			//Fetch
			foreach($_GET as $key => $value) {
				if(preg_match('/fetch_(?<entity>.*)/', $key)) {
					$args['args'][$key] = $value;
				}
			}

			// Passing args via URL
			if($url_args = $this->request->get('args')) {

				$args = array_merge($args, $url_args);
			}

			// Specifically passing PDO Args
			if($pdo = $this->request->get('pdo')) {

				$args['args'] = array_merge($args['args'], $pdo);
			}

			//$args['debug'] = 1;

			// Conditions Filter
			$args['conditions'] = $this->filterListConditions(get_item($args, 'conditions', []));

			if(is_array($args['conditions'])) {
				$args['conditions'] = array_filter($args['conditions']);
			}

			$items = $this->plural::all($args);
			$this->data = $items;

			$count = $this->plural::count($args['conditions'] ?? []);
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

			$this->respond();
		}

		/**
		 * @return void
		 */
		function create() {
			$this->requireJWT();

			try {
				$item = $this->upsert(new $this->singular);

				if($item) {
					$this->data = $item;
					$this->result = 'success';
					$this->message = "{$this->singular} has been created successfully";
				} else {
					$this->status = 409;
					$this->message = "Error creating {$this->singular}";
				}
			} catch(Exception $e) {
				$this->result = 'error';
				$this->status = 409;
				$this->message = $e->getMessage();
			}

			$this->respond();
		}

		/**
		 * @param $id
		 * @return void
		 */
		function update($id) {

			$this->requireJWT();

			$args = $this->request->put('args', []);
			$item = $this->getItemById($id, $args);

			if($item) {

				try {

					$item = $this->upsert($item);

					if($item) {

						$this->data = $item;
						$this->result = 'success';
						$this->message = "{$this->singular} has been updated successfully";

					} else {

						$this->status = 409;
						$this->message = "Error updating {$this->singular}";
					}

				} catch (Exception $e) {

					$this->result = 'error';
					$this->status = 409;
					$this->message = $e->getMessage();
				}
			} else {

				$this->status = 409;
				$this->message = "Error updating {$this->singular}: item not found";
			}

			$this->respond();
		}

		/**
		 * @param $id
		 * @return void
		 */
		function delete($id) {

			$this->requireJWT();
			$multiple = !!preg_match('/(.*),(.*)/', $id);

			$ids = $multiple ? explode(',', $id) : [];

			$errors = [];

			if(!$multiple) {

				$item = $this->getItemById($id);

				if($item) {

					try {

						$deleted = $item->delete();

						if($deleted) {

							$this->result = 'success';
							$this->message = "{$this->singular} has been deleted successfully";

						} else {

							$this->result = 'error';
							$this->status = 409;
							$this->message = 'Error deleting {$this->singular}';
						}

					} catch(Exception $e) {

						$this->result = 'error';
						$this->status = 409;
						$this->message = $e->getMessage();
					}

				} else {

					$this->status = 404;
					$this->message = "Error deleting {$this->singular}: item not found.";
				}

			} else {

				$success = [];

				foreach($ids as $id) {

					$item = $this->getItemById($id);

					if($item) {

						try {

							$deleted = $item->delete();

							if($deleted) {

								$success[] = $id;

							} else {

								$errors[] = $id;
							}

						} catch(Exception $e) {

							$errors[] = $id;
						}

					} else {

						$errors[] = $id;
					}
				}

				if(count($success)) {

					$this->message = (count($success) == 1 ? $this->singular : $this->plural) . ' with id' . (count($success) == 1 ? '' : 's') . ' ' . implode(', ', $success) . " successfully deleted";

					if(count($errors)) {

						$this->message .= '. ' . (count($success) == 1 ? $this->singular : $this->plural) . ' with id' . (count($errors) == 1 ? '' : 's') . ' ' . implode(', ', $errors) . " not deleted because error.";
					}

				} else {

					$this->message = $this->plural . ' with id' . (count($errors) == 1 ? '' : 's') . ' ' . implode(', ', $errors) . " not deleted because error.";
					$this->status = 409;
				}
			}

			$this->respond();
		}

		/**
		 * @param $id
		 * @return void
		 */
		function single($id) {

			$this->requireJWT();
			$multiple = !!preg_match('/(.*),(.*)/', $id);

			$args = ['args' => []];

			//Defines if we want to fetch metas or not
			$fetch_metas = $this->request->get('fetch_metas');

			if($fetch_metas) {

				if($fetch_metas != 1) $fetch_metas = explode(',', $fetch_metas);
				$args['args']['fetch_metas'] = $fetch_metas;
			}

			// Passing args via URL
			if($url_args = $this->request->get('args')) {

				$args = array_merge($args, $url_args);
			}

			// Specifically passing PDO Args
			if($pdo = $this->request->get('pdo')) {

				$args['args'] = array_merge($args['args'], $pdo);
			}

			$item = $multiple ? $this->allItemInId(explode(',', $id), $args) : $this->getItemById($id, $args);

			if($item) {

				$this->data = $item;
				$this->result = 'success';

			} else {

				$this->status = 404;
				$this->message = "Error getting {$this->singular}: item not found.";
			}

			$this->respond();
		}

		/**
		 * @param $id
		 * @param $meta_name
		 * @return void
		 */
		function meta($id, $meta_name) {

			$this->requireJWT();
			$item = $this->getItemById($id);

			if($item) {

				if($this->request->type == 'get') {

					$this->data = $item->getMeta($meta_name);
					$this->result = 'success';
				}

				if($this->request->type == 'put') {

					$value = $this->request->post('value');

					$item->updateMeta($meta_name, $value);
					$this->data = $item->getMeta($meta_name);
					$this->result = 'success';
				}

			} else {

				$this->status = 409;
				$this->message = "Error updating {$this->singular}: item not found";
			}

			$this->respond();
		}
	}