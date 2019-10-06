<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;
?>

	<tr>
		<th>
			<?php $id = Base::O_UTIL_CHECK_ADVCACHE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<div class="litespeed-tick-wrapper">
				<?php
					$this->build_checkbox( $id, __( 'Include advanced-cache.php', 'litespeed-cache' ) ) ;
				?>
			</div>
			<div class="litespeed-desc">
				<?php echo __( 'The advanced-cache.php file is used by many caching plugins to signal that a cache is active.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'When this option is checked and this file is detected as belonging to another plugin, LiteSpeed\'s caching functionality will not work.', 'litespeed-cache' ) ; ?>
			</div>
			<p>
				<i><?php echo __( 'You may need to uncheck this option if you are using multiple optimization plugins.', 'litespeed-cache' ) ; ?></i>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:customizations:multi-cache-plugins' ) ; ?>
			</p>

		</td>
	</tr>

