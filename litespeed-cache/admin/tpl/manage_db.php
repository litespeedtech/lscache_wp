<?php
if ( ! defined( 'WPINC' ) ) die ;

$_panels = array(
	'all' => array(
		'title'	=> __( 'Clean All', 'litespeed-cache' ),
		'desc'	=> '',
	),
	'revision' => array(
		'title'	=> __( 'Post Revisions', 'litespeed-cache' ),
		'desc'	=> __( 'Clean all post revisions', 'litespeed-cache' ),
	),
	'auto_draft' => array(
		'title'	=> __( 'Auto Drafts', 'litespeed-cache' ),
		'desc'	=> __( 'Clean all auto saved drafts', 'litespeed-cache' ),
	),
	'trash_post' => array(
		'title'	=> __( 'Trashed Posts', 'litespeed-cache' ),
		'desc'	=> __( 'Clean all trashed posts and pages', 'litespeed-cache' ),
	),
	'spam_comment' => array(
		'title'	=> __( 'Spam Comments', 'litespeed-cache' ),
		'desc'	=> __( 'Clean all spam comments', 'litespeed-cache' ),
	),
	'trash_comment' => array(
		'title'	=> __( 'Trashed Comments', 'litespeed-cache' ),
		'desc'	=> __( 'Clean all trashed comments', 'litespeed-cache' ),
	),
	'trackback-pingback' => array(
		'title'	=> __( 'Trackbacks/Pingbacks', 'litespeed-cache' ),
		'desc'	=> __( 'Clean all trackbacks and pingbacks', 'litespeed-cache' ),
	),
	'expired_transient' => array(
		'title'	=> __( 'Expired Transients', 'litespeed-cache' ),
		'desc'	=> __( 'Clean expired transient options', 'litespeed-cache' ),
	),
	'all_transients' => array(
		'title'	=> __( 'All Transients', 'litespeed-cache' ),
		'desc'	=> __( 'Clean all transient options', 'litespeed-cache' ),
	),
	'optimize_tables' => array(
		'title'	=> __( 'Optimize Tables', 'litespeed-cache' ),
		'desc'	=> __( 'Optimize all tables in your database', 'litespeed-cache' ),
	),
) ;

$total = 0 ;
foreach ( $_panels as $tag => $val ) {
	if ( $tag != 'all' ) {
		$_panels[ $tag ][ 'count' ] = LiteSpeed_Cache_Admin_Optimize::db_count( $tag ) ;
		if ( $tag != 'optimize_tables' ) {
			$total += $_panels[ $tag ][ 'count' ] ;
		}
	}
	$_panels[ $tag ][ 'link' ] = LiteSpeed_Cache_Admin_Optimize::generate_url( $tag ) ;
}

$_panels[ 'all' ][ 'count' ] = $total ;

?>

<h3 class="litespeed-title"><?php echo __('Database Optimizer', 'litespeed-cache'); ?></h3>

<div class="litespeed-panel-wrapper">

<?php foreach ( $_panels as $tag => $val ): ?>

	<a href="<?php echo $val[ 'link' ] ; ?>" class="litespeed-panel">
		<section class="litespeed-panel-wrapper-icon">
			<span class="litespeed-panel-icon-<?php echo $tag ; ?>"></span>
		</section>
		<section class="litespeed-panel-content">
			<h3>
				<?php echo $val[ 'title' ] ; ?>
				<span class="litespeed-panel-counter<?php if ( $val[ 'count' ] > 0 ) echo '-red' ; ?>">(<?php echo $val[ 'count' ] ; ?>)</span>
			</h3>
			<div class="litespeed-panel-para"><?php echo $val[ 'desc' ] ; ?></div>
		</section>
		<section class="litespeed-panel-wrapper-top-right">
			<span class="litespeed-panel-top-right-icon<?php echo $val[ 'count' ] > 0 ? '-cross' : '-tick' ; ?>"></span>
		</section>
	</a>
<?php endforeach; ?>

</div>