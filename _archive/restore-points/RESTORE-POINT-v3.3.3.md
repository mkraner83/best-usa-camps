# RESTORE POINT v3.3.3 - Daily Batched Email Notifications + Mobile Responsive Grid

**Date:** January 30, 2026
**Plugin Version:** 3.3.3
**Status:** ✅ Stable - Daily Notification System + Mobile Grid Fixed

## What's New in v3.3.3

### Daily Batched Email Notifications
- **Queue System**: Profile update notifications are now queued instead of sent immediately
- **Daily Summary**: One email per camp at 8:00 PM CET with all updates from that day
- **Update Details**: Each notification shows timestamp and what was changed (photos, logo, profile)
- **Database Table**: New `wp_camp_notification_queue` table stores pending notifications

### Mobile Responsive Grid Fix
- **Featured Camps**: Display one card per row on mobile devices (≤768px)
- **Camps List**: Display one card per row on mobile devices (≤768px)
- **Removed Inline Styles**: Eliminated `style="grid-template-columns..."` from PHP output
- **CSS-Based Layout**: All responsive behavior now controlled via CSS media queries

## Changes Since v3.3.2

### Database Changes
**New Table: `wp_camp_notification_queue`**
- `id` (int, auto_increment, primary key)
- `camp_id` (int, indexed) - Camp identifier
- `camp_name` (varchar 255) - Camp name for email display
- `update_type` (varchar 50) - Type of update ('profile_update')
- `update_time` (datetime) - When the update occurred
- `photos_uploaded` (tinyint 1) - Whether photos were uploaded
- `logo_uploaded` (tinyint 1) - Whether logo was updated

### File Changes

#### Modified Files
1. **creativedbs-camp-mgmt.php**
   - Version updated to 3.3.3
   - Added `migrations-daily-notifications.php` to required files

2. **includes/Public/class-camp-dashboard.php** (lines 75-237, 3360-3385)
   - Modified `send_admin_notification_camp_updated()` to queue notifications instead of sending immediately
   - Added `schedule_daily_notification_cron()` - Sets up daily cron job at 8 PM CET
   - Added `send_daily_notification_batch()` - Processes queue and sends batched emails
   - Added `get_daily_batch_email_template()` - HTML email template for daily summaries
   - Registered cron hooks in constructor

3. **includes/Public/class-featured-camps-frontend.php** (lines 156, 231)
   - Removed inline `style="grid-template-columns: repeat(X, 1fr);"` from grid containers
   - Grid layout now controlled entirely by CSS

4. **assets/featured-camps.css**
   - Added explicit grid rules based on `data-columns` attribute (1-4 columns)
   - Updated `@media (max-width: 768px)` to force 1 column layout with `!important`
   - Added explicit rules for `data-columns="2"`, `"3"`, and `"4"` to collapse to 1 column on mobile
   - Removed redundant 480px breakpoint

5. **assets/camps-list.css**
   - Updated `@media (max-width: 768px)` to force 1 column layout with `!important`
   - Changed from `repeat(2, 1fr)` to `1fr !important` for mobile
   - Ensures all grid classes (`.camps-grid-2`, `.camps-grid-3`, `.camps-grid-4`) show single column on mobile

#### New Files
1. **includes/migrations-daily-notifications.php**
   - Creates `wp_camp_notification_queue` table
   - Tracked with `creativedbs_campmgmt_daily_notifications_migrated` option

## Responsive Grid Implementation

### The Problem
- Grid containers had inline styles: `style="grid-template-columns: repeat(3, 1fr);"`
- Inline styles override CSS media queries
- Mobile devices showed 2 columns (cramped layout) instead of 1
- Original breakpoint at 480px was too small for modern mobile devices

### The Solution
1. **Removed Inline Styles** (PHP files)
   - `class-featured-camps-frontend.php`: Removed inline grid styles from both shortcode render functions
   - Grid containers now only have `data-columns` attribute for reference

2. **CSS-Based Grid** (CSS files)
   - Desktop: Use `data-columns` attribute to set 1-4 column layouts
   - Tablet (901-1200px): Max 3 columns for 4-column grids
   - Mobile (≤768px): Force 1 column with `!important` flag
   - All grid classes collapse to single column on mobile

### Breakpoint Strategy
```css
/* Desktop - based on data-columns */
.featured-camps-grid[data-columns="1"] { grid-template-columns: 1fr; }
.featured-camps-grid[data-columns="2"] { grid-template-columns: repeat(2, 1fr); }
.featured-camps-grid[data-columns="3"] { grid-template-columns: repeat(3, 1fr); }
.featured-camps-grid[data-columns="4"] { grid-template-columns: repeat(4, 1fr); }

/* Tablet */
@media (max-width: 1200px) {
    .featured-camps-grid[data-columns="4"] { grid-template-columns: repeat(3, 1fr) !important; }
}

@media (max-width: 900px) {
    .featured-camps-grid[data-columns="3"],
    .featured-camps-grid[data-columns="4"] { grid-template-columns: repeat(2, 1fr) !important; }
}

/* Mobile - CRITICAL FIX */
@media (max-width: 768px) {
    .featured-camps-grid { grid-template-columns: 1fr !important; }
    .featured-camps-grid[data-columns="2"],
    .featured-camps-grid[data-columns="3"],
    .featured-camps-grid[data-columns="4"] { grid-template-columns: 1fr !important; }
}
```

### Why 768px?
- Most mobile devices (iPhone, Android) have logical widths of 360-428px in portrait
- Tablets start around 768-820px
- 768px is industry standard mobile/tablet breakpoint
- Ensures all mobile phones get single-column layout
- Previous 480px breakpoint missed most modern phones

### 1. Update Queueing
When a camp director updates their profile:
```php
// Old behavior: Immediate email
wp_mail( $admin_email, $subject, $message );

// New behavior: Queue for daily batch
$wpdb->insert( 'wp_camp_notification_queue', [
    'camp_id' => $camp_id,
    'camp_name' => $camp_name,
    'update_time' => current_time('mysql'),
    'photos_uploaded' => 1,
    'logo_uploaded' => 0
]);
```

### 2. Cron Scheduling
- **Time**: 8:00 PM CET (Europe/Paris timezone)
- **Frequency**: Daily
- **Hook**: `camp_send_daily_notifications`
- **Auto-adjusts**: Handles daylight saving time changes

### 3. Batch Processing
At 8:00 PM CET, the system:
1. Retrieves all notifications from that day
2. Groups by camp_id
3. Sends one email per camp with:
   - Camp name
   - Total update count
   - Chronological list of updates with timestamps
   - Link to view camp profile
4. Clears sent notifications from queue

### 4. Email Format
**Subject:** `Daily Update Summary: [Camp Name] (X updates)`

**Content:**
- Header with "📊 Daily Update Summary"
- Camp name
- Update count
- List of updates with:
  - Time (e.g., "3:45 PM")
  - Changes (photos uploaded, logo updated, profile updated)
  - Border-separated entries
- "View Camp Profile" button
- Best USA Summer Camps footer

## Database Migration

The migration runs automatically on plugin activation or when the migration file is loaded.

**Migration code:**
```php
if ( ! get_option( 'creativedbs_campmgmt_daily_notifications_migrated' ) ) {
    // Create wp_camp_notification_queue table
    $wpdb->query( $create_queue_table_sql );
    update_option( 'creativedbs_campmgmt_daily_notifications_migrated', '1' );
}
```

## Cron Job Details

### Registration
```php
public function schedule_daily_notification_cron() {
    if ( ! wp_next_scheduled( 'camp_send_daily_notifications' ) ) {
        $timezone = new \DateTimeZone( 'Europe/Paris' );
        $target = new \DateTime( 'today 20:00', $timezone );
        
        if ( $now > $target ) {
            $target->modify( '+1 day' );
        }
        
        wp_schedule_event( $target->getTimestamp(), 'daily', 'camp_send_daily_notifications' );
    }
}
```

### Execution
```php
public function send_daily_notification_batch() {
    // 1. Get today's notifications
    $notifications = $wpdb->get_results("
        SELECT * FROM {$table_name} 
        WHERE DATE(update_time) = CURDATE()
        ORDER BY camp_id, update_time ASC
    ");
    
    // 2. Group by camp
    $grouped_by_camp = [];
    foreach ( $notifications as $notification ) {
        $grouped_by_camp[ $notification->camp_id ][] = $notification;
    }
    
    // 3. Send one email per camp
    foreach ( $grouped_by_camp as $camp_id => $camp_notifications ) {
        wp_mail( $admin_email, $subject, $message, $headers );
    }
    
    // 4. Clear sent notifications
    $wpdb->query("DELETE FROM {$table_name} WHERE DATE(update_time) = CURDATE()");
}
```

## Testing the System

### 1. Verify Migration
```sql
SHOW TABLES LIKE '%camp_notification_queue%';
DESCRIBE wp_camp_notification_queue;
SELECT * FROM wp_options WHERE option_name = 'creativedbs_campmgmt_daily_notifications_migrated';
```

### 2. Test Queue Insertion
1. Log in as camp director
2. Edit profile (change any field)
3. Save changes
4. Check queue: `SELECT * FROM wp_camp_notification_queue;`

### 3. Verify Cron Schedule
```php
wp_next_scheduled( 'camp_send_daily_notifications' );
// Should return timestamp for today at 8 PM CET
```

### 4. Manual Cron Trigger (Testing)
```php
do_action( 'camp_send_daily_notifications' );
```

### 5. Test Email Batching
1. Update multiple camps throughout the day
2. Verify each update adds to queue
3. At 8 PM CET, check admin email inbox
4. Should receive one email per camp with all updates
5. Verify queue is cleared after sending

## Edge Cases Handled

1. **No updates today**: Function returns early, no emails sent
2. **Multiple updates same camp**: All grouped in single email with timestamps
3. **Timezone changes**: Uses Europe/Paris timezone for CET/CEST
4. **Cron already scheduled**: Checks `wp_next_scheduled()` before registering
5. **Same-day updates**: All updates from CURDATE() grouped and cleared together

## Benefits vs. Immediate Emails

| Feature | Immediate (v3.3.2) | Batched (v3.3.3) |
|---------|-------------------|------------------|
| Emails per update | 1 | 0 (queued) |
| Daily email volume | High (1 per update) | Low (1 per camp max) |
| Update visibility | Instant | End of day |
| Email clutter | High | Low |
| Chronological context | No | Yes (all updates listed) |
| Admin efficiency | Low | High (review all at once) |

## Rollback Instructions

If you need to revert to immediate notifications:

### 1. Restore v3.3.2 Files
```bash
# Replace modified files with v3.3.2 versions
cp backup/class-camp-dashboard.php includes/Public/
cp backup/creativedbs-camp-mgmt.php ./
```

### 2. Remove Cron Job
```php
wp_clear_scheduled_hook( 'camp_send_daily_notifications' );
```

### 3. Optional: Drop Queue Table
```sql
DROP TABLE IF EXISTS wp_camp_notification_queue;
DELETE FROM wp_options WHERE option_name = 'creativedbs_campmgmt_daily_notifications_migrated';
```

## File Reference

### Core Files (Modified)
- `creativedbs-camp-mgmt.php` (version, migration include)
- `includes/Public/class-camp-dashboard.php` (queue system, cron, batch sender)
- `includes/Public/class-featured-camps-frontend.php` (removed inline styles)
- `assets/featured-camps.css` (responsive grid, mobile breakpoints)
- `assets/camps-list.css` (responsive grid, mobile breakpoints)

### New Files
- `includes/migrations-daily-notifications.php` (queue table creation)
- `RESTORE-POINT-v3.3.3.md` (this file)

### Related Files (Unchanged)
- `includes/Public/class-camp-signup-form.php` (still has social/video fields)
- `assets/camp-signup-form.js` (still has dynamic fields)
- `assets/camp-signup-form.css` (still has popup styling)
- All other v3.3.2 files remain unchanged

## Known Limitations

1. **Time Precision**: Cron runs at approximately 8 PM CET (may be ±15 minutes depending on WordPress cron)
2. **Timezone**: Fixed to CET - doesn't adapt to user's timezone
3. **Email Format**: HTML only (no plain text alternative)
4. **Queue Retention**: Notifications cleared after sending (no history beyond that day)
5. **Manual Testing**: WordPress cron requires site traffic to trigger

## Future Enhancements (Ideas)

- Admin setting to customize daily send time
- Timezone selection per admin
- Weekly digest option
- Queue history retention
- Plain text email alternative
- Manual "Send Now" button for testing

## Version History

- **v3.3.3** (Jan 30, 2026) - Daily batched email notifications + mobile responsive grid fix
- **v3.3.2** (Jan 19, 2026) - Social media links, video URL, dashboard fields
- **v3.3.1** (Previous) - Foundation for current features

---

**STABLE BUILD - READY FOR PRODUCTION**

This version successfully:
1. Batches all camp profile update notifications into daily summary emails sent at 8:00 PM CET
2. Displays all camp cards (featured and lists) in single-column layout on mobile devices (≤768px)
3. Removes inline styles to allow CSS media queries to work properly
