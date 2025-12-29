<?php
/*
Plugin Name: Client List Shortcode
Description: Displays a client list from a user-selected text file via shortcode, with optional 3-column tabbed navigation.
Version: 1.3.0
Author: Chris Eklund
Text Domain: client-list-shortcode
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;
add_action( 'init', 'cls_load_textdomain' );
function cls_load_textdomain() {
    load_plugin_textdomain(
        'client-list-shortcode',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

// Register admin settings page
function cls_add_settings_page() {
    add_options_page(
        __( 'Client List Settings', 'client-list-shortcode' ),
        __( 'Client List', 'client-list-shortcode' ),
        'manage_options',
        'cls-client-list',
        'cls_render_settings_page'
    );    
}
add_action('admin_menu', 'cls_add_settings_page');

// Render settings page for file selection/upload
function cls_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Client List Settings', 'client-list-shortcode' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cls_client_list_options');
            do_settings_sections('cls_client_list');
            ?>
            <input type="text" name="cls_client_list_file" id="cls_client_list_file" value="<?php echo esc_attr(get_option('cls_client_list_file')); ?>" style="width:70%;" readonly>
            <input type="button" class="button" value="<?php esc_attr_e( 'Select File', 'client-list-shortcode' ); ?>" id="cls_select_file_button">
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register and sanitize option
function cls_register_settings() {
    register_setting('cls_client_list_options', 'cls_client_list_file', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw'
    ]);
}
add_action('admin_init', 'cls_register_settings');

// Convert file URL to absolute path (works with local uploads)
function cls_url_to_path( $url ) {
    $upload_dir = wp_get_upload_dir();
    if ( ! empty( $upload_dir['error'] ) ) {
        return '';
    }

    $base_url = $upload_dir['baseurl'];
    $base_dir = $upload_dir['basedir'];

    if ( strncmp( $url, $base_url, strlen( $base_url ) ) === 0 ) {
        return $base_dir . substr( $url, strlen( $base_url ) );
    }

    return '';
}

// Helper: render client rows in 3 columns
function cls_render_clients_rows($clients) {
    $out = '';
    $columns = 3;
    $col = 1;
    foreach($clients as $client) {
        $client = esc_html(trim($client));
        if ($col == 1) $out .= '<div class="client-table-row">';
        $out .= '<div class="client-table-cell">' . $client . '</div>';
        if ($col == $columns) {
            $out .= '</div>';
            $col = 1;
        } else {
            $col++;
        }
    }
    // After all clients: fill empty cells if last row incomplete
    if ($col !== 1) {
        while ($col <= $columns) {
            $out .= '<div class="client-table-cell"></div>';
            $col++;
        }
        $out .= '</div>';
    }
    return $out;
}

//helper function to filter clients by letter
function cls_filter_clients_by_letter( array $clients, $letter ) {
    $letter = strtoupper( (string) $letter );

    if ( $letter === '' ) {
        return $clients;
    }

    return array_filter( $clients, function ( $c ) use ( $letter ) {
        $first = strtoupper( substr( trim( $c ), 0, 1 ) );

        if ( $letter === 'A' ) {
            return $first === 'A' || is_numeric( $first );
        }

        return $first === $letter;
    } );
}

// Main shortcode logic with tab option
function cls_client_list_shortcode($atts = []) {
    $atts = shortcode_atts([
        'letter' => '',
        'show_tabs' => 'true'
    ], $atts, 'client_list');

    $file_url = get_option('cls_client_list_file');
    $clients = [];
    if ($file_url) {
        $file_path = cls_url_to_path($file_url);
        if (file_exists($file_path) && is_readable($file_path)) {
            $clients = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
    }

    $clients = apply_filters( 'cls_client_list_clients', $clients, $atts );


    $letters = range('A', 'Z');
    $show_tabs = filter_var($atts['show_tabs'], FILTER_VALIDATE_BOOLEAN);

    $output = '';

    if (empty($clients)) {
        $output .= '<p>' . esc_html__( 'No client list file selected or file is unreadable.', 'client-list-shortcode' ) . '</p>';
        return $output;
    }

    if ($show_tabs) {
        $output .= '<div class="client-tabs-container">';
        $output .= '<div class="client-tabs-nav" role="tablist" aria-label="' . esc_attr__( 'Client list tabs', 'client-list-shortcode' ) . '">
            <button class="client-tab-btn active" role="tab" aria-selected="true" tabindex="0" data-tab="All">All</button>';
        foreach ( $letters as $letter ) {
            $output .= sprintf(
                '<button class="client-tab-btn" role="tab" aria-selected="false" tabindex="-1" data-tab="%1$s">%2$s</button>',
                esc_attr( $letter ),
                esc_html( $letter )
            );
        }
        $output .= '</div>';

        // All tab panel (all clients)
        $output .= '<div class="client-tabs-panel active" role="tabpanel" tabindex="0" data-panel="All">';
        $output .= cls_render_clients_rows($clients);
        $output .= '</div>';

        // Panels for each letter
       // Panels for each letter
        foreach ( $letters as $letter ) {
            $filtered = cls_filter_clients_by_letter( $clients, $letter );
            $filtered = apply_filters( 'cls_client_list_filtered_clients', $filtered, $letter, $atts );

            $output .= '<div class="client-tabs-panel" role="tabpanel" tabindex="0" data-panel="' . esc_attr( $letter ) . '">';
            $output .= cls_render_clients_rows( $filtered );
            $output .= '</div>';
        }


        $output .= '</div>';
        
    } else {
        $filtered = cls_filter_clients_by_letter( $clients, $atts['letter'] );
        $filtered = apply_filters( 'cls_client_list_filtered_clients', $filtered, $atts['letter'], $atts );

        $output .= '<div class="client-table-parent">';
        $output .= cls_render_clients_rows( $filtered );
        $output .= '</div>';
    }
    return $output;
}
add_shortcode('client_list', 'cls_client_list_shortcode');

// Media Uploader admin scripts
function cls_enqueue_media_uploader( $hook ) {
    if ( $hook === 'settings_page_cls-client-list' ) {
        wp_enqueue_media();
        wp_enqueue_script(
            'cls-client-list-admin',
            plugin_dir_url( __FILE__ ) . 'assets/js/client-list-admin.js',
            [ 'jquery' ],
            '1.3.0',
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'cls_enqueue_media_uploader' );


function cls_enqueue_assets() {
    wp_enqueue_style(
        'cls-client-list',
        plugin_dir_url( __FILE__ ) . 'assets/css/client-list.css',
        [],
        '1.3.0'
    );
    
     wp_enqueue_script(
        'cls-client-list',
        plugin_dir_url( __FILE__ ) . 'assets/js/client-list.js',
        [],
        '1.3.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'cls_enqueue_assets' );
