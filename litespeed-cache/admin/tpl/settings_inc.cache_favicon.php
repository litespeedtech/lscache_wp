<?php
if (!defined('WPINC')) die;

?>
	<?php $file_writable = LiteSpeed_Cache_Admin_Rules::is_file_able(LiteSpeed_Cache_Admin_Rules::WRITABLE); ?>
	<tr>
		<th><?=__('Cache favicon.ico', 'litespeed-cache')?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_CACHE_FAVICON; ?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_enable" value="1" <?=$_options[$id]?'checked':''?> <?=$file_writable?'':'disabled'?> />
					<label for="conf_<?=$id?>_enable"><?=__('Enable', 'litespeed-cache')?></label>

					<input type="radio" name="<?=LiteSpeed_Cache_Config::OPTION_NAME?>[<?=$id?>]" id="conf_<?=$id?>_disable" value="0" <?=$_options[$id]?'':'checked'?> <?=$file_writable?'':'disabled'?> />
					<label for="conf_<?=$id?>_disable"><?=__('Disable', 'litespeed-cache')?></label>
				</div>
			</div>
			<div class="litespeed-desc">
				<?=__('favicon.ico is requested on most pages.', 'litespeed-cache')?>
				<?=__('Caching this recource may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache')?>
			</div>
		</td>
	</tr>
