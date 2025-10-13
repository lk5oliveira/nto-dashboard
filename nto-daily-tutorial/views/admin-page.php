<?php
/**
 * Admin page template for NTO Daily Tutorial
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div style="max-width: 800px; margin-top: 30px;">

        <!-- Current Tutorial Card -->
        <div class="card" style="padding: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0;">Current Daily Tutorial</h2>

            <?php if ($tutorial_data) : ?>
                <div style="display: flex; gap: 20px; align-items: start; margin-bottom: 20px;">
                    <?php if ($tutorial_data['thumbnail']) : ?>
                        <img src="<?php echo esc_url($tutorial_data['thumbnail']); ?>"
                             alt="<?php echo esc_attr($tutorial_data['title']); ?>"
                             style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px;" />
                    <?php else : ?>
                        <div style="width: 150px; height: 150px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <span class="dashicons dashicons-video-alt3" style="font-size: 48px; color: #999;"></span>
                        </div>
                    <?php endif; ?>

                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 10px 0; font-size: 18px;">
                            <a href="<?php echo esc_url($tutorial_data['url']); ?>" target="_blank">
                                <?php echo esc_html($tutorial_data['title']); ?>
                            </a>
                        </h3>
                        <p style="margin: 0 0 5px 0; color: #666;">
                            <strong>Course:</strong> <?php echo esc_html($tutorial_data['course_title']); ?>
                        </p>
                        <p style="margin: 0 0 5px 0; color: #666;">
                            <strong>Lesson ID:</strong> <?php echo esc_html($tutorial_data['id']); ?>
                        </p>
                        <p style="margin: 0; color: #666;">
                            <strong>Selected At:</strong> <?php echo esc_html($tutorial_data['selected_at']); ?>
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <a href="<?php echo esc_url($tutorial_data['url']); ?>"
                       class="button button-secondary"
                       target="_blank">
                        <span class="dashicons dashicons-external" style="vertical-align: middle;"></span> View Lesson
                    </a>
                    <a href="<?php echo esc_url(get_edit_post_link($tutorial_data['id'])); ?>"
                       class="button button-secondary"
                       target="_blank">
                        <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span> Edit Lesson
                    </a>
                    <button type="button"
                            id="nto-refresh-tutorial"
                            class="button button-primary">
                        <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Change Tutorial Now
                    </button>
                </div>
            <?php else : ?>
                <div style="padding: 40px; text-align: center; background: #f9f9f9; border-radius: 8px;">
                    <span class="dashicons dashicons-warning" style="font-size: 48px; color: #f0ad4e;"></span>
                    <p style="margin: 10px 0 20px 0; color: #666;">No tutorial is currently set.</p>
                    <button type="button" id="nto-refresh-tutorial" class="button button-primary">
                        <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Generate Tutorial
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Settings -->
        <div class="card" style="padding: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
            <h2 style="margin-top: 0;">Settings</h2>

            <form id="nto-settings-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nto-category-select">Tutorial Category</label>
                        </th>
                        <td>
                            <select id="nto-category-select" name="category" class="regular-text" style="min-width: 300px;">
                                <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                                    <?php foreach ($categories as $category) : ?>
                                        <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>>
                                            <?php echo esc_html($category->name); ?> (<?php echo esc_html($category->count); ?> courses)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <option value="">No categories found</option>
                                <?php endif; ?>
                            </select>
                            <p class="description">
                                Select which category to pull tutorial lessons from. Only categories used by LearnDash courses are shown.
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary" id="nto-save-settings">
                        Save Settings
                    </button>
                    <span id="nto-settings-message" style="margin-left: 10px; display: none;"></span>
                </p>
            </form>
        </div>

        <!-- Schedule Info -->
        <div class="card" style="padding: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
            <h2 style="margin-top: 0;">Automatic Rotation Schedule</h2>
            <p style="margin: 0 0 10px 0;">
                <strong>Next Automatic Rotation:</strong> <?php echo esc_html($next_rotation_time); ?>
            </p>
            <p style="margin: 0; color: #666; font-size: 13px;">
                The tutorial automatically rotates daily at midnight. You can manually change it anytime using the button above.
            </p>
        </div>

        <!-- How It Works -->
        <div class="card" style="padding: 20px; background: #f9f9f9; margin-top: 20px;">
            <h2 style="margin-top: 0;">How It Works</h2>
            <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                <li>Plugin selects a random lesson from courses in your selected category</li>
                <li>The same tutorial is shown to all users for the entire day</li>
                <li>Automatically rotates at midnight UK time</li>
                <li>Avoids showing the same tutorial within 30 days</li>
                <li>You can manually change the tutorial or category anytime</li>
            </ul>
        </div>

    </div>
</div>

<style>
#nto-refresh-tutorial.loading {
    opacity: 0.6;
    pointer-events: none;
}
#nto-refresh-tutorial.loading .dashicons {
    animation: rotation 1s infinite linear;
}
@keyframes rotation {
    from { transform: rotate(0deg); }
    to { transform: rotate(359deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Refresh tutorial button
    $('#nto-refresh-tutorial').on('click', function(e) {
        e.preventDefault();

        const $button = $(this);
        $button.addClass('loading');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nto_refresh_tutorial',
                nonce: '<?php echo wp_create_nonce('nto_refresh_tutorial'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Failed to refresh tutorial'));
                    $button.removeClass('loading');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.removeClass('loading');
            }
        });
    });

    // Save settings form
    $('#nto-settings-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $button = $('#nto-save-settings');
        const $message = $('#nto-settings-message');
        const category = $('#nto-category-select').val();

        $button.prop('disabled', true).text('Saving...');
        $message.hide();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nto_save_settings',
                nonce: '<?php echo wp_create_nonce('nto_save_settings'); ?>',
                category: category
            },
            success: function(response) {
                if (response.success) {
                    $message
                        .text('✓ ' + response.data.message)
                        .css('color', '#46b450')
                        .show();

                    // Optionally reload to refresh tutorial with new category
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $message
                        .text('✗ ' + (response.data || 'Failed to save settings'))
                        .css('color', '#dc3232')
                        .show();
                }
                $button.prop('disabled', false).text('Save Settings');
            },
            error: function() {
                $message
                    .text('✗ An error occurred. Please try again.')
                    .css('color', '#dc3232')
                    .show();
                $button.prop('disabled', false).text('Save Settings');
            }
        });
    });
});
</script>
