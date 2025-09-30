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

    $out .= '<form method="post">';
    $out .= wp_nonce_field('mmec_front_add_event', 'mmec_front_nonce', true, false);
    $out .= '<input type="hidden" name="mmec_front_submit" value="1" />';
    $out .= '<p><label>Title<br><input type="text" name="title" value="'. $val('title') .'" required></label></p>';
    $out .= '<p><label>Description<br><textarea name="description" rows="5">'. $val('description') .'</textarea></label></p>';
    $out .= '<p><label>Start Date & Time<br><input type="datetime-local" name="starts_at" value="'. $val('starts_at') .'" required></label></p>';
    $out .= '<p><label>End Date & Time<br><input type="datetime-local" name="ends_at" value="'. $val('ends_at') .'"></label></p>';
    $out .= '<p><label>Location<br><input type="text" name="location" value="'. $val('location') .'"></label></p>';
    $out .= '<p><button type="submit">Submit Event</button></p>';
    $out .= '</form>';

    return $out;
    

});


add_action('plugins_loaded', function() {
    //to be continued
});