<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$_panels = array(
	'all'                => array(
		'title' => __( 'Clean All', 'litespeed-cache' ),
		'desc'  => '',
	),
	'revision'           => array(
		'title' => __( 'Post Revisions', 'litespeed-cache' ),
		'desc'  => __( 'Clean all post revisions', 'litespeed-cache' ),
	),
	'orphaned_post_meta' => array(
		'title' => __( 'Orphaned Post Meta', 'litespeed-cache' ),
		'desc'  => __( 'Clean all orphaned post meta records', 'litespeed-cache' ),
	),
	'auto_draft'         => array(
		'title' => __( 'Auto Drafts', 'litespeed-cache' ),
		'desc'  => __( 'Clean all auto saved drafts', 'litespeed-cache' ),
	),
	'trash_post'         => array(
		'title' => __( 'Trashed Posts', 'litespeed-cache' ),
		'desc'  => __( 'Clean all trashed posts and pages', 'litespeed-cache' ),
	),
	'spam_comment'       => array(
		'title' => __( 'Spam Comments', 'litespeed-cache' ),
		'desc'  => __( 'Clean all spam comments', 'litespeed-cache' ),
	),
	'trash_comment'      => array(
		'title' => __( 'Trashed Comments', 'litespeed-cache' ),
		'desc'  => __( 'Clean all trashed comments', 'litespeed-cache' ),
	),
	'trackback-pingback' => array(
		'title' => __( 'Trackbacks/Pingbacks', 'litespeed-cache' ),
		'desc'  => __( 'Clean all trackbacks and pingbacks', 'litespeed-cache' ),
	),
	'expired_transient'  => array(
		'title' => __( 'Expired Transients', 'litespeed-cache' ),
		'desc'  => __( 'Clean expired transient options', 'litespeed-cache' ),
	),
	'all_transients'     => array(
		'title' => __( 'All Transients', 'litespeed-cache' ),
		'desc'  => __( 'Clean all transient options', 'litespeed-cache' ),
	),
	'optimize_tables'    => array(
		'title' => __( 'Optimize Tables', 'litespeed-cache' ),
		'desc'  => __( 'Optimize all tables in your database', 'litespeed-cache' ),
	),
);

$rev_max = $this->conf( Base::O_DB_OPTM_REVISIONS_MAX );
$rev_age = $this->conf( Base::O_DB_OPTM_REVISIONS_AGE );
if ( $rev_max || $rev_age ) {
	$_panels['revision']['desc'] = sprintf( __( 'Clean revisions older than %1$s day(s), excluding %2$s latest revisions', 'litespeed-cache' ), '<strong>' . $rev_age . '</strong>', '<strong>' . $rev_max . '</strong>' );
}

$total = 0;
foreach ( $_panels as $tag => $v ) {
	if ( $tag != 'all' ) {
		$_panels[ $tag ]['count'] = $this->cls( 'DB_Optm' )->db_count( $tag );
		if ( ! in_array( $tag, array( 'optimize_tables' ) ) ) {
			$total += $_panels[ $tag ]['count'];
		}
	}
	$_panels[ $tag ]['link'] = Utility::build_url( Router::ACTION_DB_OPTM, $tag );
}

$_panels['all']['count'] = $total;

$autoload_summary = DB_Optm::cls()->autoload_summary();

?>

<h3 class="litespeed-title">
	<?php echo __( 'Database Optimizer', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/database/' ); ?>
</h3>

<div class="litespeed-panel-wrapper litespeed-cards-wrapper">

	<?php foreach ( $_panels as $tag => $v ) : ?>

		<a href="<?php echo $v['link']; ?>" class="litespeed-panel postbox">
			<section class="litespeed-panel-wrapper-icon">
				<span class="litespeed-panel-icon-<?php echo $tag; ?>"></span>
			</section>
			<section class="litespeed-panel-content">
				<div class="litespeed-h3">
					<?php echo $v['title']; ?>
					<span class="litespeed-panel-counter
					<?php
					if ( $v['count'] > 0 ) {
						echo '-red';}
					?>
					">(<?php echo $v['count']; ?><?php echo DB_Optm::hide_more() ? '+' : ''; ?>)</span>
				</div>
				<span class="litespeed-panel-para"><?php echo $v['desc']; ?></span>
			</section>
			<section class="litespeed-panel-wrapper-top-right">
				<span class="litespeed-panel-top-right-icon<?php echo $v['count'] > 0 ? '-cross' : '-tick'; ?>"></span>
			</section>
		</a>
	<?php endforeach; ?>

</div>

<h3 class="litespeed-title"><?php echo __( 'Database Table Engine Converter', 'litespeed-cache' ); ?></h3>

<div class="litespeed-panel-wrapper">

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col">#</th>
				<th scope="col"><?php echo __( 'Table', 'litespeed-cache' ); ?></th>
				<th scope="col"><?php echo __( 'Engine', 'litespeed-cache' ); ?></th>
				<th scope="col"><?php echo __( 'Tool', 'litespeed-cache' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$list = DB_Optm::cls()->list_myisam();
			if ( $list ) :
				foreach ( $list as $k => $v ) :
					?>
					<tr>
						<td><?php echo $k + 1; ?></td>
						<td><?php echo $v->TABLE_NAME; ?></td>
						<td><?php echo $v->ENGINE; ?></td>
						<td>
							<a href="<?php echo Utility::build_url( Router::ACTION_DB_OPTM, DB_Optm::TYPE_CONV_TB, false, false, array( 'tb' => $v->TABLE_NAME ) ); ?>">
								<?php echo __( 'Convert to InnoDB', 'litespeed-cache' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="4" class="litespeed-success litespeed-text-center">
						<?php echo __( 'We are good. No table uses MyISAM engine.', 'litespeed-cache' ); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

</div>

<style type="text/css">
	.litespeed-body .field-col {
		display: inline-block;
		vertical-align: top;
		margin-left: 20px;
		margin-right: 20px;
	}

	.litespeed-body .field-col:first-child {
		margin-left: 0;
	}
</style>

<h3 class="litespeed-title"><?php echo __( 'Database Summary', 'litespeed-cache' ); ?></h3>
<div>
	<div class="field-col">
		<p>
			Autoload size: <strong><?php echo Utility::real_size( $autoload_summary->autoload_size ); ?></strong></p>
		<p>Autoload entries: <strong><?php echo $autoload_summary->autload_entries; ?></strong></p>


	</div>

	<div class="field-col">
		<p>Autoload top list:</p>
		<table class="wp-list-table widefat striped litespeed-width-auto litespeed-table-compact">
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col"><?php echo __( 'Option Name', 'litespeed-cache' ); ?></th>
					<th scope="col"><?php echo __( 'Autoload', 'litespeed-cache' ); ?></th>
					<th scope="col"><?php echo __( 'Size', 'litespeed-cache' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $autoload_summary->autoload_toplist as $k => $v ) : ?>
					<tr>
						<td><?php echo $k + 1; ?></td>
						<td><?php echo $v->option_name; ?></td>
						<td><?php echo $v->autoload; ?></td>
						<td><?php echo $v->option_value_length; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	</div>
</div>