<?php
/**
 * Plugin Name: NTO Daily Tutorial
 * Description: Automatically rotates a daily tutorial from LearnDash courses in the Tutorials category
 * Version: 1.0.0
 * Author: The Nail Tech Org
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NTO_DAILY_TUTORIAL_VERSION', '1.0.0');
define('NTO_DAILY_TUTORIAL_PATH', plugin_dir_path(__FILE__));
define('NTO_DAILY_TUTORIAL_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class NTO_Daily_Tutorial {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Cron hook
        add_action('nto_daily_tutorial_rotation', array($this, 'rotate_tutorial'));

        // AJAX handlers for manual change and settings
        add_action('wp_ajax_nto_refresh_tutorial', array($this, 'ajax_refresh_tutorial'));
        add_action('wp_ajax_nto_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Schedule daily cron event at midnight UK time
        if (!wp_next_scheduled('nto_daily_tutorial_rotation')) {
            wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'nto_daily_tutorial_rotation');
        }

        // Set initial tutorial
        $this->rotate_tutorial();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron
        $timestamp = wp_next_scheduled('nto_daily_tutorial_rotation');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'nto_daily_tutorial_rotation');
        }
    }

    /**
     * Add admin menu under LearnDash
     */
    public function add_admin_menu() {
        add_submenu_page(
            'learndash-lms',
            'Daily Tutorial',
            'Daily Tutorial',
            'manage_options',
            'nto-daily-tutorial',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Get current tutorial data (already complete, no queries needed)
        $tutorial_data = get_option('nto_daily_tutorial');

        // Validate it's an array with required data
        if (!is_array($tutorial_data) || empty($tutorial_data)) {
            $tutorial_data = null;
        }

        // Get next cron run time
        $next_rotation = wp_next_scheduled('nto_daily_tutorial_rotation');
        $next_rotation_time = $next_rotation ? date('F j, Y g:i A', $next_rotation) : 'Not scheduled';

        // Get settings
        $selected_category = get_option('nto_daily_tutorial_category', 'nail-tutorials');

        // Get all LearnDash course categories
        $categories = get_terms(array(
            'taxonomy' => 'ld_course_category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        include NTO_DAILY_TUTORIAL_PATH . 'views/admin-page.php';
    }

    /**
     * Rotate tutorial - main logic
     */
    public function rotate_tutorial() {
        // Get selected category from settings
        $selected_category = get_option('nto_daily_tutorial_category', 'nail-tutorials');

        // Get all courses in the selected category
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'ld_course_category',
                    'field' => 'slug',
                    'terms' => $selected_category
                )
            )
        ));

        if (empty($courses)) {
            return false;
        }

        // Collect all lessons from these courses
        $all_lessons = array();
        foreach ($courses as $course) {
            $lessons = learndash_course_get_steps_by_type($course->ID, 'sfwd-lessons');
            if (!empty($lessons)) {
                $all_lessons = array_merge($all_lessons, $lessons);
            }
        }

        if (empty($all_lessons)) {
            return false;
        }

        // Get previously shown tutorials to avoid immediate repeats
        $shown_tutorials = get_option('nto_daily_tutorial_history', array());

        // Filter out recently shown (last 30 days)
        $available_lessons = array_diff($all_lessons, $shown_tutorials);

        // If all have been shown, reset the pool
        if (empty($available_lessons)) {
            $available_lessons = $all_lessons;
            $shown_tutorials = array();
        }

        // Pick random lesson
        $random_key = array_rand($available_lessons);
        $selected_lesson_id = $available_lessons[$random_key];

        // Get course ID for this lesson
        $course_id = learndash_get_course_id($selected_lesson_id);

        // Get course thumbnail (primary) with lesson thumbnail as fallback
        $thumbnail = get_the_post_thumbnail_url($course_id, 'medium');
        if (!$thumbnail) {
            $thumbnail = get_the_post_thumbnail_url($selected_lesson_id, 'medium');
        }

        // Build complete tutorial data array
        $tutorial_data = array(
            'id' => $selected_lesson_id,
            'title' => get_the_title($selected_lesson_id),
            'url' => get_permalink($selected_lesson_id),
            'thumbnail' => $thumbnail ? $thumbnail : '',
            'excerpt' => get_the_excerpt($selected_lesson_id),
            'course_id' => $course_id,
            'course_title' => $course_id ? get_the_title($course_id) : '',
            'selected_at' => current_time('mysql')
        );

        // Store complete tutorial data
        update_option('nto_daily_tutorial', $tutorial_data);

        // Add to history (keep last 30)
        $shown_tutorials[] = $selected_lesson_id;
        $shown_tutorials = array_slice($shown_tutorials, -30);
        update_option('nto_daily_tutorial_history', $shown_tutorials);

        return $tutorial_data;
    }

    /**
     * AJAX handler for manual refresh
     */
    public function ajax_refresh_tutorial() {
        check_ajax_referer('nto_refresh_tutorial', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $tutorial_data = $this->rotate_tutorial();

        if ($tutorial_data && is_array($tutorial_data)) {
            wp_send_json_success($tutorial_data);
        } else {
            wp_send_json_error('No tutorials available');
        }
    }

    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('nto_save_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        if (empty($category)) {
            wp_send_json_error('Category is required');
        }

        // Verify category exists
        $term = get_term_by('slug', $category, 'ld_course_category');
        if (!$term) {
            wp_send_json_error('Invalid category');
        }

        // Save setting
        update_option('nto_daily_tutorial_category', $category);

        wp_send_json_success(array(
            'message' => 'Settings saved successfully',
            'category' => $category,
            'category_name' => $term->name
        ));
    }
}

/**
 * Helper function to get current daily tutorial
 * Use this in your dashboard template
 *
 * Returns the stored tutorial data directly (no queries)
 */
function nto_get_daily_tutorial() {
    $tutorial_data = get_option('nto_daily_tutorial');

    // Return null if no tutorial set or invalid data
    if (empty($tutorial_data) || !is_array($tutorial_data)) {
        return null;
    }

    return $tutorial_data;
}

// Initialize plugin
function nto_daily_tutorial_init() {
    return NTO_Daily_Tutorial::get_instance();
}
add_action('plugins_loaded', 'nto_daily_tutorial_init');
