<?php

	/**
	 * Endpoint class
	 *
	 * A simple wrapper for endpoints.
	 * You must override the init() method.
	 */

	namespace CHAPI;

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

			$this->init();
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