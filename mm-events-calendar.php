<?php

/**
 * Plugin Name:       MM Events Calendar
 * Description:       A simple events calendar plugin for WordPress.
 * Version:           1.0.0
 * Author:            MapilitMedia
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       mm-events-calendar
 */


defined('ABSPATH') || exit;

/*
    * Define constants
    */

define('MMEC_VERSION', '1.0.0');
define('MMEC_DB_VERSION', '1.0');
define('MMEC_OPTION_DB_VERSION', 'mmec_db_version');


function mmec_table_name() : string {
    global $wpdb;
    return $wpdb->prefix . 'mm_ec_events';
}

register_activation_hook(__FILE__, 'mmec_activate');

function mmec_activate() {
    //load dbDelta

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    global $wpdb;

    $table = mmec_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `{$table}` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `starts_at` DATETIME NOT NULL,
        `ends_at` DATETIME NOT NULL,
        `location` VARCHAR(255) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `starts_at_idx` (`starts_at`)
    ) {$charset_collate};";

    dbDelta($sql);

    update_option(MMEC_OPTION_DB_VERSION, MMEC_DB_VERSION);

}

add_action('admin_menu', 'mmec_register_admin_menu');

function mmec_register_admin_menu() {
    add_menu_page(
        __('Events','mm-events-calendar'),
        __('Events','mm-events-calendar'),
        'manage_options',
        'mmec_events',
        'mmec_render_events_page',
        'dashicons-calendar-alt',
        20
    );
}

function mmec_render_events_page() {
    global $wpdb;
    $table = mmec_table_name();

    if (isset($_POST['mmec_submit_event']) && check_admin_referer('mmec_add_event')){
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $starts_at = sanitize_text_field($_POST['starts_at']);
        $ends_at = sanitize_text_field($_POST['ends_at']);
        $location = sanitize_text_field($_POST['mmec_event_location']);
        $now = current_time('mysql');

        $wpdb->insert($table, [
            'title' => $title,
            'description' => $description,
            'starts_at' => $starts_at,
            'ends_at' => $ends_at,
            'location' => $location,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        echo '<div class="notice notice-success is-dismissible"><p>' . __('Event added successfully.', 'mm-events-calendar') . '</p></div>';
    }

    //render form
    echo '<div class="wrap"><h1>' . __('Add New Event', 'mm-events-calendar') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('mmec_add_event');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="title"><?php _e('Title', 'mm-events-calendar'); ?></label></th>
            <td><input name="title" type="text" id="title" value="" class="regular-text" required></td>
        </tr>
        <tr>
            <th scope="row"><label for="description"><?php _e('Description', 'mm-events-calendar'); ?></label></th>
            <td><textarea name="description" id="description" class="large-text" rows="5" required></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="starts_at"><?php _e('Start Date & Time', 'mm-events-calendar'); ?></label></th>
            <td><input name="starts_at" type="datetime-local" id="starts_at" value="" class="regular-text" required></td>
        </tr>
        <tr>
            <th scope="row"><label for="ends_at"><?php _e('End Date & Time', 'mm-events-calendar'); ?></label></th>
            <td><input name="ends_at" type="datetime-local" id="ends_at" value="" class="regular-text" required></td>
        </tr>
        <tr>
            <th scope="row"><label for="location"><?php _e('Location', 'mm-events-calendar'); ?></label></th>
            <td><input name="mmec_event_location" type="text" id="location" value="" class="regular-text" required></td>
        </tr>
    </table>
    <?php
    submit_button(__('Add Event', 'mm-events-calendar'), 'primary', 'mmec_submit_event');
    echo '</form></div>';

    // Fetch all events
$events = $wpdb->get_results("SELECT * FROM  {$table} ORDER BY starts_at ASC");

if($events){
    echo '<div class="wrap"><h2>' . __('All Events', 'mm-events-calendar') . '</h2>';
    echo '<table class="widefat fixed" cellspacing="0"><thead><tr>'
        . '<th id="id" class="manage-column column-id" scope="col">' . __('ID', 'mm-events-calendar') . '</th>'
        . '<th id="title" class="manage-column column-title" scope="col">' . __('Title', 'mm-events-calendar') . '</th>'
        . '<th id="description" class="manage-column column-description" scope="col">' . __('Description', 'mm-events-calendar') . '</th>'
        . '<th id="starts_at" class="manage-column column-starts_at" scope="col">' . __('Starts At', 'mm-events-calendar') . '</th>'
        . '<th id="ends_at" class="manage-column column-ends_at" scope="col">' . __('Ends At', 'mm-events-calendar') . '</th>'
        . '<th id="location" class="manage-column column-location" scope="col">' . __('Location', 'mm-events-calendar') . '</th>'
        . '<th id="created_at" class="manage-column column-created_at" scope="col">' . __('Created At', 'mm-events-calendar') . '</th>'
        . '<th id="updated_at" class="manage-column column-updated_at" scope="col">' . __('Updated At', 'mm-events-calendar') . '</th>'
        . '</tr></thead><tbody>';

    foreach($events as $event){
        echo '<tr>'
            . '<td class="column-id">' . esc_html($event->id) . '</td>'
            . '<td class="column-title">' . esc_html($event->title) . '</td>'
            . '<td class="column-description">' . esc_html($event->description) . '</td>'
            . '<td class="column-starts_at">' . esc_html($event->starts_at) . '</td>'
            . '<td class="column-ends_at">' . esc_html($event->ends_at) . '</td>'
            . '<td class="column-location">' . esc_html($event->location) . '</td>';
        echo '</tr>';

}

    echo '</tbody></table></div>';

}

}

add_shortcode('mmec_event_form', function(){
    global $wpdb;
    $table = mmec_table_name();
    $out = '';
    
    if (!empty($_POST['mmec_front_submit']) && isset($_POST['mmec_front_nonce']) && wp_verify_nonce($_POST['mmec_front_nonce'], 'mmec_front_add_event')) {
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $starts_at = sanitize_text_field($_POST['starts_at']);
        $ends_at = sanitize_text_field($_POST['ends_at']);
        $location = sanitize_text_field($_POST['location']);
        $now = current_time('mysql');

        $to_mysql = function($val) {
            if ($val === '') return '';
            $val = str_replace('T', ' ', $val);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $val)) $val .= ':00';
            return $val;
        };
        $starts_at = $to_mysql($starts_at);
        $ends_at   = $to_mysql($ends_at);

        $errors = [];
        if ($title === '') {
            $errors[] = __('Title is required.', 'mm-events-calendar');
        }
        if ($starts_at === '') {
            $errors[] = __('Start date & time is required.', 'mm-events-calendar');
        }

        if (empty($errors)) {
            $wpdb->insert($table, [
                'title' => $title,
                'description' => $description,
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
                'location' => $location,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            $out .= '<div class="mmec-notice mmec-success">' . __('Event added successfully.', 'mm-events-calendar') . '</div>';
        } else {
            foreach ($errors as $error) {
                $out .= '<div class="mmec-notice mmec-error">' . esc_html($error) . '</div>';
            }
        }

    }

    //simple form
    // Simple form
    $val = fn($k) => isset($_POST[$k]) ? esc_attr($_POST[$k]) : '';

    $out .= '<form class="mmec-form" method="post" action="' . esc_url(get_permalink()) . '">';
$out .= wp_nonce_field('mmec_front_add_event', '_mmec_nonce', true, false);
$out .= '<input type="hidden" name="mmec_front_submit" value="1" />';

$out .= '<div class="mmec-row">
            <label for="mmec_title">'. esc_html__('Title','mm-events-calendar') .'</label>
            <input id="mmec_title" name="title" type="text" value="'. $val('title') .'" required>
        </div>';

$out .= '<div class="mmec-row">
            <label for="mmec_description">'. esc_html__('Description','mm-events-calendar') .'</label>
            <textarea id="mmec_description" name="description" rows="5">'. $val('description') .'</textarea>
        </div>';

$out .= '<div class="mmec-row">
            <label for="mmec_starts">'. esc_html__('Start Date & Time','mm-events-calendar') .'</label>
            <input id="mmec_starts" name="starts_at" type="datetime-local" value="'. $val('starts_at') .'" required>
        </div>';

$out .= '<div class="mmec-row">
            <label for="mmec_ends">'. esc_html__('End Date & Time','mm-events-calendar') .'</label>
            <input id="mmec_ends" name="ends_at" type="datetime-local" value="'. $val('ends_at') .'">
        </div>';

$out .= '<div class="mmec-row">
            <label for="mmec_location">'. esc_html__('Location','mm-events-calendar') .'</label>
            <input id="mmec_location" name="location" type="text" value="'. $val('location') .'">
        </div>';

$out .= '<div class="mmec-actions"><button type="submit">'. esc_html__('Submit Event','mm-events-calendar') .'</button></div>';
$out .= '</form>';
    return $out;
    

});

function mmec_default_options(): array {
    return [
        'max_width'        => '640px',
        'border_radius'    => '8px',
        'label_color'      => '#111827',
        'input_border'     => '#cbd5e1',
        'button_bg'        => '#2271b1',
        'button_text'      => '#ffffff',
        'notice_success_bg'=> '#ecfdf5',
        'notice_error_bg'  => '#fef2f2',
    ];
}

function mmec_get_options(): array {
    $opts = get_option('mmec_form_styles', []);
    return array_merge(mmec_default_options(), is_array($opts) ? $opts : []);
}

add_action('admin_menu', function() {
    add_submenu_page(
        'mmec_events',
        __('Form Styles','mm-events-calendar'),
        __('Form Styles','mm-events-calendar'),
        'manage_options',
        'mmec_form_styles',
        'mmec_render_form_styles_page'
    );
});

add_action('admin_init', function() {
    register_setting('mmec_form_styles_group', 'mmec_form_styles',[
        'type' => 'array',
        'sanitize_callback' => function($in){
            $d = mmec_default_options();
            $hex = function($v,$fallback){ $v = sanitize_text_field($v ?? ''); return preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i',$v)?$v:$fallback; };
            return [
                'max_width'         => sanitize_text_field($in['max_width'] ?? $d['max_width']),
                'border_radius'     => sanitize_text_field($in['border_radius'] ?? $d['border_radius']),
                'label_color'       => $hex($in['label_color'] ?? '', $d['label_color']),
                'input_border'      => $hex($in['input_border'] ?? '', $d['input_border']),
                'button_bg'         => $hex($in['button_bg'] ?? '', $d['button_bg']),
                'button_text'       => $hex($in['button_text'] ?? '', $d['button_text']),
                'notice_success_bg' => $hex($in['notice_success_bg'] ?? '', $d['notice_success_bg']),
                'notice_error_bg'   => $hex($in['notice_error_bg'] ?? '', $d['notice_error_bg']),
            ];
        },
        'default' => mmec_default_options(),
    ]);
});

function mmec_render_form_styles_page() {
    if (!current_user_can('manage_options')) return;
    $o = mmec_get_options();
    ?>
    <div class="wrap">
      <h1><?php _e('MMEC Form Styles','mm-events-calendar'); ?></h1>
      <form method="post" action="options.php">
        <?php settings_fields('mmec_form_styles_group'); ?>
        <table class="form-table">
          <tr><th><label for="max_width">Form Max Width</label></th>
              <td><input id="max_width" name="mmec_form_styles[max_width]" class="regular-text" value="<?php echo esc_attr($o['max_width']); ?>"> <code>e.g. 640px</code></td></tr>
          <tr><th><label for="border_radius">Border Radius</label></th>
              <td><input id="border_radius" name="mmec_form_styles[border_radius]" class="regular-text" value="<?php echo esc_attr($o['border_radius']); ?>"></td></tr>
          <tr><th><label for="label_color">Label Color</label></th>
              <td><input id="label_color" name="mmec_form_styles[label_color]" class="regular-text" value="<?php echo esc_attr($o['label_color']); ?>"></td></tr>
          <tr><th><label for="input_border">Input Border Color</label></th>
              <td><input id="input_border" name="mmec_form_styles[input_border]" class="regular-text" value="<?php echo esc_attr($o['input_border']); ?>"></td></tr>
          <tr><th><label for="button_bg">Button Background</label></th>
              <td><input id="button_bg" name="mmec_form_styles[button_bg]" class="regular-text" value="<?php echo esc_attr($o['button_bg']); ?>"></td></tr>
          <tr><th><label for="button_text">Button Text</label></th>
              <td><input id="button_text" name="mmec_form_styles[button_text]" class="regular-text" value="<?php echo esc_attr($o['button_text']); ?>"></td></tr>
          <tr><th><label for="notice_success_bg">Success Notice BG</label></th>
              <td><input id="notice_success_bg" name="mmec_form_styles[notice_success_bg]" class="regular-text" value="<?php echo esc_attr($o['notice_success_bg']); ?>"></td></tr>
          <tr><th><label for="notice_error_bg">Error Notice BG</label></th>
              <td><input id="notice_error_bg" name="mmec_form_styles[notice_error_bg]" class="regular-text" value="<?php echo esc_attr($o['notice_error_bg']); ?>"></td></tr>
        </table>
        <?php submit_button(__('Save Styles','mm-events-calendar')); ?>
      </form>
    </div>
    <?php
}

add_action('wp_enqueue_scripts', function () {
    $o = mmec_get_options();
    $css = "
    .mmec-form{max-width:{$o['max_width']};margin:20px 0;padding:40px;border:1px solid {$o['input_border']};border-radius:{$o['border_radius']};background:#fff}
    .mmec-form .mmec-row{margin-bottom:12px}
    .mmec-form label{display:block;font-weight:600;margin-bottom:6px;color:{$o['label_color']}}
    .mmec-form input[type='text'],
    .mmec-form input[type='datetime-local'],
    .mmec-form textarea{width:100%;padding:10px;border:1px solid {$o['input_border']};border-radius:6px;background:#fff}
    .mmec-form .mmec-actions{margin-top:12px}
    .mmec-form button{padding:10px 14px;border:0;border-radius:6px;cursor:pointer;background:{$o['button_bg']};color:{$o['button_text']}}
    .mmec-alert{margin:12px 0;padding:12px;border-radius:6px}
    .mmec-alert--success{background:{$o['notice_success_bg']}}
    .mmec-alert--error{background:{$o['notice_error_bg']}}
    ";
    wp_register_style('mmec-inline', false);
    wp_enqueue_style('mmec-inline');
    wp_add_inline_style('mmec-inline', $css);
});

add_action('plugins_loaded', function() {
    //to be continued
});