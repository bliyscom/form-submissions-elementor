<?php
/**
 * Plugin Name: Form Submissions Table for Elementor
 * Description: A custom Elementor widget to display form submissions in a table.
 * Version: 1.0.0
 * Author: Babey Dimla Tonny - Bliyscom Technological Solutions
 * Author URI: https://www.bliyscom.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: form-submissions-table-for-elementor
 * Requires Plugins: elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function register_form_submissions_widget( $widgets_manager ) {
    require_once( __DIR__ . '/widgets/form-submissions-widget.php' );
    $widgets_manager->register( new \Form_Submissions_Table_Widget() );
}
add_action( 'elementor/widgets/register', 'register_form_submissions_widget' );