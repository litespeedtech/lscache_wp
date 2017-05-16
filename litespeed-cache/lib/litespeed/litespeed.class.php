<?php

/**
* LiteSpeed Library Class
* @since 1.1.0
*/
class LiteSpeed
{
	private static $_instance_list = array();
	private static $_var_list = array();

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current LiteSpeed child class.
	 */
	public static function get_instance()
	{
		$cls = get_called_class();
		if (!isset(self::$_instance_list[$cls])) {
			self::$_instance_list[$cls] = new $cls();
		}

		return self::$_instance_list[$cls];
	}

	/**
	 * Check if a variable from current child class is set
	 * Set variable while checking
	 * 
	 * @since 1.1.0
	 * @param  string $var variable name
	 * @return mixed
	 */
	public static function is_var($var)
	{
		$var = self::_get_var_name($var);
		if (!isset(self::$_var_list[$var])) {
			self::$_var_list[$var] = false;
			return false;
		}
		return true;
	}

	/**
	 * Get a variable from current child class
	 * @since 1.1.0
	 * @param  string $var variable name
	 * @return mixed
	 */
	public static function get_var($var)
	{
		$var = self::_get_var_name($var);
		return self::$_var_list[$var];
	}

	/**
	 * Register a variable for current child class
	 * @since 1.1.0
	 * @param  string $var variable name
	 * @return bool
	 */
	public static function set_var($var, $val)
	{
		$var = self::_get_var_name($var);
		self::$_var_list[$var] = $val;
		return $val;
	}

	/**
	 * Generate variable name
	 * 
	 * @since 1.1.0
	 * @param  string $var
	 * @return string
	 */
	private static function _get_var_name($var)
	{
		$cls = get_called_class();
		return $cls.'__'.$var;
	}

}