# Import/Export Functionality Documentation
**Plugin:** CreativeDBS Camp Management  
**Version:** 2.9.0  
**Feature:** CSV Import/Export for Camp Data  
**Location:** [Admin → Camp Management → Import / Export](https://bestusacamps.com/wp-admin/admin.php?page=creativedbs-camp-mgmt-import-export)

---

## Overview

The Import/Export feature allows administrators to:
- **Export** all camp data to a CSV file for backup, migration, or analysis
- **Import** camp data from CSV files, with automatic duplicate prevention
- **Bulk Operations** on camp records
- **Auto-create** taxonomy terms (types, weeks, activities) during import

---

## Current Implementation (v2.9.0)

### File Location
**Main Plugin File:** `creativedbs-camp-mgmt.php` (lines 889-1074)

### Class & Methods
- **Class:** `CreativeDBS_Camp_Management` (legacy singleton)
- **Export Menu:** `render_import_export_page()` (line 889)
- **Import Logic:** `do_import_csv()` (line 914)
- **Term Linking:** `link_terms_from_csv()` (line 983)
- **Export Handler:** `handle_export_csv()` (line 1009)

### Admin Menu Registration
```php
add_submenu_page(
    self::SLUG, 
    __('Import / Export', 'creativedbs'), 
    __('Import / Export', 'creativedbs'), 
    'manage_options', 
    self::SLUG.'-import-export', 
    [$this, 'render_import_export_page']
);
```

### AJAX/POST Hooks
```php
add_action('admin_post_cdbs_export', [$this, 'handle_export_csv']);
add_action('admin_post_nopriv_cdbs_export', [$this, 'handle_export_csv']);
```

---

## Export Feature

### How It Works
1. User clicks "Export CSV" button
2. System generates CSV with ALL camps
3. File downloads with timestamp: `camp-management-export-YYYY-MM-DD-HH-MM-SS.csv`

### Exported Columns (24 total)
**Main Camp Data:**
1. `unique_key` - Unique identifier for duplicate detection
2. `camp_name` - Camp name
3. `opening_day` - Season start date (DATE)
4. `closing_day` - Season end date (DATE)
5. `minprice_2026` - Lowest price (DECIMAL)
6. `maxprice_2026` - Highest price (DECIMAL)
7. `activities` - Legacy comma-separated activities (TEXT)
8. `email` - Contact email
9. `phone` - Contact phone
10. `website` - Camp website URL
11. `camp_directors` - Director names (TEXT)
12. `address` - Street address
13. `city` - City
14. `state` - State
15. `zip` - ZIP code
16. `about_camp` - Camp description (LONGTEXT, may contain HTML)
17. `photos` - Comma-separated photo URLs (LONGTEXT)
18. `logo` - Logo URL
19. `search_image` - Search result image URL
20. `approved` - Approval status (0/1)
21. `created_at` - Creation timestamp
22. `updated_at` - Last update timestamp

**Taxonomy Columns (added dynamically):**
23. `camp_types` - Comma-separated camp type names (e.g., "Day Camp, Overnight Camp")
24. `weeks` - Comma-separated week duration names (e.g., "1 Week, 2 Weeks")

### Export Process Details

**Database Queries:**
1. Fetch all camps: `SELECT * FROM wp_camp_management ORDER BY id ASC`
2. Fetch type terms: `SELECT id, name FROM wp_camp_type_terms`
3. Fetch week terms: `SELECT id, name FROM wp_camp_week_terms`
4. For each camp:
   - Fetch type_ids: `SELECT type_id FROM wp_camp_management_types_map WHERE camp_id=%d`
   - Fetch week_ids: `SELECT week_id FROM wp_camp_management_weeks_map WHERE camp_id=%d`

**CSV Headers:**
```
Content-Type: text/csv; charset=utf-8
Content-Disposition: attachment; filename=camp-management-export-YYYY-MM-DD-HH-MM-SS.csv
X-Content-Type-Options: nosniff
```

**Special Handling:**
- Output buffer cleared: `ob_end_clean()`
- Cache disabled: `nocache_headers()`
- Taxonomy names joined with `', '` (comma + space)
- Direct output via `php://output` stream

---

## Import Feature

### How It Works
1. User uploads CSV file via form
2. System parses header row to map columns
3. For each data row:
   - Check if `unique_key` exists in database
   - **Skip** existing camps (preserves data)
   - **Insert** new camps with auto-generated `unique_key` if missing
   - **Link** taxonomy terms (auto-create if needed)
4. Display results: Inserted / Skipped / Errors

### Duplicate Prevention
**Logic:**
```php
if ($unique_key !== '') {
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE unique_key=%s", 
        $unique_key
    ));
}
if ($exists) { 
    $skipped++; 
    continue; 
}
```

**Behavior:**
- Existing `unique_key` → **Skip entirely** (no update)
- Missing `unique_key` → Generate new: `md5(uniqid('camp_', true))`
- Blank rows → Skip

### Required Columns
**Minimum:** `camp_name` (all others optional)

### Optional Taxonomy Columns
| Column | Format | Example | Auto-Create |
|--------|--------|---------|-------------|
| `camp_types` | Comma/pipe/semicolon separated | "Day Camp, Overnight Camp" | Yes |
| `weeks` | Comma/pipe/semicolon separated | "1 Week, 2 Weeks, 4 Weeks" | Yes |
| `activities` | Comma/pipe/semicolon separated | "Swimming, Hiking, Arts & Crafts" | Yes |

**Delimiter Normalization:**
```php
$types_csv = str_replace(['|',';'], ',', (string)$types_csv);
```

### Taxonomy Linking Logic

For each taxonomy (types, weeks, activities):
1. **Split** CSV value by comma
2. **Search** for existing term by name OR slug
3. **Create** term if not found (with auto-generated slug)
4. **Link** camp to term via pivot table

**Example Flow (Camp Types):**
```php
$id = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$type_terms_table} WHERE name=%s OR slug=%s", 
    $nm, 
    sanitize_title($nm)
));
if (!$id) { 
    $wpdb->insert($type_terms_table, [
        'name' => $nm,
        'slug' => sanitize_title($nm),
        'is_active' => 1,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ]); 
    $id = intval($wpdb->insert_id); 
}
$wpdb->insert($pivot_types, [
    'camp_id' => $camp_id,
    'type_id' => intval($id)
]);
```

### Data Sanitization
| Field | Sanitization Function |
|-------|----------------------|
| `camp_name` | `sanitize_text_field()` |
| `email` | `sanitize_email()` |
| `website`, `logo`, `search_image` | `esc_url_raw()` |
| `phone`, `address`, `city`, `state`, `zip` | `sanitize_text_field()` |
| `camp_directors` | `sanitize_textarea_field()` |
| `about_camp` | `wp_kses_post()` (allows safe HTML) |
| `approved` | `intval()` then 0/1 |

### Import Results Display
```html
<div class="updated">
    <p>Import complete. 
       Inserted: 25, 
       Skipped (existing): 10, 
       Errors: 2
    </p>
</div>
```

**Error Conditions:**
- Missing `camp_name` → Count as error, continue
- Database insert failure → Count as error, continue
- File not readable → Display error, halt

---

## Current Limitations

### Export Limitations
1. **No Sessions Data** - Sessions table (`wp_camp_sessions`) not included
2. **No FAQs Data** - FAQs table (`wp_camp_faqs`) not included
3. **No Accommodations Data** - Accommodations table (`wp_camp_accommodations`) not included
4. **No Activity Terms Column** - Activities exported as legacy TEXT field, not from pivot table
5. **No Ratings Export** - `rating` field not included in export columns
6. **No Filter Options** - Exports ALL camps (no selective export)
7. **No Format Options** - CSV only (no JSON, Excel, etc.)
8. **No Chunking** - Large exports may timeout or exceed memory

### Import Limitations
1. **No Updates** - Existing camps skipped entirely (no merge/update option)
2. **No Validation** - No pre-import validation report
3. **No Dry Run** - Cannot preview import results
4. **No Error Details** - Generic error count, no specifics
5. **No Progress Indicator** - Large imports have no progress feedback
6. **No Rollback** - Failed imports partially complete (no transaction)
7. **No Sessions/FAQs/Accommodations** - Cannot import module data
8. **No Photo Upload** - Photos must be pre-uploaded (URLs only)
9. **Single File** - Cannot import related tables separately

### Technical Debt
1. **Legacy Code** - In monolithic plugin file, not modular
2. **No Error Logging** - Errors not logged to file
3. **No Security Audit** - Basic nonce check only
4. **No File Size Limit** - May fail on large uploads
5. **No Charset Detection** - Assumes UTF-8
6. **Synchronous Processing** - No background job queue
7. **No Email Notifications** - Admin not notified on completion/errors

---

## Database Schema Context

### Main Tables
```sql
wp_camp_management (camps)
wp_camp_sessions (sessions - NOT exported/imported)
wp_camp_faqs (FAQs - NOT exported/imported)
wp_camp_accommodations (facilities - NOT exported/imported)
```

### Taxonomy Tables
```sql
wp_camp_type_terms (types: Day Camp, Overnight Camp, etc.)
wp_camp_week_terms (durations: 1 Week, 2 Weeks, etc.)
wp_camp_activity_terms (activities: Swimming, Hiking, etc.)
```

### Pivot Tables (Many-to-Many)
```sql
wp_camp_management_types_map (camp_id, type_id)
wp_camp_management_weeks_map (camp_id, week_id)
wp_camp_management_activities_map (camp_id, activity_id)
```

### Not Included in Import/Export
- `wp_camp_sessions` (session dates, prices, descriptions)
- `wp_camp_faqs` (questions and answers)
- `wp_camp_accommodations` (facility details)
- `wp_camp_modules_completed` (migration tracking)
- Camp-to-WordPress user relationships

---

## Security

### Authorization
```php
if (!current_user_can('manage_options')) {
    // Export: wp_die('Unauthorized')
    // Import: return (silent fail)
}
```

**Required Capability:** `manage_options` (Administrator only)

### Nonce Verification
**Export:**
```php
wp_verify_nonce($_GET['_wpnonce'], 'cdbs_export_csv')
```

**Import:**
```php
check_admin_referer('cdbs_import_csv')
```

### File Handling
- Upload via `$_FILES['csv_file']['tmp_name']`
- Read-only mode: `fopen($tmp_path, 'r')`
- No file retention (processed in temp directory)

### Data Escaping
- All output escaped in admin page
- CSV data sanitized on import
- HTML allowed in `about_camp` via `wp_kses_post()`

---

## User Interface

### Export UI
```
[H1] Import / Export

[H2] Export
Download a CSV of all camps, including types, weeks and activities.
[Button] Export CSV

[HR]

[H2] Import
Upload a CSV. Rows with an existing unique_key are skipped to keep 
existing records untouched. If unique_key is missing, a new one will 
be generated and the camp will be inserted.

Optional columns to link terms: camp_types, weeks, activities 
(comma‑separated names). Unknown terms are auto‑created.

[File Input] .csv only
[Button] Import CSV
```

### Access Path
1. WordPress Admin
2. Camp Management menu
3. Import / Export submenu

**Direct URL:** `/wp-admin/admin.php?page=creativedbs-camp-mgmt-import-export`

---

## Use Cases

### Current Supported Use Cases
1. **Full Backup** - Export all camps for disaster recovery
2. **Migration** - Move camps between WordPress installations
3. **Bulk Add** - Import camps from external source (e.g., spreadsheet)
4. **Analysis** - Export for Excel/Google Sheets analysis
5. **Term Seeding** - Auto-populate taxonomy terms

### Currently NOT Supported
1. **Partial Backup** - Cannot export specific camps
2. **Incremental Updates** - Cannot update existing camps
3. **Module Data** - Cannot export/import sessions, FAQs, accommodations
4. **Media Import** - Cannot upload photos/logos via CSV
5. **User Account Creation** - Cannot create camp director accounts
6. **Relationships** - Cannot export camp-to-page mappings

---

## Code Architecture

### Method Flow

**Export:**
```
User clicks button
  ↓
admin-post.php?action=cdbs_export
  ↓
handle_export_csv()
  ↓
- Verify nonce & capability
- Clear output buffers
- Set CSV headers
- Fetch camps + taxonomy data
- Stream CSV rows
- Exit
```

**Import:**
```
User uploads CSV
  ↓
POST to render_import_export_page()
  ↓
do_import_csv($tmp_path)
  ↓
- Parse CSV header
- For each row:
  - Check duplicates
  - Insert camp
  - link_terms_from_csv()
- Display results
```

### Dependencies
- WordPress Core: `fgetcsv()`, `fputcsv()`, `wpdb`
- Required capabilities: `manage_options`
- Nonce functions: `wp_nonce_url()`, `check_admin_referer()`

---

## Performance Considerations

### Export Performance
- **Memory:** Streams output (low memory footprint)
- **Time:** ~0.5s per 100 camps (database dependent)
- **Timeout:** May timeout on 1000+ camps without `max_execution_time` increase

### Import Performance
- **Memory:** Loads entire file into memory via `fgetcsv()`
- **Time:** ~2-3s per 100 camps (includes taxonomy lookups)
- **Database Queries:** ~8-12 queries per camp (main + pivots + term lookups)

### Scalability Issues
- Large files (>10,000 camps) may exceed PHP limits
- No pagination or chunking
- No background processing

---

## Testing Recommendations

### Export Tests
- [ ] Export empty database (0 camps)
- [ ] Export 1 camp
- [ ] Export 100+ camps
- [ ] Verify all 24 columns present
- [ ] Check UTF-8 encoding (accented characters)
- [ ] Verify taxonomy columns populated
- [ ] Test filename timestamp format

### Import Tests
- [ ] Import file with unique_key (should skip)
- [ ] Import file without unique_key (should insert)
- [ ] Import with new taxonomy terms (should auto-create)
- [ ] Import with invalid data (should count errors)
- [ ] Import large file (1000+ rows)
- [ ] Import file with special characters
- [ ] Import file with missing columns
- [ ] Import file with extra columns (should ignore)

---

## Upgrade Recommendations

### High Priority
1. **Module Data Export/Import** - Include sessions, FAQs, accommodations
2. **Update Mode** - Allow updating existing camps (not just insert/skip)
3. **Error Logging** - Detailed error messages with row numbers
4. **Validation** - Pre-import validation report
5. **Progress Indicator** - Show progress for large imports

### Medium Priority
6. **Selective Export** - Filter by state, type, date range
7. **Format Options** - JSON, Excel formats
8. **Dry Run Mode** - Preview import without committing
9. **Chunking** - Process large files in batches
10. **Rollback** - Transaction support or manual rollback

### Low Priority
11. **Media Import** - Upload photos/logos via ZIP + CSV
12. **User Creation** - Create WordPress accounts for directors
13. **Relationship Mapping** - Export/import camp-to-page connections
14. **Email Notifications** - Notify admin on completion
15. **Scheduled Exports** - Auto-export on schedule (backup)

### Refactoring Needs
- Move to separate Admin class (not in main plugin file)
- Use WordPress HTTP API for remote imports
- Add `WP_CLI` commands for command-line access
- Implement `WP_Background_Processing` for large imports
- Add unit tests (PHPUnit)

---

## Version History

### v2.1.4 - v2.9.0
- Export/Import feature remains **unchanged**
- Still in legacy monolithic file
- No enhancements or bug fixes to import/export

### Known Since
- Feature exists since at least **v2.1.4** (December 2025)
- Code location: Main plugin file (legacy section)
- No documented changes in CHANGELOG.md

---

## Related Documentation
- [SESSION-SUMMARY-2026-01-15.md](SESSION-SUMMARY-2026-01-15.md) - Mentions future enhancement
- [README-INSTRUCTIONS.md](README-INSTRUCTIONS.md) - Brief mention of feature
- [RESTORE-POINT-v2.9.0.md](RESTORE-POINT-v2.9.0.md) - Current version state

---

**Documentation Created:** January 16, 2026  
**Last Review:** January 16, 2026  
**Prepared For:** Import/Export Feature Upgrade Planning

