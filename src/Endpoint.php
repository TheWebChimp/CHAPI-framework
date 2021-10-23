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
			$this->init();
		}

		/**
		 * Initialization callback, must be overriden in your extended classes
		 */
		abstract function init();

		function dispatchAction($id) {
			return false;
		}
	}
?>