<?php

	namespace CHAPI;

	use CHAPI\Request;
	use CHAPI\Response;

	class Router {

		private $base_url;

		protected $routes;
		protected $default_route;
		protected $request;
		protected $response;

		function __construct($base_url = '') {
			$this->routes = [];
			$this->base_url = $base_url;
			$this->default_route = '/';
			$this->request = new Request();
			$this->response = new Response();
		}

		/**
		 * Display a generic error message
		 * @param  string $message The error message
		 */
		function errorMessage($message, $response_code = 404) {
			$markup = '<!DOCTYPE html>
						<html lang="en">
							<head>
								<meta charset="UTF-8">
								<title>CHAPI: Router Error</title>
								<style>
									body { font-family: sans-serif; font-size: 14px; background: #F8F8F8; }
									div.center { width: 960px; margin: 0 auto; padding: 1px 0; }
									p.message { padding: 15px; background: #F1F1F1; color: #656565; }
								</style>
							</head>
							<body>
								<div class="center">
									<p class="message">%s</p>
								</div>
							</body>
						</html>';
			$markup = sprintf($markup, $message);
			echo $markup;
		}

		/**
		 * Get the default route
		 * @return string The default route
		 */
		function getDefaultRoute() {
			return $this->default_route;
		}

		/**
		 * Get base URL for router
		 * @return string The base URL
		 */
		function getBaseUrl() {
			return $this->base_url;
		}

		/**
		 * Get router request object
		 * @return NORM\Request The request object
		 */
		function getRequest() {
			return $this->request;
		}

		/**
		 * Get router response object
		 * @return NORM\Response The response object
		 */
		function getResponse() {
			return $this->response;
		}

		/**
		 * Set the default route
		 * @param string $route Full route, defaults to '/default'
		 */
		function setDefaultRoute($route) {
			$this->default_route = $route;
		}

		/**
		 * Add a new route
		 * @param  string  $route     Parametrized route
		 * @param  string  $func      Handler function name
		 * @param  boolean $prepend   If set, the route will be inserted at the beginning
		 */
		function add($route, $func, $method = '*') {
			$this->routes["{$method}::{$route}"] = $func;
		}

		/**
		 * Prepend a new route
		 * @param  string  $route     Parametrized route
		 * @param  string  $func Handler function name
		 */
		function prepend($route, $func, $method = '*') {
			$this->routes = ["{$method}::{$route}" => $func] + $this->routes;
		}

		function all($route, $func, $prepend = false) {
			if($prepend) $this->prepend($route, $func, '*');
			else $this->add($route, $func, '*');
		}

		function get($route, $func, $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'get');
			else $this->add($route, $func, 'get');
		}

		function post($route, $func, $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'post');
			else $this->add($route, $func, 'post');
		}

		function put($route, $func, $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'put');
			else $this->add($route, $func, 'put');
		}

		function delete($route, $func, $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'delete');
			else $this->add($route, $func, 'delete');
		}

		/**
		 * Removes the specified route
		 * @param  string $route Parametrized route
		 * @return boolean       True if the route was found and removed, false otherwise
		 */
		function remove($route) {
			if ( $this->isRoute($route) ) {
				unset( $this->routes[$route] );
				return true;
			}
			return false;
		}

		/**
		 * Check whether a given route exists or not
		 * @param  string $route Parametrized route
		 * @return boolean       True if the route exists, false otherwise
		 */
		function isRoute($route) {
			return isset( $this->routes[$route] );
		}

		/**
		 * Get the registered routes
		 * @return array The registered routes
		 */
		function getRoutes() {
			return $this->routes;
		}

		/**
		 * Retrieve the current request URI
		 * @return string The current request URI
		 */
		function getCurrentUrl() {

			# Routing stuff, first get the base url
			$base_url = trim($this->getBaseUrl(), '/');

			# Remove the protocol from it
			$domain = preg_replace('/^(http|https):\/\//', '', $base_url);

			# Now remove the path
			$segments = explode('/', $domain, 2);
			if (count($segments) > 1) {
				$domain = array_pop($segments);
			}

			# Get the request and remove the domain
			$request_uri = trim(rawurldecode($_SERVER['REQUEST_URI']), '/');

			$request_uri = preg_replace("/".str_replace('/', '\/', $domain)."/", '', $request_uri, 1);
			$request_uri = ltrim($request_uri, '/');

			return $request_uri;
		}


		function set404($fn) {

			$this->add('404', function() use ($fn) {

				$this->response->setStatus(404);
				$fn();
			}, '*');
		}

		/**
		 * Remove all the registered routes
		 * @return nothing
		 */
		function clearRoutes() {
			$this->routes = [];
		}

		/**
		 * Try to match the given route with one of the registered handlers and process it
		 * @param  string $route  		The route to match
		 * @return boolean        		TRUE if the route matched with a handler, FALSE otherwise
		 */
		function match($spec_route) {
			$ret = false;
			# And try to match the route with the registered ones
			$matches = [];
			$cur_method = $this->request->getMethod();

			$matched_route = false;

			foreach($this->routes as $route => $handler) {

				//removing and checking the method
				list($method, $route) = explode('::', $route);
				if($method != '*' && strtolower($method) != $cur_method) continue;

				$route = str_replace('/', "\/", $route);
				$route = str_replace('[', '(?:', $route);
				$route = str_replace(']', ')?', $route);
				$route = preg_replace('/\/{(.*?)}/', '/(.*?)', $route);

				# Compile route into regular expression
				$pattern = "~^{$route}$~";

				//Route is matched
				if ( preg_match($pattern, $spec_route, $matches) == 1) {

					array_shift($matches);
					$ret = ['route' => $route, 'handler' => $handler, 'params' => $matches];
					break;
				}
			}

			return $ret;
		}

		/**
		 * Process current request
		 * @return boolean TRUE if routing has succeeded, FALSE otherwise
		 */
		function routeRequest() {
			$ret = false;

			# Get the current URL
			$request_uri = $this->getCurrentUrl();

			# Save current request string
			$this->request->uri = $request_uri;

			# Get the segments
			$segments = explode('?', $request_uri);
			$cur_route = array_shift($segments);
			$this->request->parts = explode('/', $cur_route);

			# Now make sure the current route begins with '/' and doesn't end with '/'
			$cur_route = '/' . $cur_route;
			$cur_route = rtrim($cur_route, '/');

			# Make sure we have a valid route
			if ( empty($cur_route) ) {
				$cur_route = $this->default_route;
			}

			ob_start();

			$route = $this->match($cur_route);

			if($route) {

				try {

					call_user_func_array($route['handler'], $route['params']);
					$ret = true;

				} catch(\Exception $e) {

					$this->response->setStatus('500');
					$this->response->ajaxRespond('error', [], "Router Error: Error handling route {$route['route']} on file " . $e->getFile() . " on line" . $e->getLine() . ":" . $e->getMessage());
				}

			} else {

				//Check if we have a 404 route set
				if(isset($this->getRoutes()['*::404'])) {

					call_user_func_array($this->getRoutes()['*::404'], []);
					$this->response->setStatus('404');
					$ret = true;

				} else {

					$this->errorMessage('Route not found', 404);
					$this->response->setStatus('404');
				}
			}

			$this->response->setBody(ob_get_clean());
			$this->response->respond();

			return $ret;
		}
	}
?>