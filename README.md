# Dashboard Conditional Display Logic

Simple guide to understand what displays when on the NTO dashboard.

## 🔐 Authentication

**Function**: Lines 8-11 in [template-dashboard.php](template-dashboard.php)
```php
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}
```
**Shows**: Entire dashboard only for logged-in users
**Hides**: Redirects to login if not authenticated

---

## 👋 Continue Learning Button

**Function**: Lines 116-158 (Desktop) & 200-218 (Mobile) in [template-dashboard.php](template-dashboard.php)
```php
$user_courses = learndash_user_get_enrolled_courses($user_id);
foreach ($user_courses as $course_id) {
    $course_status = learndash_course_status($course_id, $user_id);
    if ($course_status === 'in_progress' || $course_status === 'In Progress') {
        $last_course_id = $course_id;
        break;
    }
}
```
**Shows**: "Continue Learning" button with course thumbnail
**When**: User has at least one in-progress course (not completed, not "not started")
**Where**: Desktop = top right header | Mobile = card below alerts

---

## 🔴 Live Class Alert

**Function**: `checkLiveClass()` - Lines 462-550 in [template-dashboard.php](template-dashboard.php)
**API**: Fetches from `/wp-json/tribe/events/v1/events` and `/wp-json/wp/v2/tribe_events/{id}`

### Three States:

#### 1. BEFORE Event
**Shows**: "Starting at [TIME]" + "Join Zoom" button
**When**: Event scheduled for today & current time is before event start
**Links to**: `zoom_link` from custom field

#### 2. DURING Event
**Shows**: "LIVE NOW" pulsing badge + "Join Now" button
**When**: Current time is between event start and end time
**Links to**: `zoom_link` from custom field

#### 3. AFTER Event
**Shows**: "Replay available" + "Watch Replay" button
**When**: Event ended AND `replay_link` custom field is not empty
**Links to**: `replay_link` from custom field
**Hides**: If no replay link exists (empty string)

**Element**: `#live-class-alert` - Line 172

---

## 📝 Monday Check-in

**Function**: `checkMondayUK()` - Lines 447-459 in [template-dashboard.php](template-dashboard.php)
```javascript
const ukTime = new Date().toLocaleString("en-US", {timeZone: "Europe/London"});
const ukDate = new Date(ukTime);
const dayOfWeek = ukDate.getDay(); // 1 = Monday
```
**Shows**: Monday check-in card
**When**: Current day is Monday in UK timezone (Europe/London)
**Element**: `#monday-checkin` - Line 186

---

## 🎓 Educator Elevation Section

**Function**: `checkGroupAccess()` - Lines 565-587 in [template-dashboard.php](template-dashboard.php)
```javascript
const userGroups = <?php echo $user_groups_json; ?>;
const requiredGroups = [272088]; // Educator Elevation group ID
const hasAccess = requiredGroups.some(groupId => userGroups.includes(groupId));
```
**Shows**: Educator Elevation section with 3 quick links
**When**: User is enrolled in LearnDash group ID `272088`
**Element**: `#educator-elevation` - Line 228
**Attribute**: `data-require-groups="272088"`

---

## 📢 Community Feed

**Function**: `loadCommunityFeed()` - Lines 355-411 in [template-dashboard.php](template-dashboard.php)
**API**: `/wp-json/buddyboss/v1/activity?per_page=20`

**Shows**: Latest 5 community posts
**When**: Always visible (no conditions)
**Filter**: Only shows `activity_update` type posts (filters out profile changes, new members, etc.)
**Element**: `#community-feed` - Line 338

---

## 🔧 Reusable Group Access System

**Function**: `checkGroupAccess()` - Lines 565-587

### How to Use:
Add these attributes to ANY element to control visibility by LearnDash group:

```html
<!-- Single group -->
<div data-require-groups="123" style="display: none;">
  Shows only to users in group 123
</div>

<!-- Multiple groups (OR logic) -->
<div data-require-groups="123,456,789" style="display: none;">
  Shows to users in group 123 OR 456 OR 789
</div>
```

**Logic**: Uses OR (user needs to be in ANY of the listed groups)
**Default**: Must add `style="display: none;"` to hide by default

---

## 🎨 Always Visible Elements

These show for all logged-in users with no conditions:

- Welcome banner with first name
- "Your Dashboard" main navigation grid (6 cards)
- "New Here? Start Here" quick start card
- Community feed sidebar

---

## 📱 Responsive Behavior

### Mobile (< 768px):
- Header logo hidden
- Continue Learning shows as full-width card (not in header)
- Navigation grid adjusts to 2 columns

### Desktop (≥ 768px):
- Continue Learning in top right header
- Left column (2/3 width) + Right sidebar (1/3 width)
- Navigation grid shows 3 columns

---

## 🔗 Custom Fields Used

### Events Calendar Custom Fields
Defined in [functions.php](functions.php) - Lines 1-73

#### `_zoom_link`
- **Type**: URL field (optional)
- **Used for**: Live class Zoom meeting link
- **Exposed to REST API**: `zoom_link` field
- **Saved by**: `save_zoom_replay_fields()` function

#### `_replay_link`
- **Type**: URL field (optional)
- **Used for**: Post-event replay/recording link
- **Exposed to REST API**: `replay_link` field
- **Saved by**: `save_zoom_replay_fields()` function

---

## 🕐 Timezone Logic

All time-based conditions use **UK timezone (Europe/London)**:
- Monday check-in detection
- Live class event timing (before/during/after)
# nto-dashboard
