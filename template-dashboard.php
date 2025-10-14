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

    <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8" style="padding:0px;">

        <!-- Conditional Alerts Section - Flexible layout - Gold Members Only -->
        <section id="alerts" class="flex flex-col md:flex-row gap-3 lg:gap-4 mb-6 relative" data-require-groups="4383" style="display: none;">
            <!-- Loading indicator for live class check -->
            <div id="live-class-loader" class="absolute -top-8 right-0 flex items-center gap-2 text-dark-green opacity-60" style="display: none;">
                <span class="material-icons-outlined text-sm animate-spin">refresh</span>
                <span class="text-xs">Checking for live events...</span>
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
                <button class="w-full md:w-auto bg-dark-green text-white font-bold py-2 px-6 rounded-full hover:bg-sand transition-all flex items-center justify-center gap-2">
                    Check In Now <span class="material-icons-outlined text-lg">arrow_forward</span>
                </button>
            </div>
        </section>

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
                <section data-require-groups="4383" style="display: none;">
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

                <!-- Educator Elevation Section - Conditional -->
                <section id="educator-elevation" class="bg-white rounded-xl p-5 lg:p-8 shadow-md" data-require-groups="272088" style="display: none;">
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

                <!-- Builder Brand Programme Section - For BBP and BBP VIP members -->
                <section id="bbp-section" class="bg-white rounded-xl p-5 lg:p-8 shadow-md" data-require-groups="347879,348042" style="display: none;">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-icons-outlined text-3xl lg:text-4xl text-dark-green">rocket_launch</span>
                        <h2 class="text-xl lg:text-2xl font-bold text-dark-green">Builder Brand Programme</h2>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <a href="https://thenailtech.org/groups/brand-builder-incubator-1570503813/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">groups</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">BBP Community</h3>
                                <p class="text-xs opacity-90">Connect with BBP members</p>
                            </div>
                        </a>
                        <a href="https://thenailtech.org/groups/brand-builder-incubator-vip/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]" data-require-groups="348042" style="display: none;">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">workspace_premium</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">VIP Community</h3>
                                <p class="text-xs opacity-90">Exclusive VIP access</p>
                            </div>
                        </a>
                        <a href="https://thenailtech.org/courses/brand-builder-incubator/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">menu_book</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Course Materials</h3>
                                <p class="text-xs opacity-90">Lessons & resources</p>
                            </div>
                        </a>
                        <a href="https://thenailtech.org/courses/brand-builder-incubator/" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md flex-1 min-w-[calc(50%-0.5rem)] md:min-w-[calc(33.333%-0.67rem)]">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">calendar_month</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Schedule</h3>
                                <p class="text-xs opacity-90">View cohort sessions</p>
                            </div>
                        </a>
                    </div>
                </section>

                <!-- Upgrade to Gold - Non-Members -->
                <section data-exclude-groups="4383" style="display: none;">
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

                            <a href="#" class="inline-flex items-center gap-2 bg-white text-dark-green font-bold py-3 px-8 rounded-full hover:bg-sand transition-all shadow-lg">
                                Become a Gold Member <span class="material-icons-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </section>

            </div>

            <!-- Right Sidebar (1/3 width on desktop) - Gold Members Only -->
            <div class="lg:col-span-1 space-y-6" data-require-groups="4383" style="display: none;">

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

        </div>

    </main>
</div>

<script>
// Map LearnDash groups to BuddyBoss groups
const userGroups = <?php echo $user_groups_json; ?>;
const adminHasFullAccess = <?php echo (current_user_can('administrator') && true) ? 'true' : 'false'; ?>; // Change the second 'true' to 'false' to disable
const communityFeeds = [];

// Check which communities user has access to
if (adminHasFullAccess || userGroups.includes(4383)) { // Gold Members
    communityFeeds.push({ id: null, name: 'Gold Members', label: 'Gold' });
}
if (adminHasFullAccess || userGroups.includes(272088)) { // Educator Elevation
    communityFeeds.push({ id: 65, name: 'Educator Elevation', label: 'Educator' });
}
if (adminHasFullAccess || userGroups.includes(348042)) { // BBP VIP
    communityFeeds.push({ id: 68, name: 'BBP VIP', label: 'BBP VIP' });
}
if (adminHasFullAccess || userGroups.includes(347879)) { // BBP
    communityFeeds.push({ id: 67, name: 'BBP', label: 'BBP' });
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
            const date = new Date(activity.date);
            const timeAgo = getTimeAgo(date);

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

            const timeSpan = document.createElement('span');
            timeSpan.className = 'text-xs text-gray-500';
            timeSpan.textContent = timeAgo;

            infoInner.appendChild(userName);
            infoInner.appendChild(timeSpan);
            userInfo.appendChild(infoInner);

            header.appendChild(avatar);
            header.appendChild(userInfo);
            postCard.appendChild(header);

            // Content section
            if (activity.content_stripped || activity.content?.rendered) {
                const content = document.createElement('div');
                content.className = 'text-xs text-gray-700 mb-3 line-clamp-3';
                content.textContent = activity.content_stripped || activity.content?.rendered?.replace(/<[^>]*>/g, '') || '';
                postCard.appendChild(content);
            }

            // Footer with likes, comments, and read more
            const footer = document.createElement('div');
            footer.className = 'flex items-center justify-between gap-3';

            // Likes and comments count
            const stats = document.createElement('div');
            stats.className = 'flex items-center gap-3 text-gray-500';

            // Likes
            if (activity.favorite_count && activity.favorite_count > 0) {
                const likes = document.createElement('div');
                likes.className = 'flex items-center gap-1 text-xs';
                likes.innerHTML = `<span class="material-icons-outlined text-sm">favorite</span> ${activity.favorite_count}`;
                stats.appendChild(likes);
            }

            // Comments
            if (activity.comment_count && activity.comment_count > 0) {
                const comments = document.createElement('div');
                comments.className = 'flex items-center gap-1 text-xs';
                comments.innerHTML = `<span class="material-icons-outlined text-sm">chat_bubble_outline</span> ${activity.comment_count}`;
                stats.appendChild(comments);
            }

            footer.appendChild(stats);

            // Read more link
            const link = document.createElement('a');
            link.href = activity.link;
            link.target = '_blank';
            link.className = 'text-dark-green font-semibold hover:underline flex items-center gap-1 text-xs';
            link.innerHTML = 'Read More <span class="material-icons-outlined text-sm">arrow_forward</span>';

            footer.appendChild(link);
            postCard.appendChild(footer);
            postsContainer.appendChild(postCard);
        });

        feedContainer.innerHTML = '';
        feedContainer.appendChild(postsContainer);

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
    const seconds = Math.floor((new Date() - date) / 1000);

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
    // Show loader
    const loader = document.getElementById('live-class-loader');
    if (loader) loader.style.display = 'flex';

    try {
        // Get today's date in YYYY-MM-DD format
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const todayDate = `${year}-${month}-${day}`;

        // Fetch events for today and upcoming from The Events Calendar
        const response = await fetch(`https://thenailtech.org/wp-json/tribe/events/v1/events?start_date=${todayDate}&per_page=10`);
        const data = await response.json();

        // Check if there are any events
        if (data.events && data.events.length > 0) {
            const event = data.events[0]; // Get the next/current event

            // Fetch custom fields from WordPress REST API
            const eventId = event.id;
            const wpResponse = await fetch(`https://thenailtech.org/wp-json/wp/v2/tribe_events/${eventId}`);
            const wpEvent = await wpResponse.json();

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

            const alertBox = document.getElementById('live-class-alert');
            const linkElement = document.getElementById('live-class-link');

            if (isSameDay) {
                // EVENT IS TODAY - Show with buttons based on state
                if (nowUK < eventStartUK) {
                    // BEFORE EVENT - Show "Starting at [TIME]" with Zoom link
                    const hours = eventStartUK.getHours();
                    const minutes = String(eventStartUK.getMinutes()).padStart(2, '0');

                    document.getElementById('live-class-title').innerHTML = eventTitle;
                    document.getElementById('live-class-time').textContent = `Starting at ${hours}:${minutes} (UK time)`;
                    linkElement.innerHTML = 'Join Zoom <span class="material-icons-outlined text-lg">arrow_forward</span>';
                    linkElement.href = zoomLink;
                    linkElement.style.display = 'flex';
                    alertBox.classList.add('animate-fade-in-up');
                    alertBox.style.display = 'block';

                } else if (nowUK >= eventStartUK && nowUK <= eventEndUK) {
                    // DURING EVENT - Show "LIVE NOW" with pulsing indicator and Zoom link
                    document.getElementById('live-class-title').innerHTML = `${eventTitle} <span class="inline-flex items-center ml-2 px-2 py-1 bg-red-600 text-white text-xs font-bold rounded-full animate-pulse">LIVE</span>`;
                    document.getElementById('live-class-time').textContent = 'Happening now!';
                    linkElement.innerHTML = 'Join Now <span class="material-icons-outlined text-lg">arrow_forward</span>';
                    linkElement.href = zoomLink;
                    linkElement.style.display = 'flex';
                    alertBox.classList.add('animate-fade-in-up');
                    alertBox.style.display = 'block';

                } else {
                    // AFTER EVENT (same day) - Show replay if link exists
                    if (replayLink && replayLink.trim() !== '') {
                        document.getElementById('live-class-title').innerHTML = eventTitle;
                        document.getElementById('live-class-time').textContent = 'Replay available';
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

                document.getElementById('live-class-title').innerHTML = `Next Live Session: ${eventTitle}`;
                document.getElementById('live-class-time').textContent = `${eventDateFormatted} at ${eventTime} (UK time)`;
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

/**
 * Reusable LearnDash Group Access System
 *
 * To show/hide any element based on LearnDash group enrollment:
 * 1. Add data-require-groups="123" attribute (replace 123 with actual group ID)
 * 2. Add style="display: none;" to hide by default
 * 3. For multiple groups (OR logic), use: data-require-groups="123,456,789"
 * 4. To show content ONLY to non-members, use: data-exclude-groups="123"
 *
 * Example:
 * <div data-require-groups="123,456" style="display: none;">
 *   Content only visible to users in group 123 OR 456
 * </div>
 * <div data-exclude-groups="123" style="display: none;">
 *   Content only visible to users NOT in group 123
 * </div>
 */
function checkGroupAccess() {
    // Note: userGroups and adminHasFullAccess are already defined globally above

    // Find all elements with data-require-groups attribute
    const requireElements = document.querySelectorAll('[data-require-groups]');

    requireElements.forEach(element => {
        // Get required group IDs from data attribute (comma-separated)
        const requiredGroups = element.getAttribute('data-require-groups')
            .split(',')
            .map(id => parseInt(id.trim()))
            .filter(id => !isNaN(id));

        // Check if user is in ANY of the required groups OR is admin with bypass enabled
        const hasAccess = adminHasFullAccess || requiredGroups.some(groupId => userGroups.includes(groupId));

        // Show element if user has access
        if (hasAccess) {
            // Get the computed display value or use 'block' as default
            const displayValue = element.classList.contains('flex') || element.style.display === 'flex' ? 'flex' : 'block';
            element.style.display = displayValue;
        }
    });

    // Find all elements with data-exclude-groups attribute
    const excludeElements = document.querySelectorAll('[data-exclude-groups]');

    excludeElements.forEach(element => {
        // Get excluded group IDs from data attribute (comma-separated)
        const excludedGroups = element.getAttribute('data-exclude-groups')
            .split(',')
            .map(id => parseInt(id.trim()))
            .filter(id => !isNaN(id));

        // Check if user is NOT in ANY of the excluded groups (admins never see excluded content when bypass is on)
        const hasAccess = adminHasFullAccess ? false : !excludedGroups.some(groupId => userGroups.includes(groupId));

        // Show element if user is not in excluded groups
        if (hasAccess) {
            // Get the computed display value or use 'block' as default
            const displayValue = element.classList.contains('flex') || element.style.display === 'flex' ? 'flex' : 'block';
            element.style.display = displayValue;
        }
    });
}

// Load on page load
initializeCommunityFeeds();
checkMondayUK();
checkLiveClass();
checkGroupAccess();
</script>

<?php wp_footer(); ?>
<?php get_footer(); ?>
