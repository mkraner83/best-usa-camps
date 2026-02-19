<?php
/**
 * Elementor Page Structure Reader
 * 
 * TEMPORARY DEBUG TOOL - DELETE AFTER USE
 * 
 * Upload to your WordPress root, then visit:
 *   https://bestusacamps.com/debug-read-elementor-page.php?wpid=YOUR_PAGE_ID&key=cdbs2026
 * 
 * Replace YOUR_PAGE_ID with the WordPress page ID of a camp page.
 * 
 * IMPORTANT: Delete this file from the server after use!
 */

// Simple security key - must match URL param
define( 'ACCESS_KEY', 'cdbs2026' );

if ( empty( $_GET['key'] ) || $_GET['key'] !== ACCESS_KEY ) {
	http_response_code( 403 );
	die( 'Forbidden. Add ?key=cdbs2026 to the URL.' );
}

// Load WordPress
$wp_load = __DIR__ . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	die( 'wp-load.php not found. Make sure this file is in the WordPress root directory.' );
}
require_once $wp_load;

$page_id = isset( $_GET['wpid'] ) ? intval( $_GET['wpid'] ) : 0;

if ( ! $page_id ) {
	die( 'Please provide a wpid parameter. Example: ?wpid=123&key=cdbs2026' );
}

$post = get_post( $page_id );
if ( ! $post ) {
	die( 'Page not found: ' . $page_id );
}

$elementor_data = get_post_meta( $page_id, '_elementor_data', true );
if ( ! $elementor_data ) {
	die( 'No Elementor data found on page ID ' . $page_id . '. Make sure this page was built with Elementor.' );
}

$data = json_decode( $elementor_data, true );
if ( ! $data ) {
	die( 'Failed to decode Elementor JSON.' );
}

// --- Read camp_id custom field for reference ---
$camp_id = get_post_meta( $page_id, 'camp_id', true );

// --- Helper: parse widgets recursively ---
function parse_el_element( $el, $depth = 0 ) {
	$type     = $el['elType']    ?? '?';
	$widgType = $el['widgetType'] ?? null;
	$settings = $el['settings']  ?? [];
	$children = $el['elements']  ?? [];

	$indent = str_repeat( '  ', $depth );
	$line   = '';

	// Width / column size
	$col_width     = $settings['_column_size']           ?? null;
	$col_width_tab = $settings['_column_size_tablet']    ?? null;
	$col_width_mob = $settings['_column_size_mobile']    ?? null;
	$inline_width  = $settings['width']['size']          ?? null;
	$inline_unit   = $settings['width']['unit']          ?? '%';

	// Responsive visibility
	$hide_desktop = ( $settings['hide_desktop'] ?? '' ) === 'hidden-desktop';
	$hide_tablet  = ( $settings['hide_tablet']  ?? '' ) === 'hidden-tablet';
	$hide_mobile  = ( $settings['hide_mobile']  ?? '' ) === 'hidden-phone';

	$visibility = [];
	if ( $hide_desktop ) $visibility[] = 'hidden:desktop';
	if ( $hide_tablet )  $visibility[] = 'hidden:tablet';
	if ( $hide_mobile )  $visibility[] = 'hidden:mobile';

	switch ( $type ) {
		case 'section':
			$cols         = count( $children );
			$is_inner     = ! empty( $settings['is_inner'] );
			$structure    = $settings['structure'] ?? '';
			$line = $indent . '[SECTION' . ( $is_inner ? ':inner' : '' ) . '] cols=' . $cols
				  . ( $structure    ? ' structure=' . $structure : '' )
				  . ( $visibility   ? ' ' . implode( ' ', $visibility ) : '' );
			break;

		case 'container':
			$flex_dir     = $settings['flex_direction']   ?? '';
			$flex_wrap    = $settings['flex_wrap']        ?? '';
			$content_pos  = $settings['content_position'] ?? '';
			$width_val    = $settings['width']['size']    ?? '';
			$width_unit   = $settings['width']['unit']    ?? '%';
			$line = $indent . '[CONTAINER] dir=' . ( $flex_dir ?: 'row' )
				  . ( $flex_wrap   ? ' wrap=' . $flex_wrap : '' )
				  . ( $width_val   ? ' width=' . $width_val . $width_unit : '' )
				  . ( $content_pos ? ' align=' . $content_pos : '' )
				  . ( $visibility  ? ' ' . implode( ' ', $visibility ) : '' );
			break;

		case 'column':
			$line = $indent . '[COLUMN]'
				  . ( $col_width     ? ' desktop=' . $col_width . '%'   : '' )
				  . ( $col_width_tab ? ' tablet='  . $col_width_tab . '%' : '' )
				  . ( $col_width_mob ? ' mobile='  . $col_width_mob . '%' : '' )
				  . ( $visibility    ? ' ' . implode( ' ', $visibility ) : '' );
			break;

		case 'widget':
			$label = '';
			switch ( $widgType ) {
				case 'shortcode':
					$sc = trim( $settings['shortcode'] ?? '' );
					$label = 'SHORTCODE: ' . $sc;
					break;
				case 'heading':
					$title = wp_strip_all_tags( $settings['title'] ?? '' );
					$tag   = $settings['header_size'] ?? 'h2';
					$align = $settings['align']       ?? '';
					$label = 'HEADING <' . $tag . '>: "' . $title . '"'
						   . ( $align ? ' align=' . $align : '' );
					break;
				case 'text-editor':
					$text  = wp_strip_all_tags( $settings['editor'] ?? '' );
					$label = 'TEXT: "' . mb_substr( $text, 0, 80 ) . ( strlen( $text ) > 80 ? '...' : '' ) . '"';
					break;
				case 'divider':
					$style  = $settings['style']        ?? 'solid';
					$weight = $settings['weight']['size'] ?? '';
					$color  = $settings['color']        ?? '';
					$label  = 'DIVIDER style=' . $style
							. ( $weight ? ' weight=' . $weight : '' )
							. ( $color  ? ' color=' . $color  : '' );
					break;
				case 'spacer':
					$space = $settings['space']['size'] ?? '';
					$label = 'SPACER ' . $space . ( $settings['space']['unit'] ?? 'px' );
					break;
				case 'image':
					$url   = $settings['image']['url'] ?? '';
					$label = 'IMAGE: ' . ( $url ? basename( $url ) : '(dynamic)' );
					break;
				case 'image-carousel':
				case 'media-carousel':
					$label = 'IMAGE CAROUSEL (' . count( $settings['slides'] ?? [] ) . ' slides)';
					break;
				case 'button':
					$btn_text = $settings['text']      ?? '';
					$btn_link = $settings['link']['url'] ?? '';
					$label    = 'BUTTON: "' . $btn_text . '"' . ( $btn_link ? ' → ' . $btn_link : '' );
					break;
				case 'icon-list':
					$items = $settings['icon_list'] ?? [];
					$label = 'ICON LIST (' . count( $items ) . ' items): ' . implode( ', ', array_map( fn($i) => wp_strip_all_tags($i['text'] ?? ''), array_slice( $items, 0, 3 ) ) );
					break;
				default:
					$label = strtoupper( $widgType ?? 'unknown-widget' );
					// Add any title/text hint
					if ( isset( $settings['title'] ) ) $label .= ' title="' . wp_strip_all_tags( $settings['title'] ) . '"';
					if ( isset( $settings['text'] )  ) $label .= ' text="'  . mb_substr( wp_strip_all_tags( $settings['text'] ), 0, 60 ) . '"';
					break;
			}
			// Sizing / alignment on widget
			$w_size  = $settings['width']['size']  ?? null;
			$w_unit  = $settings['width']['unit']  ?? '%';
			$w_align = $settings['align']          ?? null;

			$line = $indent . '[WIDGET:' . $widgType . '] ' . $label
				  . ( $w_size  ? ' width=' . $w_size . $w_unit  : '' )
				  . ( $w_align ? ' align=' . $w_align : '' )
				  . ( $visibility ? ' ' . implode( ' ', $visibility ) : '' );
			break;
	}

	$out = [ $line ];
	foreach ( $children as $child ) {
		$out = array_merge( $out, parse_el_element( $child, $depth + 1 ) );
	}
	return $out;
}

$lines = [];
foreach ( $data as $section ) {
	$lines = array_merge( $lines, parse_el_element( $section, 0 ) );
}

$output = implode( "\n", $lines );

?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Elementor Structure: <?php echo esc_html( $post->post_title ); ?></title>
<style>
  body { font-family: monospace; background: #0f0f0f; color: #d4d4d4; padding: 20px; margin: 0; }
  h1   { color: #4ade80; font-size: 16px; }
  .meta { color: #94a3b8; font-size: 12px; margin-bottom: 16px; }
  pre  { background: #1e1e1e; padding: 20px; border-radius: 8px; overflow-x: auto; font-size: 13px; line-height: 1.6; white-space: pre-wrap; word-break: break-all; }
  .section  { color: #f97316; }
  .container{ color: #fb923c; }
  .column   { color: #60a5fa; }
  .shortcode{ color: #4ade80; }
  .heading  { color: #facc15; }
  .divider  { color: #94a3b8; }
  .spacer   { color: #64748b; }
  .widget   { color: #c084fc; }
  .copy-btn { background: #3b7a57; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; margin-bottom: 12px; }
  .copy-btn:hover { background: #2d6446; }
  .warning  { background: #7f1d1d; color: #fca5a5; padding: 10px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 13px; }
</style>
</head>
<body>
<h1>Elementor Page Structure Reader</h1>
<div class="meta">
  Page: <strong><?php echo esc_html( $post->post_title ); ?></strong> (ID: <?php echo $page_id; ?>)
  &nbsp;|&nbsp; camp_id custom field: <strong><?php echo $camp_id ?: '(not set)'; ?></strong>
  &nbsp;|&nbsp; <?php echo count( $lines ); ?> elements found
</div>
<div class="warning">
  ⚠ DELETE this file from the server after you are done! (<code>debug-read-elementor-page.php</code>)
</div>
<button class="copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('output').innerText).then(()=>this.innerText='Copied!')">Copy All to Clipboard</button>
<pre id="output"><?php
foreach ( $lines as $line ) {
	// Color-code
	$esc = htmlspecialchars( $line );
	if ( str_contains( $line, '[SECTION' ) )         $esc = '<span class="section">'   . $esc . '</span>';
	elseif ( str_contains( $line, '[CONTAINER' ) )   $esc = '<span class="container">' . $esc . '</span>';
	elseif ( str_contains( $line, '[COLUMN' ) )      $esc = '<span class="column">'    . $esc . '</span>';
	elseif ( str_contains( $line, 'SHORTCODE:' ) )   $esc = '<span class="shortcode">' . $esc . '</span>';
	elseif ( str_contains( $line, 'HEADING' ) )      $esc = '<span class="heading">'   . $esc . '</span>';
	elseif ( str_contains( $line, 'DIVIDER' ) )      $esc = '<span class="divider">'   . $esc . '</span>';
	elseif ( str_contains( $line, 'SPACER' ) )       $esc = '<span class="spacer">'    . $esc . '</span>';
	elseif ( str_contains( $line, '[WIDGET:' ) )     $esc = '<span class="widget">'    . $esc . '</span>';
	echo $esc . "\n";
}
?></pre>
<br>
<details>
  <summary style="color:#94a3b8;cursor:pointer;">Raw JSON (for reference)</summary>
  <pre style="font-size:11px;max-height:400px;overflow-y:auto;"><?php echo htmlspecialchars( json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); ?></pre>
</details>
</body>
</html>
