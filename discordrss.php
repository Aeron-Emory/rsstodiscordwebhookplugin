<?php
/**
 * Plugin Name: Discord RSS to Webhook
 * Description: Posts RSS feed to discord chat using webhooks.
 * Version: 1.0.0
 * Author: Aeron Emory
 */

// Initialize the plugin
function discordrss_init() {
    // Add initialization code here
    // For example, you can set up the plugin settings
    add_action('admin_menu', 'discordrss_settings_menu');
    add_action('discordrss_fetch_feed', 'discordrss_fetch_and_post');
}
add_action('init', 'discordrss_init');

// Display plugin settings in the footer
function discordrss_display_settings() {
    echo '<h2>Discord RSS to Webhook Settings</h2>';
    echo '<p>Enter the RSS feed URL and the Discord webhook URL below:</p>';
    echo '<form method="post" action="options.php">';
    settings_fields('discordrss_settings_group');
    do_settings_sections('discordrss_settings_page');
    echo '<input type="submit" name="submit" value="Save Settings" />';
    echo '</form>';

    // Check if the webhook URL is set and display a test button
    $webhook_url = get_option('discordrss_webhook_url');
    if ($webhook_url) {
        echo '<h3>Test the Discord Webhook</h3>';
        echo '<p>Click the button below to test the Discord webhook:</p>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="discordrss_test_webhook" value="1" />';
        echo '<input type="submit" name="submit" value="Test Webhook" />';
        echo '</form>';
    }
}
add_action('admin_footer', 'discordrss_display_settings');

// Add plugin settings menu
function discordrss_settings_menu() {
    add_options_page('Discord RSS to Webhook Settings', 'Discord RSS to Webhook', 'manage_options', 'discordrss_settings', 'discordrss_display_settings');
}
add_action('admin_menu', 'discordrss_settings_menu');

// Register plugin settings
function discordrss_register_settings() {
    register_setting('discordrss_settings_group', 'discordrss_feed_url');
    register_setting('discordrss_settings_group', 'discordrss_webhook_url');

    add_settings_section(
        'discordrss_settings_section',
        'Discord RSS to Webhook Settings',
        '',
        'discordrss_settings_page'
    );

    add_settings_field(
        'discordrss_feed_url',
        'RSS Feed URL',
        'discordrss_feed_url_callback',
        'discordrss_settings_page',
        'discordrss_settings_section'
    );

    add_settings_field(
        'discordrss_webhook_url',
        'Discord Webhook URL',
        'discordrss_webhook_url_callback',
        'discordrss_settings_page',
        'discordrss_settings_section'
    );
}
add_action('admin_init', 'discordrss_register_settings');

// Callback function for the RSS Feed URL setting
function discordrss_feed_url_callback() {
    $feed_url = get_option('discordrss_feed_url');
    echo '<input type="text" name="discordrss_feed_url" value="' . esc_attr($feed_url) . '" />';
}

// Callback function for the Discord Webhook URL setting
function discordrss_webhook_url_callback() {
    $webhook_url = get_option('discordrss_webhook_url');
    echo '<input type="text" name="discordrss_webhook_url" value="' . esc_attr($webhook_url) . '" />';
}

// Handle test webhook request
function discordrss_handle_test_webhook() {
    if (isset($_POST['discordrss_test_webhook'])) {
        $webhook_url = get_option('discordrss_webhook_url');

        // Post a test message to the Discord webhook
        $data = array(
            'content' => 'This is a test message from the Discord RSS to Webhook plugin.',
        );

        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        );

        $response = wp_remote_post($webhook_url, $args);

        if (is_wp_error($response)) {
            echo 'Failed to send test message.';
        } else {
            echo 'Test message sent successfully.';
        }
    }
}
add_action('admin_init', 'discordrss_handle_test_webhook');

// Fetch and post the RSS feed to Discord
function discordrss_fetch_and_post() {
    $feed_url = get_option('discordrss_feed_url');
    $webhook_url = get_option('discordrss_webhook_url');

    // Fetch the RSS feed
    $response = wp_remote_get($feed_url);
    if (is_wp_error($response)) {
        // Handle error, display a message, or log the error
        return;
    }

    // Parse the RSS feed
    $body = wp_remote_retrieve_body($response);
    $feed = simplexml_load_string($body);

    if ($feed === false) {
        // Handle error, display a message, or log the error
        return;
    }

    // Loop through each item in the RSS feed
    foreach ($feed->channel->item as $item) {
        $title = (string) $item->title;
        $description = (string) $item->description;
        $link = (string) $item->link;

        // Post the data to the Discord webhook
        $data = array(
            'content' => '**' . $title . '**' . PHP_EOL . $description . PHP_EOL . $link,
        );

        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        );

        $response = wp_remote_post($webhook_url, $args);

        if (is_wp_error($response)) {
            // Handle error, display a message, or log the error
            continue;
        }
    }
}

// Schedule the RSS feed fetch and post event
function discordrss_schedule_fetch() {
    wp_schedule_event(time(), 'hourly', 'discordrss_fetch_feed');
}
add_action('wp', 'discordrss_schedule_fetch');
