<?php
/**
 * exception.cls.php - modified PHP implementation of Matthias Mullie's Exceptions Classes.
 * @author Matthias Mullie <minify@mullie.eu>
 */

namespace LiteSpeed\Lib\CSS_JS_MIN\Minify\Exception;

defined( 'WPINC' ) || exit ;

abstract class Exception extends \Exception
{
}

abstract class BasicException extends Exception
{
}

class FileImportException extends BasicException
{
}

class IOException extends BasicException
{
}