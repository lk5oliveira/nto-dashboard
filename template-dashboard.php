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

// TEMPORARY: Testing override - REMOVE AFTER TESTING
$test_groups = isset($_GET['test_groups']) ? array_map('intval', explode(',', $_GET['test_groups'])) : null;
$is_testing = $test_groups !== null;
if ($is_testing) {
    $user_groups = $test_groups;
    $user_groups_json = json_encode($test_groups);
}

// Check group membership (server-side for security)
$is_admin = current_user_can('administrator');
$admin_bypass = $is_admin && !$is_testing; // Disable bypass during testing
$is_gold_member = $admin_bypass || in_array(4383, $user_groups);
$is_educator = $admin_bypass || in_array(272088, $user_groups);
$is_bbp = $admin_bypass || in_array(347879, $user_groups);
$is_bbp_vip = $admin_bypass || in_array(348042, $user_groups);
$has_any_group = $is_gold_member || $is_educator || $is_bbp || $is_bbp_vip;

// Generate nonce for REST API authentication
$rest_nonce = wp_create_nonce('wp_rest');
?>

<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
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
</style>

<div class="nto-dashboard" style="background: #f6f1ea; min-height: 100vh;padding: 0px">
    <!-- Welcome Banner -->
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 pt-6 pb-4">
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
// ============================================
// TEMPORARY TESTING FUNCTION - REMOVE AFTER TESTING
// ============================================
// Override groups for testing (server-side rendering)
// Usage in browser console:
//   testGroups([4383])              - Test as Gold member only
//   testGroups([347879, 348042])    - Test as BBP + BBP VIP member
//   testGroups([272088])            - Test as Educator member
//   testGroups([])                  - Test as non-member
//   testGroups()                    - Reset to actual groups
// ============================================
function testGroups(groups = null) {
    const currentUrl = new URL(window.location.href);

    if (groups === null) {
        currentUrl.searchParams.delete('test_groups');
        console.log('Testing disabled. Reloading with actual groups...');
    } else {
        currentUrl.searchParams.set('test_groups', groups.join(','));
        console.log('🧪 Testing with groups:', groups, '- Reloading...');
    }

    window.location.href = currentUrl.toString();
}

<?php if ($is_testing) : ?>
console.log('🧪 TESTING MODE: Simulating groups <?php echo json_encode($user_groups); ?>');
<?php endif; ?>

// Map LearnDash groups for JavaScript use
const userGroups = <?php echo $user_groups_json; ?>;
const adminHasFullAccess = <?php echo $admin_bypass ? 'true' : 'false'; ?>;
const communityFeeds = [];

// Check which communities user has access to
// General first (default feed)
if (adminHasFullAccess || userGroups.includes(4383)) { // Gold Members
    communityFeeds.push({ id: null, name: 'General', label: 'General' });
}
if (adminHasFullAccess || userGroups.includes(347879)) { // BBP
    communityFeeds.push({ id: 67, name: 'BBP', label: 'BBP' });
}
if (adminHasFullAccess || userGroups.includes(348042)) { // BBP VIP
    communityFeeds.push({ id: 68, name: 'BBP VIP', label: 'BBP VIP' });
}
if (adminHasFullAccess || userGroups.includes(272088)) { // Educator Elevation
    communityFeeds.push({ id: 65, name: 'Educator Elevation', label: 'Educator' });
}

let activeFeedIndex = 0;

async function loadCommunityFeed(groupId = null, containerId = 'community-feeds') {
    const feedContainer = document.getElementById(containerId);

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
        postsContainer.className = 'space-y-3';

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
            avatar.className = 'w-10 h-10 rounded-full object-cover ring-2 ring-sand flex-shrink-0';

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
                groupTag.className = 'bg-dark-green text-white px-2 py-0.5 rounded-full font-semibold';
                groupTag.style.fontSize = '9px';
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

            // Likes
            if (activity.favorite_count && activity.favorite_count > 0) {
                const likes = document.createElement('div');
                likes.className = 'flex items-center gap-1 text-xs';
                likes.innerHTML = `<span class="material-icons-outlined text-sm">favorite</span> ${activity.favorite_count}`;
                footer.appendChild(likes);
            }

            // Comments
            if (activity.comment_count && activity.comment_count > 0) {
                const comments = document.createElement('div');
                comments.className = 'flex items-center gap-1 text-xs';
                comments.innerHTML = `<span class="material-icons-outlined text-sm">chat_bubble_outline</span> ${activity.comment_count}`;
                footer.appendChild(comments);
            }

            postCard.appendChild(footer);
            postsContainer.appendChild(postCard);
        });

        feedContainer.innerHTML = '';
        feedContainer.appendChild(postsContainer);

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
        viewAllButton.className = 'mt-4 block text-center bg-dark-green text-white font-bold py-3 px-6 rounded-full hover:bg-dark-green-light transition-all shadow-md';
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

    // Load first feed
    loadCommunityFeed(communityFeeds[0].id);
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
async function checkLiveClass() {
    // Check if elements exist (only available for Gold members)
    const alertBox = document.getElementById('live-class-alert');
    if (!alertBox) {
        return; // User doesn't have access to this feature, exit early
    }

    const linkElement = document.getElementById('live-class-link');
    const titleElement = document.getElementById('live-class-title');
    const timeElement = document.getElementById('live-class-time');
    const loader = document.getElementById('live-class-loader');

    // Show loader
    if (loader) loader.style.display = 'flex';

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
        // Hide loader when done
        const loader = document.getElementById('live-class-loader');
        if (loader) loader.style.display = 'none';
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

// Load on page load
initializeCommunityFeeds();
checkMondayUK();
checkLiveClass();
checkGroupEvents();
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

