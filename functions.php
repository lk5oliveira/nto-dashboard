<?php

/**
 * Add custom Zoom and Replay link fields to The Events Calendar (Tribe Events) editor.
 */

add_action('add_meta_boxes_tribe_events', 'add_zoom_replay_metabox');
function add_zoom_replay_metabox() {
    add_meta_box(
        'zoom_replay_links',
        __('Zoom and Replay Links', 'your-theme-textdomain'),
        'render_zoom_replay_metabox',
        'tribe_events',
        'normal',
        'high'
    );
}

function render_zoom_replay_metabox($post) {
    wp_nonce_field('zoom_replay_nonce', 'zoom_replay_nonce');

    $zoom_link   = get_post_meta($post->ID, '_zoom_link', true);
    $replay_link = get_post_meta($post->ID, '_replay_link', true);
    ?>
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-bottom: 15px;">
        <p style="margin: 0; font-size: 13px; color: #856404;">
            <strong>Note:</strong> These fields are optional but recommended for student access to live classes and replays.
        </p>
    </div>
    <p>
        <label for="zoom_link"><strong><?php _e('Zoom Link', 'your-theme-textdomain'); ?></strong></label>
        <input type="url" id="zoom_link" name="zoom_link" value="<?php echo esc_attr($zoom_link); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="replay_link"><strong><?php _e('Replay Link', 'your-theme-textdomain'); ?></strong></label>
        <input type="url" id="replay_link" name="replay_link" value="<?php echo esc_attr($replay_link); ?>" style="width:100%;" />
    </p>
    <?php
}

add_action('save_post_tribe_events', 'save_zoom_replay_fields', 10, 3);
function save_zoom_replay_fields($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!isset($_POST['zoom_replay_nonce']) || !wp_verify_nonce($_POST['zoom_replay_nonce'], 'zoom_replay_nonce')) return;

    $zoom_link = isset($_POST['zoom_link']) ? esc_url_raw(trim($_POST['zoom_link'])) : '';
    $replay_link = isset($_POST['replay_link']) ? esc_url_raw(trim($_POST['replay_link'])) : '';

    update_post_meta($post_id, '_zoom_link', $zoom_link);
    update_post_meta($post_id, '_replay_link', $replay_link);
}

/**
 * Expose custom fields to REST API for dashboard access
 */
add_action('rest_api_init', function() {
    register_rest_field('tribe_events', 'zoom_link', array(
        'get_callback' => function($object) {
            return get_post_meta($object['id'], '_zoom_link', true);
        },
        'schema' => array(
            'description' => 'Zoom meeting link for live class',
            'type' => 'string'
        )
    ));

    register_rest_field('tribe_events', 'replay_link', array(
        'get_callback' => function($object) {
            return get_post_meta($object['id'], '_replay_link', true);
        },
        'schema' => array(
            'description' => 'Replay/recording link for class',
            'type' => 'string'
        )
    ));
});

?>