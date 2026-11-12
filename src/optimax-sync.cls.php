<?php
/**
 * Tells whether a page changed since its OptimaX build.
 *
 * @since   8.0
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Fingerprint of a rendered page: its design (tags, attributes, stylesheets) and content items, read from the raw HTML.
 *
 * @since 8.0
 */
class Optimax_Sync {

	/**
	 * A converted copy's suffix on a convertible original: `.jpg.webp`, `.png.avif`.
	 *
	 * @var string
	 */
	const NEXTGEN_SUFFIX = '\.(jpe?g|png|gif)\.(webp|avif)';

	const TYPE_TEXT = 't';
	const TYPE_HREF = 'h';
	const TYPE_IMG  = 'i';
	const TYPE_META = 'm';
	const TYPE_ATTR = 'd';
	const TYPE_JSON = 'j';

	/**
	 * Fingerprint format. A stored fingerprint of another format is recorded again, never compared.
	 */
	const VERSION = 12;

	/**
	 * A page-builder stylesheet (Elementor's `post-*.css`, Divi's `et-cache` files): the builder rewrites it on a save.
	 */
	const RE_BUILDER_SHEET = '~/elementor/css/post-|/et-cache/~';

	/**
	 * A design line of a local stylesheet (sheet_id()): group 1 its URL path, group 2 its file's md5.
	 */
	const RE_SHEET_LINE = '~ href=(\S+)#([0-9a-f]{32})$~';

	/**
	 * A class in a selector: `.name`, with escapes (`\:`, a hex `\31 `) and non-ASCII letters.
	 */
	const RE_CLASS = '~\.((?:\\\\[0-9a-fA-F]{1,6}\s?|\\\\.|[\w-]|[^\x00-\x7F])+)~';

	/**
	 * Byte limits: one builder stylesheet copy, all copies of one render, a build's own CSS. Past one, the CSS is not kept or counts as unknown.
	 */
	const SHEET_COPY_MAX   = 524288;
	const SHEET_COPIES_MAX = 1048576;
	const BUILD_CSS_MAX    = 4194304;

	/**
	 * A price in JSON-LD (schema.org Offer / AggregateOffer): key, then its raw JSON value.
	 */
	const RE_JSON_PRICE = '~"(price|lowPrice|highPrice)"\s*:\s*("(?:[^"\\\\]|\\\\.)*"|-?[0-9][0-9.eE+-]*)~';

	/**
	 * Why the last patch() could not place a change, for the debug log.
	 *
	 * @var string
	 */
	public static $why = '';

	/**
	 * One token: a comment, a declaration, a raw-text element (group 1) with its content (group 2), or a tag. Quoted attribute values may hold `>`.
	 */
	const RE_TOKEN = '~<!--.*?-->|<!\[CDATA\[.*?\]\]>|<![^>]*>|<\?.*?\?>|<(script|style|noscript|template|textarea|title)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>(.*?)</\1\s*>|</?[a-zA-Z][^\s/>]*(?:[^>"\']|"[^"]*"|\'[^\']*\')*>~is';

	/**
	 * One attribute of a tag: name, then its value quoted or bare.
	 */
	const RE_ATTR = '~([^\s"\'<>/=]+)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?~';

	/**
	 * Attributes an `<img>` may carry its URL in, real or lazy.
	 */
	const IMG_URL_ATTRS = [ 'src', 'data-src', 'data-lazy-src', 'data-original' ];

	/**
	 * Attributes holding an element id, or pointing at one: themes number their elements per render, so the value is no design.
	 */
	const ID_ATTRS = [ 'id', 'for', 'aria-controls', 'aria-labelledby', 'aria-describedby', 'aria-owns', 'headers', 'form', 'list' ];

	/**
	 * `<body>` classes a theme (Divi) derives from the visitor's user agent, not from the page.
	 */
	const UA_BODY_CLASSES = [ 'osx', 'linux', 'windows' ];

	/**
	 * The fingerprint of a rendered page.
	 *
	 * @since 8.0
	 * @param string $html Page HTML.
	 * @return array|null `design`, `lines` (a hash per design line), `skel`, `items`; null when the HTML cannot be read.
	 */
	public static function fingerprint( $html ) {
		$tokens = self::tokenize( $html );
		if ( null === $tokens ) {
			return null;
		}

		// A `<style>` rule naming a class the page does not use styles nothing here.
		$classes = self::page_classes( $tokens );
		$skel    = [];
		$items   = [];
		foreach ( $tokens as $tok ) {
			if ( 'tag' !== $tok['kind'] ) {
				$text = 'text' === $tok['kind'] ? self::norm_text( $tok['raw'] ) : '';
				if ( '' !== $text ) {
					$skel[]  = 'T';
					$items[] = [ self::TYPE_TEXT, $text ];
				}
				continue;
			}
			$name = $tok['name'];
			if ( '/' === $name[0] ) {
				$skel[] = $name;
				continue;
			}
			$attrs = self::attrs( $tok['raw'] );
			if ( 'img' === $name ) {
				$skel[]  = 'img';
				$items[] = [ self::TYPE_IMG, $tok['raw'], self::img_key( $attrs ) ];
				continue;
			}
			// LiteSpeed's own `litespeed-dummy.css` placeholder is no part of the design: its `?ver=` follows WordPress updates.
			if ( 'link' === $name && self::is_sheet( $attrs, false ) && '/assets/css/litespeed-dummy.css' === substr( (string) wp_parse_url( $attrs['href'], PHP_URL_PATH ), -31 ) ) {
				continue;
			}
			if ( 'body' === $name && isset( $attrs['class'] ) ) {
				$attrs['class'] = implode( ' ', array_diff( preg_split( '~\s+~', trim( $attrs['class'] ), -1, PREG_SPLIT_NO_EMPTY ), self::UA_BODY_CLASSES ) );
			}

			// A link target and a meta content are content, not design. An in-page anchor names an element id: neither.
			$meta = 'meta' === $name ? self::meta_key( $attrs ) : '';
			if ( 'a' === $name && isset( $attrs['href'] ) ) {
				if ( '#' !== substr( ltrim( $attrs['href'] ), 0, 1 ) ) {
					$items[] = [ self::TYPE_HREF, $attrs['href'] ];
				}
				unset( $attrs['href'] );
			} elseif ( $meta && isset( $attrs['content'] ) ) {
				$items[] = [ self::TYPE_META, $attrs['content'], $meta ];
				unset( $attrs['content'] );
			}
			// A stylesheet counts by the local file it loads: a cache-busting `?ver=` bump over the same file is no new design.
			$sheet = '';
			if ( 'link' === $name && self::is_sheet( $attrs ) ) {
				$sheet = ' href=' . self::sheet_id( $attrs['href'] );
				unset( $attrs['href'] );
			}

			$line = $name;
			foreach ( $attrs as $attr => $val ) {
				$line .= ' ' . $attr;
				if ( self::is_content_attr( $attr ) ) {
					$items[] = [ self::TYPE_ATTR, $val, $attr ];
				} elseif ( 'value' !== $attr && false === strpos( $attr, 'nonce' ) && ! in_array( $attr, self::ID_ATTRS, true ) ) {
					// A `value`, a nonce and an element id change between renders of the same page: only the name is design.
					$line .= '=' . self::mask( $val );
				}
			}
			$line .= $sheet;
			if ( isset( $tok['inner'] ) ) {
				if ( 'title' === $name ) {
					$text = self::norm_text( $tok['inner'] );
					if ( '' !== $text ) {
						$line   .= ' T';
						$items[] = [ self::TYPE_TEXT, $text ];
					}
				} elseif ( 'style' === $name ) {
					$line .= ' ' . md5( self::mask( self::used_css( $tok['inner'], $classes ) ) );
				} elseif ( 'script' === $name && 'application/ld+json' === strtolower( trim( $attrs['type'] ?? '' ) ) ) {
					// Structured data repeats the price shown on the page: Google reads it from here.
					foreach ( self::json_prices( $tok['inner'], 0 ) as $price ) {
						$items[] = [ self::TYPE_JSON, $price['raw'], $price['key'] ];
					}
				}
			}
			$skel[] = $line;
		}

		$lines = [];
		foreach ( $skel as $line ) {
			$lines[] = substr( md5( $line ), 0, 8 );
		}
		return [ 'design' => md5( implode( "\n", $skel ) ), 'lines' => $lines, 'skel' => $skel, 'items' => $items ];
	}

	/**
	 * The classes a page's elements carry.
	 *
	 * @since 8.0
	 * @param array $tokens tokenize() of the page.
	 * @return array Classes, as keys.
	 */
	private static function page_classes( $tokens ) {
		$classes = [];
		foreach ( $tokens as $tok ) {
			if ( 'tag' === $tok['kind'] && false !== stripos( $tok['raw'], 'class' ) ) {
				foreach ( preg_split( '~\s+~', self::attrs( $tok['raw'] )['class'] ?? '', -1, PREG_SPLIT_NO_EMPTY ) as $class ) {
					$classes[ $class ] = true;
				}
			}
		}
		return $classes;
	}

	/**
	 * The first content item that changed between two renders of the same design; per-render values do not count (sig()).
	 *
	 * @since 8.0
	 * @param array $old Items of the render the build matches.
	 * @param array $now Items of this render.
	 * @return string|null The change, for the debug log; null when the content is the same.
	 */
	public static function changed_item( $old, $now ) {
		if ( count( $old ) !== count( $now ) ) {
			return count( $old ) . ' items, now ' . count( $now );
		}
		foreach ( $old as $i => $item ) {
			if ( $item !== $now[ $i ] && self::sig( $item ) !== self::sig( $now[ $i ] ) ) {
				return $item[0] . ' ' . substr( (string) $item[1], 0, 80 ) . ' => ' . substr( (string) $now[ $i ][1], 0, 80 );
			}
		}
		return null;
	}

	/**
	 * A signature of a render's content: two renders showing the same change sign the same.
	 *
	 * Serialized, not JSON-encoded: invalid UTF-8 would fail the encode, and every failed encode would sign the same.
	 *
	 * @since 8.0
	 * @param array $items Items of a render.
	 * @return string
	 */
	public static function content_sig( $items ) {
		return md5( serialize( array_map( [ __CLASS__, 'sig' ], $items ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- hashed, never unserialized.
	}

	/**
	 * An item as compared and signed: without per-render values (ids, `uniqid()` tokens, unset JSON keys, next-gen suffixes).
	 *
	 * @since 8.0
	 * @param array $item Item.
	 * @return array
	 */
	private static function sig( $item ) {
		list( $type, $val ) = $item;
		if ( self::TYPE_ATTR === $type ) {
			// A JSON object or array is compared by what it sets (json_set()), any other value as text.
			$val  = (string) $val;
			$data = in_array( substr( ltrim( $val ), 0, 1 ), [ '{', '[' ], true ) ? json_decode( $val, true ) : null;
			return [ $type, self::mask( is_array( $data ) ? (string) wp_json_encode( self::json_set( $data ) ) : $val ), $item[2] ];
		}
		if ( self::TYPE_IMG !== $type ) {
			return $item;
		}

		$sig = '';
		foreach ( self::attrs( $val ) as $name => $attr ) {
			if ( in_array( $name, self::IMG_URL_ATTRS, true ) ) {
				$attr = self::nextgen_neutral( $attr );
			} elseif ( 'srcset' === $name || 'data-srcset' === $name ) {
				// Candidates are trimmed and rejoined with `, `: whitespace and separator differences cancel out.
				$set = [];
				foreach ( explode( ',', $attr ) as $candidate ) {
					$parts = preg_split( '~\s+~', trim( $candidate ), 2 );
					$set[] = self::nextgen_neutral( $parts[0] ) . ( isset( $parts[1] ) ? ' ' . $parts[1] : '' );
				}
				$attr = implode( ', ', $set );
			}
			$sig .= ' ' . $name . ( in_array( $name, self::ID_ATTRS, true ) ? '' : '=' . self::mask( $attr ) );
		}
		return [ $type, $sig ];
	}

	/**
	 * Write the content changes between two renders into the stored OptimaX HTML, each found there by its old value.
	 *
	 * @since 8.0
	 * @param string   $html    Stored OptimaX HTML.
	 * @param array    $old     Items of the render the build matches.
	 * @param array    $now     Items of this render; same design, so same length and types.
	 * @param callable $img_tag Maps a fresh `<img>` tag to the one to store (next-gen links).
	 * @return string|false Patched HTML, or false when a change cannot be placed (`$why`).
	 */
	public static function patch( $html, $old, $now, $img_tag ) {
		self::$why = '';
		$tokens    = count( $old ) === count( $now ) ? self::tokenize( $html ) : null;
		if ( null === $tokens ) {
			return false;
		}

		// Where each value sits in the OptimaX HTML, in page order.
		$found      = [];
		$preloaded  = [];
		$in_picture = 0;
		foreach ( $tokens as $tok ) {
			if ( 'text' === $tok['kind'] ) {
				$found[ self::TYPE_TEXT ][ self::norm_text( $tok['raw'] ) ][] = $tok;
			}
			// Open tags only: a token that is no tag has no name.
			$name        = $tok['name'] ?? '/';
			$in_picture += 'picture' === $name ? 1 : ( '/picture' === $name && $in_picture ? -1 : 0 );
			if ( '/' === $name[0] ) {
				continue;
			}
			if ( 'title' === $name && isset( $tok['inner'] ) ) {
				$found[ self::TYPE_TEXT ][ self::norm_text( $tok['inner'] ) ][] = [ 'raw' => $tok['inner'], 'offset' => $tok['inner_offset'] ];
				continue;
			}
			$attrs = self::attrs( $tok['raw'] );
			if ( 'img' === $name ) {
				$found[ self::TYPE_IMG ][ self::img_key( $attrs ) ][] = $tok + [ 'in_picture' => $in_picture > 0 ];
				continue;
			}
			if ( 'link' === $name && 'image' === strtolower( trim( $attrs['as'] ?? '' ) ) && false !== stripos( $attrs['rel'] ?? '', 'preload' ) ) {
				foreach ( [ 'href', 'imagesrcset' ] as $attr ) {
					foreach ( empty( $attrs[ $attr ] ) ? [] : self::img_urls( $attrs[ $attr ] ) as $url ) {
						$preloaded[ self::url_path( $url ) ] = true;
					}
				}
			}
			if ( isset( $tok['inner'] ) && 'script' === $name && 'application/ld+json' === strtolower( trim( $attrs['type'] ?? '' ) ) ) {
				foreach ( self::json_prices( $tok['inner'], $tok['inner_offset'] ) as $price ) {
					$found[ self::TYPE_JSON ][ $price['key'] ][] = $price;
				}
				continue;
			}
			$meta = 'meta' === $name ? self::meta_key( $attrs ) : '';
			if ( 'a' === $name && isset( $attrs['href'] ) && '#' !== substr( ltrim( $attrs['href'] ), 0, 1 ) ) {
				$found[ self::TYPE_HREF ][ $attrs['href'] ][] = $tok;
			} elseif ( $meta && isset( $attrs['content'] ) ) {
				$found[ self::TYPE_META ][ $meta ][] = $tok;
			}
			foreach ( $attrs as $attr => $val ) {
				if ( self::is_content_attr( $attr ) ) {
					$found[ self::TYPE_ATTR ][ self::locate_key( [ self::TYPE_ATTR, $val, $attr ] ) ][] = $tok;
				}
			}
		}

		// Where each value sits in the old render: the items holding it, in page order.
		$seen = [];
		foreach ( $old as $i => $item ) {
			$seen[ $item[0] ][ self::locate_key( $item ) ][] = $i;
		}

		$edits   = [];
		$carried = [];
		foreach ( $old as $i => $item ) {
			if ( $item === $now[ $i ] || self::sig( $item ) === self::sig( $now[ $i ] ) ) {
				continue;
			}
			list( $type, $val ) = $now[ $i ];
			if ( $item[0] !== $type ) {
				return false;
			}
			$key   = self::locate_key( $item );
			$cands = $found[ $type ][ $key ] ?? [];
			$olds  = $seen[ $type ][ $key ];
			if ( count( $cands ) !== count( $olds ) ) {
				// A meta tag or JSON-LD price OptimaX dropped is not on the page: skipped.
				if ( self::TYPE_META === $type || self::TYPE_JSON === $type ) {
					continue;
				}
				self::$why = $type . ' ' . substr( $key, 0, 120 ) . ': ' . count( $cands ) . ' in OptimaX HTML, ' . count( $olds ) . ' in the page';
				// For an attribute, where the old and new values part.
				if ( self::TYPE_ATTR === $type ) {
					$at         = strspn( $item[1] ^ $val, "\0" );
					self::$why .= ' [old md5=' . md5( $item[1] ) . ' new md5=' . md5( $val ) . ' differ at byte ' . $at . ' of ' . strlen( $item[1] ) . '/' . strlen( $val );
					foreach ( [ 'old' => $item[1], 'new' => $val ] as $side => $side_val ) {
						self::$why .= ' ' . $side . '="' . preg_replace( '~[^\x20-\x7e]~', '?', self::mask( substr( $side_val, max( 0, $at - 20 ), 40 ) ) ) . '"';
					}
					self::$why .= ']';
				}
				return false;
			}

			// The same occurrence as in the old render: the 2nd "Read more" stays the 2nd.
			$tok = $cands[ array_search( $i, $olds, true ) ];
			if ( self::TYPE_HREF === $type || self::TYPE_META === $type || self::TYPE_ATTR === $type ) {
				// A tag may take several attribute edits; a text, a price and an image are one item each.
				$attr = self::TYPE_ATTR === $type ? $item[2] : ( self::TYPE_HREF === $type ? 'href' : 'content' );
				$repl = self::set_attr( $edits[ $tok['offset'] ][1] ?? $tok['raw'], $attr, $val );
			} elseif ( self::TYPE_TEXT === $type ) {
				// Escaped as OptimaX's serializer writes it, between the whitespace the old text had.
				preg_match( '~^(\s*).*?(\s*)$~s', $tok['raw'], $m );
				$repl = $m[1] . str_replace( "\xc2\xa0", '&nbsp;', htmlspecialchars( $val, ENT_NOQUOTES, 'UTF-8' ) ) . $m[2];
			} else {
				// A price is the raw JSON value, quotes included: a number stays a number.
				$repl = self::TYPE_JSON === $type ? $val : self::patch_img( $tok['raw'], call_user_func( $img_tag, $val ), $item[1], $val, $preloaded, $tok['in_picture'], $carried );
			}
			if ( ! is_string( $repl ) ) {
				return false;
			}
			$edits[ $tok['offset'] ] = [ strlen( $tok['raw'] ), $repl ];
		}

		// A carried `<img>` text attribute is no change of the page's look unless a rule of the build's own CSS selects on it.
		$css = $carried ? self::build_css( $html, true ) : '';
		foreach ( array_keys( $carried ) as $name ) {
			if ( null === $css || preg_match( '~\[\s*(?:[\w*-]*\|)?' . preg_quote( $name, '~' ) . '(?![\w-])~i', $css ) ) {
				self::$why = 'img ' . $name . ( null === $css ? ' changed, and the build CSS cannot be read' : ' is selected on by the build CSS' );
				return false;
			}
		}

		krsort( $edits );
		foreach ( $edits as $offset => $edit ) {
			$html = substr_replace( $html, $edit[1], $offset, $edit[0] );
		}
		return $html;
	}

	/**
	 * The page-builder stylesheets whose change made a design line differ (instrumentation only).
	 *
	 * @since 8.0
	 * @param array $old_lines Line hashes of the stored fingerprint.
	 * @param array $now       fingerprint() of this render.
	 * @return array One note per such stylesheet, at most five: its new id, and its file's size and mtime.
	 */
	public static function changed_sheets( $old_lines, $now ) {
		$notes = [];
		foreach ( $now['skel'] as $i => $line ) {
			$was = $old_lines[ $i ] ?? 'none';
			if ( $was === $now['lines'][ $i ] || ! preg_match( self::RE_SHEET_LINE, $line, $m ) || ! preg_match( self::RE_BUILDER_SHEET, $m[1] ) ) {
				continue;
			}
			$file    = Utility::is_internal_file( $m[1] );
			$notes[] = 'line ' . $i . ' ' . $m[1] . ' old line hash ' . $was . ' new id ' . $m[2]
				. ( $file ? ' size ' . (int) @filesize( $file[0] ) . ' mtime ' . (int) @filemtime( $file[0] ) : ' file not found' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( count( $notes ) >= 5 ) {
				break;
			}
		}
		return $notes;
	}

	/**
	 * OptimaX's own `<img>` with this render's image URLs and changed text attributes (`alt`, `title`, an ARIA label); the rest stays.
	 *
	 * @since 8.0
	 * @param string $ox         OptimaX HTML's `<img>` tag.
	 * @param string $fresh      This render's `<img>` tag, next-gen links applied.
	 * @param string $old_tag    The `<img>` tag in the render the build matches.
	 * @param string $new_tag    The `<img>` tag in this render.
	 * @param array  $preloaded  Paths OptimaX preloads as images, as keys.
	 * @param bool   $in_picture Whether the tag sits inside `<picture>`.
	 * @param array  $carried    Text attributes carried, as keys: added to, for patch() to check against the build's CSS.
	 * @return string|false False, with `$why`: inside `<picture>`, the LCP image (preloaded, `fetchpriority=high`), any other change.
	 */
	private static function patch_img( $ox, $fresh, $old_tag, $new_tag, $preloaded, $in_picture, &$carried ) {
		$attrs = self::attrs( $ox );
		if ( $in_picture || 'high' === strtolower( trim( $attrs['fetchpriority'] ?? '' ) ) ) {
			self::$why = $in_picture ? 'img inside <picture>' : 'img with fetchpriority=high';
			return false;
		}

		$url_attrs = array_merge( self::IMG_URL_ATTRS, [ 'srcset', 'data-srcset' ] );
		$was       = self::attrs( $old_tag );
		$is        = self::attrs( $new_tag );
		$text      = [];
		// An empty value or a state token is what a CSS rule selects on (`img[alt=""]`): no free text.
		$state = [ '', 'true', 'false', 'mixed', 'undefined' ];
		foreach ( array_keys( $was + $is ) as $name ) {
			// No change: a URL (swapped below), an id-like attribute still there (it differs on every render), the same value.
			$both = isset( $was[ $name ], $is[ $name ] );
			if ( in_array( $name, $url_attrs, true ) || ( $both && ( in_array( $name, self::ID_ATTRS, true ) || self::mask( $was[ $name ] ) === self::mask( $is[ $name ] ) ) ) ) {
				continue;
			}
			// Carried: free text on both sides. Any other change (width, class, an attribute added or removed) goes to QUIC.cloud.
			if ( ! $both || ! in_array( $name, [ 'alt', 'title', 'aria-label', 'aria-description', 'aria-roledescription' ], true )
				|| in_array( strtolower( trim( $was[ $name ] ) ), $state, true ) || in_array( strtolower( trim( $is[ $name ] ) ), $state, true ) ) {
				self::$why = 'img changed besides its URLs: ' . $name;
				return false;
			}
			$text[ $name ] = $is[ $name ];
		}

		$new = self::attrs( $fresh );
		foreach ( $url_attrs as $name ) {
			if ( empty( $attrs[ $name ] ) || 0 === strpos( $attrs[ $name ], 'data:' ) ) {
				continue;
			}
			foreach ( self::img_urls( $attrs[ $name ] ) as $url ) {
				if ( isset( $preloaded[ self::url_path( $url ) ] ) ) {
					self::$why = 'img is preloaded';
					return false;
				}
			}
			// Without a counterpart in the render, OptimaX's attribute would keep linking the old image.
			if ( empty( $new[ $name ] ) || 0 === strpos( $new[ $name ], 'data:' ) ) {
				self::$why = 'img has ' . $name . ' in OptimaX HTML only';
				return false;
			}
			$ox = self::set_attr( $ox, $name, $new[ $name ] );
			if ( false === $ox ) {
				return false;
			}
		}

		foreach ( $text as $name => $val ) {
			$ox = self::set_attr( $ox, $name, $val );
			if ( false === $ox ) {
				self::$why = 'img has no ' . $name . ' in OptimaX HTML';
				return false;
			}
			$carried[ $name ] = true;
		}
		return $ox;
	}

	/**
	 * The URLs in an image attribute: the value itself, or each candidate of a srcset.
	 *
	 * @since 8.0
	 * @param string $val Attribute value.
	 * @return array
	 */
	private static function img_urls( $val ) {
		$urls = [];
		foreach ( explode( ',', $val ) as $candidate ) {
			$parts = preg_split( '~\s+~', trim( $candidate ) );
			if ( '' !== $parts[0] ) {
				$urls[] = $parts[0];
			}
		}
		return $urls;
	}

	/**
	 * A URL's path, with the next-gen suffix OptimaX appends left off.
	 *
	 * @since 8.0
	 * @param string $url URL.
	 * @return string
	 */
	private static function url_path( $url ) {
		return preg_replace( '~\.(webp|avif)$~i', '', (string) wp_parse_url( $url, PHP_URL_PATH ) );
	}

	/**
	 * Split HTML into tokens, with their byte offsets.
	 *
	 * @since 8.0
	 * @param string $html HTML.
	 * @return array|null List of `kind` (text|tag|other), `raw`, `offset`, and for a tag `name` (`/name` when closing)
	 *                    plus `inner`/`inner_offset` for a raw-text element. Null when the pattern gives up on the input.
	 */
	public static function tokenize( $html ) {
		$html = (string) $html;
		if ( false === preg_match_all( self::RE_TOKEN, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			return null;
		}

		$tokens = [];
		$pos    = 0;
		foreach ( $matches as $m ) {
			list( $raw, $offset ) = $m[0];
			if ( $offset > $pos ) {
				$tokens[] = [ 'kind' => 'text', 'raw' => substr( $html, $pos, $offset - $pos ), 'offset' => $pos ];
			}
			$pos = $offset + strlen( $raw );
			$tag = preg_match( '~^<(/?[a-zA-Z][^\s/>]*)~', $raw, $name );
			$tok = [ 'kind' => $tag ? 'tag' : 'other', 'raw' => $raw, 'offset' => $offset ] + ( $tag ? [ 'name' => strtolower( $name[1] ) ] : [] );
			if ( isset( $m[2] ) && '' !== $m[1][0] ) {
				// A raw-text element: its open tag is the token, its content kept apart.
				$tok = array_merge( $tok, [ 'raw' => substr( $raw, 0, $m[2][1] - $offset ), 'inner' => $m[2][0], 'inner_offset' => $m[2][1] ] );
			}
			$tokens[] = $tok;
		}
		if ( $pos < strlen( $html ) ) {
			$tokens[] = [ 'kind' => 'text', 'raw' => substr( $html, $pos ), 'offset' => $pos ];
		}
		return $tokens;
	}

	/**
	 * A tag's attributes, names lowercased, values decoded. The first of a repeated name wins, as in browsers.
	 *
	 * @since 8.0
	 * @param string $tag Open tag.
	 * @return array
	 */
	public static function attrs( $tag ) {
		$attrs = [];
		foreach ( self::raw_attrs( $tag ) as list( $name, $val ) ) {
			$attrs += [ $name => self::attr_val( $val ) ];
		}
		return $attrs;
	}

	/**
	 * Text as both renders and OptimaX's serializer agree on it: decoded, with runs of whitespace as one space.
	 *
	 * @since 8.0
	 * @param string $raw Raw text.
	 * @return string
	 */
	public static function norm_text( $raw ) {
		$text = html_entity_decode( (string) $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( preg_replace( '~[ \t\n\r\f]+~', ' ', $text ), " \t\n\r\f" );
	}

	/**
	 * A tag with one attribute's value replaced.
	 *
	 * Walks the real attributes, as attrs() reads them: `src=` inside another attribute's quoted value is no attribute.
	 *
	 * @since 8.0
	 * @param string $tag  Open tag.
	 * @param string $name Attribute name.
	 * @param string $val  Decoded value.
	 * @return string|false False when the tag has no such attribute.
	 */
	public static function set_attr( $tag, $name, $val ) {
		if ( ! preg_match( '~^<[^\s/>]+~', $tag, $start ) ) {
			return false;
		}
		preg_match_all( self::RE_ATTR, $tag, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, strlen( $start[0] ) );
		foreach ( $matches as $m ) {
			if ( strtolower( $m[1][0] ) === strtolower( $name ) ) {
				return isset( $m[2] ) && $m[2][1] >= 0 ? substr_replace( $tag, '"' . str_replace( [ '&', '"', "\xc2\xa0" ], [ '&amp;', '&quot;', '&nbsp;' ], $val ) . '"', $m[2][1], strlen( $m[2][0] ) ) : false;
			}
		}
		return false;
	}

	/**
	 * The URL an image is found by: its first real or lazy URL that is not inline data, without its query and next-gen suffix.
	 *
	 * @since 8.0
	 * @param array $attrs attrs() of the `<img>`.
	 * @return string
	 */
	public static function img_key( $attrs ) {
		foreach ( self::IMG_URL_ATTRS as $name ) {
			if ( ! empty( $attrs[ $name ] ) && 0 !== strpos( $attrs[ $name ], 'data:' ) ) {
				return preg_replace( '~\.(webp|avif)$~i', '', preg_replace( '~[?#].*$~s', '', trim( $attrs[ $name ] ) ) );
			}
		}
		return '';
	}

	/**
	 * What tells one meta tag from another: its name, property or itemprop.
	 *
	 * @since 8.0
	 * @param array $attrs attrs() of the `<meta>`.
	 * @return string '' for a meta tag without one (charset, http-equiv).
	 */
	public static function meta_key( $attrs ) {
		foreach ( [ 'name', 'property', 'itemprop' ] as $name ) {
			if ( ! empty( $attrs[ $name ] ) ) {
				return $name . '=' . strtolower( $attrs[ $name ] );
			}
		}
		return '';
	}

	/**
	 * Mask `uniqid()` tokens (13 hex digits), which change on every render.
	 *
	 * @since 8.0
	 * @param string $val Attribute value or inline CSS.
	 * @return string
	 */
	private static function mask( $val ) {
		return preg_replace( '~(?<![0-9a-f])[0-9a-f]{13}(?![0-9a-f])~i', '#', $val );
	}

	/**
	 * JSON settings without their unset values, object keys sorted: themes write unset keys on some renders only.
	 *
	 * Unset: null, "", an empty array or object, an object holding nothing but a `unit`. A list keeps its length; 0 and false are values.
	 *
	 * @since 8.0
	 * @param mixed $data Decoded JSON.
	 * @return mixed Null when nothing is set.
	 */
	private static function json_set( $data ) {
		if ( ! is_array( $data ) ) {
			return '' === $data ? null : $data;
		}
		$out = array_map( [ __CLASS__, 'json_set' ], $data );
		if ( array_keys( $data ) === range( 0, count( $data ) - 1 ) ) {
			return $out;
		}
		$out = array_diff_key( $out, array_filter( $out, 'is_null' ) );
		ksort( $out );
		return ! $out || [ 'unit' ] === array_keys( $out ) ? null : $out;
	}

	/**
	 * The part of a `<style>` block that styles this page, comments and whitespace left out.
	 *
	 * A rule whose every selector names a class no element has is dropped: generators (Tailwind) emit rules for words of the page text.
	 *
	 * @since 8.0
	 * @param string $css     CSS.
	 * @param array  $classes Classes the page uses, as keys.
	 * @return string
	 */
	private static function used_css( $css, $classes ) {
		$css = preg_replace( '~/\*.*?\*/~s', '', (string) $css );
		$len = strlen( $css );
		$out = '';
		$pos = 0;
		while ( $pos < $len ) {
			$open = strpos( $css, '{', $pos );
			if ( false === $open ) {
				$out .= self::squeeze_css( substr( $css, $pos ) );
				break;
			}

			// The block's closing brace, or the end of the CSS.
			for ( $end = $open, $depth = 0; $end < $len; $end++ ) {
				$depth += '{' === $css[ $end ] ? 1 : ( '}' === $css[ $end ] ? -1 : 0 );
				if ( ! $depth ) {
					break;
				}
			}

			// Declarations before a nested rule belong to the enclosing rule.
			$prelude = substr( $css, $pos, $open - $pos );
			$cut     = strrpos( $prelude, ';' );
			if ( false !== $cut ) {
				$out    .= self::squeeze_css( substr( $prelude, 0, $cut + 1 ) );
				$prelude = substr( $prelude, $cut + 1 );
			}
			$prelude = trim( $prelude );
			$body    = substr( $css, $open + 1, $end - $open - 1 );

			// Conditional at-rules keep only their used rules; other at-rules (`@font-face`, `@keyframes`) are kept whole.
			if ( '@' === substr( $prelude, 0, 1 ) ) {
				$inner = preg_match( '~^@(media|supports|layer|container|scope)\b~i', $prelude ) ? self::used_css( $body, $classes ) : self::squeeze_css( $body );
				if ( '' !== $inner ) {
					$out .= self::squeeze_css( $prelude ) . '{' . rtrim( $inner, ';' ) . '}';
				}
			} elseif ( self::selector_used( $prelude, $classes ) ) {
				$out .= self::squeeze_css( $prelude ) . '{' . rtrim( self::used_css( $body, $classes ), ';' ) . '}';
			}
			$pos = $end + 1;
		}
		return $out;
	}

	/**
	 * Whether one of a rule's selectors names only classes the page uses. Ids are not checked: themes number them per render.
	 *
	 * @since 8.0
	 * @param string $prelude Selector list.
	 * @param array  $classes Classes the page uses, as keys.
	 * @return bool
	 */
	private static function selector_used( $prelude, $classes ) {
		foreach ( explode( ',', $prelude ) as $selector ) {
			preg_match_all( '~\.((?:\\\\.|[\w-])+)~', $selector, $m );
			if ( ! array_diff_key( array_flip( array_map( 'stripslashes', $m[1] ) ), $classes ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * CSS with whitespace collapsed: minified and unminified copies compare equal.
	 *
	 * @since 8.0
	 * @param string $css CSS.
	 * @return string
	 */
	private static function squeeze_css( $css ) {
		return trim( preg_replace( [ '~\s+~', '~\s*([{}:;,>])\s*~', '~;}~' ], [ ' ', '$1', '}' ], $css ) );
	}

	/**
	 * Copies of the page-builder stylesheets a render links, kept with the build cut from it: only the old bytes tell which rules changed.
	 *
	 * @since 8.0
	 * @param string $html Render.
	 * @return array URL path => CSS. A sheet over SHEET_COPY_MAX, or past SHEET_COPIES_MAX for the render, is not copied.
	 */
	public static function sheet_copies( $html ) {
		$out   = [];
		$total = 0;
		foreach ( self::sheet_links( $html ) as $href ) {
			$path = (string) wp_parse_url( $href, PHP_URL_PATH );
			$css  = isset( $out[ $path ] ) || ! preg_match( self::RE_BUILDER_SHEET, $path ) ? null : self::read_css( Utility::is_internal_file( $href ), min( self::SHEET_COPY_MAX, self::SHEET_COPIES_MAX - $total ) );
			if ( null !== $css ) {
				$total       += strlen( $css );
				$out[ $path ] = $css;
			}
		}
		return $out;
	}

	/**
	 * A local `.css` file's content, when it is no longer than a limit: sized before it is read, and read no further.
	 *
	 * @since 8.0
	 * @param array|false $file Utility::is_internal_file().
	 * @param int         $max  Most bytes allowed.
	 * @return string|null Null when there is no such file, it is not `.css`, or it is over the limit.
	 */
	private static function read_css( $file, $max ) {
		if ( ! $file || $max < 0 || 'css' !== strtolower( pathinfo( $file[0], PATHINFO_EXTENSION ) ) ) {
			return null;
		}
		$size = @filesize( $file[0] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $size || $size > $max ) {
			return null;
		}
		$css = @file_get_contents( $file[0], false, null, 0, $max + 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged
		return false === $css || strlen( $css ) > $max ? null : $css;
	}

	/**
	 * The page-builder stylesheets whose change since the build's copy (sheet_copies()) styles nothing this page can show.
	 *
	 * @since 8.0
	 * @param array      $fp      fingerprint() of this render.
	 * @param array      $copies  The build's copies: URL path => md5 of the CSS.
	 * @param callable   $copy    Returns a copy's CSS by URL path, or null; called only for a sheet whose verdict is not known.
	 * @param callable   $classes Returns rule_context() of the build, or null; called once, and only when a sheet must be parsed.
	 * @param array      $known   Verdicts for this build so far, `path|copy md5|file md5` => bool (`#context` => false: unreadable): read and added to.
	 * @param array|null $notes   Null: no log, and a sheet stops at its first rule that may apply. An array: one line per differing sheet is added.
	 * @return array URL path => `from` (md5 of the copy) and `to` (md5 of the file now), for with_sheets() and sheet_changes().
	 */
	public static function same_sheets( $fp, $copies, $copy, $classes, &$known = [], &$notes = null ) {
		$out = [];
		$set = isset( $known['#context'] ) ? null : false;
		foreach ( $fp['skel'] as $line ) {
			if ( ! preg_match( self::RE_SHEET_LINE, $line, $m ) || ! isset( $copies[ $m[1] ] ) || isset( $out[ $m[1] ] ) || (string) $copies[ $m[1] ] === $m[2] ) {
				continue;
			}
			$path = $m[1];
			$from = (string) $copies[ $path ];
			$key  = $path . '|' . $from . '|' . $m[2];
			$ok   = isset( $known[ $key ] ) ? (bool) $known[ $key ] : null;

			if ( null === $ok ) {
				$old  = call_user_func( $copy, $path );
				$css  = null === $old || md5( $old ) !== $from ? null : self::read_css( Utility::is_internal_file( $path ), self::SHEET_COPY_MAX );
				$read = null !== $css && md5( $css ) === $m[2];
				if ( $read && false === $set ) {
					$set = call_user_func( $classes );
					if ( null === $set ) {
						$known['#context'] = false;
					}
				}
				if ( ! $read || null === $set ) {
					// Not remembered: the next render may read it whole.
					if ( null !== $notes ) {
						$notes[] = $path . ( $read ? ': the build CSS or a page cannot be read' : ': changed again since this render, over the copy limit, or its copy is unreadable' );
					}
					continue;
				}

				$ok            = self::sheet_unchanged( $path, $old, $css, $set, $notes );
				$known[ $key ] = $ok;
			} elseif ( null !== $notes ) {
				$notes[] = $path . ': ' . ( $ok ? 'styles nothing' : 'may apply' ) . ' (known)';
			}

			if ( $ok ) {
				$out[ $path ] = [ 'from' => $from, 'to' => $m[2] ];
			}
		}
		return $out;
	}

	/**
	 * Whether no rule that changed between a builder stylesheet's copy and its file may apply.
	 *
	 * @since 8.0
	 * @param string     $path    URL path, for the log.
	 * @param string     $old_css The copy the build was cut from.
	 * @param string     $new_css The file now.
	 * @param array      $set     rule_context().
	 * @param array|null $notes   As same_sheets(): with an array, every changed rule is judged and logged as `-` (the copy's) or `+` (the file's), `!` when it may apply.
	 * @return bool
	 */
	private static function sheet_unchanged( $path, $old_css, $new_css, $set, &$notes ) {
		$changed = self::changed_rules( $old_css, $new_css );
		// The classes the copy names: QUIC.cloud's recorded classes were matched against it.
		$named = false === preg_match_all( self::RE_CLASS, $old_css, $m ) ? null : array_fill_keys( array_map( 'stripslashes', $m[1] ), true );
		if ( null === $changed || null === $named ) {
			if ( null !== $notes ) {
				$notes[] = $path . ': cannot be parsed';
			}
			return false;
		}

		$may = [];
		$not = [];
		foreach ( $changed as $rule ) {
			$text = substr( $rule['text'], 0, 120 );
			if ( self::rule_may_apply( $rule, $set['classes'], $set['recorded'], $named ) ) {
				if ( null === $notes ) {
					return false;
				}
				$may[] = $rule['side'] . '! ' . $text;
			} elseif ( null !== $notes ) {
				$not[] = $rule['side'] . ' ' . $text;
			}
		}
		if ( null !== $notes ) {
			$shown   = array_merge( $may, $not );
			$notes[] = $path . ': ' . count( $changed ) . ' rule(s) changed, ' . count( $may ) . ' may apply' . ( $shown ? ': ' . implode( ' | ', array_slice( $shown, 0, 5 ) ) : '' );
		}
		return ! $may;
	}

	/**
	 * A fingerprint with each stylesheet same_sheets() found unchanged read as its copy.
	 *
	 * @since 8.0
	 * @param array $fp     fingerprint().
	 * @param array $sheets same_sheets().
	 * @return array
	 */
	public static function with_sheets( $fp, $sheets ) {
		if ( ! $sheets ) {
			return $fp;
		}
		foreach ( $fp['skel'] as $i => $line ) {
			if ( preg_match( self::RE_SHEET_LINE, $line, $m ) && isset( $sheets[ $m[1] ] ) && $sheets[ $m[1] ]['to'] === $m[2] ) {
				$fp['skel'][ $i ]  = substr( $line, 0, -32 ) . $sheets[ $m[1] ]['from'];
				$fp['lines'][ $i ] = substr( md5( $fp['skel'][ $i ] ), 0, 8 );
			}
		}
		$fp['design'] = md5( implode( "\n", $fp['skel'] ) );
		return $fp;
	}

	/**
	 * Which local stylesheets of a render a sync may show QUIC.cloud as the build's, and which changed.
	 *
	 * QUIC.cloud reads a stylesheet as its file is now, so a file that changed under the same URL is a change it cannot see: a rebuild.
	 *
	 * @since 8.0
	 * @param array $old_lines Line hashes of the stored fingerprint.
	 * @param array $now       fingerprint() of this render, after with_sheets().
	 * @param array $same      same_sheets().
	 * @return array `allow`: sheet ids (sheet_id()) as keys, for align_stylesheets(); `changed`: URL paths.
	 */
	public static function sheet_changes( $old_lines, $now, $same ) {
		$had = array_flip( $old_lines );
		$out = [ 'allow' => [], 'changed' => [] ];
		foreach ( $now['skel'] as $i => $line ) {
			if ( ! preg_match( self::RE_SHEET_LINE, $line, $m ) ) {
				// A local sheet line whose path cannot be read back (a literal space in it).
				if ( 0 === strpos( $line, 'link ' ) && preg_match( '~#[0-9a-f]{32}$~', $line ) && ! isset( $had[ $now['lines'][ $i ] ] ) ) {
					$out['changed'][] = substr( $line, 0, 120 );
				}
			} elseif ( isset( $same[ $m[1] ] ) ) {
				$out['allow'][ $m[1] . '#' . $same[ $m[1] ]['to'] ] = true;
			} elseif ( isset( $had[ $now['lines'][ $i ] ] ) ) {
				$out['allow'][ $m[1] . '#' . $m[2] ] = true;
			} else {
				$out['changed'][] = $m[1];
			}
		}
		return $out;
	}

	/**
	 * The rules that differ between two copies of a stylesheet, in order.
	 *
	 * Never sorted: the cascade gives a later rule the win, so a moved rule is a change, shown as both its old and new place.
	 *
	 * @since 8.0
	 * @param string $old_css The copy the build was cut from.
	 * @param string $new_css The file now.
	 * @return array|null css_rules() entries, each with `side`: `-` from the copy, `+` from the file. Null when either cannot be read.
	 */
	public static function changed_rules( $old_css, $new_css ) {
		$a = self::css_rules( $old_css );
		$b = self::css_rules( $new_css );
		if ( null === $a || null === $b ) {
			return null;
		}

		// Cut at the common start and end: every rule between, on either side, counts as changed.
		$na   = count( $a );
		$nb   = count( $b );
		$min  = min( $na, $nb );
		$head = 0;
		while ( $head < $min && $a[ $head ]['text'] === $b[ $head ]['text'] ) {
			++$head;
		}
		$tail = 0;
		while ( $tail < $min - $head && $a[ $na - 1 - $tail ]['text'] === $b[ $nb - 1 - $tail ]['text'] ) {
			++$tail;
		}
		$out = [];
		foreach ( [ '-' => $a, '+' => $b ] as $side => $rules ) {
			foreach ( array_slice( $rules, $head, count( $rules ) - $head - $tail ) as $rule ) {
				$out[] = $rule + [ 'side' => $side ];
			}
		}
		return $out;
	}

	/**
	 * A stylesheet as a flat, ordered list of rules, comments and whitespace left out.
	 *
	 * `@media`, `@supports`, `@layer` and `@container` blocks are opened (32 deep at most): each rule in them is listed with its wrappers in front.
	 *
	 * @since 8.0
	 * @param string $css   CSS.
	 * @param string $ctx   Wrappers around it, as written in front of each rule.
	 * @param int    $depth Wrappers around it.
	 * @param array  $out   Rules so far, appended to.
	 * @return array|null List of `text` (the rule with its wrappers, compared), `sel` (its prelude) and `nested`. Null when the CSS cannot be read.
	 */
	private static function css_rules( $css, $ctx = '', $depth = 0, $out = [] ) {
		// Comments go; a comment marker inside a quoted string is no comment.
		$css = $depth ? $css : preg_replace( '~("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')|/\*.*?\*/~s', '$1', (string) $css );
		if ( null === $css ) {
			return null;
		}

		$len = strlen( $css );
		$pos = 0;
		while ( $pos < $len ) {
			$open = strpos( $css, '{', $pos );
			$semi = strpos( $css, ';', $pos );
			if ( false === $open || ( false !== $semi && $semi < $open ) ) {
				// A statement (`@import`, `@charset`, `@layer a, b;`), a stray `;`, or what is left after the last rule.
				$end  = false === $semi ? $len : $semi;
				$stmt = self::squeeze_css( substr( $css, $pos, $end - $pos ) );
				if ( '' !== $stmt ) {
					$out[] = [ 'text' => $ctx . $stmt . ( false === $semi ? '' : ';' ), 'sel' => $stmt, 'nested' => false ];
				}
				$pos = $end + 1;
				continue;
			}

			for ( $end = $open, $level = 0; $end < $len; $end++ ) {
				$level += '{' === $css[ $end ] ? 1 : ( '}' === $css[ $end ] ? -1 : 0 );
				if ( ! $level ) {
					break;
				}
			}
			$prelude = self::squeeze_css( substr( $css, $pos, $open - $pos ) );
			$body    = substr( $css, $open + 1, $end - $open - 1 );
			if ( $depth < 32 && preg_match( '~^@(media|supports|layer|container)\b~i', $prelude ) ) {
				$out = self::css_rules( $body, $ctx . $prelude . '{', $depth + 1, $out );
			} else {
				$out[] = [ 'text' => $ctx . $prelude . '{' . self::squeeze_css( $body ) . '}', 'sel' => $prelude, 'nested' => $depth >= 32 || false !== strpos( $body, '{' ) ];
			}
			$pos = $end + 1;
		}
		return $out;
	}

	/**
	 * Whether a stylesheet rule may style something on the page or its build: when in doubt, yes.
	 *
	 * No only for a plain rule each of whose selectors names a class outside the set, and no recorded pattern. Ids are not checked.
	 *
	 * @since 8.0
	 * @param array      $rule     css_rules() entry, with its `side` when from changed_rules().
	 * @param array      $classes  Class set, as keys.
	 * @param array|null $recorded Patterns of the build's recorded classes (rule_context()); null when it lists none.
	 * @param array|null $named    Classes the sheet's copy names, as keys, for a gained rule; null: not checked.
	 * @return bool
	 */
	public static function rule_may_apply( $rule, $classes, $recorded = null, $named = null ) {
		$sel    = $rule['sel'];
		$gained = '+' === ( $rule['side'] ?? '' );
		// Any failed match below counts as "may apply".
		if ( ( null === $recorded && $gained ) || '@' === substr( $sel, 0, 1 ) || ! empty( $rule['nested'] )
			|| 0 !== preg_match( '~:(?!nth-(?:last-)?(?:child|of-type)\(|lang\(|dir\()[\w-]+\(|:nth-(?:last-)?child\([^)]*\bof\b~i', $sel ) ) {
			return true;
		}

		foreach ( self::selector_parts( $sel ) as $part ) {
			foreach ( (array) $recorded as $re ) {
				if ( 0 !== preg_match( $re, $part ) ) {
					return true;
				}
			}

			// Attribute values and strings name no class: `[href$=".pdf"]`.
			$part = preg_replace( '~\[(?:[^\]"\']|"[^"]*"|\'[^\']*\')*\]|"[^"]*"|\'[^\']*\'~', '[]', $part );
			if ( null === $part || false === preg_match_all( self::RE_CLASS, $part, $m ) ) {
				return true;
			}
			$all = true;
			foreach ( $m[1] as $class ) {
				$name = stripslashes( $class );
				if ( 0 !== preg_match( '~\\\\[0-9a-fA-F]~', $class ) || ( $gained && null !== $named && ! isset( $named[ $name ] ) ) ) {
					return true;
				}
				$all = $all && isset( $classes[ $name ] );
			}
			if ( $all ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * A selector list split at its top-level commas: a comma inside `()`, `[]` or a string stays.
	 *
	 * @since 8.0
	 * @param string $sel Selector list.
	 * @return array
	 */
	private static function selector_parts( $sel ) {
		$parts = [];
		$depth = 0;
		$quote = '';
		$start = 0;
		$len   = strlen( $sel );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $sel[ $i ];
			if ( '\\' === $c ) {
				++$i;
			} elseif ( '' !== $quote ) {
				$quote = $c === $quote ? '' : $quote;
			} elseif ( '"' === $c || "'" === $c ) {
				$quote = $c;
			} elseif ( '(' === $c || '[' === $c ) {
				++$depth;
			} elseif ( ( ')' === $c || ']' === $c ) && $depth > 0 ) {
				--$depth;
			} elseif ( ',' === $c && 0 === $depth ) {
				$parts[] = substr( $sel, $start, $i - $start );
				$start   = $i + 1;
			}
		}
		$parts[] = substr( $sel, $start );
		return $parts;
	}

	/**
	 * What builder stylesheet rules are judged by (rule_may_apply()), for one build.
	 *
	 * @since 8.0
	 * @param string $base The build's render.
	 * @param string $now  This render.
	 * @param string $html The build's HTML.
	 * @return array|null `classes`: every class on the three pages, in the build's own CSS (read loosely) and recorded by the build, as keys;
	 *                    `recorded`: its recorded patterns as regexes of up to 500 each, null when it lists none. Null when any of it cannot be read.
	 */
	public static function rule_context( $base, $now, $html ) {
		$css    = self::build_css( $html );
		$named  = null === $css || false === preg_match_all( self::RE_CLASS, $css, $m ) ? null : array_fill_keys( array_map( 'stripslashes', $m[1] ), true );
		$tokens = null === $named ? [ null ] : array_map( [ __CLASS__, 'tokenize' ], [ $base, $now, $html ] );
		if ( in_array( null, $tokens, true ) ) {
			return null;
		}

		$recorded = self::recorded_classes( $html );
		$patterns = [];
		foreach ( array_chunk( $recorded['patterns'] ?? [], 500 ) as $chunk ) {
			$patterns[] = '~' . implode( '|', array_map( 'preg_quote', $chunk, array_fill( 0, count( $chunk ), '~' ) ) ) . '~';
		}
		return [
			'classes'  => self::page_classes( array_merge( ...$tokens ) ) + $named + array_fill_keys( $recorded['names'] ?? [], true ),
			'recorded' => null === $recorded ? null : $patterns,
		];
	}

	/**
	 * The classes OptimaX saw on elements during its render, as the build lists them: `<!--ox-classes NAME ...--><!--ox-class-patterns PATTERN ...-->`.
	 *
	 * Read only as that pair, right before the first `</head>`: a comment anywhere else may be the page's own.
	 *
	 * @since 8.0
	 * @param string $html The build's HTML.
	 * @return array|null `names` and `patterns`; null when the build has no such lists, or they cannot be used (over 256 KB or 5000 entries).
	 */
	public static function recorded_classes( $html ) {
		$html = (string) $html;
		$max  = 2 * 262144 + 64;
		if ( ! preg_match( '~</head\s*>~i', $html, $h, PREG_OFFSET_CAPTURE )
			|| ! preg_match( '~<!--ox-classes(\s[^<>]*)?-->\s*<!--ox-class-patterns(\s[^<>]*)?-->\s*\z~', substr( $html, max( 0, $h[0][1] - $max ), min( $h[0][1], $max ) ), $m ) ) {
			return null;
		}

		$out = [];
		foreach ( [ 'names' => 1, 'patterns' => 2 ] as $key => $group ) {
			$list = $m[ $group ] ?? '';
			$all  = strlen( $list ) > 262144 ? false : preg_split( '~\s+~', $list, -1, PREG_SPLIT_NO_EMPTY );
			if ( false === $all || count( $all ) > 5000 ) {
				return null;
			}
			if ( 'patterns' === $key ) {
				// A leading dot is dropped and an empty pattern skipped; one of a single character would cover nearly every selector.
				$all = array_filter( array_map( 'ltrim', $all, array_fill( 0, count( $all ), '.' ) ), 'strlen' );
				if ( in_array( 1, array_map( 'strlen', $all ), true ) ) {
					return null;
				}
			}
			$out[ $key ] = array_values( array_unique( $all ) );
		}
		return $out;
	}

	/**
	 * The CSS a build styles its page with: its inline `<style>` blocks and the local stylesheets it links, preloads included.
	 *
	 * @since 8.0
	 * @param string $html   The build's HTML.
	 * @param bool   $strict Whether a stylesheet that is not local, other than a font service's, makes the CSS unknown; otherwise it is left out.
	 * @return string|null Null when the HTML or a local stylesheet cannot be read, or the whole is over BUILD_CSS_MAX: unknown.
	 */
	public static function build_css( $html, $strict = false ) {
		$html = (string) $html;
		if ( false === preg_match_all( '~<style\b[^>]*>(.*?)</style\s*>~is', $html, $m ) || false === preg_match_all( '~<link\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>~i', $html, $l ) ) {
			return null;
		}
		$css  = implode( "\n", $m[1] );
		$read = [];
		foreach ( $l[0] as $link ) {
			$attrs = self::attrs( $link );
			if ( ! self::is_sheet( $attrs ) ) {
				continue;
			}
			$file = Utility::is_internal_file( $attrs['href'] );
			if ( ! $file && $strict && ! preg_match( '~^(fonts\.googleapis\.com|fonts\.bunny\.net|use\.typekit\.net)$~i', (string) wp_parse_url( $attrs['href'], PHP_URL_HOST ) ) ) {
				return null;
			}
			if ( ! $file || isset( $read[ $file[0] ] ) ) {
				continue;
			}
			$read[ $file[0] ] = true;

			$con = self::read_css( $file, self::BUILD_CSS_MAX - strlen( $css ) - 1 );
			if ( null === $con ) {
				return null;
			}
			$css .= "\n" . $con;
		}
		return strlen( $css ) > self::BUILD_CSS_MAX ? null : $css;
	}

	/**
	 * This render, with each stylesheet link (or preload) that loads the same file as in the build's render written as it was there.
	 *
	 * QUIC.cloud refuses a changed `<link>` as a new resource, so a `?ver=` bump over an unchanged file must not show; any other change of the tag still does.
	 *
	 * @since 8.0
	 * @param string     $base  The render the build matches.
	 * @param string     $now   This render.
	 * @param array|null $allow Local sheet ids (sheet_id()) that may be aligned, as keys (sheet_changes()); null: any.
	 * @return string
	 */
	public static function align_stylesheets( $base, $now, $allow = null ) {
		$was = [];
		foreach ( self::sheet_links( $base ) as $raw => $href ) {
			$was[ self::sheet_id( $href ) ][ $raw ] = (string) wp_json_encode( array_diff_key( self::attrs( $raw ), [ 'href' => 1 ] ) );
		}
		if ( ! $was ) {
			return $now;
		}

		$swap = [];
		foreach ( self::sheet_links( $now ) as $raw => $href ) {
			$id = self::sheet_id( $href );
			if ( empty( $was[ $id ] ) || isset( $was[ $id ][ $raw ] ) || ( null !== $allow && ! isset( $allow[ $id ] ) && preg_match( '~#[0-9a-f]{32}$~', $id ) ) ) {
				continue;
			}
			// The first link there that differs in nothing but its href.
			$old = array_search( (string) wp_json_encode( array_diff_key( self::attrs( $raw ), [ 'href' => 1 ] ) ), $was[ $id ], true );
			if ( false !== $old ) {
				$swap[ $raw ] = $old;
			}
		}
		return $swap ? strtr( $now, $swap ) : $now;
	}

	/**
	 * This render, with each `<img>` that differs from the build's render only by a proven next-gen copy (`a.jpg` vs `a.jpg.avif`) written as it was there.
	 *
	 * QUIC.cloud refuses a changed image as a new resource. Images are paired by position; one in a `<picture>`, or another image count, is left as it is.
	 *
	 * @since 8.0
	 * @param string $base The render the build matches.
	 * @param string $now  This render.
	 * @return string
	 */
	public static function align_images( $base, $now ) {
		$was = self::img_tags( $base );
		$is  = self::img_tags( $now );
		if ( ! $was || count( $was ) !== count( $is ) ) {
			return $now;
		}

		// A tag is rewritten wherever it appears, so only when every occurrence aligns to the same base tag.
		$swap = [];
		foreach ( $is as $i => $tag ) {
			$old = $was[ $i ];
			$to  = $tag['raw'] !== $old['raw'] && ! $tag['picture'] && ! $old['picture'] && self::same_img( $old['raw'], $tag['raw'] ) ? $old['raw'] : false;

			$swap[ $tag['raw'] ] = ( $swap[ $tag['raw'] ] ?? $to ) === $to ? $to : false;
		}
		$swap = array_filter( $swap );
		return $swap ? strtr( $now, $swap ) : $now;
	}

	/**
	 * The `<img>` tags of a page in order, each marked when it sits in a `<picture>`.
	 *
	 * @since 8.0
	 * @param string $html Page HTML.
	 * @return array List of `raw` and `picture`.
	 */
	private static function img_tags( $html ) {
		$out     = [];
		$picture = 0;
		foreach ( (array) self::tokenize( $html ) as $tok ) {
			$name     = $tok['name'] ?? '';
			$picture += 'picture' === $name ? 1 : ( '/picture' === $name && $picture ? -1 : 0 );
			if ( 'img' === $name ) {
				$out[] = [ 'raw' => $tok['raw'], 'picture' => $picture > 0 ];
			}
		}
		return $out;
	}

	/**
	 * Whether two `<img>` tags differ only in URLs, one showing a proven next-gen copy (nextgen_copy()) where the other shows the original.
	 *
	 * @since 8.0
	 * @param string $base `<img>` in the build's render.
	 * @param string $now  `<img>` in this render.
	 * @return bool
	 */
	private static function same_img( $base, $now ) {
		$a = self::raw_attrs( $base );
		$b = self::raw_attrs( $now );
		if ( count( $a ) !== count( $b ) ) {
			return false;
		}
		foreach ( $a as $i => list( $name, $raw ) ) {
			if ( $name !== $b[ $i ][0] ) {
				return false;
			}
			if ( $raw === $b[ $i ][1] ) {
				continue;
			}
			$was = self::attr_val( $raw );
			$is  = self::attr_val( $b[ $i ][1] );
			$set = 'srcset' === $name || 'data-srcset' === $name;
			if ( ! ( in_array( $name, self::IMG_URL_ATTRS, true ) ? self::nextgen_copy( $was, $is ) : $set && self::same_srcset( $was, $is ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether two srcsets list the same candidates, each URL the same or its proven next-gen copy.
	 *
	 * @since 8.0
	 * @param string $base srcset in the build's render.
	 * @param string $now  srcset in this render.
	 * @return bool
	 */
	private static function same_srcset( $base, $now ) {
		$a = explode( ',', $base );
		$b = explode( ',', $now );
		if ( count( $a ) !== count( $b ) ) {
			return false;
		}
		foreach ( $a as $i => $candidate ) {
			$was = preg_split( '~\s+~', trim( $candidate ), 2 );
			$is  = preg_split( '~\s+~', trim( $b[ $i ] ), 2 );
			if ( ( $was[1] ?? '' ) !== ( $is[1] ?? '' ) || ( $was[0] !== $is[0] && ! self::nextgen_copy( $was[0], $is[0] ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether a URL is another's next-gen copy that exists: `a.jpg?x=1` and `a.jpg.avif?x=1` with `a.jpg.avif` beside `a.jpg` on disk.
	 *
	 * The suffix alone proves nothing, so the files are checked as Media::replace_webp() does when it links the copy, filters included.
	 *
	 * @since 8.0
	 * @param string $base The original's URL.
	 * @param string $now  The URL in this render.
	 * @return bool
	 */
	private static function nextgen_copy( $base, $now ) {
		if ( $base === $now || self::nextgen_neutral( $now ) !== $base || ! preg_match( '~\.(webp|avif)$~i', substr( $now, 0, strcspn( $now, '?#' ) ), $m ) ) {
			return false;
		}
		$ori = apply_filters( 'litespeed_media_check_ori', Utility::is_internal_file( $base ), $base );
		return $ori && apply_filters( 'litespeed_media_check_webp', Utility::is_internal_file( $base, $m[1] ), $base );
	}

	/**
	 * A tag's attributes in order, repeats included, each as name and raw value (quotes kept).
	 *
	 * @since 8.0
	 * @param string $tag Open tag.
	 * @return array List of [ lowercased name, raw value ].
	 */
	private static function raw_attrs( $tag ) {
		$tag = preg_replace( '~^<[^\s/>]+|/?>$~', '', $tag );
		preg_match_all( self::RE_ATTR, (string) $tag, $matches, PREG_SET_ORDER );

		$out = [];
		foreach ( $matches as $m ) {
			$out[] = [ strtolower( $m[1] ), $m[2] ?? '' ];
		}
		return $out;
	}

	/**
	 * An attribute's raw value without its quotes, decoded.
	 *
	 * @since 8.0
	 * @param string $raw Raw value.
	 * @return string
	 */
	private static function attr_val( $raw ) {
		$quoted = '' !== $raw && ( '"' === $raw[0] || "'" === $raw[0] );
		return html_entity_decode( $quoted ? substr( $raw, 1, -1 ) : $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * The stylesheet and stylesheet preload `<link>` tags of a page.
	 *
	 * @since 8.0
	 * @param string $html Page HTML.
	 * @return array Raw tag => decoded href.
	 */
	private static function sheet_links( $html ) {
		$out = [];
		foreach ( (array) self::tokenize( $html ) as $tok ) {
			if ( 'tag' === $tok['kind'] && 'link' === $tok['name'] ) {
				$attrs = self::attrs( $tok['raw'] );
				if ( self::is_sheet( $attrs ) ) {
					$out[ $tok['raw'] ] = $attrs['href'];
				}
			}
		}
		return $out;
	}

	/**
	 * Whether a `<link>` with an href loads a stylesheet, or preloads one: `rel=preload as=style`.
	 *
	 * @since 8.0
	 * @param array $attrs   Decoded attributes.
	 * @param bool  $preload Whether a stylesheet preload counts.
	 * @return bool
	 */
	private static function is_sheet( $attrs, $preload = true ) {
		$rel = preg_split( '~\s+~', strtolower( $attrs['rel'] ?? '' ), -1, PREG_SPLIT_NO_EMPTY );
		return isset( $attrs['href'] ) && ( in_array( 'stylesheet', $rel, true ) || ( $preload && in_array( 'preload', $rel, true ) && 'style' === strtolower( trim( $attrs['as'] ?? '' ) ) ) );
	}

	/**
	 * What a stylesheet link loads: its local `.css` file, by path and content, when the URL resolves to one; otherwise the URL itself.
	 *
	 * So the same CSS behind a new `?ver=` reads the same, and new CSS behind the same URL reads as new. Hashed once per request.
	 *
	 * @since 8.0
	 * @param string $href Decoded href.
	 * @return string
	 */
	public static function sheet_id( $href ) {
		static $hashed = [];

		// A stylesheet a script writes (`style.php?skin=dark`) is told apart by its URL: the PHP file is the same for every output.
		$file = Utility::is_internal_file( $href );
		if ( ! $file || 'css' !== strtolower( pathinfo( $file[0], PATHINFO_EXTENSION ) ) ) {
			return self::mask( $href );
		}
		if ( ! isset( $hashed[ $file[0] ] ) ) {
			$hashed[ $file[0] ] = (string) md5_file( $file[0] );
		}
		return '' === $hashed[ $file[0] ] ? self::mask( $href ) : wp_parse_url( $href, PHP_URL_PATH ) . '#' . $hashed[ $file[0] ];
	}

	/**
	 * The prices in a JSON-LD block, in order, each with its byte offset in the page.
	 *
	 * Read as text, not decoded: the patch rewrites the value where it sits, so the rest of the block stays as it is.
	 *
	 * @since 8.0
	 * @param string $json   Script content.
	 * @param int    $offset Byte offset of the content in the page.
	 * @return array List of `key`, `raw` (the JSON value), `offset`.
	 */
	private static function json_prices( $json, $offset ) {
		$out = [];
		if ( preg_match_all( self::RE_JSON_PRICE, (string) $json, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			foreach ( $matches as $m ) {
				$out[] = [ 'key' => $m[1][0], 'raw' => $m[2][0], 'offset' => $offset + $m[2][1] ];
			}
		}
		return $out;
	}

	/**
	 * Whether an attribute value is content: data-* or an inline style, which page builders rewrite on a save. A nonce is neither design nor content.
	 *
	 * @since 8.0
	 * @param string $name Lowercased attribute name.
	 * @return bool
	 */
	private static function is_content_attr( $name ) {
		return ( 0 === strpos( $name, 'data-' ) || 'style' === $name ) && false === strpos( $name, 'nonce' );
	}

	/**
	 * An image URL without the next-gen suffix LiteSpeed's Image Optimization adds: `a.jpg.avif` is `a.jpg`.
	 *
	 * Mirrors Media::replace_webp(): only a convertible original gets a suffix, so an original `.webp` upload is left as is. Query and fragment are kept.
	 *
	 * @since 8.0
	 * @param string $url Image URL.
	 * @return string
	 */
	private static function nextgen_neutral( $url ) {
		$cut = strcspn( $url, '?#' );
		return preg_replace( '~' . self::NEXTGEN_SUFFIX . '$~i', '.$1', substr( $url, 0, $cut ) ) . substr( $url, $cut );
	}

	/**
	 * The value an item is found by in the OptimaX HTML.
	 *
	 * @since 8.0
	 * @param array $item An item.
	 * @return string
	 */
	private static function locate_key( $item ) {
		if ( self::TYPE_ATTR !== $item[0] ) {
			return $item[2] ?? $item[1];
		}
		// An inline style as OptimaX's minifier writes it: no whitespace around `:` and `;`, no last `;`.
		$val = self::mask( $item[1] );
		return $item[2] . '=' . ( 'style' === $item[2] ? rtrim( preg_replace( '~\s*([:;])\s*~', '$1', trim( $val ) ), ';' ) : $val );
	}
}
