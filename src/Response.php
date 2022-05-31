<?php

	namespace CHAPI;

	class Response {

		/**
		 * Current response body
		 * @var string
		 */
		protected string $body;

		/**
		 * Current response status code (HTTP status)
		 * @var integer
		 */
		protected int $status;

		/**
		 * Current response headers
		 * @var array
		 */
		protected array $headers;

		/**
		 * Constructor
		 */
		function __construct() {
			$this->body = '';
			$this->status = 200;
			$this->headers = [];
		}

		/**
		 * Write to the current response body, appends data
		 * @param string $data Raw response data
		 */
		function write(string $data) {
			$this->body .= $data;
		}

		/**
		 * Set the body for the current response, replaces contents (if any)
		 * @param string $data Raw response body
		 */
		function setBody(string $data) {
			$this->body = $data;
		}

		/**
		 * Get the status code for the current response
		 * @return integer Current response status code
		 */
		function getStatus(): int {
			return $this->status;
		}

		/**
		 * Set the status code for the current response
		 * @param integer $code A valid HTTP response code (200, 404, 500, etc.)
		 */
		function setStatus(int $code) {
			$this->status = $code;
		}

		/**
		 * Get the current response body
		 * @return string The response body
		 */
		function getBody(): string {
			return $this->body;
		}

		/**
		 * Set the value of a specific header for the current response
		 * @param string $name  Header name
		 * @param string $value Header value
		 */
		function setHeader(string $name, string $value) {
			$this->headers[$name] = $value;
		}

		/**
		 * Get the value of a specific header for the current response
		 * @param string $name Header name
		 * @return mixed        Header value or Null if it's not set
		 */
		function getHeader(string $name) {
			return $this->headers[$name] ?? null;
		}

		/**
		 * Set all the headers for the current response
		 * @param array $headers Headers array
		 */
		function setHeaders(array $headers) {
			$this->headers = $headers;
		}

		/**
		 * Get the array of headers for the current response
		 * @return array  All current headers
		 */
		function getHeaders(): array {
			return $this->headers;
		}

		/**
		 * Do an HTTP redirection
		 * @param string $url URL to redirect to
		 */
		function redirect(string $url) {
			header("Location: {$url}");
			exit;
		}

		/**
		 * Flush headers and response body
		 * @return boolean This will always return true
		 */
		function respond(): bool {
			http_response_code($this->status);
			# Send headers
			foreach ($this->headers as $header => $value) header("{$header}: {$value}");
			# Send response
			echo $this->getBody();
			return true;
		}

		/**
		 * Flush headers and response body
		 * @param string      $result     Readable result for response (success or error are the most common)
		 * @param mixed       $data       Array with data to respond
		 * @param string|null $message    Readable message for the response
		 * @param array       $properties Extra information to pass that should be outside data
		 * @return boolean                This will always return true
		 */
		function ajaxRespond(string $result, $data = null, string $message = null, array $properties = []): bool {
			$ret = [];
			$ret = array_merge($properties, $ret);
			$ret['result'] = $result;
			$ret['status'] = $this->getStatus();
			if ($data !== null) {
				$ret['data'] = $data;
			}
			if ($message) {
				$ret['message'] = $message;
			}
			$this->setHeader('Content-Type', 'application/json');
			$this->setBody( json_encode($ret) );
			$this->respond();
			return true;
		}

		/**
		 * Responds an error with a specific message
		 * @param string     $message Readable message for the response
		 * @param int|string $status  HTTP code to respond, default to 404
		 * @return boolean            This will always return true
		 */
		function error(string $message, $status = 404): bool {
			$result = 'error';
			$data = null;
			$this->setStatus($status);
			return $this->ajaxRespond($result, $data, $message);
		}

		/**
		 * Shortcut / Helper function to respond a "bad HTTP method" error
		 * @param string $method Bad method used
		 * @return boolean       This will always return true
		 */
		function badHTTPMethod(string $method): bool {
			return $this->error("Incorrect http method: {$method}");
		}
	}