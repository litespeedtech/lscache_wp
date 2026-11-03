<?php
// phpcs:ignoreFile
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

		$this->_replacementHash = $this->_generate_hash();
		$this->_placeholders    = array();

		// Determine context before removing comments or invoking a script/style minifier.
		$html = \LiteSpeed\Utility::html_tokens( $this->_html, function ( $token ) {
			if ( ! empty( $token['opaque'] ) ) {
				return $this->_reservePlace( $token[0] );
			}
			if ( ! empty( $token['raw'] ) ) {
				$name = strtolower( $token['raw'] );
				// Unclosed blocks remain intact; adding a terminator would change the document.
				if ( ! empty( $token['close'] ) && 'script' === $name ) {
					return $this->_removeScriptCB( [ $token[0], '', $token['raw_attrs'] . '>', $token['body'], '' ] );
				}
				if ( ! empty( $token['close'] ) && 'style' === $name ) {
					return $this->_removeStyleCB( [ $token[0], $token['raw_attrs'] . '>', $token['body'] ] );
				}
				return $this->_reservePlace( $token[0] );
			}
			if ( ! empty( $token['tag'] ) && ! empty( $token['end'] ) ) {
				// Keep the tag visible to whitespace rules while protecting all attribute bytes.
				return '<' . $token['tag'] . $this->_reservePlace( $token['attrs'] ) . '>';
			}
			if ( '<!--' === substr( $token[0], 0, 4 ) && '-->' === substr( $token[0], -3 ) ) {
				return $this->_commentCB( [ $token[0], substr( $token[0], 4, -3 ) ] );
			}
			if ( preg_match( '#\A</[a-z][^\s/>]*\s*>\z#i', $token[0] ) ) {
				return $token[0];
			}
			return $this->_reservePlace( $token[0] );
		}, true );
		if ( null === $html ) {
			return $this->_html;
		}
		$this->_html = $html;

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
		// Restore once: inserted script/style content must not expand another token.
		$this->_html = strtr( $this->_html, $this->_placeholders );

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

		// A separator prevents removal from assembling a new tag across this boundary.
		return ' ';
	}

	protected function _reservePlace( $content ) {
		$placeholder                         = '%' . $this->_replacementHash . count( $this->_placeholders ) . '%';
		$this->_placeholders[ $placeholder ] = $content;

		return $placeholder;
	}

	/** Generate a request-private token that content cannot predict. */
	protected function _generate_hash() {
		return 'MINIFYHTML' . bin2hex( random_bytes( 16 ) );
	}

	protected $_isXhtml         = null;
	protected $_replacementHash = null;
	protected $_placeholders    = array();
	protected $_cssMinifier     = null;
	protected $_jsMinifier      = null;

	protected function _removeStyleCB( $m ) {
		$openStyle = "<style{$m[1]}";
		$css       = $m[2];
		// remove HTML comments
		$css = preg_replace( '/(?:^\\s*<!--|-->\\s*$)/', '', $css );

		// remove CDATA section markers
		if ( $this->_isXhtml ) {
			$css = $this->_removeCdata( $css );
		}

		// minify
		$minifier = $this->_cssMinifier
			? $this->_cssMinifier
			: 'trim';
		$css      = call_user_func( $minifier, $css );
		if ( false !== stripos( $css, '</style' ) ) {
			return $this->_reservePlace( $m[0] );
		}

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
		if ( $this->_isXhtml ) {
			$js = $this->_removeCdata( $js );
		}

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
		if ( false !== stripos( $js, '</script' ) ) {
			return $this->_reservePlace( $m[0] );
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
