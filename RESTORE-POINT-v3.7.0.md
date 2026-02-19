# RESTORE POINT — v3.7.0
**Date:** February 20, 2026  
**Status:** ✅ STABLE — All features committed and pushed  
**Zip:** `creativedbs-camp-mgmt-v3.7.0.zip`  
**Git commit:** `d08c58e` → version bump commit (pushed after)  
**Branch:** `main`  
**Previous restore point:** `RESTORE-POINT-v3.6.0.md` (February 19, 2026)

---

## What Was Built In This Session (v3.6.0 → v3.7.0)

Three features/fixes from a single VS Code session on February 20, 2026:

1. **`[camp_page]` typography & spacing polish**
2. **Admin Approved checkbox — two bugs fixed**
3. **`[camp_livesearch]` — new autocomplete shortcode**
4. **Shortcodes guide updated** with both new shortcodes

---

## 1. `[camp_page]` Typography & Spacing Polish

**File:** `assets/camp-frontend.css`

### Typography System
| Class | Font | Size | Weight | Notes |
|-------|------|------|--------|-------|
| `.cdbs-cp-heading` | Abel | 26px | 400 | All section headings, letter-spacing: 2px |
| `.cdbs-cp-hl` | Amaranth | 26px | 900 | Bold inline spans, letter-spacing: 1.9px |
| `.cdbs-cp-heading--main` | Abel | 40px | 400 | Main camp title only |
| `.cdbs-cp-heading--main .cdbs-cp-hl` | Amaranth | 39px | 900 | Bold part of main title |

### Divider
```css
.cdbs-cp-divider {
    border: none;
    border-top: 2px solid rgba(190,190,190,0.42);
    margin: 52px 0px 30px 0 !important; /* !important needed — Elementor sets hr { margin:0 } */
}
```

### Mobile / Responsive
```css
/* ≤1024px */
.cdbs-camp-page { padding-left: 10px; padding-right: 10px; }
.cdbs-cp-heading { font-size: 18px; }
.cdbs-cp-hl      { font-size: 18px; }

/* ≤480px */
.cdbs-cp-heading { font-size: 16px; }
.cdbs-cp-hl      { font-size: 16px; }
.cdbs-cp-heading--main { font-size: 22px; }
```

### Key Design Notes
- `···——` Unicode characters used in section heading separators (rendered inline before heading text via `::before` pseudo-elements or direct HTML)
- Fonts loaded via Google Fonts in theme (Abel + Amaranth)
- `!important` on `.cdbs-cp-divider` margin is intentional — Elementor's default stylesheet sets `hr { margin: 0 }` which overrides without it

---

## 2. Admin Approved Checkbox — Two Bug Fixes

**File:** `creativedbs-camp-mgmt.php`

### Bug 1: Toggle JS scope (line ~308)
The checkbox toggle JS (inline `<script>`) used to early-return when not on the main `self::SLUG` page. But there are **two** admin pages with an Approved checkbox column:
- **Overview page:** `?page=creativedbs-camp-mgmt` (= `self::SLUG`)
- **All Camps page:** `?page=creativedbs-camp-mgmt-camps` (= `self::SLUG . '-camps'`)

**Fix:**
```php
// BEFORE (only ran on Overview):
if ($_GET['page'] !== self::SLUG) return;

// AFTER (runs on both):
if ($_GET['page'] !== self::SLUG && $_GET['page'] !== self::SLUG . '-camps') return;
```

### Bug 2: Full Edit form missing Approved checkbox
The Full Edit form (`?action=edit&id=X`) had no `approved` checkbox. Because there was no `approved` input in the POST, `isset($_POST['approved'])` was always `false`, causing **every Full Edit save to reset approved → 0**.

**Fix:** Added as the first `<tr>` in the edit form table:
```php
<tr>
    <th>Approved</th>
    <td>
        <input type="checkbox" name="approved" value="1" <?php checked($camp->approved, 1); ?> />
        Approved — camp is visible on the public site
    </td>
</tr>
```

Save logic (already correct, no change needed):
```php
'approved' => isset($post_data['approved']) ? 1 : 0,
```

---

## 3. `[camp_livesearch]` — New Autocomplete Shortcode

### New Files
- `assets/camp-livesearch.css` — 190 lines
- `assets/camp-livesearch.js` — 213 lines

### Modified File
- `includes/Public/class-camp-frontend.php` — added constructor hooks, enqueue logic, `render_livesearch()`, `ajax_livesearch()`

---

### 3a. PHP — `includes/Public/class-camp-frontend.php`

#### Constructor additions (lines 51–53)
```php
// Live-search (nav/header autocomplete)
add_shortcode( 'camp_livesearch', [ $this, 'render_livesearch' ] );
add_action( 'wp_ajax_cdbs_livesearch',        [ $this, 'ajax_livesearch' ] );
add_action( 'wp_ajax_nopriv_cdbs_livesearch', [ $this, 'ajax_livesearch' ] );
```

#### `enqueue_frontend_styles()` — livesearch assets (lines ~113–133)
```php
// Livesearch: always enqueue (can appear in nav/header on any page)
wp_enqueue_style(
    'camp-livesearch',
    plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-livesearch.css',
    [],
    CDBS_CAMP_VERSION
);
wp_enqueue_script(
    'camp-livesearch',
    plugin_dir_url( CREATIVE_DBS_CAMPMGMT_FILE ) . 'assets/camp-livesearch.js',
    [],
    CDBS_CAMP_VERSION,
    true // footer
);
wp_localize_script( 'camp-livesearch', 'CDBS_LS', [
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'nonce'    => wp_create_nonce( 'cdbs_livesearch' ),
] );
```
> **Important:** livesearch assets are ALWAYS enqueued (not conditional on `has_camp_id`), because `[camp_livesearch]` is placed in the site-wide header/nav and must work on every page.

#### `render_livesearch( $atts )` (line 1981)
```php
public function render_livesearch( $atts ) {
    $atts = shortcode_atts( [
        'placeholder'   => 'Search camps…',
        'show_all_link' => '',  // URL to full search page
    ], $atts );

    $uid  = 'cdbs-ls-' . wp_unique_id();
    // Outputs .cdbs-ls-wrap wrapper with:
    //   data-nonce="<fresh nonce>"
    //   .cdbs-ls-field (SVG icon + input + clear ×)
    //   .cdbs-ls-dropdown (empty, filled by JS)
    //   .cdbs-ls-footer > .cdbs-ls-all-link  (only if show_all_link set)
}
```

**Nonce strategy:** nonce is created BOTH in `wp_localize_script` (global `CDBS_LS.nonce`) AND as `data-nonce` attribute on the `.cdbs-ls-wrap` element. JS reads `data-nonce` first (per-widget), falls back to `CDBS_LS.nonce`. This supports multiple widgets per page.

#### `ajax_livesearch()` (line 2019)
```php
public function ajax_livesearch() {
    check_ajax_referer( 'cdbs_livesearch', 'nonce' );
    global $wpdb;
    $q = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
    if ( strlen($q) < 2 ) { wp_send_json_success([]); }

    $table = $wpdb->prefix . 'camp_management';
    // SELECT id, camp_name, logo, state, website FROM ... WHERE approved=1 AND camp_name LIKE %s LIMIT 10

    // Resolves WP page URLs via postmeta:
    // SELECT post_id, meta_value FROM wp_postmeta WHERE meta_key='camp_id' AND CAST(meta_value AS UNSIGNED) IN (...)
    // Returns: get_permalink( $post_id )

    // JSON response: [ { id, name, logo, state, url }, ... ]
}
```

---

### 3b. CSS — `assets/camp-livesearch.css`

| Class | Purpose |
|-------|---------|
| `.cdbs-ls-wrap` | Container: `position:relative; display:inline-block; width:260px; max-width:100%` |
| `.cdbs-ls-field` | Input row: flex, white bg, `border:1.5px solid #d0d5dd`, `border-radius:8px` |
| `.cdbs-ls-field:focus-within` | Focused: `border-color:#4a6b5a`, green ring |
| `.cdbs-ls-icon` | SVG search icon: 16×16px, grey |
| `.cdbs-ls-input` | Text input: flex:1, no border/outline, Abel font 14px |
| `.cdbs-ls-clear` | × button: hidden by default, shown by JS when input has text |
| `.cdbs-ls-dropdown` | Results panel: `position:absolute; top:calc(100%+6px); display:none` → `.cdbs-ls-open { display:block }` |
| `.cdbs-ls-item` | Result row: flex, hover `#f0f6f3`, cursor pointer |
| `.cdbs-ls-logo` | 30×30px circle, `border:1.5px solid #cde0d8`, overflow:hidden |
| `.cdbs-ls-logo-placeholder` | Letter fallback when no logo |
| `.cdbs-ls-info` | Name + state column: flex:1 |
| `.cdbs-ls-name` | Camp name: 14px |
| `.cdbs-ls-state` | State pill: 11px, grey |
| `.cdbs-ls-match` | Highlighted text: `background:#d4edda` |
| `.cdbs-ls-status` | "No camps found…" message |
| `.cdbs-ls-footer` | Optional bottom row with "Browse all camps →" link |
| `.cdbs-ls-all-link` | Footer link: green color |
| `.cdbs-ls-full` | Width modifier: `width:100%` (full-width variant) |
| `.cdbs-ls-item--active` | Keyboard-focused row: `background:#e8f4ee` |

---

### 3c. JavaScript — `assets/camp-livesearch.js`

**No jQuery dependency.** Uses vanilla XHR.

```
Global:  CDBS_LS.ajax_url, CDBS_LS.nonce (from wp_localize_script)
         Each widget also reads data-nonce from its wrapper element.

Key functions:
  highlight(text, query)   — wraps query matches in .cdbs-ls-match spans
  escHtml(s)               — HTML entity escaping
  escRe(s)                 — regex special char escaping
  debounce(fn, 280)        — 280ms delay before AJAX fires
  initWidget(wrap)         — initializes one .cdbs-ls-wrap element

Per-widget state:
  currentQ    — last query string
  activeIdx   — keyboard cursor (-1 = none)
  xhr         — in-flight XHR (aborted on new keystroke)

Behavior:
  - Starts searching at 2+ characters
  - Abort previous XHR on each new keystroke
  - ArrowUp/ArrowDown — navigate items (.cdbs-ls-item--active)
  - Enter — navigate to active item URL (or first item if none active)
  - Escape — close dropdown
  - Click outside — close dropdown
  - Clear (×) button — clears input + closes dropdown
  - Multiple widgets on same page fully supported (each has own state)

Init: DOMContentLoaded → querySelectorAll('.cdbs-ls-wrap') → forEach initWidget
```

---

### 3d. Shortcode Usage

```
[camp_livesearch]
```
- Default width: 260px
- Default placeholder: "Search camps…"
- No footer link

```
[camp_livesearch placeholder="Find a camp..." show_all_link="/camp-search/"]
```
- Custom placeholder
- Footer "Browse all camps →" link to `/camp-search/`

**Recommended placement:** Elementor header — add a **Shortcode** widget to your header template, paste `[camp_livesearch]`.  
**For full-width:** add CSS class `cdbs-ls-full` to the Elementor widget wrapper, or use a custom width via Elementor's width control.

---

## 4. Shortcodes Guide Updated

**File:** `includes/Admin/class-shortcodes-guide.php`

### `[camp_page]` entry
- **Location:** Individual Camp Page section — first item, above `[camp_favourite_button]`
- **Border color:** `#497C5E` (green, matching camp theme)
- **Content:** Full description, auto-`camp_id` explanation, list of all included sections

### `[camp_livesearch]` entry
- **Location:** General / Utility Shortcodes section — first item, above `[cdbs_login_bar]`
- **Content:** Full description, parameter docs (`placeholder`, `show_all_link`), keyboard shortcuts, recommended placement

**Verified positions (grep):** `camp_livesearch` at line 432, `cdbs_login_bar` at line 448.

---

## Complete List of All Shortcodes (v3.7.0)

### Login / Auth
| Shortcode | File | Description |
|-----------|------|-------------|
| `[cdbs_login_bar]` | `creativedbs-camp-mgmt.php` | Header login status bar (guest + logged-in states, invisible to admins) |
| `[camp_login_page]` | `creativedbs-camp-mgmt.php` | Unified login page for all roles |
| `[camp_lost_password_page]` | `creativedbs-camp-mgmt.php` | Forgot password form |
| `[camp_set_password_page]` | `creativedbs-camp-mgmt.php` | Set/reset password (camp directors + parents, page: `/set-password/`) |

### Camp Director
| Shortcode | File | Description |
|-----------|------|-------------|
| `[camp_signup_form]` | `includes/Public/class-camp-signup-form.php` | Camp director registration |
| `[camp_dashboard]` | `includes/Public/class-camp-dashboard.php` | Camp director profile management |

### Parent / Camper
| Shortcode | File | Description |
|-----------|------|-------------|
| `[parent_registration_form]` | `includes/Public/class-parent-registration.php` | Parent + camper submission (pre-fills for logged-in users) |
| `[parent_dashboard]` | `includes/Public/class-parent-dashboard.php` | My Submissions / My Favourites / Messages (with count badges) |

### Camp Lists
| Shortcode | File | Description |
|-----------|------|-------------|
| `[camps_list]` | `includes/Public/class-camps-list.php` | Searchable/filterable camp list with pagination |
| `[camp_search]` | `includes/Public/class-camp-frontend.php` | Search form |

### Featured Camps
| Shortcode | File | Description |
|-----------|------|-------------|
| `[featured_camps]` | `includes/Public/class-featured-camps-frontend.php` | All featured camps |
| `[best_day_camps]` | same | Day camps |
| `[best_overnight_camps]` | same | Overnight camps |
| `[best_girls_camps]` | same | Girls camps |
| `[best_boys_camps]` | same | Boys camps |
| `[latest_camps]` | same | Most recently added |
| `[single_camp]` | same | Single camp card |

### Individual Camp Page (requires WP page with `camp_id` custom field)
| Shortcode | File | Description |
|-----------|------|-------------|
| `[camp_page]` | `includes/Public/class-camp-frontend.php` | **All-in-one** — full camp detail layout; auto-reads `camp_id`; all sections hidden when no data |
| `[camp_header]` | same | Combined header (logo + name + subtitle + contact bar + rating) |
| `[camp_logo]` | same | Logo only |
| `[camp_name]` | same | Name heading |
| `[camp_name_text]` | same | Name as plain text |
| `[camp_subtitle]` | same | Subtitle/tagline |
| `[camp_contact_bar]` | same | Contact bar (website, phone, email) |
| `[camp_rating]` | same | Star rating |
| `[camp_description]` | same | About description |
| `[camp_activities]` | same | Activities tags |
| `[camp_types]` | same | Camp types |
| `[camp_weeks]` | same | Program durations |
| `[camp_types_weeks]` | same | Types + weeks combined (backward compat) |
| `[camp_accommodations]` | same | Accommodations section |
| `[camp_faqs]` | same | FAQs section |
| `[camp_sessions]` | same | Session dates section |
| `[camp_additional_info]` | same | Additional info |
| `[camp_contact_info]` | same | Contact info block |
| `[camp_gallery]` | same | Photo gallery |
| `[camp_social_media]` | same | Social media links |
| `[camp_video]` | same | Video embed + Schema.org VideoObject markup |
| `[camp_favourite_button]` | `includes/Public/class-parent-camp-shortcodes.php` | Heart save button (auto-reads `camp_id`) |
| `[camp_contact_form]` | same | Message form (auto-reads `camp_id`; guest blur overlay) |

### General / Utility
| Shortcode | File | Description |
|-----------|------|-------------|
| `[camp_livesearch]` | `includes/Public/class-camp-frontend.php` | Nav/header autocomplete; 280ms debounce; logo circles; keyboard nav |
| `[cdbs_login_bar]` | `creativedbs-camp-mgmt.php` | (also listed under Login) |
| `[contact_form]` | (general contact) | General site contact form |
| `[camp_debug]` | `includes/Public/class-camp-frontend.php` | Dev debug info (should not be on production pages) |

---

## Database Schema (key table)

Table: `wp_camp_management`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `camp_name` | VARCHAR | |
| `approved` | TINYINT(1) DEFAULT 0 | 0=hidden, 1=visible on public site |
| `logo` | TEXT | URL string |
| `state` | VARCHAR | US state abbrev |
| `website` | VARCHAR | |
| `created_at` | DATETIME | |
| `last_edited` | DATETIME | |
| … | … | many more camp detail fields |

**`camp_id` custom field (WP postmeta):** Each WP page for a camp has a custom field `camp_id` with the integer matching `wp_camp_management.id`. All `[camp_*]` shortcodes on camp pages auto-detect this via `get_post_meta( $post->ID, 'camp_id', true )`.

---

## File Inventory (Modified Files This Session)

| File | Change |
|------|--------|
| `creativedbs-camp-mgmt.php` | Version bumped 3.6.0→3.7.0; Approved checkbox toggle JS fix; Full Edit form Approved checkbox added |
| `includes/Public/class-camp-frontend.php` | `[camp_livesearch]` shortcode + AJAX handler + always-on asset enqueue |
| `includes/Admin/class-shortcodes-guide.php` | `[camp_page]` and `[camp_livesearch]` documented |
| `assets/camp-frontend.css` | Typography system; divider margin `!important`; mobile padding/scaling |
| `assets/camp-livesearch.css` | NEW — livesearch widget styles |
| `assets/camp-livesearch.js` | NEW — livesearch vanilla JS |

---

## Key Technical Facts for Continuation

### Admin pages with camps list
- **Overview:** `?page=creativedbs-camp-mgmt`  (`self::SLUG`)
- **All Camps:** `?page=creativedbs-camp-mgmt-camps` (`self::SLUG . '-camps'`)
- **Edit form:** `?page=creativedbs-camp-mgmt-camps&action=edit&id=X` (Full Edit)

### Constants
```php
CDBS_CAMP_VERSION  = '3.7.0'         // defined at top of main plugin file
CREATIVE_DBS_CAMPMGMT_FILE           // path to main plugin file (used in plugin_dir_url() calls)
self::SLUG         = 'creativedbs-camp-mgmt'
self::table_camps() → $wpdb->prefix . 'camp_management'
```

### User roles (WP roles registered by plugin)
- `camp` — Camp Director → dashboard at `/user-dashboard/`
- `parent` — Parent → dashboard at `/parent-dashboard/`
- `administrator` — WP admin (plugin hides some UI from admins intentionally)

### Approved checkbox save pattern
```php
'approved' => isset($post_data['approved']) ? 1 : 0,
```
Checkbox must be present in POST to set `approved=1`. Missing = `approved=0`. Works correctly because checkboxes only submit when checked.

### Livesearch AJAX endpoint
- **Action:** `cdbs_livesearch`
- **Method:** POST
- **Params:** `action`, `nonce`, `q` (search query)
- **Min query length:** 2 characters
- **Returns:** `wp_send_json_success( [ {id, name, logo, state, url}, ... ] )` (max 10 results)

### CSS specificity notes
- `.cdbs-cp-divider` margin needs `!important` — Elementor stylesheet sets `hr { margin: 0 }` which overrides component styles
- Livesearch `.cdbs-ls-input` uses `!important` on border/outline/bg/box-shadow to prevent theme overrides of `input` elements

---

## Git History (recent)
```
d08c58e  Add [camp_livesearch], fix Approved checkbox, update shortcodes guide
3b5e7f9  Add [camp_page] combined shortcode, auto camp_id, typography polish
```

---

## Known Issues / Outstanding Items
- `[cdbs_login_bar]` mobile full-width: CSS `width:100% !important` is set in plugin — the Elementor **column** containing the widget also needs `width:100%` set in Elementor's responsive editor (Elementor UI config, not a code change)
- `CDBS: Featured camps migration completed` fires repeatedly in `debug.log` — pre-existing, low priority
- `[camp_livesearch]` logo URL: stored as raw URL string in `wp_camp_management.logo` — if a camp has no logo, the JS shows a letter placeholder (first char of camp name). This is correct behavior.
- Live search URL resolution depends on WP pages having `camp_id` postmeta set. Camps with no matching WP page will appear in results but have no link (`url: ''`).

---

## Next Possible Steps (No Commitment)
- Add pagination or "load more" to `[camp_livesearch]` dropdown (currently hard-capped at 10)
- Add camp type/state filter chips to livesearch dropdown
- Consider bumping to v3.8.0 for next major feature set
- Monitor `[camp_livesearch]` on live site for edge cases (e.g., very long camp names, special characters)
