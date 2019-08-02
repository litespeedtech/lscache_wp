<?php
defined( 'WPINC' ) || exit ;

$last_generated = LiteSpeed_Cache_Avatar::get_summary() ;

?>


<h3 class="litespeed-title-short">
	<?php echo __( 'Discussion Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:discussion', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table><tbody>
	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_DISCUSS_AVATAR_CACHE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Store Gravatar locally.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Accelerates the speed by caching Gravatar (Globally Recognized Avatars).', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_DISCUSS_AVATAR_CRON ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Update Gravatar cache in cron.', 'litespeed-cache' ) ; ?>
			</div>

			<?php if ( $last_generated ) : ?>
			<div class="litespeed-desc litespeed-left20">
				<?php if ( ! empty( $last_generated[ 'last_request' ] ) ) : ?>
					<p>
						<?php echo __( 'Last ran', 'litespeed-cache' ) . ': <code>' . LiteSpeed_Cache_Utility::readable_time( $last_generated[ 'last_request' ] ) . '</code>' ; ?>
					</p>
				<?php endif ; ?>
				<?php if ( $last_generated[ 'queue_count' ] ) : ?>
					<div class="litespeed-callout-warning">
						<h4>
							<?php echo __( 'Avatar list in queue waiting for update','litespeed-cache' ) ; ?>:
							<?php echo $last_generated[ 'queue_count' ] ; ?>
						</h4>
					</p>
					<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache_Router::ACTION_AVATAR, LiteSpeed_Cache_Avatar::TYPE_GENERATE ) ; ?>" class="litespeed-btn-success">
						<?php echo __( 'Run Queue Manually', 'litespeed-cache' ) ; ?>
					</a>
				<?php endif ; ?>
			</div>
			<?php endif ; ?>

		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_DISCUSS_AVATAR_CACHE_TTL ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, Gravatar files are cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 3600 ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>
