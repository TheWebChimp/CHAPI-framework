<?php

	if(!function_exists('print_a')) {
		function print_a($a) {
			print('<pre>');
			print_r($a);
			print('</pre>');
		}
	}

	if(!function_exists('get_item')) {
		function get_item($var, $key, $default = '') {
			return is_object($var) ?
				( isset( $var->$key ) ? $var->$key : $default ) :
				( isset( $var[$key] ) ? $var[$key] : $default );
		}
	}
?>