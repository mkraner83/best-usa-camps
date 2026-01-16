# Import/Export Field Mapping Document
**Date:** January 16, 2026  
**Version:** v2.9.0 Upgrade  
**Status:** Ready for Implementation

---

## CSV to Database Field Mapping

### Complete Column Mapping (24 columns)

| # | CSV Column | Database Field | Data Type | Required | Special Handling | Notes |
|---|------------|----------------|-----------|----------|------------------|-------|
| 1 | `unique_key` | `wp_camp_management.unique_key` | VARCHAR(64) | ❌ Auto-generate if missing | MD5 hash | Duplicate detection key |
| 2 | `camp_name` | `wp_camp_management.camp_name` | VARCHAR(255) | ✅ Required | Sanitize text | Validation: Not empty |
| 3 | `opening_day` | `wp_camp_management.opening_day` | DATE | ❌ Optional | Parse YYYY-MM-DD | Validation: Valid date |
| 4 | `closing_day` | `wp_camp_management.closing_day` | DATE | ❌ Optional | Parse YYYY-MM-DD | Validation: Valid date |
| 5 | `minprice_2026` | `wp_camp_management.minprice_2026` | DECIMAL(10,2) | ❌ Optional | Strip `$` and `,` | Validation: Numeric |
| 6 | `maxprice_2026` | `wp_camp_management.maxprice_2026` | DECIMAL(10,2) | ❌ Optional | Strip `$` and `,` | Validation: Numeric |
| 7 | `activities` | `wp_camp_management.activities` | TEXT | ❌ Optional | Legacy field (deprecated) | Not used in new system |
| 8 | `email` | `wp_camp_management.email` | VARCHAR(190) | ✅ Required for user creation | Sanitize email | Used for WordPress user |
| 9 | `phone` | `wp_camp_management.phone` | VARCHAR(64) | ❌ Optional | Sanitize text | Display only |
| 10 | `website` | `wp_camp_management.website` | VARCHAR(255) | ❌ Optional | Validate & escape URL | Used for WordPress user |
| 11 | `camp_directors` | `wp_camp_management.camp_directors` | TEXT | ✅ Required for user creation | Split by `\|`, use first name | First director → WordPress user |
| 12 | `address` | `wp_camp_management.address` | VARCHAR(255) | ❌ Optional | Sanitize text | Display only |
| 13 | `city` | `wp_camp_management.city` | VARCHAR(190) | ❌ Optional | Sanitize text | Display only |
| 14 | `state` | `wp_camp_management.state` | VARCHAR(64) | ❌ Optional | Sanitize text | Display only |
| 15 | `zip` | `wp_camp_management.zip` | VARCHAR(32) | ❌ Optional | Sanitize text | Display only |
| 16 | `about_camp` | `wp_camp_management.about_camp` | LONGTEXT | ❌ Optional | Allow HTML (wp_kses_post) | Rich text allowed |
| 17 | `photos` | `wp_camp_management.photos` | LONGTEXT | ❌ Optional | CSV URLs (deprecated) | Legacy field |
| 18 | `logo` | `wp_camp_management.logo` | VARCHAR(255) | ❌ Optional | Validate URL | Display only |
| 19 | `search_image` | `wp_camp_management.search_image` | VARCHAR(255) | ❌ Optional | Validate URL | Display only |
| 20 | `approved` | `wp_camp_management.approved` | TINYINT(1) | ❌ Optional | Convert 0/1 | Default: 0 |
| 21 | `created_at` | `wp_camp_management.created_at` | DATETIME | ❌ Auto-set | Set to NOW if empty | Import timestamp |
| 22 | `updated_at` | `wp_camp_management.updated_at` | DATETIME | ❌ Auto-set | Set to NOW | Always update |
| 23 | `camp_types` | `wp_camp_type_terms` (pivot) | TEXT | ❌ Optional | Split by `\|`, auto-create | Links via `wp_camp_management_types_map` |
| 24 | `weeks` | `wp_camp_week_terms` (pivot) | TEXT | ❌ Optional | Split by `\|`, auto-create | Links via `wp_camp_management_weeks_map` |

---

## New Field: WordPress User Linkage

### New Column to Add
| Field | Type | Purpose | Notes |
|-------|------|---------|-------|
| `wordpress_user_id` | BIGINT UNSIGNED | Link to WordPress user | Created during import |

**Database Migration:**
```sql
ALTER TABLE wp_camp_management ADD COLUMN wordpress_user_id BIGINT UNSIGNED NULL AFTER updated_at;
```

---

## User Creation Logic

### Requirements
✅ **Trigger:** Import each camp row  
✅ **Source:** First director name from `camp_directors` field  
✅ **Role:** Existing "Camp" role in WordPress  
✅ **No Email Auto-send:** Email notifications disabled  

### User Data Derivation

| WordPress Field | Source | Example | Format |
|-----------------|--------|---------|--------|
| `user_login` | `camp_name` | "Camp North Star" | Remove spaces + lowercase = `campnorthstar` |
| `user_email` | `email` | "info@campnorthstarmaine.com" | As-is |
| `first_name` | `camp_directors` (first name of first director) | "Steven Bernstein" | Extract: `Steven` |
| `last_name` | `camp_directors` (last name of first director) | "Steven Bernstein" | Extract: `Bernstein` |
| `user_url` | `website` | "https://campnorthstarmaine.com/" | As-is |
| `role` | Fixed | "camp" | Hardcoded |
| `user_pass` | Generated | Random 12 chars | Use `wp_generate_password(12)` |

### Username Generation Rules

**Input:** Camp name (e.g., "Camp North Star")  
**Process:**
1. Convert to lowercase: `"camp north star"`
2. Remove all spaces/special chars: `"campnorthstar"`
3. Check if exists: If yes, append number: `"campnorthstar2"`
4. Validate length (3-60 chars): If too short/long, error

**Examples:**
- "Timber Lake Camp" → `timberlakecamp`
- "Camp North Star" → `campnorthstar`
- "Crestwood Country Day Camp" → `crestwoodcountrydaycamp`

### First Director Extraction

**Input:** `"Steven Bernstein | Brooke Bernstein"`  
**Process:**
1. Split by `|` (pipe)
2. Trim whitespace: `"Steven Bernstein"`
3. Split by space: `["Steven", "Bernstein"]`
4. First name: `"Steven"`
5. Last name: `"Bernstein"`

**Edge Cases:**
- Single name: "Madonna" → first: `"Madonna"`, last: `""`
- Multiple spaces: "John Paul Smith" → first: `"John"`, last: `"Paul Smith"`
- Titles: "Dr. Mike Brown" → first: `"Dr."`, last: `"Mike Brown"` (may need cleanup)

---

## Data Validation Rules

### Pre-Import Validation

| Field | Rule | Error Message |
|-------|------|---------------|
| `camp_name` | Not empty, 1-255 chars | "Camp name is required" |
| `email` | Valid email format | "Invalid email address" |
| `opening_day` | Valid date (YYYY-MM-DD) | "Invalid opening day format" |
| `closing_day` | Valid date (YYYY-MM-DD) | "Invalid closing day format" |
| `minprice_2026` | Numeric, ≥ 0 | "Invalid minimum price" |
| `maxprice_2026` | Numeric, ≥ minprice | "Invalid maximum price" |
| `state` | 2-char state code (optional) | "Invalid state format" |
| `zip` | 5-9 digits (optional) | "Invalid ZIP code" |
| `logo` | Valid URL format | "Invalid logo URL" |
| `search_image` | Valid URL format | "Invalid search image URL" |

### Duplicate Detection

**Primary Key:** `unique_key`
- If exists in DB → **Skip row** (no update)
- If empty → **Generate new** with `md5(uniqid('camp_', true))`

**Secondary Check:** Username + Email
- If username exists → **Error** (skip camp, don't create user)
- If email exists → **Warning** (allow multiple users, but notify)

---

## Data Transformation

### Price Cleanup

**Before:**
```
minprice_2026: "$4,700"
maxprice_2026: "$13,950"
```

**After:**
```
minprice_2026: 4700.00
maxprice_2026: 13950.00
```

**Code:**
```php
$price = str_replace(['$', ','], '', $price);
$price = floatval($price);
```

### Multi-Value Field Parsing

**Camp Directors (user creation):**
```
Input: "Steven Bernstein | Brooke Bernstein"
Output: ["Steven Bernstein", "Brooke Bernstein"]
Used: First only = "Steven Bernstein"
```

**Camp Types (taxonomy linking):**
```
Input: "Coed Camp | Overnight Camp"
Output: ["Coed Camp", "Overnight Camp"]
Process: Link each to camp via pivot table
```

**Weeks (taxonomy linking):**
```
Input: "2 Weeks | 3 Weeks | 4 Weeks"
Output: ["2 Weeks", "3 Weeks", "4 Weeks"]
Process: Link each to camp via pivot table
```

**Delimiter Support:** Pipe `|` (primary), fallback to semicolon `;`

---

## Import Modes

### Mode 1: INSERT (New Camps)
- `unique_key` missing or not found in DB
- **Action:** Insert new camp record
- **User:** Create new WordPress user
- **Status:** "Inserted" in results

### Mode 2: SKIP (Existing Camps)
- `unique_key` exists in DB
- **Action:** Skip entire row (no update)
- **User:** Don't create user
- **Status:** "Skipped" in results

### Mode 3: UPDATE (Existing Camps - NEW FEATURE)
- `unique_key` exists in DB + update flag enabled
- **Action:** Update camp fields
- **User:** Don't create user (already linked)
- **Status:** "Updated" in results

---

## Export Behavior

### What Gets Exported

✅ **Camp Data:** All 24 columns (including wordpress_user_id)  
✅ **Taxonomy Names:** Fetched from term tables, joined with ` | `  
✅ **All Camps:** No filtering options  

### Export Format

**Headers (25 columns with new field):**
```
unique_key,camp_name,opening_day,closing_day,minprice_2026,maxprice_2026,
activities,email,phone,website,camp_directors,address,city,state,zip,
about_camp,photos,logo,search_image,approved,created_at,updated_at,
camp_types,weeks,wordpress_user_id
```

**Data Row Example:**
```
camp_13,"Camp North Star","2026-06-28","2026-08-15",4700.00,13950.00,,
info@campnorthstarmaine.com,(207) 998-4777,https://campnorthstarmaine.com/,
Steven Bernstein | Brooke Bernstein,200 Verril Road,Poland Spring,Maine,04274,
"The North Star Story: ...","","","",0,"2025-09-02 00:00:00","2025-09-02 00:00:00",
Coed Camp | Overnight Camp,2 Weeks | 3 Weeks | 4 Weeks | 5 Weeks | 7 Weeks,12
```

**Taxonomy Format:** Pipe-separated names (` | `)

---

## Error Handling & Reporting

### Row-Level Errors

**Captured Info:**
- Row number (1-based)
- Camp name (if available)
- Error type: "validation", "user_creation", "duplicate", "database"
- Error message (specific)

**Example Report:**
```
Row 5: Camp North Star
  - Error: Username "campnorthstar" already exists
  - Action: Skipped (camp and user not created)

Row 8: Camp Lohikan
  - Error: Email "info@lohikan.com" already in use
  - Action: Warning (camp inserted, user skipped)
```

### Summary Statistics
- **Inserted:** # of new camps
- **Updated:** # of camps updated
- **Skipped:** # of duplicate camps
- **User Created:** # of WordPress users created
- **User Failed:** # of user creation failures
- **Errors:** # of validation errors

---

## Security Considerations

### Authorization
- **Required Role:** `manage_options` (Administrator)
- **Nonce Check:** CSRF protection on both export & import

### Data Sanitization (Import)
- `camp_name`: `sanitize_text_field()`
- `email`: `sanitize_email()`
- `phone`, `address`, `city`, `state`, `zip`: `sanitize_text_field()`
- `camp_directors`: `sanitize_text_field()`
- `about_camp`: `wp_kses_post()` (allows safe HTML)
- `website`, `logo`, `search_image`: `esc_url_raw()`

### File Handling
- Accept `.csv` only
- Read from temp directory (no persistence)
- Max file size: 10MB (configurable)

### User Creation
- Passwords NOT shown in admin UI
- Passwords displayed in download CSV only
- No email notifications sent
- Users can reset password via "Forgot Password"

---

## Database Changes Needed

### Alter Table
```sql
ALTER TABLE wp_camp_management 
ADD COLUMN wordpress_user_id BIGINT UNSIGNED NULL 
AFTER updated_at;
```

### Index (Optional, for performance)
```sql
ALTER TABLE wp_camp_management 
ADD INDEX idx_wordpress_user_id (wordpress_user_id);
```

---

## Implementation Checklist

- [ ] Create new modular admin class: `includes/Admin/class-import-export.php`
- [ ] Add database migration for `wordpress_user_id` column
- [ ] Implement validation layer with detailed error messages
- [ ] Add user creation logic with password generation
- [ ] Implement update mode (toggle feature)
- [ ] Add dry-run/preview functionality
- [ ] Create progress indicator (AJAX-based)
- [ ] Add password export CSV generation
- [ ] Update export to include `wordpress_user_id`
- [ ] Add comprehensive error reporting
- [ ] Add unit tests
- [ ] Update admin UI with enhanced form
- [ ] Update documentation

---

## Confirmation Needed

**Before I proceed with implementation, please confirm:**

1. ✅ Field mapping looks correct?
2. ✅ User creation logic matches requirements?
3. ✅ Validation rules are comprehensive?
4. ✅ Error handling approach is acceptable?
5. ✅ Ready to add `wordpress_user_id` column to database?

**Any changes or clarifications?**

