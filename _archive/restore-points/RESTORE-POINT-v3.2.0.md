# RESTORE POINT - Version 3.2.0
**Date:** January 27, 2026  
**Status:** ✅ STABLE - Production Ready

## Version Summary
Version 3.2.0 focuses on UX improvements for the camps list search functionality and camp activities display, plus mobile responsive enhancements.

## Key Features Added in v3.2.0

### 1. Search Criteria Display
- **File:** `includes/Public/class-camps-list.php`
- **Feature:** Active search filters are displayed above results in sage green (#4a6b5a)
- **Format:** "Search for: [criteria] | Results: X camps"
- **Criteria shown:**
  - Search query text
  - State (full name, not abbreviation)
  - Camp type name
  - Duration/weeks
  - Price range ($X - $Y or From $X or Up to $X)
  - Date range (formatted as "Mon D - Mon D, YYYY")
- **Styling:** Green color #4a6b5a, font-weight 600

### 2. Activities Show More/Less
- **File:** `includes/Public/class-camp-frontend.php`
- **Feature:** Camp activities ([camp_activities] shortcode) now shows only first 5 activities with Show More button
- **Behavior:**
  - Initially displays 5 activities
  - "Show More (X more)" button appears if >5 activities
  - Button expands to show all activities
  - "Show Less" button appears to collapse back to 5
  - Buttons styled with Roboto font, #497C5E green color
- **CSS:** `assets/camp-frontend.css`
  - `.activity-tag.activity-hidden` - hidden by default
  - `.activities-grid.show-all .activity-hidden` - shown when expanded
  - `.activities-show-more`, `.activities-show-less` - button styles with Roboto font

### 3. Empty State Styling
- **File:** `assets/camps-list.css`
- **Improvements:**
  - Reduced padding: 30px 40px (was 80px 20px)
  - Added light background: #f8f9fa
  - Added border and shadow for depth
  - Max-width: 100% (full width)
  - Margin: 20px auto

### 4. Scroll Position Maintenance
- **File:** `includes/Public/class-camps-list.php`
- **Feature:** JavaScript auto-scrolls to search form after filter submission
- **Behavior:**
  - Detects if any filters/search parameters are in URL
  - Scrolls to `.camps-search-bar` with 100px offset
  - Smooth scrolling animation
  - Only triggers when filters are active

### 5. Reset All Anchor
- **File:** `includes/Public/class-camps-list.php`
- **Fix:** Reset All button now includes `#camps-filter-form` anchor
- **Result:** Stays at search form instead of jumping to page top

### 6. Mobile Responsive Improvements
- **File:** `assets/camps-list.css`
- **Search Bar (mobile ≤480px):**
  - Search input: full width (100%)
  - Search button: 50% width
  - Reset All button: 50% width
  - Buttons displayed in row below search input
  - Uses flexbox order for proper layout
  
- **Opening Dates (mobile ≤576px):**
  - Two date fields side-by-side in 50/50 grid
  - Each field max-width: 100% (not 120px)
  - Proper responsive spacing

## Files Modified in v3.2.0

### PHP Files
1. **includes/Public/class-camps-list.php**
   - Added search criteria building logic (lines ~387-445)
   - Added criteria display HTML with conditional rendering
   - Added scroll-to-form JavaScript for UX
   - Updated Reset All link with anchor

2. **includes/Public/class-camp-frontend.php**
   - Modified `render_activities()` method
   - Added show more/less logic
   - Limited initial display to 5 activities
   - Added dynamic count in button text

### CSS Files
1. **assets/camps-list.css**
   - Updated `.camps-empty` styling
   - Added mobile responsive rules (@media max-width: 480px, 576px)
   - Search form mobile layout with flexbox order
   - Date fields grid layout for mobile
   - `.camps-filter-input-small` max-width override

2. **assets/camp-frontend.css**
   - Added `.activity-tag.activity-hidden` rules
   - Added `.activities-grid.show-all` rules
   - Added `.activities-show-more` and `.activities-show-less` styles
   - Roboto font family, 14px size, 600 weight, 0px letter-spacing
   - Hover effects with transform and shadow

## Database Schema
No database changes in v3.2.0.

## Version History Context
- **v2.9.0:** Base search functionality
- **v3.0.0:** Admin enhancements (modules, approval, date edited)
- **v3.1.0:** Fixed last_edited field
- **v3.2.0:** Search UX improvements, activities show more/less, mobile responsive

## Critical Code Snippets

### Search Criteria Display Logic
```php
// Build search criteria display
$criteria = array();

if ( ! empty( $search_query ) ) {
    $criteria[] = $search_query;
}

if ( ! empty( $filter_state ) ) {
    $state_map = array( /* full state mapping */ );
    $state_name = isset( $state_map[ $filter_state ] ) ? $state_map[ $filter_state ] : $filter_state;
    $criteria[] = $state_name;
}

// ... type, duration, price, dates logic ...

$has_criteria = ! empty( $criteria );
$criteria_text = $has_criteria ? implode( ', ', $criteria ) : '';
```

### Activities Show More/Less
```php
$total_activities = count( $activities );
$show_more = $total_activities > 5;

// In HTML loop:
<span class="activity-tag<?php echo ( $index >= 5 ) ? ' activity-hidden' : ''; ?>">
    <?php echo esc_html( $activity ); ?>
</span>

// Buttons:
<button class="activities-show-more" onclick="...">Show More (X more)</button>
<button class="activities-show-less" style="display: none;" onclick="...">Show Less</button>
```

### Scroll to Form Script
```javascript
var hasFilters = urlParams.has('camp_search') || urlParams.has('filter_state') || ...;

if (hasFilters) {
    setTimeout(function() {
        var searchForm = document.querySelector('.camps-search-bar');
        if (searchForm) {
            var offset = 100;
            var elementPosition = searchForm.getBoundingClientRect().top;
            var offsetPosition = elementPosition + window.pageYOffset - offset;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    }, 100);
}
```

## Mobile Responsive Breakpoints
- **≤480px:** Search full width, buttons row, general mobile optimizations
- **≤576px:** Date fields side-by-side, single column filters
- **≤992px:** 2-column filter grid
- **≤1200px:** 3-column filter grid

## Testing Checklist
- ✅ Search criteria displays correctly with all filter types
- ✅ State names show full name (not abbreviation)
- ✅ Show More button appears when >5 activities
- ✅ Show Less button works and collapses activities
- ✅ Empty state styled with background and border
- ✅ Page scrolls to search form after filter submission
- ✅ Reset All stays at search form (doesn't jump to top)
- ✅ Mobile: Search input full width
- ✅ Mobile: Search and Reset All buttons in row (50/50)
- ✅ Mobile: Date fields side-by-side (50/50)
- ✅ Desktop layout unaffected

## Known Issues
None reported.

## Deployment Notes
1. Upload plugin files to WordPress
2. No database migration required
3. Clear browser cache to see CSS changes
4. Test on mobile devices for responsive layout
5. Verify search criteria display with various filter combinations

## Rollback Instructions
If issues occur, restore from:
- **Previous stable version:** v3.1.0
- **Restore point file:** RESTORE-POINT-v3.1.0.md (if exists) or RESTORE-POINT-v3.0.0.md
- **Zip file:** creativedbs-camp-mgmt-v3.1.0.zip

## Development Notes
- Search criteria uses inline styles for color (#4a6b5a)
- Activities buttons use inline onclick handlers for simplicity
- Mobile breakpoint at 480px for smallest devices
- Date fields use :has() selector (modern browsers only)
- Flexbox order property used for mobile layout reordering

## Plugin Info
- **Plugin Name:** CreativeDBS Camp Management
- **Version:** 3.2.0
- **WordPress Compatibility:** 5.8+
- **PHP Version:** 7.4+
- **Main File:** creativedbs-camp-mgmt.php
- **Package:** creativedbs-camp-mgmt-v3.2.0.zip (812KB)

---
*This restore point created: January 27, 2026*
*All systems operational and tested*
