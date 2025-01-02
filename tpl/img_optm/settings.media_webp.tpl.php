<?php

namespace LiteSpeed;

defined('WPINC') || exit;
?>

<tr>
	<th>
		<?php $id = Base::O_IMG_OPTM_WEBP; ?>
		<?php $this->title($id); ?>
	</th>
	<td>
		<?php $this->build_switch($id, array(__('OFF', 'litespeed-cache'), 'WebP', 'AVIF')); ?>
		<?php Doc::maybe_on_by_gm($id); ?>
		<div class="litespeed-desc">
			<?php echo __('Request WebP/AVIF versions of original images when doing optimization.', 'litespeed-cache'); ?>
			<?php echo sprintf(__('Significantly improve load time by replacing images with their optimized %s versions.', 'litespeed-cache'), '.webp/.avif'); ?>
			<br /><?php Doc::notice_htaccess(); ?>
			<br /><?php Doc::crawler_affected(); ?>
			<br />
			<font class="litespeed-warning">
				⚠️ <?php echo sprintf(__('%1$s is a %2$s paid feature.', 'litespeed-cache'), 'AVIF', 'QUIC.cloud'); ?></font>
		</div>
	</td>
</tr>