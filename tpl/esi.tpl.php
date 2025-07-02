<?php
/**
 * LiteSpeed Cache ESI Block Loader
 *
 * Loads the ESI block for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

defined( 'WPINC' ) || exit;

\LiteSpeed\ESI::cls()->load_esi_block();
