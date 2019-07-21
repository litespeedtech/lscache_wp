<?php defined( 'WPINC' ) || exit ; ?>


<h3 class="litespeed-title-short">
	<?php echo __( 'Browser Cache Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:browser', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table><tbody>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_UTIL_BROWSER_CACHE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Browser caching stores static files locally in the user\'s browser. Turn on this setting to reduce repeated requests for static files.', 'litespeed-cache' ) ; ?>
				<br /><?php LiteSpeed_Cache_Doc::notice_htaccess() ; ?>
				<br /><?php echo sprintf( __( 'You can turn on browser caching in server admin too. <a %s>Learn more about LiteSpeed browser cache setting</a>.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:browser_cache" target="_blank"' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_UTIL_BROWSER_CACHE_TTL ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'The amount of time, in seconds, that files will be stored in browser cache before expiring.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 30 ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>