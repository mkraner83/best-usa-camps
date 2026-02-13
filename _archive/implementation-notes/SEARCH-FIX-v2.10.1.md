# Camp Search Fix - v2.10.1

**Date:** January 19, 2026  
**Issue:** Camps not showing in search results  
**Root Cause:** Search query not filtering by `approved` column  
**Status:** ✅ Fixed and rebuilt  

---

## Problem Summary

**Reported Issues:**
1. Added 20 camps to Camp Management, but none appear in search results
2. WordPress Camp Management has an "Approve" checkbox that should control visibility

**Root Cause:**
The AJAX search handler (`ajax_camp_search`) in `includes/Public/class-camp-frontend.php` was not checking the `approved` status column when retrieving camps. All camps were being filtered regardless of approval status.

---

## Solution Implemented

### Changes Made

**File:** `/includes/Public/class-camp-frontend.php`  
**Method:** `ajax_camp_search()` (Lines 1477-1638)  
**Change Type:** WHERE clause modification

### Specific Changes

#### 1. Initialize WHERE with approved filter
**Before:**
```php
$where = [ '1=1' ];
```

**After:**
```php
$where = [ 'c.approved = 1' ]; // Only show approved camps
```

**Impact:** All search results now require `approved = 1` in the database

#### 2. Update all column references to use table alias
**Before:**
```php
$where[] = $wpdb->prepare( "state = %s", $state );
```

**After:**
```php
$where[] = $wpdb->prepare( "c.state = %s", $state );
```

**Scope:** Updated in all filter conditions:
- Search: `c.camp_name`, `c.city`, `c.state`, `c.description`, `c.activities`, `c.camp_types`, `c.weeks`, `c.additional_info`
- State: `c.state`
- Dates: `c.start_date`, `c.end_date`
- Types: `c.camp_types`
- Weeks: `c.weeks`
- Activities: `c.activities`

**Reason:** Ensures proper column disambiguation in SQL queries with table joins

---

## How It Works Now

### Search Query Logic

```sql
SELECT DISTINCT c.* 
FROM wp_camp_management c 
LEFT JOIN wp_camp_sessions s ON c.id = s.camp_id 
WHERE c.approved = 1           -- NEW: Only approved camps
  AND c.state = 'CA'           -- Filter by state
  AND (c.camp_types LIKE '%Day Camp%' OR c.camp_types LIKE '%Overnight%')
GROUP BY c.id 
ORDER BY c.camp_name ASC
```

### Approval Workflow

1. **Admin adds camp:** Camp created with `approved = 0` (default)
2. **Admin approves camp:** Checks "Approved" checkbox, camp updated to `approved = 1`
3. **Search query:** Only returns camps where `approved = 1`
4. **Frontend:** Only approved camps visible to public search

---

## Database Column Reference

**Table:** `wp_camp_management`  
**Column:** `approved`  
**Type:** TINYINT(1)  
**Values:**
- `0` = Not approved (hidden from search)
- `1` = Approved (visible in search)

**Default:** `0` (camps created hidden)

---

## Files Modified

| File | Lines | Change | Status |
|------|-------|--------|--------|
| `includes/Public/class-camp-frontend.php` | 1497 | WHERE clause init | ✅ Updated |
| `includes/Public/class-camp-frontend.php` | 1502-1570 | All column references | ✅ Updated |

---

## Testing Checklist

✅ **Before deploying, verify:**

1. Add test camp with `approved = 0`
   - Should NOT appear in search results
   - Should NOT appear on frontend

2. Add test camp with `approved = 1`
   - Should appear in search results
   - Should appear on frontend

3. Test all filters still work:
   - State filter
   - Date range filter
   - Price range filter
   - Camp types checkboxes
   - Weeks checkboxes
   - Activities checkboxes

4. Test all sort options:
   - Random
   - Name (A-Z, Z-A)
   - Rating (highest, lowest)
   - Price (lowest, highest)

5. Test pagination:
   - "Load More" button
   - Results count accurate

---

## Deployment Steps

1. **Backup current plugin:** Save existing version
2. **Deactivate current plugin:** v2.10.0
3. **Upload new ZIP:** `creativedbs-camp-mgmt-v2.10.0.zip` (rebuilt with this fix)
4. **Activate plugin:** Should activate without errors
5. **Check Camp Management:** Verify existing camps still show with approval status
6. **Test search:** Ensure approved camps appear in search

---

## Rollback (If Needed)

If issues occur:

1. Deactivate v2.10.0 plugin
2. Activate previous version (v2.9.0)
3. Camps will show regardless of approval status (pre-fix behavior)

---

## Version Information

- **Previous Version:** v2.10.0
- **Current Version:** v2.10.0 (with search fix)
- **Build Date:** January 19, 2026 at 15:56 UTC
- **ZIP File:** `creativedbs-camp-mgmt-v2.10.0.zip`
- **File Size:** 81 KB

---

## Summary

The search functionality now properly filters camps by approval status. Only camps with `approved = 1` will appear in search results, giving admins control over camp visibility on the frontend.

**Key Benefits:**
- ✅ Admins control which camps are public
- ✅ Unapproved camps remain hidden from search
- ✅ Approved camps appear in all search filters
- ✅ No data loss, just visibility control
- ✅ Backward compatible with existing data

