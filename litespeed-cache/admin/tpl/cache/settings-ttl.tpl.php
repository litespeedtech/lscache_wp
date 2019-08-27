<?php defined( 'WPINC' ) || exit ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'TTL', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:ttl', false, 'litespeed-learn-more' ) ; ?>
</h3>


<table class="wp-list-table striped litespeed-table"><tbody>

	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_CACHE_TTL_PUB ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, public pages are cached.', 'litespeed-cache'); ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 30 ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_CACHE_TTL_PRIV ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, private pages are cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 60, 3600 ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_CACHE_TTL_FRONTPAGE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, the front page is cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 30 ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_CACHE_TTL_FEED ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, feeds are cached.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'If this is set to a number less than 30, feeds will not be cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_CACHE_TTL_STATUS ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 30 ) ; ?>
			<?php $this->recommended( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, these HTTP status pages are cached.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

