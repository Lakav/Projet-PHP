<?php
/*
 * Plugin Name: Post to Discord
 * Description: Envoie une notification sur un channel discord a chaque nouveau post.
 * Version: 1.0.0
 * Author: Jules Cavanier
*/

function post_to_discord($topicId){
    if(get_option('discord_webhook_url') == null)
        return;


    $topicTitle = bbp_get_topic_title($topicId);
    $topicURL = bbp_get_topic_permalink($topicId);
    $webhookURL = get_option('discord_webhook_url');

    $message = "Someone just posted **\"$topicTitle\"** for your reading pleasure: $topicURL";
    $postData = array('content' => $message);

    $curl = curl_init($webhookURL);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

    $response = curl_exec($curl);
    $errors = curl_error($curl);

    log_message($errors);
}

function log_message($log){
    if (true === WP_DEBUG) {
        if (is_array($log) || is_object($log)){
            error_log(print_r($log, true));
        }else{
            error_log($log);
        }
    }
}

add_action('bbp_new_topic_post_extras', 'post_to_discord', 10, 1);
add_action('admin_menu', 'create_settings_page');
add_action('admin_init', 'setup_sections');
add_action('admin_init', 'setup_fields');

function create_settings_page()
{
        $page_title = 'Settings Page';
        $menu_title = 'Discord plugin';
        $capability = 'manage_options';
        $slug = 'webhook_fields';
        $callback = 'plugin_settings_content';
        $icon = 'dashicons-admin-plugins';
        $position = 100;

        add_submenu_page('options-general.php', $page_title, $menu_title, $capability, $slug, $callback);
}

function plugin_settings_content()
{ ?>
        <div class="wrap">
            <h2>Discord plugin Settings Page</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('webhook_fields');
                do_settings_sections('webhook_fields');
                submit_button();
                ?>
            </form>
        </div> <?php
}

function setup_sections()
{
        add_settings_section('discord_section', 'Discord', 'section_callback', 'webhook_fields');
}

function section_callback($arguments)
{
        switch ($arguments['id']) {
            case 'discord_section':
                echo 'Discord settings';
                break;
        }
}

function setup_fields()
{
        $fields = array(
            array(
                'uid' => 'discord_webhook_url',
                'label' => 'Webhook URL',
                'section' => 'discord_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => 'https://discord.com/...'
            )
        );
        foreach ($fields as $field) {
            add_settings_field($field['uid'], $field['label'], 'field_callback', 'webhook_fields', $field['section'], $field);
            register_setting('webhook_fields', $field['uid']);
        }
}

function field_callback($arguments)
{
        $value = get_option($arguments['uid']);
        if (!$value) {
            $value = "";
        }

        printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value);
}