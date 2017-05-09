<?php
/**
* LiteSpeed File Operator Library Class
* Append/Replace content to a file
* 
* @since 1.1.0
*/


class Litespeed_File
{
	const MARKER = 'LiteSpeed Operator';
	
	function __construct()
	{
		// nothing to do here
	}

	/**
	 * Appends an array of strings into a file (.htaccess ), placing it between
	 * BEGIN and END markers.
	 *
	 * Replaces existing marked info. Retains surrounding
	 * data. Creates file if none exists.
	 *
	 * @param string       $filename  Filename to alter.
	 * @param string       $marker    The marker to alter.
	 * @param array|string $insertion The new content to insert.
	 * @param bool 	       $prepend Prepend insertion if not exist.
	 * @return bool True on write success, false on failure.
	 */
	public static function append($filename, $insertion = false, $marker = false, $prepend = false)
	{
		if ( !$marker )
		{
			$marker = self::MARKER;
		}

		if ( !$insertion )
		{
			$insertion = array();
		}

		return self::_insert_with_markers($filename, $marker, $insertion, $prepend);
	}

	/**
	 * Return wrapped block data with marker
	 *
	 * @param string $insertion
	 * @param string $marker
	 * @return string The block data
	 */
	public static function generateBlock($insertion, $marker = false)
	{
		if ( ! $marker )
		{
			$marker = self::MARKER;
		}
		$start_marker = "# BEGIN {$marker}";
		$end_marker   = "# END {$marker}";

		$new_data = implode( "\n", array_merge(
			array( $start_marker ),
			$insertion,
			array( $end_marker )
		) );
		return $new_data;
	}

	/**
	 * Touch block data from file, return with marker
	 *
	 * @param string $filename
	 * @param string $marker
	 * @return string The current block data
	 */
	public static function touchBlock($filename, $marker = false)
	{
		if( ! $marker )
		{
			$marker = self::MARKER;
		}

		$result = self::_extract_from_markers($filename, $marker);

		if( ! $result )
		{
			return false;
		}

		$start_marker = "# BEGIN {$marker}";
		$end_marker   = "# END {$marker}";
		$new_data = implode( "\n", array_merge(
			array( $start_marker ),
			$result,
			array( $end_marker )
		) );
		return $new_data;
	}

	/**
	 * Extracts strings from between the BEGIN and END markers in the .htaccess file.
	 *
	 * @param string $filename
	 * @param string $marker
	 * @return array An array of strings from a file (.htaccess ) from between BEGIN and END markers.
	 */
	public static function parse($filename, $marker = false)
	{
		if( ! $marker )
		{
			$marker = self::MARKER;
		}
		return self::_extract_from_markers($filename, $marker);
	}

	/**
	 * Extracts strings from between the BEGIN and END markers in the .htaccess file.
	 *
	 * @param string $filename
	 * @param string $marker
	 * @return array An array of strings from a file (.htaccess ) from between BEGIN and END markers.
	 */
	private static function _extract_from_markers( $filename, $marker )
	{
		$result = array ();

		if (!file_exists( $filename ) )
		{
			return $result;
		}

		if ( $markerdata = explode( "\n", implode( '', file( $filename ) ) ) )
		{
			$state = false;
			foreach ( $markerdata as $markerline )
			{
				if ( strpos($markerline, '# END ' . $marker) !== false )
				{
					$state = false;
				}
				if ( $state )
				{
					$result[] = $markerline;
				}
				if (strpos($markerline, '# BEGIN ' . $marker) !== false)
				{
					$state = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Inserts an array of strings into a file (.htaccess ), placing it between
	 * BEGIN and END markers.
	 *
	 * Replaces existing marked info. Retains surrounding
	 * data. Creates file if none exists.
	 *
	 * @param string       $filename  Filename to alter.
	 * @param string       $marker    The marker to alter.
	 * @param array|string $insertion The new content to insert.
	 * @param bool 	       $prepend Prepend insertion if not exist.
	 * @return bool True on write success, false on failure.
	 */
	private static function _insert_with_markers( $filename, $marker, $insertion, $prepend = false)
	{
		if ( ! file_exists( $filename ) )
		{
			if ( ! is_writable( dirname( $filename ) ) )
			{
				return false;
			}
			if ( ! touch( $filename ) )
			{
				return false;
			}
		}
		elseif ( ! is_writeable( $filename ) )
		{
			return false;
		}

		if ( ! is_array( $insertion ) )
		{
			$insertion = explode( "\n", $insertion );
		}

		$start_marker = "# BEGIN {$marker}";
		$end_marker   = "# END {$marker}";

		$fp = fopen( $filename, 'r+' );
		if ( ! $fp )
		{
			return false;
		}

		// Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
		flock( $fp, LOCK_EX );

		$lines = array();
		while ( ! feof( $fp ) )
		{
			$lines[] = rtrim( fgets( $fp ), "\r\n" );
		}

		// Split out the existing file into the preceding lines, and those that appear after the marker
		$pre_lines = $post_lines = $existing_lines = array();
		$found_marker = $found_end_marker = false;
		foreach ( $lines as $line )
		{
			if ( ! $found_marker && false !== strpos( $line, $start_marker ) )
			{
				$found_marker = true;
				continue;
			}
			elseif ( ! $found_end_marker && false !== strpos( $line, $end_marker ) )
			{
				$found_end_marker = true;
				continue;
			}

			if ( ! $found_marker )
			{
				$pre_lines[] = $line;
			}
			elseif ( $found_marker && $found_end_marker )
			{
				$post_lines[] = $line;
			}
			else
			{
				$existing_lines[] = $line;
			}
		}

		// Check to see if there was a change
		if ( $existing_lines === $insertion )
		{
			flock( $fp, LOCK_UN );
			fclose( $fp );

			return true;
		}

		// Check if need to prepend data if not exist
		if( $prepend && ! $post_lines )
		{
			// Generate the new file data
			$new_file_data = implode( "\n", array_merge(
				array( $start_marker ),
				$insertion,
				array( $end_marker ),
				$pre_lines
			) );

		}
		else
		{
			// Generate the new file data
			$new_file_data = implode( "\n", array_merge(
				$pre_lines,
				array( $start_marker ),
				$insertion,
				array( $end_marker ),
				$post_lines
			) );
		}


		// Write to the start of the file, and truncate it to that length
		fseek( $fp, 0 );
		$bytes = fwrite( $fp, $new_file_data );
		if ( $bytes )
		{
			ftruncate( $fp, ftell( $fp ) );
		}
		fflush( $fp );
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return (bool) $bytes;
	}
}


