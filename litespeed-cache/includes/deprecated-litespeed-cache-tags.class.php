<?php

/**
 * Deprecated Class for API backward compatibility to v1.1.2.2
 */
class LiteSpeed_Cache_Tags
{
	public static function add_purge_tag( $tag )
	{
		LiteSpeed_Cache_API::purge( $tag ) ;
	}
}