<?php
/**
 * Lightweight script to update guest mode vary
 *
 * @since 4.1
 */

require 'lib/guest.cls.php';

$guest = new \LiteSpeed\Lib\Guest();

$guest->update_guest_vary();
