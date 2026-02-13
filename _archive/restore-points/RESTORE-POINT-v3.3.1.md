# RESTORE POINT - Version 3.3.1
**Date:** January 30, 2026  
**Status:** ✅ STABLE - Production Ready

## Version Summary
Version 3.3.1 introduces a comprehensive featured camps system with admin management interface, 6 frontend shortcodes, latest camps functionality, and a complete shortcodes guide documentation page.

## Key Features Added in v3.3.0 - v3.3.1

### 1. Featured Camps Database Schema
- **File:** `includes/migrations-featured-camps.php`
- **Database Columns Added:**
  - `is_featured` - Boolean flag for general featured camps
  - `is_best_day` - Boolean flag for best day camps
  - `is_best_overnight` - Boolean flag for best overnight camps
  - `is_best_girls` - Boolean flag for best girls camps
  - `is_best_boys` - Boolean flag for best boys camps
  - `featured_order` - Integer for drag-drop ordering
  - `day_order` - Integer for day camps ordering
  - `overnight_order` - Integer for overnight camps ordering
  - `girls_order` - Integer for girls camps ordering
  - `boys_order` - Integer for boys camps ordering
- **Migration:** Runs once, tracked with `creativedbs_campmgmt_featured_migrated` option

### 2. Featured Camps Admin Interface
- **File:** `includes/Admin/class-featured-camps.php`
- **Location:** Camp Management → 🌟 Featured Camps
- **Features:**
  - 5 tabbed interface (Featured, Day Camps, Overnight Camps, Girls Camps, Boys Camps)
  - Search modal with state filter dropdown
  - Drag-and-drop reordering with jQuery UI Sortable
  - Add/Remove camps functionality
  - AJAX auto-save on reorder
  - Camp type filtering by ID (Day=1, Overnight=3, Girls=20, Boys=19)
  
- **AJAX Endpoints:**
  - `ajax_search_camps()` - Search for camps to add
  - `ajax_update_featured_camps()` - Save reordered camps
  - `ajax_filter_camps_by_type_id()` - Filter by camp type

- **JavaScript:** `assets/featured-camps-admin.js`
  - Direct event handlers (not delegated due to WPS Hide Login plugin)
  - Search modal functionality
  - Type filtering buttons
  - Drag-drop save with automatic order calculation
  - Remove camp confirmation dialog

- **CSS:** `assets/featured-camps-admin.css`
  - Modal styling
  - Sortable list design
  - Tab navigation

### 3. Featured Camps Frontend Shortcodes
- **File:** `includes/Public/class-featured-camps-frontend.php`
- **Shortcodes:**
  1. `[featured_camps limit="100"]` - Display all featured camps
  2. `[best_day_camps limit="100"]` - Display best day camps
  3. `[best_overnight_camps limit="100"]` - Display best overnight camps
  4. `[best_girls_camps limit="100"]` - Display best girls camps
  5. `[best_boys_camps limit="100"]` - Display best boys camps
  6. `[latest_camps limit="3"]` - Display newest approved camps by created_at DESC

- **Rendering:**
  - Unified `render_camp_card()` method
  - Photo header (168px height)
  - Centered logo overlay (100px, z-index: 10)
  - Camp types, weeks, and activities enriched from junction tables
  - Dark overlay on header (rgba(0,0,0,0.6))

- **CSS:** `assets/featured-camps.css`
  - Grid layout with dynamic columns: 1-3 camps = exact columns, 4 = 4 cols, 5+ = 3 cols
  - `.featured-card-header` - 168px height, border-radius
  - `.featured-logo-circle` - 100px, centered with transform, z-index: 10
  - `.featured-header-overlay` - dark overlay, z-index: 1
  - 30px gap between cards

### 4. Latest Camps Functionality
- **Shortcode:** `[latest_camps limit="3"]`
- **Query:** `ORDER BY created_at DESC, id DESC`
- **Default:** Shows 3 most recently added camps
- **Use Case:** Homepage "New Camps" section

### 5. Shortcodes Guide Documentation
- **File:** `includes/Admin/class-shortcodes-guide.php`
- **Location:** Camp Management → 📋 Shortcodes
- **Features:**
  - 24 shortcodes documented across 4 categories:
    - Featured Camps (6 shortcodes)
    - Individual Camp Pages (14 shortcodes)
    - Forms & Search (3 shortcodes)
    - Lists (1 shortcode)
  - Click-to-copy functionality using navigator.clipboard API
  - Parameter descriptions with examples
  - Inline CSS styling
  - Visual feedback on copy (2-second green checkmark)

- **Examples Provided:**
  - `[featured_camps limit="6"]`
  - `[best_day_camps limit="8"]`
  - `[best_overnight_camps limit="10"]`
  - `[best_girls_camps limit="6"]`
  - `[best_boys_camps limit="6"]`
  - `[latest_camps limit="5"]`
  - `[camp_logo size="large" class="my-logo"]`
  - `[camp_accommodations layout="grid" columns="3"]`
  - `[camps_list columns="4"]`
  - `[camps_list state="NY" limit="12"]`

### 6. Password Reset URL Fixes (v3.2.1)
- **Files:** `includes/class-plugin.php`
- **Filters Added:**
  - `lostpassword_url` - Points to /password-reset/ page
  - `login_url` - Points to /login/ page
  - Priority: 99 to override WPS Hide Login plugin
- **Result:** Password reset button now works correctly

## Files Created in v3.3.0 - v3.3.1

### PHP Files
1. **includes/migrations-featured-camps.php**
   - Database migration for 10 new columns
   - One-time execution with option flag

2. **includes/Admin/class-featured-camps.php**
   - Admin interface with 5 tabs
   - AJAX handlers for search, save, filter
   - Drag-drop sortable lists
   - 370+ lines of code

3. **includes/Public/class-featured-camps-frontend.php**
   - 6 shortcode handlers
   - Unified rendering methods
   - Query builders for each category
   - Latest camps functionality

4. **includes/Admin/class-shortcodes-guide.php**
   - Documentation page
   - 24 shortcodes with descriptions
   - Click-to-copy JavaScript
   - Inline CSS styling

### JavaScript Files
1. **assets/featured-camps-admin.js**
   - Search modal logic
   - Type filter buttons
   - Drag-drop with jQuery UI Sortable
   - AJAX save functionality
   - Direct event handlers

### CSS Files
1. **assets/featured-camps-admin.css**
   - Modal overlay and content
   - Sortable list styling
   - Tab navigation
   - Button styles

2. **assets/featured-camps.css**
   - Card grid layout
   - Photo header styling (168px)
   - Logo overlay (100px, centered)
   - Dark overlay (0.6 opacity)
   - Responsive grid columns

## Files Modified in v3.3.0 - v3.3.1

1. **creativedbs-camp-mgmt.php**
   - Updated version to 3.3.0
   - Added requires for new class files
   - Instantiated Featured_Camps and Shortcodes_Guide classes
   - Added featured camps migration check

2. **includes/class-plugin.php**
   - Added password reset URL filters (v3.2.1)

## Technical Details

### Camp Type IDs (Important for Filtering)
- Day Camp: **ID 1**
- Overnight Camp: **ID 3**
- Boys Camp: **ID 19**
- Girls Camp: **ID 20**

These IDs are used in AJAX filtering instead of names for reliability.

### JavaScript Event Handler Pattern
Due to WPS Hide Login plugin interference, dynamically created buttons require **direct event handlers** attached immediately after creation, not delegated handlers.

**Working Pattern:**
```javascript
button.on('click', function() { ... });
```

**Not Working:**
```javascript
$(document).on('click', '.button-class', function() { ... });
```

### Grid Layout Logic
```php
$columns = ($count <= 3) ? $count : (($count === 4) ? 4 : 3);
```
- 1-3 camps: exact number of columns
- 4 camps: 4 columns
- 5+ camps: 3 columns max

### Z-Index Layering
- Card overlay: `z-index: 1`
- Logo circle: `z-index: 10`
- Card body: `z-index: 1`

## Known Issues

### Grid Layout for 5+ Camps
User reported issues with card widths not being consistent across rows when displaying 5+ camps. Multiple CSS approaches attempted (justify-items, justify-self, grid-auto-rows) but issue persists. Deprioritized as per user request.

## Database Schema Changes

### wp_camp_management Table
Added 10 new columns:
```sql
ALTER TABLE wp_camp_management ADD COLUMN is_featured TINYINT(1) DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN is_best_day TINYINT(1) DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN is_best_overnight TINYINT(1) DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN is_best_girls TINYINT(1) DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN is_best_boys TINYINT(1) DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN featured_order INT DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN day_order INT DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN overnight_order INT DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN girls_order INT DEFAULT 0;
ALTER TABLE wp_camp_management ADD COLUMN boys_order INT DEFAULT 0;
```

## Deployment Notes

1. Upload plugin zip file
2. Database migration runs automatically on first admin page load
3. New menu items appear under Camp Management
4. Shortcodes available immediately after activation
5. Featured camps initially empty - admin must add camps manually

## Next Steps / Future Enhancements

- Resolve grid layout issue for 5+ camps (if needed)
- Add bulk actions to featured camps admin
- Consider adding featured camp expiration dates
- Add analytics tracking for featured camps clicks

## Version Control

- **Previous Version:** 3.2.0
- **Current Version:** 3.3.1
- **Zip File:** `creativedbs-camp-mgmt-v3.3.0.zip`
- **WordPress Tested:** 6.4+
- **PHP Required:** 7.4+

---
**Restore Point Created:** January 30, 2026  
**All Files Backed Up:** ✅  
**Database State:** Documented  
**Status:** Production Ready
