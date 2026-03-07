<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/lib/dom-parser.php';
require_once __DIR__ . '/lib/w3tc-bridge.php';
require_once __DIR__ . '/lib/wpfc-bridge.php';

if ( ! defined( 'WHIPPET_IMAGES_CDN_URL' ) ) {
	define( 'WHIPPET_IMAGES_CDN_URL', 'https://wsrv.nl/' );
}

/**
 * Returns true when the site is on a local/private hostname that external
 * CDN proxies (like wsrv.nl) cannot reach from the public internet.
 */
function whippet_images_is_local_env(): bool {
	$host = wp_parse_url( site_url(), PHP_URL_HOST );
	if ( ! $host ) {
		return false;
	}
	// localhost, 127.x.x.x, 192.168.x.x, 10.x.x.x, or local-only TLDs
	return (
		$host === 'localhost' ||
		preg_match( '/^127\./', $host ) ||
		preg_match( '/^192\.168\./', $host ) ||
		preg_match( '/^10\./', $host ) ||
		preg_match( '/\.(local|localhost|test|example|invalid|internal)$/', $host )
	);
}

/**
 * Converts an image URL to a wsrv.nl CDN URL.
 * wsrv.nl expects the url= param without a protocol prefix.
 */
function whippet_images_to_cdn_url( string $url ): string {
	$stripped = preg_replace( '/^(\w+:)?\/\//', '', $url );
	return 'https://wsrv.nl/?url=' . $stripped;
}

function whippet_images_get_attachment_width($url) {
    try {
        // For wsrv.nl URLs, extract the original image URL before any lookups
        if ( strpos( $url, 'wsrv.nl/?url=' ) !== false ) {
            if ( preg_match( '/wsrv\.nl\/\?url=([^&]+)/', $url, $cdn_match ) ) {
                $url = 'https://' . $cdn_match[1];
            }
        }

        // Extract width if found in the url. For example something-100x100.jpg
        preg_match('/(.+)-([0-9]+)x([0-9]+)\.(jpg|jpeg|png|gif|webp)$/', $url, $matches);
        if(!empty($matches) && $matches[2] && is_numeric($matches[2])) 
            return $matches[2];

        // Width not found in url, try to get the actual size from DB
        $url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif|webp)$)/i', '', $url);
        $attachment_id = attachment_url_to_postid($url);
        $width = $attachment_id ? wp_get_attachment_image_src($attachment_id, "full")[1] : false;
        return $width;
    }
    catch(Exception $e) {
        return false;
    }
}

function whippet_images_add_query_string($url, $name, $value) {
    return strpos($url, '?') !== false ? "{$url}&$name=$value" : "{$url}?$name=$value";
}

function whippet_images_add_responsiveness($images) {
    $base_widths = array( 400, 800, 1400, 2000, 3800 );

    foreach ($images as $image) {
        if($image->srcset) continue;

        if (strpos($image->src, ".svg") !== false) continue;
        if (strpos($image->src, "data:image") !== false) continue;

        $original_image_width = whippet_images_get_attachment_width($image->src);

        if(!$original_image_width) continue;

        $widths = array_filter($base_widths, function ($width) use ($original_image_width) {
            return $width < $original_image_width;
        });
        $widths[] = (int) $original_image_width;

        $srcset = array_reduce($widths, function ($carry, $width) use ($image) {
            $image_url = whippet_images_add_query_string($image->src, "w", $width);
            return $carry . "{$image_url} {$width}w, \n";
        }, '');
        $image->setAttribute("srcset", $srcset);

        $sizes = "(max-width: {$original_image_width}px) 100vw, {$original_image_width}px";
        $image->setAttribute("sizes", $sizes);
    }
}

function whippet_images_add_compression($images, $quality) {
    foreach ($images as $image) {
        // Exclude base64 images
        if (strpos($image->src, "data:image") !== false) continue;
        
        $image->src = whippet_images_add_query_string($image->src, "q", $quality);

        // Similarly to srcset
        if($image->srcset) {
            $srcset = "";
            preg_match_all('/(https?:\/\/\S+)\s(\d+w)/', $image->srcset, $matches);
            $images_urls = $matches[1];
            $sizes = $matches[2];
            foreach($images_urls as $index=>$image_url) {
                $image_url = whippet_images_add_query_string($image_url, "q", $quality);
                $srcset .= "{$image_url} {$sizes[$index]},\n";
            }
            $image->srcset = $srcset;
        }
    }
}

function whippet_images_add_webp($images) {
    foreach ($images as $image) {
        // Exclude base64 images
        if (strpos($image->src, "data:image") !== false) continue;

        // Only process wsrv.nl CDN images
        if (strpos($image->src, "wsrv.nl") === false) continue;
        
        $image->src = whippet_images_add_query_string($image->src, "output", "webp");

        // Similarly to srcset
        if($image->srcset) {
            $srcset = "";
            preg_match_all('/(https?:\/\/\S+)\s(\d+w)/', $image->srcset, $matches);
            $images_urls = $matches[1];
            $sizes = $matches[2];
            foreach($images_urls as $index=>$image_url) {
                $image_url = whippet_images_add_query_string($image_url, "output", "webp");
                $srcset .= "{$image_url} {$sizes[$index]},\n";
            }
            $image->srcset = $srcset;
        }
    }
}


function whippet_images_add_cdn($images) {
    $exclude_keywords = get_option('whippet_images_cdn_exclude_keywords');
    array_push($exclude_keywords, "data:image", "brizy_post", "wsrv.nl");

    foreach ($images as $image) {

        // Exclude images
        foreach ($exclude_keywords as $keyword) {
            if ($keyword && strpos($image->src, $keyword) !== false) continue 2;
        }
        
        // Rewrite relative urls
        $image->src = preg_replace("/(?:^|\s)(\/)(?!\/)/", site_url()."/", $image->src);
        if($image->srcset) $image->srcset = preg_replace("/(?:^|\s)(\/)(?!\/)/im", site_url()."/", $image->srcset);

        // Add wsrv.nl CDN to src
        $image->src = whippet_images_to_cdn_url($image->src);

        // Add wsrv.nl CDN to each URL in srcset
        if($image->srcset) {
            $new_srcset = '';
            preg_match_all('/((?:https?:)?\/\/\S+)\s(\d+w)/', $image->srcset, $matches);
            foreach($matches[1] as $idx => $src_url) {
                $new_srcset .= whippet_images_to_cdn_url($src_url) . ' ' . $matches[2][$idx] . ",\n";
            }
            $image->srcset = $new_srcset;
        }
    }
}

function whippet_images_add_cdn_to_styles($styles, $compression_enabled, $quality) {

    $exclude_keywords = get_option('whippet_images_cdn_exclude_keywords');

    foreach ($styles as $style) {
        // Split inline style to 3 parts, before background image, image url, after background image
        $regex = '/(.*background.*:\s*url\((?:\'|")*)(.*(?:\.(?:jpg|jpeg|png|gif|svg|webp)))((?:\'|")*\).*)/s';

        if(preg_match($regex, $style->innertext, $matches)) {
            
            // Add wsrv.nl CDN
            $image_url = whippet_images_to_cdn_url($matches[2]);

            // Exclude image if needed
            foreach ($exclude_keywords as $keyword) {
                if ($keyword && strpos($image_url, $keyword) !== false) continue 2;
            }
            
            // Add compression if enabled and images are not svg
            if($compression_enabled && strpos($image_url, '.svg') === false)
                $image_url = whippet_images_add_query_string($image_url, "q", $quality);

            // Update style
            $style->innertext = "{$matches[1]}{$image_url}{$matches[3]}";
        }
    }
}

function whippet_images_add_lazy_load($images) {
    // Get options
    $lazymethod = get_option('whippet_images_lazymethod');
    $exclude_keywords = get_option('whippet_images_exclude_keywords');

    $default_exclude_keywords = [
        'data-src=',
        'data-no-lazy=',
        'data-lazy-original=',
        'data-lazy-src=',
        'data-lazysrc=',
        'data-lazyload=',
        'data-bgposition=',
        'data-envira-src=',
        'fullurl=',
        'lazy-slider-img=',
        'data-srcset=',
        'class="ls-l',
        'class="ls-bg',
        'soliloquy-image',
        'loading="eager"',
        'swatch-img',
        'data-height-percentage',
        'data-large_image',
        'avia-bg-style-fixed',
        'skip-lazy',
    ];
    $exclude_keywords = array_merge($exclude_keywords, $default_exclude_keywords);

    // Transparent placeholder
    $placeholder = "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";

    foreach ($images as $image) {
        // Exclude base64 images
        if (strpos($image->src, "data:image") !== false) continue;

        // Skip if the image if matched against exclude keywords
        foreach ($exclude_keywords as $keyword) {
            if ($keyword && strpos($image->parent()->outertext, $keyword) !== false) continue 2;
        }

        if($lazymethod === "native") {
            // Add browsers native lazy loading
            $image->setAttribute("loading", "lazy");
        }
        else {
            // Native or JS+Native lazy loading            

            // Add lazy loading attribute
            $image->setAttribute("data-loading", "lazy");

            // Skip rest if lazy loading method is native only
            if ($lazymethod === "native") continue;
            
            // Add data-src and data-srcset
            $image->setAttribute("data-src", $image->src);
            $image->setAttribute("data-srcset", $image->srcset);
            
            // Remove srcset
            $image->removeAttribute("srcset");

            // Apply placeholder
            $image->setAttribute("src", $placeholder);
        }
        
    }
}

function whippet_images_lazy_load_elementor_bg_images($divs) {

    $exclude_keywords = get_option('whippet_images_exclude_keywords');

    foreach($divs as $div) {
        // Skip if the image if matched against exclude keywords
        foreach ($exclude_keywords as $keyword) {
            if ($keyword && $div->class && strpos($div->class, $keyword) !== false) continue 2;
        }

        $div->setAttribute("data-loading", "lazy-background");

        if($div->style)
            $div->style = "background:none;{$div->style}";
        else
            $div->setAttribute("style", "background:none;");
    }
}

function whippet_images_process_background_images($images, $cdn_enabled, $compression_enabled, $quality, $lazy_loading_enabled) {

    foreach ($images as $image) {
        // Split inline style to 3 parts, before background image, image url, after background image
        $regex = '/(.*background.*:\s*url\((?:\'|")*)(.*(?:\.(?:jpg|jpeg|png|gif|svg|webp)))((?:\'|")*\).*)/';

        if(preg_match($regex, $image->style, $matches)) {
            
            // Add wsrv.nl CDN if enabled
            $image_url = $cdn_enabled ? whippet_images_to_cdn_url($matches[2]) : $matches[2];
            
            // Add compression if enabled and images are not svg
            if($compression_enabled && strpos($image_url, '.svg') === false)
                $image_url = whippet_images_add_query_string($image_url, "q", $quality);
            
            // Add lazy loading if enabled
            if($lazy_loading_enabled) {
                $image->setAttribute("data-loading","lazy-background");
                $style = "background:none;{$matches[1]}{$image_url}{$matches[3]}";
            }
            else {
                $style = "{$matches[1]}{$image_url}{$matches[3]}";
            }

            // Update style
            $image->style = $style;
        }
    }
}

function whippet_images_process_woocommerce_thumbnails($images, $compression_enabled, $quality) {

    foreach ($images as $image) {
        $src = $image->getAttribute("data-thumb");

        // Remove relative URLs
        $src = preg_replace("/(?:^|\s)(\/)/", site_url()."/", $src);

        // Add wsrv.nl CDN
        $src = whippet_images_to_cdn_url($src);

        // Append quality if compression is enabled
        if($compression_enabled)
            $src = whippet_images_add_query_string($src, "q", $quality);

        $image->setAttribute("data-thumb", $src);
    }
}

function whippet_images_rewrite_html($html) {
    try {
        // Process only GET requests
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
		  return $html;
		}
        
        // check empty
        if (!isset($html) || trim($html) === '') {
            return $html;
        }
        
        // return if content is XML
        if (strcasecmp(substr($html, 0, 5), '<?xml') === 0) {
            return $html;
        }

        // Check if the code is HTML, otherwise return
        if (trim($html)[0] !== "<") {
            return $html;
        }

        // Parse HTML
        $newHtml = str_get_html($html);

        // Not HTML, return original
        if (!is_object($newHtml)) {
            return $html;
        }

        // Get options
        // External CDN proxies cannot reach local/private hostnames.
        $cdn_enabled = get_option('whippet_images_enable_cdn') && ! whippet_images_is_local_env();
        $lazy_loading_enabled = get_option('whippet_images_enable_lazyloading');
        $responsiveness_enabled = get_option('whippet_images_enable_responsive_images') && $cdn_enabled;
        $compression_enabled = get_option('whippet_images_enable_compression') && $cdn_enabled;
        $quality = get_option('whippet_images_quality');

        // Remove picture tag
        foreach ($newHtml->find('picture') as $picture) {
            $picture->innertext = $picture->find('img', 0);
        }

        // Process normal images with img tag
        $images = $newHtml->find('img');

        // CDN rewriting must run first so subsequent params are appended as
        // top-level wsrv.nl query params rather than embedded in the url= value.
        if($cdn_enabled) {
            whippet_images_add_cdn($images);

            // Process WooCommerce thumbnails
            $woocommerce_thumbnails = $newHtml->find('div[data-thumb]');
            whippet_images_process_woocommerce_thumbnails($woocommerce_thumbnails, $compression_enabled, $quality);

            // Process background images in style tags
            $styles = $newHtml->find('style');
            whippet_images_add_cdn_to_styles($styles, $compression_enabled, $quality);
        }

        if($responsiveness_enabled) whippet_images_add_responsiveness($images);

        if($compression_enabled) whippet_images_add_compression($images, $quality);

        if($cdn_enabled) whippet_images_add_webp($images);

        if($lazy_loading_enabled) {
            whippet_images_add_lazy_load($images);

            // $elementor_background_divs = $newHtml->find('[data-settings*=background_background]');
            // whippet_images_lazy_load_elementor_bg_images($elementor_background_divs);
        }

        // Process background images
        $background_images = $newHtml->find('[style*=background]');
        whippet_images_process_background_images($background_images, $cdn_enabled, $compression_enabled, $quality, $lazy_loading_enabled);
        
        return $newHtml;

    } catch (Exception $e) {
        return $html;
    }
}

if (!is_admin() && apply_filters('whippet_images_output_buffer', true)) {
    ob_start("whippet_images_rewrite_html");
}