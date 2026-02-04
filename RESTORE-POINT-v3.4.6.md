# RESTORE POINT v3.4.6
**Date:** February 4, 2026  
**Status:** ✅ STABLE - Single Camp Shortcode & Enhanced Featured Camp Cards

## Summary

Version 3.4.6 adds a new `[single_camp]` shortcode for displaying individual camp cards and enhances all featured camp cards with weeks and activities badges, matching the design of search result cards.

## Changes from v3.4.5

### 1. New Single Camp Shortcode
- Created `[single_camp]` shortcode to display one specific camp card
- Supports lookup by camp ID or slug
- Uses same beautiful card design as grid views
- Shows all camp details: logo, photo, location, camp types, weeks, activities, dates, prices
- Only displays approved camps

**Usage:**
```
[single_camp id="123"]
[single_camp slug="camp-laney"]
```

### 2. Enhanced Featured Camp Cards
- Added **Weeks badges** to all featured camp cards (light green background)
- Added **Activities badges** to all featured camp cards (gray background)
- Shows "+X" indicator when there are more than 4 activities
- Matches search card design for consistency

**Badge styling:**
- Camp Types: White background, green border
- Weeks: Light green background (#e8f5e9), green border
- Activities: Gray background (#e0e0e0), gray border

### 3. Updated Shortcodes Guide
- Added documentation for `[single_camp]` shortcode
- Included examples with both `id` and `slug` parameters
- Visible in admin under Camp Mgmt → Shortcodes Guide

## Files Modified

### PHP Files
1. **includes/Public/class-featured-camps-frontend.php**
   - Added: `render_single_camp()` method - handles single camp shortcode
   - Added: `single_camp` shortcode registration in constructor
   - Updated: `render_camp_card()` - added weeks and activities badge sections
   - Queries now load weeks and activities data for all camps

2. **includes/Admin/class-shortcodes-guide.php**
   - Added: Documentation for `[single_camp]` shortcode with examples
   - Shows both ID and slug usage patterns

### CSS Files
3. **assets/featured-camps.css**
   - Added: Weeks badge styling (light green, second meta row)
   - Added: Activities badge styling (gray, third meta row)
   - Added: Badge-specific styling using nth-of-type selectors
   - Updated: `.featured-badge-plus` styling for activity count indicator

### Plugin Core
4. **creativedbs-camp-mgmt.php**
   - Updated version to 3.4.6

## Shortcode Features

### [single_camp]
**Parameters:**
- `id` (optional) - Camp ID from database
- `slug` (optional) - Camp slug from URL
- Must specify either `id` OR `slug`

**Examples:**
```
[single_camp id="45"]
[single_camp slug="camp-laney"]
```

**Features:**
- Displays single camp in grid format
- Loads all taxonomy data (types, weeks, activities)
- Shows only approved camps
- Responsive design
- Returns error message if camp not found

### Enhanced Featured Camp Shortcodes
All existing shortcodes now include weeks and activities:
- `[featured_camps]`
- `[best_day_camps]`
- `[best_overnight_camps]`
- `[best_girls_camps]`
- `[best_boys_camps]`
- `[latest_camps]`

## Database Queries Added

### Single Camp Shortcode
```sql
-- Get camp by ID
SELECT id, camp_name, city, state, logo, photos, internal_link, rating, 
       opening_day, closing_day, minprice_2026, maxprice_2026
FROM wp_camp_management 
WHERE id = %d AND approved = 1 
LIMIT 1

-- Get camp by slug
SELECT ... FROM wp_camp_management 
WHERE slug = %s AND approved = 1 
LIMIT 1
```

### Taxonomy Queries (for all cards)
```sql
-- Get weeks
SELECT t.name FROM wp_camp_week_terms t
INNER JOIN wp_camp_management_weeks_map m ON t.id = m.week_id
WHERE m.camp_id = %d
ORDER BY t.sort_order ASC, t.name ASC

-- Get activities (first 4)
SELECT t.name FROM wp_camp_activity_terms t
INNER JOIN wp_camp_management_activities_map m ON t.id = m.activity_id
WHERE m.camp_id = %d
ORDER BY t.sort_order ASC, t.name ASC
LIMIT 4

-- Get total activity count
SELECT COUNT(*) FROM wp_camp_management_activities_map
WHERE camp_id = %d
```

## CSS Classes Added

### Badge Styling
- `.featured-badge` - Base badge styling (all types)
- `.featured-badge-plus` - Plus indicator for additional activities
- `.featured-meta-row:nth-of-type(1) .featured-badge` - Camp Types (white bg)
- `.featured-meta-row:nth-of-type(2) .featured-badge` - Weeks (light green bg)
- `.featured-meta-row:nth-of-type(3) .featured-badge` - Activities (gray bg)

## Testing Checklist

- [x] `[single_camp id="123"]` displays correct camp
- [x] `[single_camp slug="camp-name"]` displays correct camp
- [x] Invalid ID shows error message
- [x] Invalid slug shows error message
- [x] Missing parameters show error message
- [x] Weeks badges display on all featured camp shortcodes
- [x] Activities badges display on all featured camp shortcodes
- [x] "+X" indicator shows when more than 4 activities
- [x] Badge styling matches search cards
- [x] All shortcodes work after update
- [x] Responsive design maintained
- [x] Shortcodes guide updated

## Known Issues
None

## Database Changes
None - uses existing tables and relationships

## Backwards Compatibility
✅ Fully compatible with v3.4.5
✅ No breaking changes
✅ Existing shortcodes enhanced with new badges
✅ New shortcode is optional

## Performance Impact
Minimal - additional queries only run when:
1. Shortcodes are used on a page
2. Data is already indexed and cached by WordPress

## Security Notes
- Camp ID validated as integer
- Slug sanitized using WordPress `sanitize_title()`
- Only approved camps displayed
- All output properly escaped with `esc_html()`, `esc_url()`, `esc_attr()`

## Code Quality
### New Methods
- `render_single_camp()` - Handles single camp shortcode logic
- Follows existing coding patterns
- Proper error handling and validation
- Database queries use prepared statements

### CSS Enhancements
- Uses nth-of-type selectors for clean badge styling
- Maintains existing class structure
- Responsive and accessible

## How to Find Camp ID or Slug

### Camp ID
1. Go to WordPress Admin → Camp Management
2. View the camps list - ID shown in first column

### Camp Slug
1. View any camp page on frontend
2. URL format: `https://yoursite.com/camp-name/`
3. The slug is the last part: `camp-name`

Or in database:
- Table: `wp_camp_management`
- Column: `slug`

## Git Commands
```bash
git add .
git commit -m "v3.4.6: Single camp shortcode and enhanced featured cards with weeks/activities badges"
git tag -a v3.4.6 -m "Restore point: Single camp shortcode and enhanced badges"
git push origin main --tags
```

## Rollback Instructions
If issues arise, rollback to v3.4.5:
```bash
git checkout v3.4.5
```

Or restore from previous stable version:
- RESTORE-POINT-v3.4.5.md
- RESTORE-POINT-v3.3.3.md

## File Manifest
```
creativedbs-camp-mgmt-v3.4.6.zip (215KB)
├── creativedbs-camp-mgmt.php (main plugin file, version 3.4.6)
├── includes/
│   ├── Public/
│   │   └── class-featured-camps-frontend.php (single_camp shortcode, weeks/activities)
│   └── Admin/
│       └── class-shortcodes-guide.php (updated documentation)
└── assets/
    └── featured-camps.css (badge styling)
```

## Usage Examples

### Single Camp on Homepage
```html
<h2>Featured Camp of the Week</h2>
[single_camp id="42"]
```

### Single Camp in Sidebar Widget
```html
[single_camp slug="camp-sunshine"]
```

### Multiple Single Camps
```html
<h2>Our Top 3 Picks</h2>
[single_camp id="10"]
[single_camp id="25"]
[single_camp id="33"]
```

### Enhanced Grid Display
```html
<h2>Best Overnight Camps</h2>
[best_overnight_camps limit="6"]
```
Now shows: Camp Types, Weeks, Activities (with +X indicator)

## Developer Notes

### Extending the Single Camp Shortcode
To add custom fields to single camp display:
1. Modify SQL query in `render_single_camp()`
2. Update `render_camp_card()` to display new fields
3. Update CSS if needed in `featured-camps.css`

### Badge Customization
Badges are styled via CSS nth-of-type selectors:
- Change order: Reorder HTML in `render_camp_card()`
- Change colors: Update CSS in `featured-camps.css`
- Add more badge types: Add new meta-row sections

### Activity Limit
Currently shows first 4 activities + count indicator.
To change limit:
1. Update `LIMIT 4` in activities query
2. Update condition `$camp->activities_total > 4`

## Support Information

### Common Questions

**Q: Can I display multiple camps with single_camp?**
A: Use multiple shortcodes or use the grid shortcodes (e.g., `[featured_camps]`)

**Q: What if a camp has no weeks or activities?**
A: The badges simply won't display (handled by `if ( ! empty() )` checks)

**Q: Can I customize badge colors?**
A: Yes, edit `assets/featured-camps.css` - see CSS Classes Added section above

**Q: Does this work with any theme?**
A: Yes, the plugin is theme-independent and uses its own styling

### Troubleshooting

**Problem:** Single camp shows "Camp not found"
**Solution:** Verify camp ID/slug is correct and camp is approved

**Problem:** Weeks/Activities not showing
**Solution:** Ensure camp has weeks/activities assigned in dashboard

**Problem:** Badges look different than search cards
**Solution:** Hard refresh browser (Cmd+Shift+R) to load new CSS

**Problem:** "+5" showing but only shows 4 activities
**Solution:** This is correct - shows first 4 + count of remaining

## Notes for Future Development

### Potential Improvements
1. Add camp rating display to cards
2. Add "favorite" or "bookmark" functionality
3. Add social sharing buttons to single camp cards
4. Add print-friendly version
5. Add camp comparison feature
6. Add filtering to single camp selection (e.g., by state)
7. Cache taxonomy queries for better performance

### Code Optimization Opportunities
- Consider caching taxonomy data with transients
- Batch load taxonomy data for multiple camps
- Add lazy loading for camp images
- Add schema.org markup for SEO

---
**End of Restore Point v3.4.6**
**Status: STABLE & PRODUCTION READY**
**Tested: February 4, 2026**
