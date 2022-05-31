<?php

	namespace CHAPI;

	use Closure;
	use Exception;

	class Router {

		private $base_url;

		protected array $routes;
		protected string $default_route;
		protected Request $request;
		protected Response $response;
		/**
		 * @var array|bool
		 */
		private $matchedRoute;

		function __construct($base_url = '') {
			$this->routes = [];
			$this->base_url = $base_url;
			$this->default_route = '/';
			$this->request = new Request();
			$this->response = new Response();
		}

		/**
		 * Display a generic error message
		 * @param string $message The error message in HTML.
		 */
		function errorMessage(string $message) {
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
		function getDefaultRoute(): string {
			return $this->default_route;
		}

		/**
		 * Get base URL for router
		 * @return string The base URL
		 */
		function getBaseUrl(): string {
			return $this->base_url;
		}

		/**
		 * Get router request object
		 * @return Request The request object
		 */
		function getRequest(): Request {
			return $this->request;
		}

		/**
		 * Get router response object
		 * @return Response The response object
		 */
		function getResponse(): Response {
			return $this->response;
		}

		/**
		 * Set the default route
		 * @param string $route Full route, defaults to '/'
		 */
		function setDefaultRoute(string $route) {
			$this->default_route = $route;
		}

		/**
		 * Add a new route
		 * @param string  $route  Parametrized route
		 * @param mixed   $func   Handler function name
		 * @param boolean $method Method in which to register the route
		 */
		function add(string $route, $func, $method = '*') {
			$this->routes["{$method}::{$route}"] = $func;
		}

		/**
		 * Prepends a new route (puts it in first place of routes array).
		 * @param string $route  Parametrized route
		 * @param mixed  $func   Handler function name
		 * @param string $method Method to use, can be GET, POST, PUT, DELETE or * for all
		 */
		function prepend(string $route, string $func, string $method = '*') {
			$this->routes = ["{$method}::{$route}" => $func] + $this->routes;
		}

		/**
		 * Adds or prepends a route for all methods
		 * @param string  $route   Parametrized route
		 * @param mixed   $func    Handler function name
		 * @param boolean $prepend Determines if route should be prepended instead of added
		 */
		function all(string $route, $func, bool $prepend = false) {
			if($prepend) $this->prepend($route, $func); else $this->add($route, $func);
		}

		/**
		 * @param      $route
		 * @param      $func
		 * @param bool $prepend
		 * @return void
		 */
		function get($route, $func, bool $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'get'); else $this->add($route, $func, 'get');
		}

		/**
		 * @param      $route
		 * @param      $func
		 * @param bool $prepend
		 * @return void
		 */
		function post($route, $func, bool $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'post'); else $this->add($route, $func, 'post');
		}

		/**
		 * @param      $route
		 * @param      $func
		 * @param bool $prepend
		 * @return void
		 */
		function put($route, $func, bool $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'put'); else $this->add($route, $func, 'put');
		}

		/**
		 * @param      $route
		 * @param      $func
		 * @param bool $prepend
		 * @return void
		 */
		function delete($route, $func, bool $prepend = false) {
			if($prepend) $this->prepend($route, $func, 'delete'); else $this->add($route, $func, 'delete');
		}

		/**
		 * Removes the specified route
		 * @param string $route Parametrized route
		 * @param string $method
		 * @return boolean       True if the route was found and removed, false otherwise
		 */
		function remove(string $route, string $method): bool {
			if($this->isRoute($route, $method)) {
				unset($this->routes["{$method}::{$route}"]);
				return true;
			}
			return false;
		}

		/**
		 * Checks whether a given route exists or not
		 * @param string $route  Parametrized route
		 * @param string $method Method of the route
		 * @return boolean       True if the route exists, false otherwise
		 */
		function isRoute(string $route, string $method): bool {
			return isset($this->routes["{$method}::{$route}"]);
		}

		/**
		 * Get the registered routes
		 * @return array The registered routes
		 */
		function getRoutes(): array {
			return $this->routes;
		}

		/**
		 * Retrieves the current request URI
		 * @return string The current request URI
		 */
		function getCurrentUrl(): string {

			# Routing stuff, first get the base url
			$base_url = trim($this->getBaseUrl(), '/');

			# Remove the protocol from it
			$domain = preg_replace('/^(http|https):\/\//', '', $base_url);

			# Now remove the path
			$segments = explode('/', $domain, 2);
			if(count($segments) > 1) {
				$domain = array_pop($segments);
			}

			# Get the request and remove the domain
			$request_uri = trim(rawurldecode($_SERVER['REQUEST_URI']), '/');

			$request_uri = preg_replace("/" . str_replace('/', '\/', $domain) . "/", '', $request_uri, 1);
			return ltrim($request_uri, '/');
		}

		/**
		 * Shortcut / Helper function that registers a route for 404
		 * @param Closure $fn Function to respond to 404 route
		 */
		function set404(Closure $fn) {
			$this->add('404', function() use ($fn) {

				$this->response->setStatus(404);
				$fn();
			});
		}

		/**
		 * Removes all the registered routes
		 * @return void
		 */
		function clearRoutes() {
			$this->routes = [];
		}

		/**
		 * Tries to match the given route with one of the registered handlers and process it
		 * @param string $spec_route The route to match
		 * @return boolean        TRUE if the route matched with a handler, FALSE otherwise
		 */
		function match(string $spec_route) {
			$ret = false;
			# And try to match the route with the registered ones
			$matches = [];
			$cur_method = $this->request->getMethod();

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
				if(preg_match($pattern, $spec_route, $matches) == 1) {

					array_shift($matches);
					$ret = ['route' => $route, 'handler' => $handler, 'params' => $matches];
					break;
				}
			}

			return $ret;
		}

		/**
		 * Processes current request
		 * @return boolean TRUE if routing has succeeded, FALSE otherwise
		 */
		function routeRequest(): bool {
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
			if(empty($cur_route)) {
				$cur_route = $this->default_route;
			}

			ob_start();

			$route = $this->match($cur_route);

			if($route) {

				try {

					$this->matchedRoute = $route;

					call_user_func_array($route['handler'], $route['params']);
					$ret = true;
				} catch(Exception $e) {

					$this->response->setStatus('500');
					$this->response->ajaxRespond('error', [], "Router Error: Error handling route {$route['route']} on file " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
				}
			} else {

				//Check if we have a 404 route set
				if(isset($this->getRoutes()['*::404'])) {

					call_user_func_array($this->getRoutes()['*::404'], []);
					$this->response->setStatus('404');
					$ret = true;
				} else {

					$this->errorMessage('Route not found');
					$this->response->setStatus('404');
				}
			}

			$this->response->setBody(ob_get_clean());
			$this->response->respond();

			return $ret;
		}
	}