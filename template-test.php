<?php
/**
 * Template Name: Dashboard
 * Description: Custom dashboard template for The Nail Tech Org members
 */

// Redirect to login if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header();

$current_user = wp_get_current_user();
$first_name = $current_user->user_firstname ?: $current_user->display_name;
$user_id = get_current_user_id();

// Get user's LearnDash groups for conditional display
$user_groups = learndash_get_users_group_ids($user_id);
$user_groups_json = json_encode($user_groups);

// Check group membership (server-side for security)
$is_admin = current_user_can('administrator');
$admin_bypass = $is_admin; // Disable bypass during testing
$is_gold_member = $admin_bypass || in_array(4383, $user_groups);
$is_educator = $admin_bypass || in_array(272088, $user_groups);
$is_bbp = $admin_bypass || in_array(347879, $user_groups);
$is_bbp_vip = $admin_bypass || in_array(348042, $user_groups);
$has_any_group = $is_gold_member || $is_educator || $is_bbp || $is_bbp_vip;

// Check for WP Fusion tag "07. Course - BBI - Bonuses"
$has_bbi_bonuses = false;
if (function_exists('wp_fusion') && class_exists('WP_Fusion')) {
    $user_tags = wp_fusion()->user->get_tags($user_id);
    if (is_array($user_tags)) {
        // Check if the tag exists in the user's tags array
        // WP Fusion stores tags as tag IDs, so we need to check by tag name
        $has_bbi_bonuses = wp_fusion()->user->has_tag('07. Course - BBI - Bonuses', $user_id);
    }
}

// Generate nonce for REST API authentication
$rest_nonce = wp_create_nonce('wp_rest');
?>

<!-- Preconnect to Google Fonts for faster loading -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined&display=swap" rel="stylesheet"/>
<!-- Tailwind CSS CDN - Note: Using CDN for simplicity. For production, consider using a build process. -->
<script>
    // Suppress Tailwind CDN development warning
    (function() {
        const originalWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) {
                return; // Suppress Tailwind CDN warning
            }
            originalWarn.apply(console, args);
        };
    })();
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    sand: '#f6f1ea',
                    'dark-green': '#0d2726',
                    'dark-green-light': '#1a4544',
                },
                fontFamily: {
                    lato: ['Lato', 'sans-serif'],
                    montserrat: ['Montserrat', 'sans-serif'],
                },
            },
        },
    };
</script>
<style>
    /* Override theme background */
    body,
    #content,
    .site-content,
    #primary,
    .site-main {
        background: #f6f1ea !important;
    }

    .nto-dashboard h1,
    .nto-dashboard h2,
    .nto-dashboard h3,
    .nto-dashboard h4,
    .nto-dashboard h5,
    .nto-dashboard h6 {
        font-family: 'Montserrat', sans-serif;
    }

    /* Force h3 text color */
    .nto-dashboard h3 {
        color: #0d2726 !important;
    }

    /* White h3 for cards on dark backgrounds */
    .nto-dashboard .bg-dark-green h3,
    .nto-dashboard .bg-dark-green-light h3 {
        color: #ffffff !important;
    }

    .nto-dashboard {
        font-family: 'Lato', sans-serif;
    }

    /* Hide header on mobile */
    @media (max-width: 767px) {
        .container.site-header-container.flex.default-header {
            display: none !important;
        }
    }

    /* Hide onscreen notifications completely */
    .bb-onscreen-notification-enable.bb-onscreen-notification-enable-mobile-support {
        display: none !important;
    }

    /* Simple fade-in animation for live class alert */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease-out;
    }

    /* Christmas Banner Background */
    .christmas-bg-image {
        position: absolute;
        inset: 0;
        background-image: url('https://nto-web-media.storage.googleapis.com/wp-content/uploads/2025/12/02170937/unnamed-2.jpg');
        background-repeat: repeat-x;
        background-size: auto 100%;
        background-position: center;
        opacity: 0.15;
    }

    .christmas-bg-gradient {
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 50%, rgba(0, 0, 0, 0) 100%);
    }

    /* Snow Animation */
    @keyframes snowfall {
        0% {
            transform: translateY(-20px) translateX(0);
            opacity: 1;
        }
        100% {
            transform: translateY(140px) translateX(15px);
            opacity: 1;
        }
    }

    .christmas-snowflake {
        position: absolute;
        top: -20px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        animation: snowfall linear infinite;
        pointer-events: none;
        z-index: 5;
    }

    .christmas-snowflake:nth-child(1) { left: 5%; animation-duration: 7s; animation-delay: 0s; }
    .christmas-snowflake:nth-child(2) { left: 12%; animation-duration: 9s; animation-delay: 1s; font-size: 12px; }
    .christmas-snowflake:nth-child(3) { left: 18%; animation-duration: 8s; animation-delay: 2s; }
    .christmas-snowflake:nth-child(4) { left: 25%; animation-duration: 10s; animation-delay: 0.5s; font-size: 16px; }
    .christmas-snowflake:nth-child(5) { left: 32%; animation-duration: 7.5s; animation-delay: 1.5s; }
    .christmas-snowflake:nth-child(6) { left: 38%; animation-duration: 8.5s; animation-delay: 0s; font-size: 13px; }
    .christmas-snowflake:nth-child(7) { left: 45%; animation-duration: 9.5s; animation-delay: 2.5s; }
    .christmas-snowflake:nth-child(8) { left: 52%; animation-duration: 7s; animation-delay: 1s; font-size: 15px; }
    .christmas-snowflake:nth-child(9) { left: 58%; animation-duration: 8s; animation-delay: 0.5s; }
    .christmas-snowflake:nth-child(10) { left: 65%; animation-duration: 9s; animation-delay: 3s; font-size: 12px; }
    .christmas-snowflake:nth-child(11) { left: 72%; animation-duration: 8.5s; animation-delay: 1.5s; font-size: 14px; }
    .christmas-snowflake:nth-child(12) { left: 78%; animation-duration: 7.5s; animation-delay: 0.5s; }
    .christmas-snowflake:nth-child(13) { left: 85%; animation-duration: 9s; animation-delay: 2s; font-size: 13px; }
    .christmas-snowflake:nth-child(14) { left: 92%; animation-duration: 8s; animation-delay: 0s; font-size: 15px; }
    .christmas-snowflake:nth-child(15) { left: 8%; animation-duration: 10s; animation-delay: 3s; }
    .christmas-snowflake:nth-child(16) { left: 22%; animation-duration: 7.5s; animation-delay: 2.5s; font-size: 12px; }
    .christmas-snowflake:nth-child(17) { left: 42%; animation-duration: 8.5s; animation-delay: 1s; font-size: 16px; }
    .christmas-snowflake:nth-child(18) { left: 62%; animation-duration: 9s; animation-delay: 3.5s; }
    .christmas-snowflake:nth-child(19) { left: 75%; animation-duration: 7s; animation-delay: 2s; font-size: 14px; }
    .christmas-snowflake:nth-child(20) { left: 95%; animation-duration: 8.5s; animation-delay: 1.5s; font-size: 13px; }

    #live-class-loader {
        width: 100%;
        justify-items: center;
    }

    #alerts {
        flex-wrap: wrap;
    }

    /* Post content expand/collapse styles */
    .post-content-wrapper {
        position: relative;
    }

    .post-content-truncated {
        position: relative;
    }

    .post-content-truncated::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2em;
        background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 80%);
        pointer-events: none;
    }

    .see-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #0d2726;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .see-more-btn:active {
        transform: scale(0.95);
    }

    .see-more-chevron {
        font-size: 14px;
        transition: transform 0.3s ease;
    }

    .see-more-chevron.expanded {
        transform: rotate(180deg);
    }

    .post-content-expanded {
        max-height: 2000px;
        transition: max-height 0.3s ease-out;
    }

    .post-content-collapsed {
        max-height: 4em;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }

    /* Safari-compatible avatar and badge styles */
    .avatar-image {
        width: 2.5rem !important;
        height: 2.5rem !important;
        min-width: 2.5rem !important;
        min-height: 2.5rem !important;
        max-width: 2.5rem !important;
        max-height: 2.5rem !important;
        border-radius: 9999px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .group-badge {
        background-color: #0d2726;
        color: white;
        padding: 0.125rem 0.5rem !important;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 9px !important;
        line-height: 1.5;
    }

    /* Posts container spacing */
    .posts-container {
        display: flex;
        flex-direction: column;
        gap: 0.75rem; /* 12px spacing between posts */
    }

    /* View All Posts button spacing */
    .view-all-posts-btn {
        margin-top: 1rem !important; /* 16px spacing above button */
        display: block;
        text-align: center;
        background-color: #0d2726;
        color: white;
        font-weight: bold;
        padding: 0.75rem 1.5rem;
        border-radius: 9999px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }

    .view-all-posts-btn:hover {
        background-color: #1a4544;
    }

    /* Comments section styles */
    .comments-section {
        background-color: #f9fafb;
        border-radius: 0.5rem;
        padding: 0.5rem;
        margin-top: 0.75rem;
    }

    /* Cursor pointer helper */
    .cursor-pointer {
        cursor: pointer;
    }

    /* Safari-compatible reply textarea styles */
    .reply-textarea {
        flex: 1;
        font-size: 0.75rem !important;
        padding: 0.5rem !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        resize: none !important;
        line-height: 1.25rem;
    }

    .reply-textarea:focus {
        outline: 2px solid #0d2726;
        outline-offset: 2px;
    }
</style>

<div class="nto-dashboard" style="background: #f6f1ea; min-height: 100vh;padding: 0px">
    <!-- Welcome Banner -->
    <div class="max-w-7xl mx-auto pt-6 pb-4">
        <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-4">
            <div class="text-center md:text-left">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-dark-green mb-1">Hey, <?php echo esc_html($first_name); ?>! 👋</h1>
                <p class="text-sm sm:text-base text-gray-600">Welcome back to The Nail Tech Org</p>
            </div>
            <?php
            // Get user's last IN PROGRESS course
            $user_id = get_current_user_id();
            $last_course_id = null;
            $last_course_url = null;
            $last_course_title = '';
            $course_thumbnail = null;

            // Get all enrolled courses
            $user_courses = learndash_user_get_enrolled_courses($user_id);

            if (!empty($user_courses)) {
                // Find courses that are in progress (not completed)
                foreach ($user_courses as $course_id) {
                    $course_status = learndash_course_status($course_id, $user_id);

                    // Only show if course is in progress (not completed, not "not started")
                    if ($course_status === 'in_progress' || $course_status === 'In Progress') {
                        $last_course_id = $course_id;
                        break; // Take the first in-progress course
                    }
                }

                // If we found an in-progress course, get its details
                if ($last_course_id) {
                    $last_course_url = get_permalink($last_course_id);
                    $last_course_title = get_the_title($last_course_id);
                    $course_thumbnail = get_the_post_thumbnail_url($last_course_id, 'thumbnail');
                }
            }

            if ($last_course_url) : ?>
                <!-- Desktop version - header area -->
                <a href="<?php echo esc_url($last_course_url); ?>" class="hidden md:flex items-center gap-3 bg-dark-green text-white px-4 py-3 rounded-xl shadow-md hover:bg-dark-green-light transition-all group">
                    <?php if ($course_thumbnail) : ?>
                        <img src="<?php echo esc_url($course_thumbnail); ?>" alt="<?php echo esc_attr($last_course_title); ?>" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                    <?php endif; ?>
                    <div class="text-right flex-1">
                        <p class="text-xs opacity-80 mb-1">Continue Learning</p>
                        <p class="text-sm font-bold font-montserrat"><?php echo esc_html(wp_trim_words($last_course_title, 4)); ?></p>
                    </div>
                    <span class="material-icons-outlined text-2xl group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 12 Days of Christmas Banner -->
    <div id="christmas-banner" class="max-w-7xl mx-auto pb-4" style="display: none;">
        <div class="bg-white rounded-xl p-4 lg:p-5 shadow-md border-2 border-sand relative overflow-hidden">
            <!-- Background Image Layer -->
            <div class="absolute inset-0 rounded-xl overflow-hidden">
                <div class="christmas-bg-image"></div>
                <!-- Snowflakes -->
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-snowflake">❄</div>
                <div class="christmas-bg-gradient"></div>
            </div>

            <!-- Content Layer -->
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-2xl">🎄</span>
                    <div class="flex-1">
                        <h2 id="christmas-day-title" class="font-bold text-sm lg:text-base text-dark-green font-montserrat">
                            ON THE FIRST DAY OF CHRISTMAS, NTO GAVE TO ME...
                        </h2>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1">
                        <h3 id="christmas-tutorial-title" class="font-bold text-xs lg:text-sm text-dark-green mb-1">
                            <!-- Tutorial title will be inserted here -->
                        </h3>
                        <p id="christmas-tutorial-author" class="text-xs text-gray-600">
                            <!-- Author info will be inserted here -->
                        </p>
                    </div>
                    <a id="christmas-tutorial-link" href="#" class="inline-flex items-center gap-2 bg-dark-green text-white font-bold py-2 px-4 rounded-full hover:bg-dark-green-light transition-all text-xs flex-shrink-0">
                        View Tutorial <span class="material-icons-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8" style="padding:0px;padding-bottom: 2rem">

        <!-- Conditional Alerts Section - Flexible layout - Gold Members Only -->
        <?php if ($is_gold_member) : ?>
        <section id="alerts" class="flex flex-col md:flex-row gap-3 lg:gap-4 mb-6 relative">
            <!-- Loading indicator for live class check -->
            <div id="live-class-loader" class="inset-0 flex items-center justify-center text-dark-green opacity-60" style="display: none;">
                <div class="flex items-center gap-2">
                    <span class="material-icons-outlined text-lg animate-spin">refresh</span>
                    <span class="text-sm">Checking for live events...</span>
                </div>
            </div>
            <!-- Live Class Alert - Conditional -->
            <div id="live-class-alert" class="flex-1 bg-gradient-to-r from-dark-green to-dark-green-light rounded-xl p-4 lg:p-6 shadow-lg text-white" style="display: none;">
                <div class="flex items-center gap-3 mb-3 lg:mb-4">
                    <span class="material-icons-outlined text-4xl lg:text-5xl flex-shrink-0">live_tv</span>
                    <div class="flex-1">
                        <p id="live-class-title" class="font-bold text-base lg:text-lg font-montserrat">Live Class Today!</p>
                        <p id="live-class-time" class="text-xs lg:text-sm opacity-90">Don't miss today's session</p>
                    </div>
                </div>
                <a id="live-class-link" href="#" class="w-full md:w-auto bg-white text-dark-green font-bold py-2 px-6 rounded-full hover:bg-sand transition-all flex items-center justify-center gap-2">
                    Join Live <span class="material-icons-outlined text-lg">arrow_forward</span>
                </a>
            </div>

            <!-- Monday Check-in - Conditional -->
            <div id="monday-checkin" class="flex-1 bg-white rounded-xl p-4 lg:p-6 shadow-md border-2 border-sand" style="display: none;">
                <div class="flex items-center gap-3 mb-3 lg:mb-4">
                    <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green flex-shrink-0">edit_note</span>
                    <div class="flex-1">
                        <p class="font-bold text-base lg:text-lg text-dark-green font-montserrat">Monday Check In!</p>
                        <p class="text-xs lg:text-sm text-gray-600">Share wins, learnings & changes</p>
                    </div>
                </div>
                <a href="https://thenailtech.org/news-feed/" class="w-full md:w-auto bg-dark-green text-white font-bold py-2 px-6 rounded-full hover:bg-sand transition-all flex items-center justify-center gap-2">
                    Check In Now <span class="material-icons-outlined text-lg">arrow_forward</span>
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Group-Restricted Event Alerts - Dynamic for all group members -->
        <?php if ($has_any_group) : ?>
        <section id="group-event-alerts" class="flex flex-col md:flex-row gap-3 lg:gap-4 mb-6" style="display: none;">
            <div id="group-event-banner" class="flex-1 bg-white border-2 border-dark-green rounded-xl p-4 lg:p-6 shadow-md">
                <div class="flex items-center gap-3 mb-3 lg:mb-4">
                    <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green flex-shrink-0" id="group-event-icon">event</span>
                    <div class="flex-1">
                        <p id="group-event-title" class="font-bold text-base lg:text-lg text-dark-green font-montserrat"></p>
                        <p id="group-event-time" class="text-xs lg:text-sm text-gray-600"></p>
                    </div>
                </div>
                <a id="group-event-link" href="#" class="w-full md:w-auto bg-dark-green text-white font-bold py-2 px-6 rounded-full hover:bg-dark-green-light transition-all flex items-center justify-center gap-2" style="display: none;"></a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Brand Builder Programme Call Banners -->
        <?php if ($is_bbp || $is_bbp_vip) : ?>
        <section id="bbp-call-banners" class="flex flex-col md:flex-row gap-3 lg:gap-4 mb-6" style="display: none;">
            <!-- Banner 1: The Brand Builder Programme Call -->
            <?php if ($is_bbp) : ?>
            <div id="bbp-call-1" class="flex-1 bg-white border-2 border-dark-green rounded-xl p-4 lg:p-6 shadow-md" style="display: none;">
                <div class="flex items-center gap-3 mb-3 lg:mb-4">
                    <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green flex-shrink-0">video_call</span>
                    <div class="flex-1">
                        <p class="font-bold text-base lg:text-lg text-dark-green font-montserrat">The Brand Builder Programme - Call</p>
                        <p class="text-xs lg:text-sm text-gray-600">Today at 11:30am - 12:30pm (UK Time)</p>
                    </div>
                </div>
                <a href="https://us02web.zoom.us/j/88640707079?pwd=y4sOIgc4v2Abq6qXmXUI2671P2RdlE.1" target="_blank" class="w-full md:w-auto bg-dark-green text-white font-bold py-2 px-6 rounded-full hover:bg-dark-green-light transition-all flex items-center justify-center gap-2">
                    Join Call <span class="material-icons-outlined text-lg">video_camera_front</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Banner 2: The Brand Builder Programme VIP Call -->
            <?php if ($is_bbp_vip) : ?>
            <div id="bbp-call-2" class="flex-1 bg-white border-2 border-dark-green rounded-xl p-4 lg:p-6 shadow-md" style="display: none;">
                <div class="flex items-center gap-3 mb-3 lg:mb-4">
                    <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green flex-shrink-0">video_call</span>
                    <div class="flex-1">
                        <p class="font-bold text-base lg:text-lg text-dark-green font-montserrat">The Brand Builder Programme VIP - Call</p>
                        <p class="text-xs lg:text-sm text-gray-600">Today at 12:30pm - 1:30pm (UK Time)</p>
                    </div>
                </div>
                <a href="https://us02web.zoom.us/j/82282298336?pwd=USJb2yDHdaBiY9PmnYJFRCJXVLJPRF.1" target="_blank" class="w-full md:w-auto bg-dark-green text-white font-bold py-2 px-6 rounded-full hover:bg-dark-green-light transition-all flex items-center justify-center gap-2">
                    Join Call <span class="material-icons-outlined text-lg">video_camera_front</span>
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($last_course_url) : ?>
            <!-- Mobile version - Continue Learning Card -->
            <section class="md:hidden mb-6">
                <a href="<?php echo esc_url($last_course_url); ?>" class="flex items-center gap-3 bg-gradient-to-r from-dark-green to-dark-green-light text-white p-4 rounded-xl shadow-md active:scale-95 transition-all">
                    <?php if ($course_thumbnail) : ?>
                        <img src="<?php echo esc_url($course_thumbnail); ?>" alt="<?php echo esc_attr($last_course_title); ?>" class="w-16 h-16 rounded-lg object-cover flex-shrink-0" />
                    <?php else : ?>
                        <div class="w-16 h-16 bg-dark-green-light rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="material-icons-outlined text-3xl">menu_book</span>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs opacity-80 mb-1">Continue Learning</p>
                        <p class="text-sm font-bold font-montserrat"><?php echo esc_html(wp_trim_words($last_course_title, 6)); ?></p>
                    </div>
                    <span class="material-icons-outlined text-2xl flex-shrink-0">arrow_forward</span>
                </a>
            </section>
        <?php endif; ?>

        <!-- Desktop: Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">

            <!-- Left Column (2/3 width on desktop) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Main Navigation Grid - Gold Members Only -->
                <?php if ($is_gold_member) : ?>
                <section>
                    <h2 class="text-xl lg:text-2xl font-bold text-dark-green mb-5 font-montserrat">Your Dashboard</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 lg:gap-5">

                        <?php
                        // Check if plugin function exists and get tutorial
                        $tutorial = null;
                        if (function_exists('nto_get_daily_tutorial')) {
                            $tutorial = nto_get_daily_tutorial();
                        }

                        if ($tutorial) :
                        ?>
                        <!-- Tutorial of the Day - Featured Card -->
                        <a href="<?php echo esc_url($tutorial['url']); ?>" class="bg-gradient-to-br from-dark-green to-dark-green-light rounded-xl p-5 lg:p-6 shadow-xl hover:shadow-2xl transition-all group border-2 border-transparent hover:border-sand col-span-2 md:col-span-3 relative overflow-hidden">
                            <!-- Animated background pattern -->
                            <div class="absolute inset-0 opacity-5">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full translate-x-16 -translate-y-16"></div>
                                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full -translate-x-12 translate-y-12"></div>
                            </div>

                            <div class="relative z-10 flex flex-col md:flex-row gap-4 items-start md:items-center">
                                <div class="flex items-center gap-3 flex-1">
                                    <?php if ($tutorial['thumbnail']) : ?>
                                        <img src="<?php echo esc_url($tutorial['thumbnail']); ?>"
                                             alt="<?php echo esc_attr($tutorial['title']); ?>"
                                             class="w-16 h-16 lg:w-20 lg:h-20 object-cover rounded-xl shadow-lg flex-shrink-0 group-hover:scale-105 transition-transform ring-2 ring-white/50" />
                                    <?php else : ?>
                                        <div class="w-16 h-16 lg:w-20 lg:h-20 bg-dark-green-light rounded-xl shadow-lg flex-shrink-0 flex items-center justify-center group-hover:scale-105 transition-transform ring-2 ring-white/50">
                                            <span class="material-icons-outlined text-4xl text-white">video_library</span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="material-icons-outlined text-xl text-white">stars</span>
                                            <p class="text-xs lg:text-sm font-bold text-white opacity-90 uppercase tracking-wide">Tutorial of the Day</p>
                                        </div>
                                        <h3 class="font-bold text-sm lg:text-base font-montserrat mb-1 line-clamp-2" style="color: #ffffff !important;">
                                            <?php echo esc_html($tutorial['title']); ?>
                                        </h3>
                                        <?php if ($tutorial['course_title']) : ?>
                                            <p class="text-xs text-white/80">
                                                <span class="material-icons-outlined text-xs align-middle">folder</span>
                                                <?php echo esc_html($tutorial['course_title']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="inline-flex items-center gap-2 bg-white text-dark-green font-bold py-2 px-6 rounded-full hover:bg-sand transition-all shadow-lg group-hover:scale-105">
                                    Watch Now <span class="material-icons-outlined text-lg">arrow_forward</span>
                                </div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <!-- Business Programme -->
                        <a href="https://thenailtech.org/business-programme/" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">business_center</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Business Programme</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Build & grow your business</p>
                            </div>
                        </a>

                        <!-- Nail Tutorials -->
                        <a href="https://thenailtech.org/nail-tutorials/" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">video_library</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Nail Tutorials</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Technique videos</p>
                            </div>
                        </a>

                        <!-- Monthly Calendar -->
                        <a href="https://thenailtech.org/events/" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">event</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Monthly Calendar</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">All upcoming events</p>
                            </div>
                        </a>

                        <!-- Live Class Replay -->
                        <a href="https://thenailtech.org/live-classes-and-replays" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">play_lesson</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Class Replays</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Catch up on sessions</p>
                            </div>
                        </a>

                        <!-- Community Feed -->
                        <a href="https://thenailtech.org/news-feed/" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">forum</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Community Feed</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Connect with nail techs</p>
                            </div>
                        </a>

                        <!-- Accredited Courses -->
                        <a href="https://thenailtech.org/accredited-courses-list/" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">verified</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Accredited Courses</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Professional certifications</p>
                            </div>
                        </a>

                    </div>
                </section>
                <?php endif; ?>

                <!-- Brand Builder Programme Section - For BBP and BBP VIP members -->
                <?php if ($is_bbp || $is_bbp_vip) : ?>
                <section id="bbp-section" class="bg-white rounded-xl p-5 lg:p-8 shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-icons-outlined text-3xl lg:text-4xl text-dark-green">rocket_launch</span>
                        <h2 class="text-xl lg:text-2xl font-bold text-dark-green">Brand Builder Programme</h2>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <a href="https://thenailtech.org/groups/brand-builder-programme/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">groups</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">BBP Community</h3>
                                <p class="text-xs opacity-90">Connect with BBP members</p>
                            </div>
                        </a>
                        <?php if ($is_bbp_vip) : ?>
                        <a href="https://thenailtech.org/groups/brand-builder-programme-vip/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">workspace_premium</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">VIP Community</h3>
                                <p class="text-xs opacity-90">Exclusive VIP access</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        <a href="https://thenailtech.org/courses/the-brand-builder-programme/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">menu_book</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Course Materials</h3>
                                <p class="text-xs opacity-90">Lessons & resources</p>
                            </div>
                        </a>
                        <a href="https://thenailtech.org/lessons/call-schedule-links/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">calendar_month</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Schedule & Replays</h3>
                                <p class="text-xs opacity-90">View cohort sessions</p>
                            </div>
                        </a>
                        <?php if ($has_bbi_bonuses) : ?>
                        <a href="https://thenailtech.org/bbp-bonuses/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)] relative">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">card_giftcard</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Bonuses Templates</h3>
                                <p class="text-xs opacity-90">Exclusive bonus content</p>
                            </div>
                            <span class="absolute top-3 right-3 flex items-center justify-center w-6 h-6 bg-red-500 rounded-full">
                                <span class="material-icons-outlined text-white" style="font-size: 14px;">notifications</span>
                            </span>
                        </a>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Educator Elevation Section - Conditional -->
                <?php if ($is_educator) : ?>
                <section id="educator-elevation" class="bg-white rounded-xl p-5 lg:p-8 shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-icons-outlined text-3xl lg:text-4xl text-dark-green">school</span>
                        <h2 class="text-xl lg:text-2xl font-bold text-dark-green">Educator Elevation</h2>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <a href="https://thenailtech.org/groups/the-educator-elevation-september-2025/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">groups</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Community Hub</h3>
                                <p class="text-xs opacity-90">Engage with cohort</p>
                            </div>
                        </a>
                        <a href="https://thenailtech.org/courses/the-educator-elevation/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">menu_book</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Course Materials</h3>
                                <p class="text-xs opacity-90">Lessons & resources</p>
                            </div>
                        </a>
                        <a href="https://thenailtech.org/september-25-group-1-educators-course-schedule/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md col-span-2 md:col-span-1">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">calendar_month</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Schedule</h3>
                                <p class="text-xs opacity-90">View cohort sessions</p>
                            </div>
                        </a>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Upgrade to Gold - Non-Members -->
                <?php if (!$is_gold_member) : ?>
                <section>
                    <div class="bg-gradient-to-br from-dark-green to-dark-green-light rounded-xl p-8 lg:p-12 shadow-xl text-white text-center relative overflow-hidden">
                        <!-- Decorative background pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 left-0 w-32 h-32 bg-white rounded-full -translate-x-16 -translate-y-16"></div>
                            <div class="absolute bottom-0 right-0 w-40 h-40 bg-white rounded-full translate-x-20 translate-y-20"></div>
                        </div>

                        <div class="relative z-10">
                            <span class="material-icons-outlined text-6xl lg:text-7xl mb-4 inline-block">workspace_premium</span>
                            <h2 class="text-2xl lg:text-3xl font-bold font-montserrat mb-3" style="color: #ffffff;">
                                Unlock <span style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 25%, #d4af37 50%, #f2c94c 75%, #ffd700 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; filter: brightness(1.1);">Gold Membership</span>
                            </h2>
                            <p class="text-base lg:text-lg mb-6 opacity-90">Get access to exclusive resources, live classes, and more</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6 justify-items-center">
                                <div class="flex items-center justify-center md:justify-start gap-2">
                                    <span class="material-icons-outlined text-2xl flex-shrink-0">check_circle</span>
                                    <span class="text-sm">Business Programme & Courses</span>
                                </div>
                                <div class="flex items-center justify-center md:justify-start gap-2">
                                    <span class="material-icons-outlined text-2xl flex-shrink-0">check_circle</span>
                                    <span class="text-sm">Monthly Live Classes</span>
                                </div>
                                <div class="flex items-center justify-center md:justify-start gap-2">
                                    <span class="material-icons-outlined text-2xl flex-shrink-0">check_circle</span>
                                    <span class="text-sm">Nail Technique Tutorials</span>
                                </div>
                                <div class="flex items-center justify-center md:justify-start gap-2">
                                    <span class="material-icons-outlined text-2xl flex-shrink-0">check_circle</span>
                                    <span class="text-sm">Community Access</span>
                                </div>
                            </div>

                            <a href="https://thenailtech.org/members-club-membership/" class="inline-flex items-center gap-2 bg-white text-dark-green font-bold py-3 px-8 rounded-full hover:bg-sand transition-all shadow-lg">
                                Become a Gold Member <span class="material-icons-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            </div>

            <!-- Right Sidebar (1/3 width on desktop) - Show for any group member -->
            <?php if ($has_any_group) : ?>
            <div class="lg:col-span-1 space-y-6">

                <!-- Quick Start -->
                <section>
                    <a href="https://thenailtech.org/new-members-area/" class="block bg-gradient-to-br from-sand to-white rounded-xl p-6 lg:p-8 shadow-md border-2 border-dark-green hover:shadow-lg transition-all group">
                        <div class="flex flex-col items-center text-center gap-4">
                            <span class="material-icons-outlined text-5xl lg:text-6xl text-dark-green group-hover:scale-110 transition-transform">play_circle</span>
                            <div>
                                <h3 class="text-lg lg:text-xl font-bold text-dark-green font-montserrat mb-2">New Here? Start Here</h3>
                                <p class="text-sm text-gray-600">Your guide to the platform</p>
                            </div>
                        </div>
                    </a>
                </section>

                <!-- Community Updates -->
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="material-icons-outlined text-2xl text-dark-green">forum</span>
                            <h2 class="text-lg lg:text-xl font-bold text-dark-green font-montserrat">Community</h2>
                        </div>
                    </div>

                    <!-- Tabs for multiple feeds -->
                    <div id="community-tabs" class="flex gap-2 mb-3 overflow-x-auto pb-2" style="display: none;">
                        <!-- Tabs will be generated dynamically -->
                    </div>

                    <!-- Community Link -->
                    <div id="community-link" class="mb-3" style="display: none;">
                        <a href="#" target="_blank" class="text-xs text-gray-600 hover:text-dark-green transition-colors flex items-center gap-1">
                            <span id="community-link-text">go to community</span>
                            <span class="material-icons-outlined" style="font-size: 14px;">arrow_forward</span>
                        </a>
                    </div>

                    <!-- Feed containers -->
                    <div id="community-feeds">
                        <!-- Loading State -->
                        <div class="bg-white rounded-xl p-6 shadow-md text-center">
                            <span class="material-icons-outlined text-4xl text-gray-400 mb-2 animate-spin">refresh</span>
                            <p class="text-gray-600 text-sm">Loading updates...</p>
                        </div>
                    </div>
                </section>

            </div>
            <?php endif; ?>

        </div>

    </main>
</div>

<script>

// Map LearnDash groups for JavaScript use
const userGroups = <?php echo $user_groups_json; ?>;
const adminHasFullAccess = <?php echo $admin_bypass ? 'true' : 'false'; ?>;
const communityFeeds = [];

// Current user info for optimistic UI updates
const currentUser = {
    id: <?php echo $user_id; ?>,
    name: '<?php echo esc_js($current_user->display_name); ?>',
    avatar: '<?php echo esc_js(get_avatar_url($user_id, ['size' => 96])); ?>'
};

// Check which communities user has access to
// General first (default feed)
if (adminHasFullAccess || userGroups.includes(4383)) { // Gold Members
    communityFeeds.push({ id: null, name: 'General', label: 'General', url: '/activity/' });
}
if (adminHasFullAccess || userGroups.includes(347879)) { // BBP
    communityFeeds.push({ id: 67, name: 'BBP', label: 'BBP', url: '/groups/brand-builder-programme/' });
}
if (adminHasFullAccess || userGroups.includes(348042)) { // BBP VIP
    communityFeeds.push({ id: 68, name: 'BBP VIP', label: 'BBP VIP', url: '/groups/brand-builder-programme-vip/' });
}
if (adminHasFullAccess || userGroups.includes(272088)) { // Educator Elevation
    communityFeeds.push({ id: 65, name: 'Educator Elevation', label: 'Educator', url: '/groups/the-educator-elevation-september-2025/' });
}

let activeFeedIndex = 0;

// ============= 12 Days of Christmas Banner =============
// Christmas tutorials data with availability dates
const christmasTutorials = [
    {
        author: 'Amy Lou',
        title: 'Male Manicure',
        category: 'Nail Tutorial',
        link: 'https://thenailtech.org/lessons/male-manicure-amy-lou/',
        availableDate: new Date('2025-12-03T00:00:00Z')
    },
    {
        author: 'Charlotte Fulcher',
        title: 'Day in the Life Reel',
        category: 'Social Media tutorial',
        link: 'https://thenailtech.org/lessons/how-to-create-a-day-in-the-life-reel-beginner-friendly-awkward-proof-charlotte-fulcher/',
        availableDate: new Date('2025-12-04T00:00:00Z')
    },
    {
        author: 'Suzanne Daggers',
        title: 'Growing Authentically',
        category: 'Social Media tutorial',
        link: 'https://thenailtech.org/lessons/growing-authentically-online-suzanne-daggers/',
        availableDate: new Date('2025-12-05T00:00:00Z')
    },
    {
        author: 'Alex Philamond',
        title: 'Antique Cat Eye',
        category: 'Nail Tutorial',
        link: 'https://thenailtech.org/lessons/antique-cat-eye-chrome-isolation-alex-philamond/',
        availableDate: new Date('2025-12-09T00:00:00Z')
    },
    {
        author: 'Chelsea Lou',
        title: 'Nail Prep',
        category: 'Nail Tutorial',
        link: 'https://thenailtech.org/lessons/nail-prep-chelsea-lou/',
        availableDate: new Date('2025-12-11T00:00:00Z')
    },
    {
        author: 'Chloe-Mae Boyce',
        title: 'Gel Toe Application',
        category: 'Nail Tutorial',
        link: 'https://thenailtech.org/lessons/gel-toe-nail-application-chloe-mae-boyce/',
        availableDate: new Date('2025-12-12T00:00:00Z')
    }
];

// Helper function to get ordinal suffix (1st, 2nd, 3rd, etc.)
function getOrdinalSuffix(num) {
    const j = num % 10;
    const k = num % 100;
    if (j === 1 && k !== 11) return num + 'st';
    if (j === 2 && k !== 12) return num + 'nd';
    if (j === 3 && k !== 13) return num + 'rd';
    return num + 'th';
}

// Function to update Christmas banner
function updateChristmasBanner() {
    const banner = document.getElementById('christmas-banner');
    if (!banner) return;

    // Get current date in London time
    const nowLondon = new Date(new Date().toLocaleString('en-US', { timeZone: 'Europe/London' }));

    let currentTutorial = null;
    let christmasDayNumber = 0;

    // Find current tutorial based on date
    const sortedTutorials = [...christmasTutorials].sort((a, b) => a.availableDate - b.availableDate);

    for (let i = 0; i < sortedTutorials.length; i++) {
        const tutorialDate = new Date(sortedTutorials[i].availableDate);

        // Check if current date is on or after this tutorial's date
        if (nowLondon >= tutorialDate) {
            currentTutorial = sortedTutorials[i];
            christmasDayNumber = i + 1;

            // If we haven't reached the next tutorial date yet, this is the current one
            if (i < sortedTutorials.length - 1) {
                const nextTutorialDate = new Date(sortedTutorials[i + 1].availableDate);
                if (nowLondon < nextTutorialDate) {
                    break; // Found the current tutorial
                }
            } else {
                break; // This is the last tutorial
            }
        }
    }

    // If we found a tutorial to display
    if (currentTutorial) {
        // Update banner content
        const dayTitle = document.getElementById('christmas-day-title');
        const tutorialTitle = document.getElementById('christmas-tutorial-title');
        const tutorialAuthor = document.getElementById('christmas-tutorial-author');
        const tutorialLink = document.getElementById('christmas-tutorial-link');

        const ordinalDay = getOrdinalSuffix(christmasDayNumber).toUpperCase();

        dayTitle.textContent = `ON THE ${ordinalDay} DAY OF CHRISTMAS, NTO GAVE TO ME...`;
        tutorialTitle.textContent = currentTutorial.title;
        tutorialAuthor.textContent = `A ${currentTutorial.category} by ${currentTutorial.author}`;
        tutorialLink.href = currentTutorial.link;

        // Show the banner
        banner.style.display = 'block';
    } else {
        // Hide banner if no tutorial is available
        banner.style.display = 'none';
    }
}

// Initialize Christmas banner on page load
document.addEventListener('DOMContentLoaded', function() {
    updateChristmasBanner();
});
// ============= End 12 Days of Christmas Banner =============

// State tracker for preserving UI state during polling
const uiState = {
    expandedPosts: new Set(),           // Posts with "see more" expanded
    openCommentSections: new Set(),     // Posts with comments visible
    openWriteSections: new Set(),       // Posts with write section visible
    writeInputValues: new Map()         // Activity ID -> textarea value
};

// Helper function to decode HTML entities (including emojis)
function decodeHtmlEntities(text) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
}

// Capture current UI state before reload
function captureUIState() {
    uiState.expandedPosts.clear();
    uiState.openCommentSections.clear();
    uiState.openWriteSections.clear();
    uiState.writeInputValues.clear();

    // Find all posts
    const posts = document.querySelectorAll('.posts-container > div[class*="bg-white"]');

    posts.forEach(postCard => {
        // Try to find activity ID from any child element
        const activityIdElement = postCard.querySelector('[data-activity-id]');
        if (!activityIdElement) return;

        const activityId = activityIdElement.getAttribute('data-activity-id');

        // Check if content is expanded
        const expandedContent = postCard.querySelector('.post-content-expanded');
        if (expandedContent) {
            uiState.expandedPosts.add(activityId);
        }

        // Check if comments section is open
        const commentsSection = postCard.querySelector('.comments-section');
        if (commentsSection && commentsSection.style.display !== 'none') {
            console.log(`   📌 Saving open comment section for activity ${activityId}`);
            uiState.openCommentSections.add(activityId);
        }

        // Check if write section is open and capture textarea value
        const writeSection = postCard.querySelector('[data-reply-section]');
        if (writeSection && writeSection.style.display !== 'none') {
            uiState.openWriteSections.add(activityId);

            // Capture any text in the textarea
            const textarea = writeSection.querySelector('textarea');
            if (textarea && textarea.value.trim()) {
                uiState.writeInputValues.set(activityId, textarea.value);
            }
        }
    });

    console.log('📸 Captured UI state:', {
        expanded: Array.from(uiState.expandedPosts),
        comments: Array.from(uiState.openCommentSections),
        write: Array.from(uiState.openWriteSections),
        writeValues: Array.from(uiState.writeInputValues.keys())
    });
}

// Restore UI state after reload
async function restoreUIState() {
    console.log('🔄 Restoring UI state...');
    console.log('   States to restore:', {
        expanded: Array.from(uiState.expandedPosts),
        comments: Array.from(uiState.openCommentSections),
        write: Array.from(uiState.openWriteSections)
    });

    const posts = document.querySelectorAll('.posts-container > div[class*="bg-white"]');
    console.log(`   Found ${posts.length} posts in DOM`);

    // Log all activity IDs found
    const foundActivityIds = [];
    posts.forEach(p => {
        const el = p.querySelector('[data-activity-id]');
        if (el) foundActivityIds.push(el.getAttribute('data-activity-id'));
    });
    console.log('   Activity IDs in DOM:', foundActivityIds);

    for (const postCard of posts) {
        const activityIdElement = postCard.querySelector('[data-activity-id]');
        if (!activityIdElement) continue;

        const activityId = activityIdElement.getAttribute('data-activity-id');

        // Restore expanded content
        if (uiState.expandedPosts.has(activityId)) {
            // Try to find collapsed content first
            let content = postCard.querySelector('.post-content-collapsed');
            const seeMoreBtn = postCard.querySelector('.see-more-btn');

            // If not collapsed, it might already be in its natural state (short content)
            if (!content) {
                content = postCard.querySelector('.post-content-expanded');
            }

            if (content && seeMoreBtn) {
                content.classList.remove('post-content-collapsed', 'post-content-truncated');
                content.classList.add('post-content-expanded');
                const chevron = seeMoreBtn.querySelector('.see-more-chevron');
                if (chevron) chevron.classList.add('expanded');
                seeMoreBtn.innerHTML = `
                    See less
                    <span class="material-icons-outlined see-more-chevron expanded" style="font-size: 14px;">expand_more</span>
                `;
            }
        }

        // Restore open comment sections
        if (uiState.openCommentSections.has(activityId)) {
            console.log(`🔍 Restoring comments for activity ${activityId}`);

            const commentsSection = postCard.querySelector(`.comments-section[data-activity-id="${activityId}"]`);
            console.log('   Found commentsSection:', !!commentsSection);

            if (commentsSection) {
                console.log('   Opening comments section...');
                commentsSection.style.display = 'block';

                // Load comments if not already loaded
                if (!commentsSection.hasAttribute('data-loaded')) {
                    console.log('   Loading comments from API...');
                    commentsSection.innerHTML = '<div class="text-center py-2 text-xs text-gray-500"><span class="material-icons-outlined text-sm animate-spin">refresh</span> Loading comments...</div>';
                    await loadComments(activityId, commentsSection);
                    commentsSection.setAttribute('data-loaded', 'true');
                } else {
                    console.log('   Comments already loaded');
                }
            } else {
                console.log('   ❌ Comments section not found in DOM');
            }
        }

        // Restore open write sections
        if (uiState.openWriteSections.has(activityId)) {
            const writeSection = postCard.querySelector(`[data-reply-section="${activityId}"]`);

            if (writeSection) {
                writeSection.style.display = 'block';

                // Restore textarea value if it was captured
                const textarea = writeSection.querySelector('textarea');
                if (textarea && uiState.writeInputValues.has(activityId)) {
                    textarea.value = uiState.writeInputValues.get(activityId);
                    // Don't auto-focus to avoid disrupting user
                    // But we can restore cursor position to the end
                    textarea.selectionStart = textarea.selectionEnd = textarea.value.length;
                }
            }
        }
    }

    console.log('✅ UI state restored');
}

// Update community link based on active feed
function updateCommunityLink() {
    const linkContainer = document.getElementById('community-link');
    const linkText = document.getElementById('community-link-text');
    const linkElement = linkContainer.querySelector('a');

    if (communityFeeds.length > 0 && linkContainer && linkText && linkElement) {
        const currentFeed = communityFeeds[activeFeedIndex];

        // Update link text
        if (currentFeed.name === 'General') {
            linkText.textContent = 'go to community';
        } else {
            linkText.textContent = `go to ${currentFeed.name} group`;
        }

        // Update link URL
        linkElement.href = currentFeed.url;

        // Show the link
        linkContainer.style.display = 'block';
    }
}

async function loadCommunityFeed(groupId = null, containerId = 'community-feeds', showIndicator = false) {
    const feedContainer = document.getElementById(containerId);

    // Capture current UI state before reload (during polling)
    if (showIndicator) {
        captureUIState();
    }

    // Add visual indicator for updates (subtle pulse)
    if (showIndicator && feedContainer) {
        feedContainer.style.transition = 'opacity 0.3s ease';
        feedContainer.style.opacity = '0.6';
    }

    try {
        // Build BuddyBoss REST API URL - request more items to ensure we get 5 activity_update posts after filtering
        let apiUrl = 'https://thenailtech.org/wp-json/buddyboss/v1/activity?per_page=20&display_comments=false';
        if (groupId) {
            apiUrl += `&group_id=${groupId}`;
        }

        const response = await fetch(apiUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': '<?php echo $rest_nonce; ?>'
            }
        });

        const activities = await response.json();

        // Log response for debugging
        console.log('BuddyBoss Activity Response:', activities);

        if (!activities || activities.length === 0) {
            feedContainer.innerHTML = '<p class="text-center text-gray-500 text-sm py-6">No recent activity</p>';
            return;
        }

        // Filter for activity_update type only and limit to 5
        const posts = activities.filter(a => a.type === 'activity_update').slice(0, 5);

        if (posts.length === 0) {
            feedContainer.innerHTML = '<p class="text-center text-gray-500 text-sm py-6">No recent activity</p>';
            return;
        }

        // Create container with posts
        const postsContainer = document.createElement('div');
        postsContainer.className = 'posts-container';

        posts.forEach(activity => {
            const timeAgo = getTimeAgo(activity.date);

            // Create post card
            const postCard = document.createElement('div');
            postCard.className = 'bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition-all border-2 border-transparent hover:border-sand';

            // Header section
            const header = document.createElement('div');
            header.className = 'flex items-start gap-3 mb-3';

            const avatar = document.createElement('img');
            avatar.src = activity.user_avatar?.thumb || activity.user_avatar?.full || '';
            avatar.alt = activity.name;
            avatar.className = 'avatar-image ring-2 ring-sand';

            const userInfo = document.createElement('div');
            userInfo.className = 'flex-1 min-w-0';
            const infoInner = document.createElement('div');
            infoInner.className = 'flex flex-col gap-1';

            const userName = document.createElement('h3');
            userName.className = 'font-bold text-dark-green font-montserrat text-sm truncate';
            userName.textContent = activity.name;

            const timeAndGroup = document.createElement('div');
            timeAndGroup.className = 'flex items-center gap-2';

            const timeSpan = document.createElement('span');
            timeSpan.className = 'text-xs text-gray-500';
            timeSpan.textContent = timeAgo;
            timeAndGroup.appendChild(timeSpan);

            // Add group tag if post is from a group
            if (activity.activity_data && activity.activity_data.group_id && activity.activity_data.group_id > 0 && activity.activity_data.group_name) {
                const groupTag = document.createElement('span');
                groupTag.className = 'group-badge';
                groupTag.textContent = activity.activity_data.group_name;
                timeAndGroup.appendChild(groupTag);
            }

            infoInner.appendChild(userName);
            infoInner.appendChild(timeAndGroup);
            userInfo.appendChild(infoInner);

            header.appendChild(avatar);
            header.appendChild(userInfo);
            postCard.appendChild(header);

            // Content section
            if (activity.content_stripped || activity.content?.rendered) {
                const contentWrapper = document.createElement('div');
                contentWrapper.className = 'mb-3 post-content-wrapper';

                const content = document.createElement('div');
                content.className = 'text-xs text-gray-700 post-content-collapsed';
                // Clean up escaped characters
                let contentText = activity.content_stripped || activity.content?.rendered?.replace(/<[^>]*>/g, '') || '';
                contentText = contentText.replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, '\\');
                // Decode HTML entities (including emojis)
                contentText = decodeHtmlEntities(contentText);
                content.textContent = contentText;

                // Check if content is long enough to truncate (approximately 3 lines = ~150 chars)
                const needsTruncation = contentText.length > 150;

                if (needsTruncation) {
                    content.classList.add('post-content-truncated');

                    // Create "See more" button
                    const seeMoreBtn = document.createElement('div');
                    seeMoreBtn.className = 'see-more-btn text-xs mt-1';
                    seeMoreBtn.innerHTML = `
                        See more
                        <span class="material-icons-outlined see-more-chevron" style="font-size: 14px;">expand_more</span>
                    `;

                    // Add click handler to expand/collapse
                    seeMoreBtn.addEventListener('click', function() {
                        const chevron = this.querySelector('.see-more-chevron');

                        if (content.classList.contains('post-content-collapsed')) {
                            // Expand
                            content.classList.remove('post-content-collapsed', 'post-content-truncated');
                            content.classList.add('post-content-expanded');
                            chevron.classList.add('expanded');
                            this.innerHTML = `
                                See less
                                <span class="material-icons-outlined see-more-chevron expanded" style="font-size: 14px;">expand_more</span>
                            `;
                        } else {
                            // Collapse
                            content.classList.remove('post-content-expanded');
                            content.classList.add('post-content-collapsed', 'post-content-truncated');
                            chevron.classList.remove('expanded');
                            this.innerHTML = `
                                See more
                                <span class="material-icons-outlined see-more-chevron" style="font-size: 14px;">expand_more</span>
                            `;
                        }
                    });

                    contentWrapper.appendChild(content);
                    contentWrapper.appendChild(seeMoreBtn);
                } else {
                    // Short content, no truncation needed
                    contentWrapper.appendChild(content);
                }

                postCard.appendChild(contentWrapper);
            }

            // Footer with likes and comments
            const footer = document.createElement('div');
            footer.className = 'flex items-center gap-3 text-gray-500';

            // Likes - always show, clickable to like/unlike
            const favoriteCount = activity.favorite_count || 0;
            const isFavorited = activity.favorited || false;

            const likesBtn = document.createElement('div');
            likesBtn.className = 'flex items-center gap-1 text-xs cursor-pointer hover:text-red-500 transition-colors';
            likesBtn.setAttribute('data-activity-id', activity.id);
            likesBtn.setAttribute('data-favorited', isFavorited);
            likesBtn.setAttribute('data-count', favoriteCount);

            const likeIcon = document.createElement('span');
            likeIcon.className = 'material-icons-outlined text-sm';
            likeIcon.textContent = isFavorited ? 'favorite' : 'favorite_border';
            if (isFavorited) {
                likesBtn.style.color = '#ef4444'; // red-500
            }

            const likeCount = document.createElement('span');
            likeCount.textContent = favoriteCount;

            likesBtn.appendChild(likeIcon);
            likesBtn.appendChild(likeCount);

            // Add click handler for like/unlike
            likesBtn.addEventListener('click', async function(e) {
                e.stopPropagation();
                const activityId = this.getAttribute('data-activity-id');
                const isFavorited = this.getAttribute('data-favorited') === 'true';
                const currentCount = parseInt(this.getAttribute('data-count'));

                const icon = this.querySelector('.material-icons-outlined');
                const countSpan = this.querySelector('span:last-child');

                // Optimistic UI update - update immediately
                if (isFavorited) {
                    // Optimistically unlike
                    icon.textContent = 'favorite_border';
                    this.style.color = '';
                    const newCount = Math.max(0, currentCount - 1);
                    countSpan.textContent = newCount;
                    this.setAttribute('data-favorited', 'false');
                    this.setAttribute('data-count', newCount);
                } else {
                    // Optimistically like
                    icon.textContent = 'favorite';
                    this.style.color = '#ef4444';
                    const newCount = currentCount + 1;
                    countSpan.textContent = newCount;
                    this.setAttribute('data-favorited', 'true');
                    this.setAttribute('data-count', newCount);
                }

                // Then update server in background
                try {
                    let response;
                    if (isFavorited) {
                        // Unlike - BuddyBoss uses POST with id parameter to toggle
                        response = await fetch(`https://thenailtech.org/wp-json/buddyboss/v1/activity/${activityId}/favorite`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-WP-Nonce': '<?php echo $rest_nonce; ?>',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: activityId
                            })
                        });
                    } else {
                        // Like
                        response = await fetch(`https://thenailtech.org/wp-json/buddyboss/v1/activity/${activityId}/favorite`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-WP-Nonce': '<?php echo $rest_nonce; ?>',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: activityId
                            })
                        });
                    }

                    if (!response.ok) {
                        // Server failed - revert optimistic update
                        console.error('Failed to toggle favorite on server');
                        if (isFavorited) {
                            // Revert back to liked
                            icon.textContent = 'favorite';
                            this.style.color = '#ef4444';
                            countSpan.textContent = currentCount;
                            this.setAttribute('data-favorited', 'true');
                            this.setAttribute('data-count', currentCount);
                        } else {
                            // Revert back to unliked
                            icon.textContent = 'favorite_border';
                            this.style.color = '';
                            countSpan.textContent = currentCount;
                            this.setAttribute('data-favorited', 'false');
                            this.setAttribute('data-count', currentCount);
                        }
                    }
                } catch (error) {
                    console.error('Error toggling favorite:', error);
                    // Revert optimistic update on error
                    if (isFavorited) {
                        icon.textContent = 'favorite';
                        this.style.color = '#ef4444';
                        countSpan.textContent = currentCount;
                        this.setAttribute('data-favorited', 'true');
                        this.setAttribute('data-count', currentCount);
                    } else {
                        icon.textContent = 'favorite_border';
                        this.style.color = '';
                        countSpan.textContent = currentCount;
                        this.setAttribute('data-favorited', 'false');
                        this.setAttribute('data-count', currentCount);
                    }
                }
            });

            footer.appendChild(likesBtn);

            // Comments - only clickable if count > 0
            const commentsCount = activity.comment_count || 0;
            const commentsBtn = document.createElement('div');

            if (commentsCount > 0) {
                commentsBtn.className = 'flex items-center gap-1 text-xs cursor-pointer hover:text-dark-green transition-colors';
                commentsBtn.innerHTML = `<span class="material-icons-outlined text-sm">chat_bubble_outline</span> ${commentsCount}`;
                commentsBtn.setAttribute('data-activity-id', activity.id);

                // Create comments container (hidden by default)
                const commentsContainer = document.createElement('div');
                commentsContainer.className = 'comments-section';
                commentsContainer.style.display = 'none';
                commentsContainer.setAttribute('data-activity-id', activity.id);

                // Add click handler to load comments
                commentsBtn.addEventListener('click', async function(e) {
                    e.stopPropagation();
                    const activityId = this.getAttribute('data-activity-id');
                    const container = postCard.querySelector(`.comments-section[data-activity-id="${activityId}"]`);

                    // Toggle visibility
                    if (container.style.display === 'none') {
                        // Load comments if not already loaded
                        if (!container.hasAttribute('data-loaded')) {
                            container.innerHTML = '<div class="text-center py-2 text-xs text-gray-500"><span class="material-icons-outlined text-sm animate-spin">refresh</span> Loading comments...</div>';
                            container.style.display = 'block';
                            await loadComments(activityId, container);
                            container.setAttribute('data-loaded', 'true');
                        } else {
                            container.style.display = 'block';
                        }
                    } else {
                        container.style.display = 'none';
                    }
                });

                footer.appendChild(commentsBtn);

                // Add comments container after footer (will be added to postCard later)
                postCard.commentsContainer = commentsContainer;
            } else {
                // Show non-clickable comment count
                commentsBtn.className = 'flex items-center gap-1 text-xs text-gray-400';
                commentsBtn.innerHTML = `<span class="material-icons-outlined text-sm">chat_bubble_outline</span> ${commentsCount}`;
                footer.appendChild(commentsBtn);
            }

            // Add "Write a comment..." button to footer
            const writeCommentBtn = document.createElement('div');
            writeCommentBtn.className = 'flex items-center gap-1 text-xs cursor-pointer hover:text-dark-green transition-colors';
            writeCommentBtn.innerHTML = '<span class="material-icons-outlined text-sm">edit</span> Write';

            // Add reply input section
            const replySection = document.createElement('div');
            replySection.className = 'mt-3 pt-3 border-t border-gray-200';
            replySection.style.padding = '1rem 0rem';
            replySection.style.display = 'none';
            replySection.setAttribute('data-reply-section', activity.id);

            const replyForm = document.createElement('div');
            replyForm.className = 'flex gap-2';

            const replyInput = document.createElement('textarea');
            replyInput.className = 'reply-textarea';
            replyInput.placeholder = 'Write a comment...';
            replyInput.rows = 2;
            replyInput.setAttribute('data-activity-id', activity.id);

            const replyButton = document.createElement('button');
            replyButton.className = 'px-4 py-2 bg-dark-green text-white text-xs font-semibold rounded-lg hover:bg-dark-green-light transition-colors';
            replyButton.textContent = 'Post';
            replyButton.setAttribute('data-activity-id', activity.id);

            // Add click handler for reply button
            replyButton.addEventListener('click', async function() {
                const activityId = this.getAttribute('data-activity-id');
                const textarea = replySection.querySelector('textarea');
                const content = textarea.value.trim();

                if (!content) {
                    return;
                }

                // Disable button and show loading
                this.disabled = true;
                this.textContent = 'Posting...';

                try {
                    // Optimistically add the comment to UI
                    const optimisticComment = {
                        id: 'temp-' + Date.now(),
                        name: currentUser.name,
                        user_avatar: {
                            thumb: currentUser.avatar,
                            full: currentUser.avatar
                        },
                        date: new Date().toISOString(),
                        content: {
                            rendered: content
                        }
                    };

                    // Clear textarea immediately
                    textarea.value = '';

                    // Find or create comments container
                    let commentsContainer = postCard.querySelector(`.comments-section[data-activity-id="${activityId}"]`);

                    if (!commentsContainer) {
                        // Create comments container if it doesn't exist
                        commentsContainer = document.createElement('div');
                        commentsContainer.className = 'comments-section';
                        commentsContainer.setAttribute('data-activity-id', activityId);
                        commentsContainer.setAttribute('data-loaded', 'true');
                        postCard.appendChild(commentsContainer);
                    }

                    // Show comments container
                    commentsContainer.style.display = 'block';

                    // If comments section is empty or has "no comments" message, initialize it
                    if (!commentsContainer.querySelector('.mt-3.pt-3.border-t') || commentsContainer.textContent.includes('No comments')) {
                        const commentsList = document.createElement('div');
                        commentsList.className = 'mt-3 pt-3 border-t border-gray-200 space-y-3';
                        commentsContainer.innerHTML = '';
                        commentsContainer.appendChild(commentsList);
                    }

                    // Add the new comment to the list
                    const commentsList = commentsContainer.querySelector('.mt-3.pt-3.border-t');
                    const newCommentElement = renderComment(optimisticComment, 0);
                    newCommentElement.style.opacity = '0.6'; // Show it's pending
                    commentsList.appendChild(newCommentElement);

                    // Update comment count in the button (need to find it within the postCard)
                    const footerElement = postCard.querySelector('.flex.items-center.gap-3.text-gray-500');
                    const commentsBtnInFooter = footerElement ? footerElement.querySelector(`div[data-activity-id="${activityId}"]`) : null;
                    if (commentsBtnInFooter && commentsBtnInFooter.innerHTML.includes('chat_bubble_outline')) {
                        const currentCount = parseInt(commentsBtnInFooter.textContent.trim()) || 0;
                        const newCount = currentCount + 1;
                        commentsBtnInFooter.innerHTML = `<span class="material-icons-outlined text-sm">chat_bubble_outline</span> ${newCount}`;

                        // Make it clickable if it wasn't before
                        if (currentCount === 0) {
                            commentsBtnInFooter.className = 'flex items-center gap-1 text-xs cursor-pointer hover:text-dark-green transition-colors';

                            // Add click handler since it was previously non-clickable
                            commentsBtnInFooter.addEventListener('click', async function(e) {
                                e.stopPropagation();
                                const container = commentsContainer;

                                // Toggle visibility
                                if (container.style.display === 'none') {
                                    container.style.display = 'block';
                                } else {
                                    container.style.display = 'none';
                                }
                            });
                        }
                    }

                    // Post to server
                    const response = await fetch(`https://thenailtech.org/wp-json/buddyboss/v1/activity/${activityId}/comment`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': '<?php echo $rest_nonce; ?>',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            content: content
                        })
                    });

                    if (response.ok) {
                        // Remove opacity to show it's confirmed
                        newCommentElement.style.opacity = '1';
                    } else {
                        // Remove the optimistic comment on error
                        newCommentElement.remove();

                        // Revert comment count
                        if (commentsBtnInFooter && commentsBtnInFooter.innerHTML.includes('chat_bubble_outline')) {
                            const currentCount = parseInt(commentsBtnInFooter.textContent.trim()) || 0;
                            const revertedCount = Math.max(0, currentCount - 1);
                            commentsBtnInFooter.innerHTML = `<span class="material-icons-outlined text-sm">chat_bubble_outline</span> ${revertedCount}`;

                            if (revertedCount === 0) {
                                commentsBtnInFooter.className = 'flex items-center gap-1 text-xs text-gray-400';
                            }
                        }

                        const error = await response.json();
                        console.error('Error posting comment:', error);
                        alert('Failed to post comment. Please try again.');

                        // Restore the text in textarea
                        textarea.value = content;
                    }
                } catch (error) {
                    console.error('Error posting comment:', error);
                    alert('Failed to post comment. Please try again.');
                    textarea.value = content; // Restore text on error
                } finally {
                    this.disabled = false;
                    this.textContent = 'Post';
                }
            });

            replyForm.appendChild(replyInput);
            replyForm.appendChild(replyButton);
            replySection.appendChild(replyForm);

            writeCommentBtn.addEventListener('click', function() {
                const section = postCard.querySelector(`[data-reply-section="${activity.id}"]`);
                if (section.style.display === 'none') {
                    section.style.display = 'block';
                    const textarea = section.querySelector('textarea');
                    textarea.focus();
                } else {
                    section.style.display = 'none';
                }
            });
            footer.appendChild(writeCommentBtn);

            postCard.appendChild(footer);
            postCard.appendChild(replySection);

            // Add comments container after reply section (if it exists)
            if (postCard.commentsContainer) {
                postCard.appendChild(postCard.commentsContainer);
            }

            postsContainer.appendChild(postCard);
        });

        feedContainer.innerHTML = '';
        feedContainer.appendChild(postsContainer);

        // Restore UI state after rendering (during polling)
        // Use requestAnimationFrame to ensure DOM is fully rendered
        if (showIndicator) {
            await new Promise(resolve => requestAnimationFrame(resolve));
            await restoreUIState();
        }

        // Add "View All Posts" button
        const viewAllButton = document.createElement('a');

        // Determine the correct URL based on groupId
        let viewAllUrl = 'https://thenailtech.org/news-feed/'; // Default - General
        if (groupId === 67) {
            viewAllUrl = 'https://thenailtech.org/groups/brand-builder-programme/';
        } else if (groupId === 68) {
            viewAllUrl = 'https://thenailtech.org/groups/brand-builder-programme-vip/';
        } else if (groupId === 65) {
            viewAllUrl = 'https://thenailtech.org/groups/the-educator-elevation-september-2025/';
        }

        viewAllButton.href = viewAllUrl;
        viewAllButton.className = 'view-all-posts-btn';
        viewAllButton.innerHTML = 'View All Posts <span class="material-icons-outlined text-sm align-middle ml-1">arrow_forward</span>';

        feedContainer.appendChild(viewAllButton);

    } catch (error) {
        console.error('Error loading community feed:', error);
        feedContainer.innerHTML = `
            <div class="bg-white rounded-xl p-6 shadow-md text-center">
                <span class="material-icons-outlined text-4xl text-gray-400 mb-2">error_outline</span>
                <p class="text-gray-600 text-sm">Unable to load updates</p>
            </div>
        `;
    } finally {
        // Restore opacity after loading (with or without error)
        if (showIndicator && feedContainer) {
            setTimeout(() => {
                feedContainer.style.opacity = '1';
            }, 100);
        }
    }
}

function switchFeed(index) {
    activeFeedIndex = index;
    const feed = communityFeeds[index];
    const feedContainer = document.getElementById('community-feeds');

    // Update tab styles
    document.querySelectorAll('[data-tab]').forEach((tab, i) => {
        if (i === index) {
            tab.className = 'px-4 py-2 rounded-lg font-semibold text-sm transition-all bg-dark-green text-white';
        } else {
            tab.className = 'px-4 py-2 rounded-lg font-semibold text-sm transition-all bg-white text-dark-green border-2 border-sand hover:border-dark-green';
        }
    });

    // Update community link
    updateCommunityLink();

    // Show loading state
    feedContainer.innerHTML = `
        <div class="bg-white rounded-xl p-6 shadow-md text-center">
            <span class="material-icons-outlined text-4xl text-gray-400 mb-2 animate-spin">refresh</span>
            <p class="text-gray-600 text-sm">Loading ${feed.name}...</p>
        </div>
    `;

    // Load the feed
    loadCommunityFeed(feed.id);
}

function initializeCommunityFeeds() {
    if (communityFeeds.length === 0) {
        // No feeds available
        document.getElementById('community-feeds').innerHTML = `
            <div class="bg-white rounded-xl p-6 shadow-md text-center">
                <span class="material-icons-outlined text-4xl text-gray-400 mb-2">info</span>
                <p class="text-gray-600 text-sm">No community access</p>
            </div>
        `;
        return;
    }

    // If multiple feeds, show tabs
    if (communityFeeds.length > 1) {
        const tabsContainer = document.getElementById('community-tabs');
        tabsContainer.style.display = 'flex';
        tabsContainer.innerHTML = communityFeeds.map((feed, index) => `
            <button
                data-tab="${index}"
                onclick="switchFeed(${index})"
                class="${index === 0 ? 'px-4 py-2 rounded-lg font-semibold text-sm transition-all bg-dark-green text-white' : 'px-4 py-2 rounded-lg font-semibold text-sm transition-all bg-white text-dark-green border-2 border-sand hover:border-dark-green'}"
            >
                ${feed.label}
            </button>
        `).join('');
    }

    // Update community link for initial feed
    updateCommunityLink();

    // Load first feed
    loadCommunityFeed(communityFeeds[0].id);
}

// Helper function to render a single comment (supports nested replies)
function renderComment(comment, depth = 0) {
    const commentDiv = document.createElement('div');
    commentDiv.className = 'flex gap-2';

    // Add left margin for nested replies
    if (depth > 0) {
        commentDiv.style.marginLeft = `${depth * 1.5}rem`;
        commentDiv.style.borderLeft = '2px solid #e5e7eb';
        commentDiv.style.paddingLeft = '0.5rem';
    }

    // Avatar
    const commentAvatar = document.createElement('img');
    commentAvatar.src = comment.user_avatar?.thumb || comment.user_avatar?.full || '';
    commentAvatar.alt = comment.name;
    commentAvatar.className = 'w-6 h-6 rounded-full object-cover flex-shrink-0';
    commentAvatar.style.width = '1.5rem';
    commentAvatar.style.height = '1.5rem';
    commentAvatar.style.minWidth = '1.5rem';
    commentAvatar.style.minHeight = '1.5rem';

    // Comment content
    const commentContent = document.createElement('div');
    commentContent.className = 'flex-1 min-w-0';

    const commentHeader = document.createElement('div');
    commentHeader.className = 'flex items-center gap-2';

    const commentAuthor = document.createElement('span');
    commentAuthor.className = 'font-semibold text-xs text-dark-green';
    commentAuthor.textContent = comment.name;

    const commentTime = document.createElement('span');
    commentTime.className = 'text-xs text-gray-500';
    commentTime.textContent = getTimeAgo(comment.date);

    commentHeader.appendChild(commentAuthor);
    commentHeader.appendChild(commentTime);

    const commentText = document.createElement('div');
    commentText.className = 'text-xs text-gray-700 mt-1';
    let commentTextContent = comment.content?.rendered?.replace(/<[^>]*>/g, '') || '';
    commentTextContent = commentTextContent.replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, '\\');
    // Decode HTML entities (including emojis)
    commentTextContent = decodeHtmlEntities(commentTextContent);
    commentText.textContent = commentTextContent;

    commentContent.appendChild(commentHeader);
    commentContent.appendChild(commentText);

    commentDiv.appendChild(commentAvatar);
    commentDiv.appendChild(commentContent);

    return commentDiv;
}

// Recursive function to render comments and their replies
function renderCommentsTree(comments, parentElement, depth = 0) {
    comments.forEach(comment => {
        // Render the comment
        const commentElement = renderComment(comment, depth);
        parentElement.appendChild(commentElement);

        // Check for replies/children and render them recursively
        const replies = comment.children || comment.replies || [];
        if (replies && replies.length > 0) {
            renderCommentsTree(replies, parentElement, depth + 1);
        }
    });
}

// Load comments for an activity
async function loadComments(activityId, container) {
    try {
        // Fetch comments with display_comments=threaded to get nested structure
        const response = await fetch(`https://thenailtech.org/wp-json/buddyboss/v1/activity/${activityId}/comment?display_comments=threaded`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': '<?php echo $rest_nonce; ?>'
            }
        });

        const data = await response.json();
        console.log('💬 Comments Response for activity', activityId, ':', data);

        // Handle different response formats
        let comments = [];
        if (Array.isArray(data)) {
            comments = data;
        } else if (data && typeof data === 'object') {
            // Check if comments are nested in a property
            if (data.comments && Array.isArray(data.comments)) {
                comments = data.comments;
            } else if (data.data && Array.isArray(data.data)) {
                comments = data.data;
            } else {
                console.error('Unexpected comments format:', data);
                container.innerHTML = '<div class="text-center py-3 text-xs text-gray-500">No comments available</div>';
                return;
            }
        }

        if (!comments || comments.length === 0) {
            container.innerHTML = '<div class="text-center py-3 text-xs text-gray-500">No comments yet</div>';
            return;
        }

        // Debug: Log each comment structure
        comments.forEach((comment, index) => {
            console.log(`Comment ${index + 1} (ID: ${comment.id}):`, {
                id: comment.id,
                content: comment.content?.rendered?.substring(0, 50) + '...',
                children: comment.children,
                replies: comment.replies,
                hasChildren: !!(comment.children && comment.children.length > 0),
                hasReplies: !!(comment.replies && comment.replies.length > 0)
            });
        });

        // Create comments list
        const commentsList = document.createElement('div');
        commentsList.className = 'mt-3 pt-3 border-t border-gray-200 space-y-3';

        // Render comments tree (including nested replies)
        renderCommentsTree(comments, commentsList);

        container.innerHTML = '';
        container.appendChild(commentsList);

    } catch (error) {
        console.error('Error loading comments:', error);
        container.innerHTML = '<div class="text-center py-3 text-xs text-red-500">Error loading comments</div>';
    }
}

function getTimeAgo(date) {
    // Parse the date string and add 'Z' to ensure it's treated as UTC
    const utcDate = new Date(date + 'Z');
    const now = new Date();
    const seconds = Math.floor((now - utcDate) / 1000);

    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };

    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return `${interval} ${unit}${interval !== 1 ? 's' : ''} ago`;
        }
    }

    return 'just now';
}

function getActivityType(type) {
    const types = {
        'activity_update': 'posted an update',
        'new_avatar': 'changed their profile photo',
        'updated_profile': 'updated their profile',
        'new_member': 'joined the community',
        'friendship_created': 'made a new connection'
    };
    return types[type] || 'posted';
}

// Check if it's Monday in UK timezone and show Monday check-in
function checkMondayUK() {
    const ukTime = new Date().toLocaleString("en-US", {timeZone: "Europe/London"});
    const ukDate = new Date(ukTime);
    const dayOfWeek = ukDate.getDay();

    // 1 = Monday
    if (dayOfWeek === 1) {
        const mondayCheckin = document.getElementById('monday-checkin');
        if (mondayCheckin) {
            mondayCheckin.style.display = 'block';
        }
    }
}

// Check for live class events today
async function checkLiveClass(showLoader = true) {
    // Check if elements exist (only available for Gold members)
    const alertBox = document.getElementById('live-class-alert');
    if (!alertBox) {
        return; // User doesn't have access to this feature, exit early
    }

    const linkElement = document.getElementById('live-class-link');
    const titleElement = document.getElementById('live-class-title');
    const timeElement = document.getElementById('live-class-time');
    const loader = document.getElementById('live-class-loader');

    // Show loader only on initial load, not during polling
    if (showLoader && loader) loader.style.display = 'flex';

    try {
        // Get today's date in YYYY-MM-DD format
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const todayDate = `${year}-${month}-${day}`;

        // Fetch events for today and upcoming from The Events Calendar (including hidden events)
        const response = await fetch(`https://thenailtech.org/wp-json/tribe/events/v1/events?start_date=${todayDate}&per_page=10&include_hidden=true`);
        const data = await response.json();

        // Check if there are any events
        if (data.events && data.events.length > 0) {
            // Find first event WITHOUT group restriction (available to all Gold members)
            let event = null;
            let wpEvent = null;

            for (const evt of data.events) {
                const wpResponse = await fetch(`https://thenailtech.org/wp-json/wp/v2/tribe_events/${evt.id}`);
                const wpData = await wpResponse.json();

                // Only show events with NO group restriction
                if (!wpData.event_group || wpData.event_group === '' || wpData.event_group === '0') {
                    event = evt;
                    wpEvent = wpData;
                    break;
                }
            }

            // If no unrestricted events found, hide banner
            if (!event || !wpEvent) {
                alertBox.style.display = 'none';
                if (loader) loader.style.display = 'none';
                return;
            }

            // Extract event details
            const eventTitle = event.title || 'Live Class Today!';
            const zoomLink = wpEvent.zoom_link || event.url || '#';
            const replayLink = wpEvent.replay_link || '';

            // Get current time in UK timezone
            const ukTimeString = new Date().toLocaleString("en-US", {timeZone: "Europe/London"});
            const nowUK = new Date(ukTimeString);

            // Parse event start and end times
            // The Events Calendar returns dates in UK timezone already (e.g., "2025-10-13 10:00:00")
            // Simply parse the date string directly - it's already in the correct UK time
            const eventStartUK = new Date(event.start_date.replace(' ', 'T'));
            const eventEndUK = new Date(event.end_date.replace(' ', 'T'));

            // Check if event is same day as today
            const eventDateUK = eventStartUK.toLocaleDateString("en-US");
            const todayDateUK = nowUK.toLocaleDateString("en-US");
            const isSameDay = eventDateUK === todayDateUK;

            if (isSameDay) {
                // EVENT IS TODAY - Show with buttons based on state
                if (nowUK < eventStartUK) {
                    // BEFORE EVENT - Show "Starting at [TIME]" with Zoom link
                    const hours = eventStartUK.getHours();
                    const minutes = String(eventStartUK.getMinutes()).padStart(2, '0');

                    titleElement.innerHTML = eventTitle;
                    timeElement.textContent = `Starting at ${hours}:${minutes} (UK time)`;
                    linkElement.innerHTML = 'Join Zoom <span class="material-icons-outlined text-lg">arrow_forward</span>';
                    linkElement.href = zoomLink;
                    linkElement.style.display = 'flex';
                    alertBox.classList.add('animate-fade-in-up');
                    alertBox.style.display = 'block';

                } else if (nowUK >= eventStartUK && nowUK <= eventEndUK) {
                    // DURING EVENT - Show "LIVE NOW" with pulsing indicator and Zoom link
                    titleElement.innerHTML = `${eventTitle} <span class="inline-flex items-center ml-2 px-2 py-1 bg-red-600 text-white text-xs font-bold rounded-full animate-pulse">LIVE</span>`;
                    timeElement.textContent = 'Happening now!';
                    linkElement.innerHTML = 'Join Now <span class="material-icons-outlined text-lg">arrow_forward</span>';
                    linkElement.href = zoomLink;
                    linkElement.style.display = 'flex';
                    alertBox.classList.add('animate-fade-in-up');
                    alertBox.style.display = 'block';

                } else {
                    // AFTER EVENT (same day) - Show replay if link exists
                    if (replayLink && replayLink.trim() !== '') {
                        titleElement.innerHTML = eventTitle;
                        timeElement.textContent = 'Replay available';
                        linkElement.innerHTML = 'Watch Replay <span class="material-icons-outlined text-lg">play_arrow</span>';
                        linkElement.href = replayLink;
                        linkElement.style.display = 'flex';
                        alertBox.classList.add('animate-fade-in-up');
                        alertBox.style.display = 'block';
                    } else {
                        // No replay link, hide banner
                        alertBox.style.display = 'none';
                    }
                }
            } else {
                // EVENT IS IN THE FUTURE (not today) - Show as featured event without button
                const eventDateFormatted = eventStartUK.toLocaleDateString("en-GB", {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                });
                const eventTime = eventStartUK.toLocaleTimeString("en-GB", {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });

                titleElement.innerHTML = `Next Live Session: ${eventTitle}`;
                timeElement.textContent = `${eventDateFormatted} at ${eventTime} (UK time)`;
                linkElement.style.display = 'none'; // Hide button for future events
                alertBox.classList.add('animate-fade-in-up');
                alertBox.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Error checking for live class:', error);
    } finally {
        // Hide loader when done (only if it was shown)
        if (showLoader) {
            const loader = document.getElementById('live-class-loader');
            if (loader) loader.style.display = 'none';
        }
    }
}

// Check for group-restricted events
async function checkGroupEvents() {
    const alertSection = document.getElementById('group-event-alerts');
    const alertBanner = document.getElementById('group-event-banner');

    if (!alertSection || !alertBanner) return;

    const userGroups = <?php echo $user_groups_json; ?>;
    const titleElement = document.getElementById('group-event-title');
    const timeElement = document.getElementById('group-event-time');
    const linkElement = document.getElementById('group-event-link');
    const iconElement = document.getElementById('group-event-icon');

    try {
        // Fetch events using WordPress REST API v2 (supports status parameter for hidden events)
        const response = await fetch(`https://thenailtech.org/wp-json/wp/v2/tribe_events?status=publish&per_page=100&orderby=date&order=asc`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': '<?php echo $rest_nonce; ?>'
            }
        });
        const events = await response.json();

        console.log('🔍 Group Events API Response (WP REST v2):', events);
        console.log('📅 User Groups:', userGroups);

        // Check if there are any events
        if (events && events.length > 0) {
            console.log(`✅ Found ${events.length} events total`);

            // Find first event WITH group restriction that matches user's groups
            for (const wpEvent of events) {
                // Get event metadata (The Events Calendar stores dates in meta)
                // Check if meta fields are arrays or direct values
                const eventStartMeta = Array.isArray(wpEvent.meta?._EventStartDate)
                    ? wpEvent.meta._EventStartDate[0]
                    : wpEvent.meta?._EventStartDate;
                const eventEndMeta = Array.isArray(wpEvent.meta?._EventEndDate)
                    ? wpEvent.meta._EventEndDate[0]
                    : wpEvent.meta?._EventEndDate;
                const eventURL = Array.isArray(wpEvent.meta?._EventURL)
                    ? wpEvent.meta._EventURL[0]
                    : (wpEvent.meta?._EventURL || '#');

                console.log(`📋 Event: "${wpEvent.title?.rendered}"`, {
                    id: wpEvent.id,
                    start_date: eventStartMeta,
                    end_date: eventEndMeta,
                    event_group: wpEvent.event_group,
                    zoom_link: wpEvent.zoom_link,
                    replay_link: wpEvent.replay_link
                });

                // Check if event has group restriction AND user belongs to that group
                if (wpEvent.event_group && wpEvent.event_group !== '' && wpEvent.event_group !== '0') {
                    const eventGroupId = parseInt(wpEvent.event_group);
                    console.log(`🔒 Event has group restriction: ${eventGroupId}`);

                    if (userGroups.includes(eventGroupId)) {
                        console.log(`✨ MATCH! User belongs to group ${eventGroupId}`);

                        // MATCH FOUND - Extract event details
                        const eventTitle = wpEvent.title?.rendered || 'Live Session';
                        const zoomLink = wpEvent.zoom_link || eventURL;
                        const replayLink = wpEvent.replay_link || '';

                        // Parse event start and end times from meta (stored in YYYY-MM-DD HH:mm:ss format in UK timezone)
                        // The Events Calendar stores dates in UK timezone (e.g., "2025-10-27 10:00:00")
                        // Parse as UTC then get components to display, no timezone conversion needed
                        const [startDatePart, startTimePart] = eventStartMeta.split(' ');
                        const [startYear, startMonth, startDay] = startDatePart.split('-');
                        const [startHour, startMinute] = startTimePart.split(':');

                        const [endDatePart, endTimePart] = eventEndMeta.split(' ');
                        const [endYear, endMonth, endDay] = endDatePart.split('-');
                        const [endHour, endMinute] = endTimePart.split(':');

                        // Create date objects for comparison (treating stored time as local browser time for comparison)
                        const eventStartUK = new Date(startYear, startMonth - 1, startDay, startHour, startMinute);
                        const eventEndUK = new Date(endYear, endMonth - 1, endDay, endHour, endMinute);

                        // Get current UK time for comparison
                        const ukTimeString = new Date().toLocaleString("en-US", {timeZone: "Europe/London"});
                        const nowUK = new Date(ukTimeString);

                        // Check if event is same day as today
                        const eventDateUK = eventStartUK.toLocaleDateString("en-US");
                        const todayDateUK = nowUK.toLocaleDateString("en-US");
                        const isSameDay = eventDateUK === todayDateUK;

                        if (isSameDay) {
                            // EVENT IS TODAY - Show with buttons based on state
                            if (nowUK < eventStartUK) {
                                // BEFORE EVENT - Show "Starting at [TIME]" with Zoom link
                                // Use the parsed time directly (already in UK time)
                                titleElement.innerHTML = eventTitle;
                                timeElement.textContent = `Starting at ${startHour}:${startMinute} (UK time)`;
                                linkElement.innerHTML = 'Join Zoom <span class="material-icons-outlined text-lg">arrow_forward</span>';
                                linkElement.href = zoomLink;
                                linkElement.style.display = 'flex';
                                iconElement.textContent = 'event';
                                alertSection.style.display = 'flex';
                                return; // Stop after first match

                            } else if (nowUK >= eventStartUK && nowUK <= eventEndUK) {
                                // DURING EVENT - Show "LIVE NOW" with pulsing indicator and Zoom link
                                titleElement.innerHTML = `${eventTitle} <span class="inline-flex items-center ml-2 px-2 py-1 bg-red-600 text-white text-xs font-bold rounded-full animate-pulse">LIVE</span>`;
                                timeElement.textContent = 'Happening now!';
                                linkElement.innerHTML = 'Join Now <span class="material-icons-outlined text-lg">arrow_forward</span>';
                                linkElement.href = zoomLink;
                                linkElement.style.display = 'flex';
                                iconElement.textContent = 'live_tv';
                                alertSection.style.display = 'flex';
                                return; // Stop after first match

                            } else {
                                // AFTER EVENT (same day) - Show replay if link exists
                                if (replayLink && replayLink.trim() !== '') {
                                    titleElement.innerHTML = eventTitle;
                                    timeElement.textContent = 'Replay available';
                                    linkElement.innerHTML = 'Watch Replay <span class="material-icons-outlined text-lg">play_arrow</span>';
                                    linkElement.href = replayLink;
                                    linkElement.style.display = 'flex';
                                    iconElement.textContent = 'play_circle';
                                    alertSection.style.display = 'flex';
                                    return; // Stop after first match
                                }
                            }
                        } else {
                            // EVENT IS IN THE FUTURE (not today) - Show as featured event
                            const eventDateFormatted = eventStartUK.toLocaleDateString("en-GB", {
                                weekday: 'long',
                                day: 'numeric',
                                month: 'long'
                            });

                            titleElement.innerHTML = `Next Session: ${eventTitle}`;
                            timeElement.textContent = `${eventDateFormatted} at ${startHour}:${startMinute} (UK time)`;
                            linkElement.style.display = 'none';
                            iconElement.textContent = 'event';
                            alertSection.style.display = 'flex';
                            return; // Stop after first match
                        }
                    }
                }
            }
        }

        // No matching events found
        alertSection.style.display = 'none';

    } catch (error) {
        console.error('Error checking group events:', error);
        alertSection.style.display = 'none';
    }
}

// Check for Brand Builder Programme time-sensitive call banners
function checkBBPCalls() {
    const bannersSection = document.getElementById('bbp-call-banners');
    const banner1 = document.getElementById('bbp-call-1');
    const banner2 = document.getElementById('bbp-call-2');

    // Exit if section doesn't exist (user not in BBP or BBP VIP group)
    if (!bannersSection) return;

    // Get current time in London timezone
    const londonTime = new Date().toLocaleString("en-US", {timeZone: "Europe/London"});
    const now = new Date(londonTime);

    // Check if today is November 17, 2025 (UK time)
    const targetDate = new Date('2025-11-17T00:00:00');
    const isSameDay = now.getFullYear() === targetDate.getFullYear() &&
                      now.getMonth() === targetDate.getMonth() &&
                      now.getDate() === targetDate.getDate();

    // Only show banners if it's November 17, 2025
    if (!isSameDay) {
        bannersSection.style.display = 'none';
        return;
    }

    // Define expiry times for each banner (November 17, 2025, London time)
    const banner1Expiry = new Date('2025-11-17T12:30:00'); // 12:30 PM London time
    const banner2Expiry = new Date('2025-11-17T13:30:00'); // 1:30 PM London time

    let anyBannerVisible = false;

    // Check Banner 1 (shows until 12:30 PM) - only if it exists in DOM
    if (banner1) {
        if (now < banner1Expiry) {
            banner1.style.display = 'block';
            anyBannerVisible = true;
        } else {
            banner1.style.display = 'none';
        }
    }

    // Check Banner 2 (shows until 1:30 PM) - only if it exists in DOM
    if (banner2) {
        if (now < banner2Expiry) {
            banner2.style.display = 'block';
            anyBannerVisible = true;
        } else {
            banner2.style.display = 'none';
        }
    }

    // Show/hide the entire section based on whether any banner is visible
    if (anyBannerVisible) {
        bannersSection.style.display = 'flex';
    } else {
        bannersSection.style.display = 'none';
    }
}

// ============================================
// POLLING MECHANISM FOR AJAX CONTENT
// ============================================

// Polling configuration
const POLLING_CONFIG = {
    communityPosts: {
        interval: 300000, // 5 minutes
        enabled: true
    },
    liveSessions: {
        interval: 600000, // 10 minutes
        enabled: true
    }
};

// Polling state management
const pollingState = {
    communityPostsTimer: null,
    liveSessionsTimer: null,
    isPageVisible: true,
    lastCommunityUpdate: null,
    lastLiveSessionUpdate: null
};

// Function to start polling for community posts
function startCommunityPostsPolling() {
    if (!POLLING_CONFIG.communityPosts.enabled) return;

    // Clear existing timer if any
    if (pollingState.communityPostsTimer) {
        clearInterval(pollingState.communityPostsTimer);
    }

    // Set up polling interval
    pollingState.communityPostsTimer = setInterval(() => {
        // Only poll if page is visible and user is not actively typing
        if (pollingState.isPageVisible && communityFeeds.length > 0) {
            // Don't reload if user is actively typing in a textarea or input
            const activeElement = document.activeElement;
            const isTyping = activeElement && (activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'INPUT');

            if (!isTyping) {
                const currentFeed = communityFeeds[activeFeedIndex];
                if (currentFeed) {
                    console.log('🔄 Polling community posts...');
                    loadCommunityFeed(currentFeed.id, 'community-feeds', true);
                }
            } else {
                console.log('⏸️ Skipping poll - user is typing');
            }
        }
    }, POLLING_CONFIG.communityPosts.interval);

    console.log('✅ Community posts polling started (every ' + (POLLING_CONFIG.communityPosts.interval / 1000) + 's)');
}

// Function to start polling for live sessions
function startLiveSessionsPolling() {
    if (!POLLING_CONFIG.liveSessions.enabled) return;

    // Clear existing timer if any
    if (pollingState.liveSessionsTimer) {
        clearInterval(pollingState.liveSessionsTimer);
    }

    // Set up polling interval
    pollingState.liveSessionsTimer = setInterval(() => {
        // Only poll if page is visible
        if (pollingState.isPageVisible) {
            console.log('🔄 Polling live sessions...');
            checkLiveClass(false); // Don't show loader during polling
            checkGroupEvents();
        }
    }, POLLING_CONFIG.liveSessions.interval);

    console.log('✅ Live sessions polling started (every ' + (POLLING_CONFIG.liveSessions.interval / 1000) + 's)');
}

// Function to stop all polling
function stopAllPolling() {
    if (pollingState.communityPostsTimer) {
        clearInterval(pollingState.communityPostsTimer);
        pollingState.communityPostsTimer = null;
    }
    if (pollingState.liveSessionsTimer) {
        clearInterval(pollingState.liveSessionsTimer);
        pollingState.liveSessionsTimer = null;
    }
    console.log('⏸️ All polling stopped');
}

// Handle page visibility changes to pause/resume polling
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        pollingState.isPageVisible = false;
        console.log('👁️ Page hidden - polling paused');
    } else {
        pollingState.isPageVisible = true;
        console.log('👁️ Page visible - polling resumed');
        // Immediately refresh content when page becomes visible again (no loader)
        if (communityFeeds.length > 0) {
            const currentFeed = communityFeeds[activeFeedIndex];
            if (currentFeed) {
                loadCommunityFeed(currentFeed.id, 'community-feeds', true);
            }
        }
        checkLiveClass(false); // Don't show loader when page becomes visible
        checkGroupEvents();
    }
});

// Clean up polling on page unload
window.addEventListener('beforeunload', () => {
    stopAllPolling();
});

// Load on page load
initializeCommunityFeeds();
checkMondayUK();
checkLiveClass();
checkGroupEvents();
checkBBPCalls();

// Start polling after initial load
setTimeout(() => {
    startCommunityPostsPolling();
    startLiveSessionsPolling();
}, 2000); // Wait 2 seconds after page load before starting polling

</script>

<!-- Hotjar Tracking Code for New Dashboard -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:6549844,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>

