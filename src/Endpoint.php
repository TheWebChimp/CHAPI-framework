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
			$this->status = 200;
			$this->result = 'error';

			$this->router = $app->router;
			$this->request = $app->router->getRequest();
			$this->response = $app->router->getResponse();

			$called_class =  get_called_class();

			$this->plural = str_replace('Endpoint', '', $called_class);
			$this->singular = rtrim($this->plural, 's');

			$this->setupRoutes();

			//JWT's

			$this->listJWT = true;
			$this->createJWT = true;
			$this->updateJWT = true;
			$this->deleteJWT = true;
			$this->singleJWT = true;

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
				$method = !!count($parts) ? $parts[0] . 'Action' : '';

				if($router->getRequest()->type == 'post' || $router->getRequest()->type == 'put') {
					$raw_input = $router->getRequest()->readInput();

					//Check for json
					$php_input = @json_decode($raw_input, true);

					if(!$php_input) {
						$php_input = $router->getRequest()->put();
					}

					if($php_input) { $_POST = $php_input; }
				}

				/*
				 * GET /plural
				 * Lists all elements of class
				 */

				if($router->getRequest()->type == 'get' && !count($parts)) {

					call_user_func([$endpoint_instance, 'list']);

				/*
				 * POST /plural/
				 * Creates a single element of class
				 */

				} else if($router->getRequest()->type == 'post' && !count($parts)) {

					call_user_func([$endpoint_instance, 'create']);

				/*
				 * PUT /plural/:id
				 * Updates a single element of class
				 */

				} else if($router->getRequest()->type == 'put' && count($parts) == 1 && !method_exists($endpoint_instance, $method)) {

					call_user_func([$endpoint_instance, 'update'], $parts[0]);

				/*
				 * DELETE /plural/:id
				 * Deletes a single or multiple elements of class
				 */

				} else if($router->getRequest()->type == 'delete' && count($parts) == 1 && !method_exists($endpoint_instance, $method)) {

					call_user_func([$endpoint_instance, 'delete'], $parts[0]);

				/*
				 * GET /plural/:id
				 * Lists a single element of class
				 */

				} else if($router->getRequest()->type == 'get' && count($parts) == 1 && !method_exists($endpoint_instance, $method)) {

					call_user_func([$endpoint_instance, 'single'], $parts[0]);

				} else if(method_exists($endpoint_instance, $method)) {

					array_shift($parts);
					call_user_func_array([$endpoint_instance, $method], [ $parts ]);

				} else {

					$endpoint_instance->status = 404;
				}

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

				} catch (\Exception $e) {

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

		function addRoute($route, $functName, $method = '*') {
			$this->router->prepend('/' . strtolower($this->plural) . '/' . $route, $functName, $method);
		}

		function respond() {
			$this->response->ajaxRespond($this->result, $this->data, $this->message, $this->properties);
			exit;
		}

		function getItemById($id, $args = []) {

			$item = $this->plural::getById($id, $args);
			return $item;
		}

		function allItemInId($id, $args) {

			$items = $this->plural::allInId($id, $args);
			return $items;
		}

		function upsert($item) {
			$metas = $this->request->post('metas');

			try {

				$fields_whitelist = $this->plural::getTableFields();

				$_POST = $this->request->put() ?? $this->request->post();

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
							$this->updateMeta($item, $name, $metas);
						}
					}

					return $item;
				}

			} catch(\Exception $e) { throw new \Exception('CHAPI Endpoint: Error at saving through upsert: ' . $e->getMessage()); }
		}

		/**
		 * Initialization callback, must be overriden in your extended classes
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
		}

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

			} catch (\Exception $e) {

				$this->result = 'error';
				$this->status = 409;
				$this->message = $e->getMessage();
			}
		}

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

				} catch (\Exception $e) {

					$this->result = 'error';
					$this->status = 409;
					$this->message = $e->getMessage();
				}
			} else {

				$this->status = 409;
				$this->message = "Error updating {$this->singular}: item not found";
			}
		}

		function delete($id) {

			$this->requireJWT();
			$multiple = !!preg_match('/(.*),(.*)/', $id);

			$ids = $multiple ? explode(',', $id) : [];

			$ids_deleted = [];
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

					} catch(\Exception $e) {

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
				$errors = [];

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

						} catch(\Exception $e) {

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

					$this->message = (count($success) == 1 ? $this->singular : $this->plural) . ' with id' . (count($errors) == 1 ? '' : 's') . ' ' . implode(', ', $errors) . " not deleted because error.";
					$this->status = 409;
				}
			}
		}

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

			$item = $multiple ? $this->allItemInId(explode(',', $id), $args) : $this->getItemById($id, $args);

			if($item) {

				$this->data = $item;
				$this->result = 'success';

			} else {

				$this->status = 404;
				$this->message = "Error getting {$this->singular}: item not found.";
			}
		}
	}
?>