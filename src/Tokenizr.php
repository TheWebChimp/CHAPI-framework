<?php

	/**
	 * Tokenizr
	 * @author    webchimp <github.com/webchimp>
	 * @version   2.0
	 * @license   MIT
	 * @example   Basic usage:
	 *
	 *    Use getToken() to tokenize your data. It will return a string with the source data and its message digest:
	 *
	 *      $token = Tokenizr::getToken('something');
	 *
	 *    You may even pass an array, either associative or not:
	 *
	 *      $token = Tokenizr::getToken(['foo' => 'bar', 'bar' => 'baz']);
	 *
	 *    You can then save the token wherever you want. To check it back, use checkToken():
	 *
	 *      $valid = Tokenizr::checkToken('something....');
	 *
	 *    To get back the data, use getData():
	 *
	 *      $data = Tokenizr::getData('something....');
	 *
	 *    Note that the data is saved in plain sight, THIS CLASS IS NOT DESIGNED TO ENCRYPT DATA. The purpose
	 *    of this class is to provide a way to check if the data (from a cookie, for example) has been generated
	 *    by your code and not forged. The checksum is reliable as long as you NEVER DISCLOSE YOUR SALTS.
	 *
	 */

	namespace CHAPI;
	include 'utilities.inc.php';

	class Tokenizr {

		/**
		 * Generate a token
		 * @param mixed  $data    String or array with data to hash
		 * @param        $key
		 * @param string $divider Divider character
		 * @return string         The resulting token
		 */
		static function getToken($data, $key, string $divider = '.'): string {
			if(is_array($data)) {
				$data = http_build_query($data);
				$ret = self::getToken($data, $key);
			} else {
				$hash = hash_hmac('sha256', $data, $key);
				$ret = "{$data}{$divider}{$hash}";
			}
			return $ret;
		}

		/**
		 * Check whether a given token is valid or not
		 * @param string $token   The token to check
		 * @param string $divider Divider character
		 * @return bool           TRUE if the token is valid, FALSE otherwise
		 */
		static function checkToken(string $token, $key, string $divider = '.'): bool {
			$ret = false;
			$parts = explode($divider, $token);
			$data = get_item($parts, 0);
			$hash = get_item($parts, 1);
			if($data && $hash) {
				$check = hash_hmac('sha256', $data, $key);
				$ret = $hash === $check;
			}
			return $ret;
		}

		/**
		 * Retrieve token data
		 * @param string $token   The token to get data from
		 * @param string $divider Divider character
		 * @return mixed          The retrieved data, either a string or an array
		 */
		static function getData(string $token, string $divider = '.') {
			$parts = explode($divider, $token);
			$data = get_item($parts, 0);
			if(strpos($data, '&')) {
				parse_str($data, $items);
				$ret = $items;
			} else {
				$ret = $data;
			}
			return $ret;
		}
	}