<?php

	/**
	 * Pretty-print an array or object
	 * @param mixed $a Array or object
	 */
	if(!function_exists('print_a')) {
		function print_a($a) {
			print('<pre>');
			print_r($a);
			print('</pre>');
		}
	}

	/**
	 * Get an item from an array/object, or a default value if it's not set
	 * @param mixed $var     Array or object
	 * @param mixed $key     Key or index, depending on the array/object
	 * @param mixed $default A default value to return if the item it's not in the array/object
	 * @return mixed           The requested item (if present) or the default value
	 */
	if(!function_exists('get_item')) {
		function get_item($var, $key, $default = '') {
			return is_object($var) ? ($var->$key ?? $default) : ($var[$key] ?? $default);
		}
	}

	/**
	 * Convert a shorthand byte value from a PHP configuration directive to an integer value
	 * @param string $value
	 * @return   int
	 */
	if(!function_exists('convert_bytes')) {
		function convert_bytes($value) {
			if(is_numeric($value)) {
				return $value;
			} else {
				$value_length = strlen($value);
				$qty = substr($value, 0, $value_length - 1);
				$unit = strtolower(substr($value, $value_length - 1));
				switch($unit) {
					case 'k':
						$qty *= 1024;
						break;
					case 'm':
						$qty *= 1048576;
						break;
					case 'g':
						$qty *= 1073741824;
						break;
				}
				return $qty;
			}
		}
	}

	if(!function_exists('isJson')) {
		/**
		 * @param $string
		 * @return bool
		 */
		function isJson($string): bool {
			json_decode($string);
			return (json_last_error() == JSON_ERROR_NONE);
		}
	}

	/**
	 * Convert camelCase to snake_case
	 * @param string $val Original string
	 * @return string      The converted string
	 */
	if(!function_exists('camel_to_snake')) {
		function camel_to_snake($val): string {
			$val = preg_replace_callback('/[A-Z]/', '_camel_to_snake_callback', $val);
			return ltrim($val, '_');
		}

		function _camel_to_snake_callback($match): string {
			return "_" . strtolower($match[0]);
		}
	}

	/**
	 * Convert camelCase to dash-case
	 * @param string $val Original string
	 * @return string      The converted string
	 */
	if(!function_exists('camel_to_dash')) {
		function camel_to_dash($val): string {
			$val = preg_replace_callback('/[A-Z]/', '_camel_to_dash_callback', $val);
			return ltrim($val, '-');
		}

		function _camel_to_dash_callback($match): string {
			return "-" . strtolower($match[0]);
		}
	}

	/**
	 * Convert snake_case to camelCase
	 * @param string $val Original string
	 * @return string      The converted string
	 */
	if(!function_exists('snake_to_camel')) {
		function snake_to_camel($val): string {
			$val = str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
			return strtolower(substr($val, 0, 1)) . substr($val, 1);
		}
	}

	/**
	 * Convert dash-case to camelCase
	 * @param string $val Original string
	 * @return string      The converted string
	 */
	if(!function_exists('dash_to_camel')) {
		function dash_to_camel($val): string {
			$val = str_replace(' ', '', ucwords(str_replace('-', ' ', $val)));
			return strtolower(substr($val, 0, 1)) . substr($val, 1);
		}
	}

	/**
	 * Get the singular or plural form of a word based on a quantity
	 * @param number $number   The quantity
	 * @param string $singular Singular form of the word
	 * @param string $plural   Plural form of the word
	 * @return string           The appropriate form of the word
	 */
	if(!function_exists('singular_plural')) {
		function singular_plural($number, $singular, $plural = '') {
			return $number == 1 ? $singular : ($plural ?: "{$singular}s");
		}
	}
