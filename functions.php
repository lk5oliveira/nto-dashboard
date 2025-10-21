<?php

/**
 * Add custom Zoom and Replay link fields to The Events Calendar (Tribe Events) editor.
 */

add_action('add_meta_boxes_tribe_events', 'add_zoom_replay_metabox');
function add_zoom_replay_metabox() {
    add_meta_box(
        'zoom_replay_links',
        __('Event Details: Links & Group Access', 'your-theme-textdomain'),
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
    $event_group = get_post_meta($post->ID, '_event_group', true);
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
    <p>
        <label for="event_group"><strong><?php _e('Restrict to LearnDash Group', 'your-theme-textdomain'); ?></strong></label>
        <select id="event_group" name="event_group" style="width:100%;">
            <option value=""><?php _e('No restriction - Available to all members', 'your-theme-textdomain'); ?></option>
            <?php
            // Get all LearnDash groups
            $groups = get_posts(array(
                'post_type' => 'groups',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));

            foreach ($groups as $group) {
                $selected = ($event_group == $group->ID) ? 'selected' : '';
                echo '<option value="' . esc_attr($group->ID) . '" ' . $selected . '>' . esc_html($group->post_title) . '</option>';
            }
            ?>
        </select>
        <small style="color: #666; display: block; margin-top: 5px;">
            <?php _e('Select a LearnDash group to restrict this event visibility on the dashboard.', 'your-theme-textdomain'); ?>
        </small>
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
    $event_group = isset($_POST['event_group']) ? absint($_POST['event_group']) : '';

    update_post_meta($post_id, '_zoom_link', $zoom_link);
    update_post_meta($post_id, '_replay_link', $replay_link);
    update_post_meta($post_id, '_event_group', $event_group);
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

    register_rest_field('tribe_events', 'event_group', array(
        'get_callback' => function($object) {
            return get_post_meta($object['id'], '_event_group', true);
        },
        'schema' => array(
            'description' => 'LearnDash group ID that can access this event',
            'type' => 'integer'
        )
    ));
});

/**
 * Modify The Events Calendar REST API to include hidden events when requested
 */
add_filter('tribe_rest_events_archive_repository_args', function($args, $request) {
    // Check if include_hidden parameter is set to true
    $include_hidden = $request->get_param('include_hidden');

    if ($include_hidden === 'true' || $include_hidden === '1') {
        // Remove the filter that hides events from listings
        if (isset($args['meta_query'])) {
            // Filter out the _EventHideFromUpcoming meta query
            $args['meta_query'] = array_filter($args['meta_query'], function($query) {
                return !isset($query['key']) || $query['key'] !== '_EventHideFromUpcoming';
            });
        }
    }

    return $args;
}, 10, 2);

?>