<?php
/**
 * Whippet Import/Export Admin Page
 *
 * @category Whippet
 * @package  Whippet
 * @author   Jake Henshall
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://nought.digital
 */

/**
 * Exit if accessed directly
 *
 * @var [type]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap whippet-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="px-2 mt-8">
		<div class="w-full px-4 py-8 rounded bg-white">
			<h2 id="import-export" class="text-lg text-bold">Import/Export Whippet Plugin Settings</h2>
			<?php whippet_settings_page(); ?>
		</div>
	</div>
</div>

