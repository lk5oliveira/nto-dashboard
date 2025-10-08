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

<div class="nto-dashboard" style="background: #f6f1ea; min-height: 100vh;">
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

    <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Conditional Alerts Section - Side by side on desktop -->
        <section id="alerts" class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-6 relative">
            <!-- Loading indicator for live class check -->
            <div id="live-class-loader" class="absolute -top-8 right-0 flex items-center gap-2 text-dark-green opacity-60" style="display: none;">
                <span class="material-icons-outlined text-sm animate-spin">refresh</span>
                <span class="text-xs">Checking for live events...</span>
            </div>
            <!-- Live Class Alert - Conditional -->
            <div id="live-class-alert" class="bg-gradient-to-r from-dark-green to-dark-green-light rounded-xl p-4 lg:p-6 shadow-lg text-white" style="display: none;">
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
            <div id="monday-checkin" class="bg-white rounded-xl p-4 lg:p-6 shadow-md border-2 border-sand" style="display: none;">
                <div class="flex items-center gap-3 mb-3 lg:mb-4">
                    <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green flex-shrink-0">edit_note</span>
                    <div class="flex-1">
                        <p class="font-bold text-base lg:text-lg text-dark-green font-montserrat">Monday Check In!</p>
                        <p class="text-xs lg:text-sm text-gray-600">Share wins, learnings & changes</p>
                    </div>
                </div>
                <button class="w-full md:w-auto bg-dark-green text-white font-bold py-2 px-6 rounded-full hover:bg-dark-green-light transition-all flex items-center justify-center gap-2">
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

                <!-- Educator Elevation Section - Conditional -->
                <!-- Replace GROUP_ID with actual Educator Elevation LearnDash group ID -->
                <section id="educator-elevation" class="bg-white rounded-xl p-5 lg:p-8 shadow-md" data-require-groups="272088" style="display: none;">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-icons-outlined text-3xl lg:text-4xl text-dark-green">school</span>
                        <h2 class="text-xl lg:text-2xl font-bold text-dark-green">Educator Elevation</h2>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <a href="#" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">groups</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Community Hub</h3>
                                <p class="text-xs opacity-90">Engage with cohort</p>
                            </div>
                        </a>
                        <a href="#" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">menu_book</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Course Materials</h3>
                                <p class="text-xs opacity-90">Lessons & resources</p>
                            </div>
                        </a>
                        <a href="#" class="group bg-dark-green hover:bg-dark-green-light rounded-xl p-5 lg:p-6 transition-all transform hover:scale-105 shadow-md col-span-2 md:col-span-1">
                            <div class="flex flex-col items-center text-center text-white">
                                <span class="material-icons-outlined text-4xl lg:text-5xl mb-3">calendar_month</span>
                                <h3 class="font-bold text-sm lg:text-base mb-1 font-montserrat">Schedule</h3>
                                <p class="text-xs opacity-90">View cohort sessions</p>
                            </div>
                        </a>
                    </div>
                </section>

                <!-- Main Navigation Grid - Always Visible -->
                <section>
                    <h2 class="text-xl lg:text-2xl font-bold text-dark-green mb-5 font-montserrat">Your Dashboard</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 lg:gap-5">

                        <!-- Business Programme -->
                        <a href="#" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">business_center</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Business Programme</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Build & grow your business</p>
                            </div>
                        </a>

                        <!-- Nail Tutorials -->
                        <a href="#" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">video_library</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Nail Tutorials</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Technique videos</p>
                            </div>
                        </a>

                        <!-- Monthly Calendar -->
                        <a href="#" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">event</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Monthly Calendar</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">All upcoming events</p>
                            </div>
                        </a>

                        <!-- Live Class Replay -->
                        <a href="#" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">play_lesson</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Class Replays</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Catch up on sessions</p>
                            </div>
                        </a>

                        <!-- Community Feed -->
                        <a href="#" class="bg-white rounded-xl p-5 lg:p-6 shadow-md hover:shadow-xl transition-all group border-2 border-transparent hover:border-dark-green col-span-2 md:col-span-1">
                            <div class="flex flex-col">
                                <span class="material-icons-outlined text-4xl lg:text-5xl text-dark-green mb-3 group-hover:scale-110 transition-transform">forum</span>
                                <h3 class="font-bold text-sm lg:text-base mb-2 text-dark-green font-montserrat">Community Feed</h3>
                                <p class="text-gray-600 text-xs lg:text-sm">Connect with nail techs</p>
                            </div>
                        </a>

                    </div>
                </section>

            </div>

            <!-- Right Sidebar (1/3 width on desktop) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Quick Start -->
                <section>
                    <a href="#" class="block bg-gradient-to-br from-sand to-white rounded-xl p-6 lg:p-8 shadow-md border-2 border-dark-green hover:shadow-lg transition-all group">
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
                        <a href="#" class="text-dark-green font-semibold hover:underline text-xs">View All</a>
                    </div>
                    <div id="community-feed" class="space-y-3">
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
async function loadCommunityFeed() {
    try {
        const response = await fetch('https://thenailtech.org/wp-json/buddyboss/v1/activity?per_page=20');
        const activities = await response.json();

        const feedContainer = document.getElementById('community-feed');

        if (!activities || activities.length === 0) {
            feedContainer.innerHTML = '<p class="text-center text-gray-500 text-sm">No recent activity</p>';
            return;
        }

        // Filter to only show actual posts (activity_update type) and limit to 5
        const posts = activities.filter(activity => activity.type === 'activity_update').slice(0, 5);

        if (posts.length === 0) {
            feedContainer.innerHTML = '<p class="text-center text-gray-500 text-sm">No recent posts</p>';
            return;
        }

        feedContainer.innerHTML = posts.map(activity => {
            const date = new Date(activity.date);
            const timeAgo = getTimeAgo(date);

            return `
                <div class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition-all border-2 border-transparent hover:border-sand">
                    <div class="flex items-start gap-3 mb-3">
                        <img src="${activity.user_avatar.thumb}" alt="${activity.name}" class="w-10 h-10 rounded-full object-cover ring-2 ring-sand flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col gap-1">
                                <h3 class="font-bold text-dark-green font-montserrat text-sm truncate">${activity.name}</h3>
                                <span class="text-xs text-gray-500">${timeAgo}</span>
                            </div>
                        </div>
                    </div>
                    ${activity.content_stripped ? `
                        <div class="text-xs text-gray-700 mb-3 line-clamp-3">
                            ${activity.content_stripped}
                        </div>
                    ` : ''}
                    <a href="${activity.link}" target="_blank" class="text-dark-green font-semibold hover:underline flex items-center gap-1 text-xs">
                        Read More <span class="material-icons-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            `;
        }).join('');

    } catch (error) {
        console.error('Error loading community feed:', error);
        document.getElementById('community-feed').innerHTML = `
            <div class="bg-white rounded-xl p-6 shadow-md text-center">
                <span class="material-icons-outlined text-4xl text-gray-400 mb-2">error_outline</span>
                <p class="text-gray-600 text-sm">Unable to load updates</p>
            </div>
        `;
    }
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
            const eventStart = new Date(event.start_date);
            const eventEnd = new Date(event.end_date);

            // Determine event state: before, during, or after
            if (nowUK < eventStart) {
                // BEFORE EVENT - Show "Starting at [TIME]" with Zoom link
                const hours = eventStart.getHours();
                const minutes = String(eventStart.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const displayHours = hours % 12 || 12;

                const alertBox = document.getElementById('live-class-alert');
                document.getElementById('live-class-title').innerHTML = eventTitle;
                document.getElementById('live-class-time').textContent = `Starting at ${displayHours}:${minutes} ${ampm}`;
                document.getElementById('live-class-link').innerHTML = 'Join Zoom <span class="material-icons-outlined text-lg">arrow_forward</span>';
                document.getElementById('live-class-link').href = zoomLink;
                alertBox.classList.add('animate-fade-in-up');
                alertBox.style.display = 'block';

            } else if (nowUK >= eventStart && nowUK <= eventEnd) {
                // DURING EVENT - Show "LIVE NOW" with pulsing indicator and Zoom link
                const alertBox = document.getElementById('live-class-alert');
                document.getElementById('live-class-title').innerHTML = `${eventTitle} <span class="inline-flex items-center ml-2 px-2 py-1 bg-red-600 text-white text-xs font-bold rounded-full animate-pulse">LIVE</span>`;
                document.getElementById('live-class-time').textContent = 'Happening now!';
                document.getElementById('live-class-link').innerHTML = 'Join Now <span class="material-icons-outlined text-lg">arrow_forward</span>';
                document.getElementById('live-class-link').href = zoomLink;
                alertBox.classList.add('animate-fade-in-up');
                alertBox.style.display = 'block';

            } else {
                // AFTER EVENT - Check if still same day in UK timezone
                const eventDateUK = new Date(event.start_date).toLocaleDateString("en-US", {timeZone: "Europe/London"});
                const todayDateUK = nowUK.toLocaleDateString("en-US", {timeZone: "Europe/London"});

                if (eventDateUK === todayDateUK) {
                    // Still same day - show replay if link exists
                    if (replayLink && replayLink.trim() !== '') {
                        const alertBox = document.getElementById('live-class-alert');
                        document.getElementById('live-class-title').innerHTML = eventTitle;
                        document.getElementById('live-class-time').textContent = 'Replay available';
                        document.getElementById('live-class-link').innerHTML = 'Watch Replay <span class="material-icons-outlined text-lg">play_arrow</span>';
                        document.getElementById('live-class-link').href = replayLink;
                        alertBox.classList.add('animate-fade-in-up');
                        alertBox.style.display = 'block';
                    } else {
                        // No replay link, hide banner
                        document.getElementById('live-class-alert').style.display = 'none';
                    }
                } else {
                    // Next day - hide this event, next event will show instead
                    document.getElementById('live-class-alert').style.display = 'none';
                }
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
 *
 * Example:
 * <div data-require-groups="123,456" style="display: none;">
 *   Content only visible to users in group 123 OR 456
 * </div>
 */
function checkGroupAccess() {
    // Get user's group IDs from PHP
    const userGroups = <?php echo $user_groups_json; ?>;

    // Find all elements with data-require-groups attribute
    const conditionalElements = document.querySelectorAll('[data-require-groups]');

    conditionalElements.forEach(element => {
        // Get required group IDs from data attribute (comma-separated)
        const requiredGroups = element.getAttribute('data-require-groups')
            .split(',')
            .map(id => parseInt(id.trim()))
            .filter(id => !isNaN(id));

        // Check if user is in ANY of the required groups
        const hasAccess = requiredGroups.some(groupId => userGroups.includes(groupId));

        // Show element if user has access
        if (hasAccess) {
            element.style.display = 'block';
        }
    });
}

// Load on page load
loadCommunityFeed();
checkMondayUK();
checkLiveClass();
checkGroupAccess();
</script>

<?php wp_footer(); ?>
<?php get_footer(); ?>
