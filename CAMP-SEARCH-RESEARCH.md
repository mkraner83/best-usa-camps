# [camp_search] Shortcode - Complete Research & Documentation

**Date:** January 19, 2026  
**Version:** v2.9.0 (Implemented)  
**Status:** ✅ Fully Functional  

## Overview

The `[camp_search]` shortcode provides a **complete camp discovery system** with live AJAX filtering, sorting, and infinite scroll pagination. It's designed for end users to search and filter available camps on the front-end.

---

## 1. Shortcode Registration & Location

**File:** `/includes/Public/class-camp-frontend.php`  
**Class:** `Camp_Frontend`  
**Namespace:** `CreativeDBS\CampMgmt\PublicArea`

### Registration
```php
add_shortcode( 'camp_search', [ $this, 'render_search' ] );
```

### AJAX Handlers (no-priv for logged-out users)
```php
add_action( 'wp_ajax_camp_search', [ $this, 'ajax_camp_search' ] );
add_action( 'wp_ajax_nopriv_camp_search', [ $this, 'ajax_camp_search' ] );
```

---

## 2. Shortcode Attributes

### Syntax
```
[camp_search]
[camp_search results_per_page="24"]
[camp_search class="custom-class"]
[camp_search results_per_page="20" class="my-search"]
```

### Available Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `results_per_page` | integer | 12 | Number of camps per page load |
| `class` | string | "" | Custom CSS class for wrapper |

---

## 3. Front-End UI Components

### Layout Structure
```
┌─────────────────────────────────────────────┐
│          Search Bar (Full Width)             │
│  🔍 [Search Input] [Search] [Clear]         │
└─────────────────────────────────────────────┘
┌──────────────────┬──────────────────────────┐
│                  │                          │
│   FILTERS        │   RESULTS AREA           │
│   (Sidebar)      │   ┌──────────────────┐  │
│                  │   │ Showing X camps   │  │
│   ☐ State        │   │ Sort: [Dropdown] │  │
│   ☐ Start Date   │   │                  │  │
│   ☐ End Date     │   │ [Camp Card]      │  │
│   ☐ Price Range  │   │ [Camp Card]      │  │
│   ☐ Camp Types   │   │ [Camp Card]      │  │
│   ☐ Weeks        │   │ [Camp Card]      │  │
│   ☐ Activities   │   │ [Load More]      │  │
│                  │   └──────────────────┘  │
└──────────────────┴──────────────────────────┘
```

### 3.1 Search Bar
- **Location:** Full-width at top
- **Icon:** FontAwesome search icon
- **Input:** Placeholder text for guidance
- **Buttons:** 
  - "Search" button (icon + text)
  - "Clear" button (clears filters & search)

### 3.2 Filters Sidebar (Left - 33% width)

#### State Filter
- Type: Dropdown `<select>`
- Default option: "All States"
- Options: Dynamically populated from database
- Sorted: Alphabetically A-Z

#### Date Filters
- **Start Date:** HTML5 date picker (`<input type="date">`)
- **End Date:** HTML5 date picker
- Label: "Start Date (From)" / "End Date (To)"
- Purpose: Filter by camp operational dates

#### Price Range Filter
- **Type:** Dual range sliders
- **Display:** "$X - $Y" format with live updates
- **Min/Max:** Calculated from session prices
- **Labels:** 
  - `#min-price-display` (updates as user drags)
  - `#max-price-display` (updates as user drags)

#### Camp Types Filter
- **Type:** Checkbox group
- **Icon:** Campground icon
- **Data:** Unique values from `camp_types` field
- **Behavior:** Multiple selection allowed

#### Weeks Filter
- **Type:** Checkbox group
- **Icon:** Calendar week icon
- **Data:** Unique values from `weeks` field
- **Behavior:** Multiple selection allowed

#### Activities Filter
- **Type:** Checkbox group (scrollable)
- **Icon:** Running/activities icon
- **Data:** Unique values from `activities` field
- **Behavior:** Multiple selection allowed
- **Special:** Has `.scrollable` class for overflow

#### Mobile Behavior
- **< 768px:** Collapses with "Toggle Filters" button
- **< 480px:** Becomes full-width overlay

### 3.3 Results Area (Right - 66% width)

#### Results Header
- **Count:** "Showing X camps" (updates dynamically)
- **Sort Dropdown:** 7 sort options (see below)

#### Loading State
- Shows spinner icon with "Loading camps..."
- Displayed during AJAX requests

#### Results Grid
- **Layout:** 2-column grid on desktop
- **Responsive:** 
  - Tablet: 2 columns
  - Mobile: 1 column
- **Grid ID:** `#camp-results-grid`

#### Camp Cards (Result Items)
Each card displays:
```
┌─────────────────┐
│   80x80 Logo    │
│   Camp Name     │
│  City, State    │
│  ⭐ ⭐ ⭐ ⭐ ⭐  │
│  Type Tags      │
│  Price Range    │
│ [View Details]  │
└─────────────────┘
```

- **Logo:** 80x80px thumbnail, or placeholder
- **Name:** Camp name (clickable → detail page)
- **Location:** City, State with map icon
- **Rating:** Star circles (filled/empty)
- **Types:** Up to 3 tag badges
- **Price:** "From $X" or "$X - $Y"
- **Button:** Links to camp detail page

#### No Results Message
- Shows if search returns 0 camps
- Icon: Search icon
- Message: "No camps found"
- Suggestion: "Try adjusting your filters..."

#### Load More Button
- **Style:** Button with plus icon
- **Text:** "Load More Camps"
- **Show:** Only when results > per_page
- **Behavior:** Appends next page to grid

---

## 4. Search & Filter Logic

### 4.1 Auto-Apply Timing

| Element | Delay | Behavior |
|---------|-------|----------|
| State dropdown | Instant | Applies immediately on change |
| Date pickers | Instant | Applies immediately on change |
| Price sliders | 500ms debounce | Debounced while dragging |
| Checkboxes | 300ms debounce | Debounce for rapid multi-select |
| Search input | 300ms debounce | Debounce for typing |

### 4.2 Search Scope (Text Search)

Full-text search across:
- `camp_name` - Camp name
- `city` - City
- `state` - State
- `description` - Camp description
- `activities` - Activities offered
- `camp_types` - Camp type tags
- `weeks` - Duration/weeks
- `additional_info` - Additional info field

### 4.3 Filter Combinations

All filters combine with **AND** logic:
```
(state = X) AND (start_date >= Y) AND (price BETWEEN Z AND W) AND ...
```

**Multi-select within same filter uses OR logic:**
```
(camp_types LIKE 'Day Camp' OR camp_types LIKE 'Overnight Camp')
```

### 4.4 Sort Options (7 choices)

| Value | Label | Order |
|-------|-------|-------|
| `random` | Random | Random order (default) |
| `name_asc` | Name (A-Z) | Alphabetical ascending |
| `name_desc` | Name (Z-A) | Alphabetical descending |
| `rating_desc` | Highest Rated | Rating descending |
| `rating_asc` | Lowest Rated | Rating ascending |
| `price_asc` | Lowest Price | Min session price ascending |
| `price_desc` | Highest Price | Max session price descending |

---

## 5. AJAX Endpoint

### Endpoint
- **Action:** `wp_ajax_camp_search` (logged-in)
- **Action:** `wp_ajax_nopriv_camp_search` (public)
- **URL:** `admin-ajax.php?action=camp_search`
- **Method:** POST

### Request Parameters
```javascript
{
    action: 'camp_search',
    search: 'keyword',           // Text search
    state: 'CA',                 // State filter
    start_date: '2026-06-01',    // Start date
    end_date: '2026-08-31',      // End date
    min_price: 500,              // Minimum price
    max_price: 2000,             // Maximum price
    camp_types: ['Day Camp', 'Overnight'], // Array
    weeks: ['Week 1', 'Week 2'],  // Array
    activities: ['Swimming'],     // Array
    sort_by: 'random',           // Sort option
    page: 1,                     // Page number
    per_page: 12                 // Results per page
}
```

### Response
```json
{
    "success": true,
    "data": {
        "camps": [
            {
                "id": 1,
                "name": "Camp Name",
                "city": "Boston",
                "state": "MA",
                "logo": "https://...",
                "rating": 4.5,
                "camp_types": "Day Camp",
                "min_price": 500,
                "max_price": 2000,
                "url": "https://...camp-page/"
            }
        ],
        "total": 45,           // Total matching camps
        "page": 1,             // Current page
        "has_more": true       // More pages available
    }
}
```

---

## 6. Database Schema Used

### Main Table
- **Table:** `wp_camp_management`
- **Fields Searched:** camp_name, city, state, description, activities, camp_types, weeks, additional_info
- **Fields Filtered:** state, camp_types, weeks, activities, start_date, end_date

### Sessions Table (for pricing)
- **Table:** `wp_camp_sessions` (via `DB::table_sessions()`)
- **Fields:** price, camp_id
- **Join Type:** LEFT JOIN (for price filtering/sorting)

### Query Example
```sql
SELECT DISTINCT c.* 
FROM wp_camp_management c 
LEFT JOIN wp_camp_sessions s ON c.id = s.camp_id 
WHERE c.state = 'CA' 
  AND (s.price >= 500 AND s.price <= 2000)
  AND (c.camp_types LIKE '%Day Camp%' OR c.camp_types LIKE '%Overnight%')
GROUP BY c.id 
ORDER BY MIN(s.price) ASC 
LIMIT 12 OFFSET 0
```

---

## 7. Assets & Dependencies

### CSS File
- **Path:** `assets/camp-search.css`
- **Size:** ~648 lines
- **Loads:** Only on pages with `[camp_search]` shortcode
- **Design System:** Matches Abel font, Lato, existing colors

### JavaScript File
- **Path:** `assets/camp-search.js`
- **Dependency:** jQuery (enqueued automatically)
- **Loads:** Only on pages with `[camp_search]` shortcode
- **Functionality:**
  - AJAX request handling
  - Debouncing
  - Event listeners (filters, search, sort)
  - DOM manipulation (append results, update grid)

### FontAwesome
- **Version:** 6.5.1
- **CDN:** CloudFlare CDN
- **Integrity Hash:** Included for security
- **Fallback:** Double-loaded for reliability
- **Icons Used:** 
  - Search, filter, map-marker, calendar, dollar-sign, campground, running, loading spinner

---

## 8. Conditional Asset Loading

### When to Load camp-search CSS/JS

Asset loading is **smart and conditional:**

```php
// Only loads if page has either:
// 1. camp_id custom field (camp detail page), OR
// 2. [camp_search] shortcode in content

$has_camp_id = get_post_meta( $post->ID, 'camp_id', true );
$has_search = has_shortcode( $post->post_content, 'camp_search' );

if ( $has_camp_id || $has_search ) {
    wp_enqueue_style( 'camp-search', ... );
    wp_enqueue_script( 'camp-search', ... );
}
```

**Performance Benefit:** CSS/JS doesn't load on pages that don't use it.

---

## 9. Data Binding to JavaScript

In the HTML, a data object is passed to JavaScript:

```html
<script>
var campSearchData = {
    ajaxUrl: 'https://site.com/wp-admin/admin-ajax.php',
    resultsPerPage: 12,
    minPrice: 100,
    maxPrice: 5000
};
</script>
```

JavaScript uses this to:
- Know where to send AJAX requests
- Set pagination limits
- Initialize price sliders

---

## 10. URL Parameters (Bookmarkable Searches)

The `camp-search.js` supports URL parameters for shareable searches:

```
?search=swimming
?state=CA
?start_date=2026-06-01&end_date=2026-08-31
?min_price=500&max_price=2000
?camp_types=Day%20Camp,Overnight%20Camp
?weeks=1,2,4
?activities=Swimming,Hiking
?sort=rating_desc
```

Example shareable URL:
```
/camps/?state=CA&sort=rating_desc&min_price=500&max_price=1500
```

---

## 11. Mobile Responsiveness

### Breakpoints

**Tablet (768px and below):**
- Search bar buttons stack vertically
- Filters collapse with toggle button
- Results: 2-column grid maintained
- Cards: Logo above details

**Phone (480px and below):**
- Results: 1-column layout
- Compact card design
- Full-width filters overlay

### Touch Optimizations
- Larger tap targets for filters
- Touch-friendly price sliders
- Collapsible filter panel

---

## 12. Current Known Behavior

### Search Behavior
- ✅ Real-time search (debounced 300ms)
- ✅ Multi-field text search
- ✅ Works with partial matches
- ✅ Case-insensitive

### Filter Behavior
- ✅ Multiple selections within filters
- ✅ Instant apply on dropdown change
- ✅ Debounced apply on checkboxes
- ✅ Debounced apply on price drag

### Sort Behavior
- ✅ Random sort (default)
- ✅ A-Z / Z-A alphabetical
- ✅ Rating based (highest/lowest)
- ✅ Price based (lowest/highest)
- ✅ Sorts re-applied on filter change

### Pagination
- ✅ Infinite scroll with "Load More" button
- ✅ Shows page count and "has_more" status
- ✅ Appends to grid (doesn't replace)

---

## 13. Integration Points

### Depends On
- `DB::table_sessions()` - For price data
- WordPress AJAX hooks
- FontAwesome icons
- jQuery

### Used By
- Any page with `[camp_search]` shortcode
- Camp search/discovery pages
- Camp listing pages

### Related Shortcodes
- `[camp_header]` - For detail pages
- `[camp_sessions]` - For camp detail display
- All other camp-related shortcodes

---

## 14. Customization Options

### Via Shortcode Attributes
```
[camp_search results_per_page="20" class="custom-class"]
```

### Via CSS Overrides
```css
.camp-search-wrapper { /* customize */ }
.camp-filters-sidebar { /* customize */ }
.camp-results-grid { /* customize */ }
.camp-result-card { /* customize */ }
```

### Via JavaScript (hooks/filters)
- Modify AJAX request before sending
- Format response data before displaying
- Add custom event listeners

---

## 15. Performance Considerations

✅ **Optimized For:**
- Debounced AJAX requests (prevents server spam)
- Pagination (infinite scroll, not all-at-once)
- Conditional asset loading
- DISTINCT queries (avoids duplicates)
- Database indexing on common fields

⚠️ **Considerations:**
- LIKE searches across multiple columns (consider full-text search for >5000 camps)
- LEFT JOIN with sessions table (ensure price data integrity)
- Dual price sliders (smooth but CPU-intensive when dragging)

---

## 16. Known Limitations

1. **Price Filtering:** Uses sessions table, assumes camps have session pricing
2. **Text Search:** Uses LIKE (wildcards), slower with large datasets
3. **Filter Values:** Populated from existing camp data (empty if no camps have the value)
4. **URL Parameters:** Not fully synchronized in current version (one-way)
5. **Filters:** Checkboxes use LIKE matching (could match partial strings)

---

## 17. Testing Checklist

- [x] Shortcode renders on page
- [x] Search input works (text appears)
- [x] Filters populate with data
- [x] AJAX requests sent on filter change
- [x] Results load and display
- [x] Sorting changes order of results
- [x] Pagination works (Load More button)
- [x] Mobile responsive
- [x] No console errors
- [x] Assets load only on search pages

---

## 18. Summary

The `[camp_search]` shortcode is a **production-ready, feature-complete search system** for camp discovery. It provides:

✅ Full-text search across all camp data  
✅ 7 filter options with multi-select  
✅ 7 sort methods  
✅ Pagination with infinite scroll  
✅ Mobile responsive design  
✅ Smart AJAX with debouncing  
✅ Conditional asset loading  
✅ SEO-friendly camp cards with internal links  

**Ready For:** Any camp listing/search page on your site.

---

**Next Steps:** Discuss any improvements or modifications needed for your specific use case.
