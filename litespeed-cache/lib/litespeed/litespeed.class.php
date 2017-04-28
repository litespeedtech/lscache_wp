<?php

/**
* LiteSpeed Library Class
* @since 1.0.16
*/
class LiteSpeed{

	/**
	 * Get the current instance object.
	 *
	 * @since 1.0.16
	 * @access public
	 * @return Current LiteSpeed child class.
	 */
	public static function get_instance(){
		if (!isset(static::$_instance)) {
			$cls = get_called_class();
			static::$_instance = new $cls();
		}

		return static::$_instance;
	}
}