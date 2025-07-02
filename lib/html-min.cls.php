<?php
/**
 * Compress HTML
 *
 * This is a heavy regex-based removal of whitespace, unnecessary comments and
 * tokens. IE conditional comments are preserved. There are also options to have
 * STYLE and SCRIPT blocks compressed by callback functions.
 *
 * A test suite is available.
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
namespace LiteSpeed\Lib;

defined( 'WPINC' ) || exit;

class HTML_MIN {

	/**
	 * @var string
	 */
	protected $_html = '';

	/**
	 * @var boolean
	 */
	protected $_jsCleanComments = true;
	protected $_skipComments    = array();

	/**
	 * "Minify" an HTML page
	 *
	 * @param string $html
	 *
	 * @param array  $options
	 *
	 * 'cssMinifier' : (optional) callback function to process content of STYLE
	 * elements.
	 *
	 * 'jsMinifier' : (optional) callback function to process content of SCRIPT
	 * elements. Note: the type attribute is ignored.
	 *
	 * 'xhtml' : (optional boolean) should content be treated as XHTML1.0? If
	 * unset, minify will sniff for an XHTML doctype.
	 *
	 * @return string
	 */
	public static function minify( $html, $options = array() ) {
		$min = new self( $html, $options );

		return $min->process();
	}

	/**
	 * Create a minifier object
	 *
	 * @param string $html
	 *
	 * @param array  $options
	 *
	 * 'cssMinifier' : (optional) callback function to process content of STYLE
	 * elements.
	 *
	 * 'jsMinifier' : (optional) callback function to process content of SCRIPT
	 * elements. Note: the type attribute is ignored.
	 *
	 * 'jsCleanComments' : (optional) whether to remove HTML comments beginning and end of script block
	 *
	 * 'xhtml' : (optional boolean) should content be treated as XHTML1.0? If
	 * unset, minify will sniff for an XHTML doctype.
	 */
	public function __construct( $html, $options = array() ) {
		$this->_html = str_replace( "\r\n", "\n", trim( $html ) );
		if ( isset( $options['xhtml'] ) ) {
			$this->_isXhtml = (bool) $options['xhtml'];
		}
		if ( isset( $options['cssMinifier'] ) ) {
			$this->_cssMinifier = $options['cssMinifier'];
		}
		if ( isset( $options['jsMinifier'] ) ) {
			$this->_jsMinifier = $options['jsMinifier'];
		}
		if ( isset( $options['jsCleanComments'] ) ) {
			$this->_jsCleanComments = (bool) $options['jsCleanComments'];
		}
		if ( isset( $options['skipComments'] ) ) {
			$this->_skipComments = $options['skipComments'];
		}
	}

	/**
	 * Minify the markeup given in the constructor
	 *
	 * @return string
	 */
	public function process() {
		if ( $this->_isXhtml === null ) {
			$this->_isXhtml = ( false !== strpos( $this->_html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML' ) );
		}

		$this->_replacementHash = 'MINIFYHTML' . md5( $_SERVER['REQUEST_TIME'] );
		$this->_placeholders    = array();

		// replace SCRIPTs (and minify) with placeholders
		$this->_html = preg_replace_callback(
			'/(\\s*)<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/i',
			array( $this, '_removeScriptCB' ),
			$this->_html
		);

		// replace STYLEs (and minify) with placeholders
		$this->_html = preg_replace_callback(
			'/\\s*<style(\\b[^>]*>)([\\s\\S]*?)<\\/style>\\s*/i',
			array( $this, '_removeStyleCB' ),
			$this->_html
		);

		// remove HTML comments (not containing IE conditional comments).
		$this->_html = preg_replace_callback(
			'/<!--([\\s\\S]*?)-->/',
			array( $this, '_commentCB' ),
			$this->_html
		);

		// replace PREs with placeholders
		$this->_html = preg_replace_callback(
			'/\\s*<pre(\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i',
			array( $this, '_removePreCB' ),
			$this->_html
		);

		// replace TEXTAREAs with placeholders
		$this->_html = preg_replace_callback(
			'/\\s*<textarea(\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i',
			array( $this, '_removeTextareaCB' ),
			$this->_html
		);

		// trim each line.
		// @todo take into account attribute values that span multiple lines.
		$this->_html = preg_replace( '/^\\s+|\\s+$/m', '', $this->_html );

		// remove ws around block/undisplayed elements
		$this->_html = preg_replace(
			'/\\s+(<\\/?(?:area|article|aside|base(?:font)?|blockquote|body'
			. '|canvas|caption|center|col(?:group)?|dd|dir|div|dl|dt|fieldset|figcaption|figure|footer|form'
			. '|frame(?:set)?|h[1-6]|head|header|hgroup|hr|html|legend|li|link|main|map|menu|meta|nav'
			. '|ol|opt(?:group|ion)|output|p|param|section|t(?:able|body|head|d|h||r|foot|itle)'
			. '|ul|video)\\b[^>]*>)/i',
			'$1',
			$this->_html
		);

		// remove ws outside of all elements
		$this->_html = preg_replace(
			'/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</',
			'>$1$2$3<',
			$this->_html
		);

		// use newlines before 1st attribute in open tags (to limit line lengths)
		// $this->_html = preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/i', "$1\n$2", $this->_html);

		// fill placeholders
		$this->_html = str_replace(
			array_keys( $this->_placeholders ),
			array_values( $this->_placeholders ),
			$this->_html
		);
		// issue 229: multi-pass to catch scripts that didn't get replaced in textareas
		$this->_html = str_replace(
			array_keys( $this->_placeholders ),
			array_values( $this->_placeholders ),
			$this->_html
		);

		return $this->_html;
	}

	/**
	 * From LSCWP 6.2: Changed the function to test for special comments that will be skipped. See: https://github.com/litespeedtech/lscache_wp/pull/622
	 */
	protected function _commentCB( $m ) {
		// If is IE conditional comment return it.
		if ( 0 === strpos( $m[1], '[' ) || false !== strpos( $m[1], '<![' ) ) {
			return $m[0];
		}

		// Check if comment text is present in Page Optimization -> HTML Settings -> HTML Keep comments
		if ( count( $this->_skipComments ) > 0 ) {
			foreach ( $this->_skipComments as $comment ) {
				if ( $comment && strpos( $m[1], $comment ) !== false ) {
					return $m[0];
				}
			}
		}

		// Comment can be removed.
		return '';
	}

	protected function _reservePlace( $content ) {
		$placeholder                         = '%' . $this->_replacementHash . count( $this->_placeholders ) . '%';
		$this->_placeholders[ $placeholder ] = $content;

		return $placeholder;
	}

	protected $_isXhtml         = null;
	protected $_replacementHash = null;
	protected $_placeholders    = array();
	protected $_cssMinifier     = null;
	protected $_jsMinifier      = null;

	protected function _removePreCB( $m ) {
		return $this->_reservePlace( "<pre{$m[1]}" );
	}

	protected function _removeTextareaCB( $m ) {
		return $this->_reservePlace( "<textarea{$m[1]}" );
	}

	protected function _removeStyleCB( $m ) {
		$openStyle = "<style{$m[1]}";
		$css       = $m[2];
		// remove HTML comments
		$css = preg_replace( '/(?:^\\s*<!--|-->\\s*$)/', '', $css );

		// remove CDATA section markers
		$css = $this->_removeCdata( $css );

		// minify
		$minifier = $this->_cssMinifier
			? $this->_cssMinifier
			: 'trim';
		$css      = call_user_func( $minifier, $css );

		return $this->_reservePlace(
			$this->_needsCdata( $css )
			? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
			: "{$openStyle}{$css}</style>"
		);
	}

	protected function _removeScriptCB( $m ) {
		$openScript = "<script{$m[2]}";
		$js         = $m[3];

		// whitespace surrounding? preserve at least one space
		$ws1 = ( $m[1] === '' ) ? '' : ' ';
		$ws2 = ( $m[4] === '' ) ? '' : ' ';

		// remove HTML comments (and ending "//" if present)
		if ( $this->_jsCleanComments ) {
			$js = preg_replace( '/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js );
		}

		// remove CDATA section markers
		$js = $this->_removeCdata( $js );

		// minify
		/**
		 * Added 2nd param by LiteSpeed
		 *
		 * @since  2.2.3
		 */
		if ( $this->_jsMinifier ) {
			$js = call_user_func( $this->_jsMinifier, $js, trim( $m[2] ) );
		} else {
			$js = trim( $js );
		}

		return $this->_reservePlace(
			$this->_needsCdata( $js )
			? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}"
			: "{$ws1}{$openScript}{$js}</script>{$ws2}"
		);
	}

	protected function _removeCdata( $str ) {
		return ( false !== strpos( $str, '<![CDATA[' ) )
			? str_replace( array( '<![CDATA[', ']]>' ), '', $str )
			: $str;
	}

	protected function _needsCdata( $str ) {
		return ( $this->_isXhtml && preg_match( '/(?:[<&]|\\-\\-|\\]\\]>)/', $str ) );
	}
}
