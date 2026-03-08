<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/lib/dom-parser.php';

function whippet_scripts_is_keyword_included($content, $keywords)
{
    $keywords = whippet_scripts_normalize_list( $keywords );
    foreach ($keywords as $keyword) {
        if (strpos($content, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

function whippet_scripts_get_match_operator() {
    $operator = get_option( 'whippet_scripts_regex_operator', 'any' );
    return in_array( $operator, array( 'any', 'all' ), true ) ? $operator : 'any';
}

function whippet_scripts_normalize_list( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }

    return array_values(
        array_filter(
            array_map(
                static function( $item ) {
                    return trim( (string) $item );
                },
                $value
            ),
            static function( $item ) {
                return '' !== $item;
            }
        )
    );
}

function whippet_scripts_prepare_regex( $pattern ) {
    $pattern = trim( (string) $pattern );
    if ( '' === $pattern ) {
        return '';
    }

    $first = substr( $pattern, 0, 1 );
    $last  = substr( $pattern, -1 );
    if ( strlen( $pattern ) > 2 && ! ctype_alnum( $first ) && ! ctype_space( $first ) && $first === $last ) {
        return $pattern;
    }

    return '/' . str_replace( '/', '\/', $pattern ) . '/i';
}

function whippet_scripts_is_regex_included( $content, $patterns, $operator = 'any' ) {
    $patterns = whippet_scripts_normalize_list( $patterns );
    if ( empty( $patterns ) ) {
        return false;
    }

    $operator = in_array( $operator, array( 'any', 'all' ), true ) ? $operator : 'any';
    $matched  = 0;
    $valid    = 0;

    foreach ( $patterns as $pattern ) {
        $regex = whippet_scripts_prepare_regex( $pattern );
        if ( '' === $regex || false === @preg_match( $regex, '' ) ) {
            if ( 'all' === $operator ) {
                return false;
            }
            continue;
        }

        $valid++;
        if ( preg_match( $regex, $content ) ) {
            $matched++;
            if ( 'any' === $operator ) {
                return true;
            }
        } elseif ( 'all' === $operator ) {
            return false;
        }
    }

    if ( 0 === $valid ) {
        return false;
    }

    return 'all' === $operator ? $matched === $valid : false;
}

function whippet_scripts_should_run_for_request() {
    if ( ! get_option( 'whippet_scripts_testing_mode', 0 ) ) {
        return true;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    $preview = isset( $_GET['whippet_preview_scripts'] ) ? sanitize_text_field( wp_unslash( $_GET['whippet_preview_scripts'] ) ) : '';
    return '1' === $preview;
}

function whippet_scripts_rewrite_html($html)
{
    try {
        if ( ! whippet_scripts_should_run_for_request() ) {
            return $html;
        }

        // Process only GET requests
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
		  return $html;
		}
        
        // Detect non-HTML
        if (!isset($html) || trim($html) === '' || strcasecmp(substr($html, 0, 5), '<?xml') === 0 || trim($html)[0] !== "<") {
            return $html;
        }

        $disabled_pages = get_option('whippet_scripts_disabled_pages');
        $disabled_pages_regex = get_option( 'whippet_scripts_disabled_pages_regex', array() );
        $operator = whippet_scripts_get_match_operator();
        $current_url = home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );
        if (
            whippet_scripts_is_keyword_included( $current_url, whippet_scripts_normalize_list( $disabled_pages ) ) ||
            whippet_scripts_is_regex_included( $current_url, $disabled_pages_regex, $operator )
        ) {
            return $html;
        }

        if ( get_option( 'whippet_scripts_mu_mode', 0 ) ) {
            do_action( 'whippet_scripts_mu_mode', $current_url );
        }

        // Parse HTML
        $newHtml = str_get_html($html);

        // Not HTML, return original
        if (!is_object($newHtml)) {
            return $html;
        }

        $include_list = get_option('whippet_scripts_include_list');
        $regex_rules = get_option( 'whippet_scripts_regex_rules', array() );

        foreach ($newHtml->find("script[!type],script[type='text/javascript']") as $script) {
            $outer = $script->outertext;
            if (
                whippet_scripts_is_keyword_included( $outer, whippet_scripts_normalize_list( $include_list ) ) ||
                whippet_scripts_is_regex_included( $outer, $regex_rules, $operator )
            ) {
                $script->setAttribute("data-type", "lazy");
                if ($script->getAttribute("src")) {
                    $script->setAttribute("data-src", $script->getAttribute("src"));
                    $script->removeAttribute("src");
                } else {
                    $script->setAttribute("data-src", "data:text/javascript;base64,".base64_encode($script->innertext));
                    $script->innertext="";
                }
            }
        }
        
        return $newHtml;
    } catch (Exception $e) {
        return $html;
    }
}

if (!is_admin()) {
    ob_start("whippet_scripts_rewrite_html");
}

// W3TC HTML rewrite
add_filter('w3tc_process_content', function ($buffer) {
    if ( is_admin() ) return $buffer;
    return whippet_scripts_rewrite_html($buffer);
});
