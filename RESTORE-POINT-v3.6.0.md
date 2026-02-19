# RESTORE POINT — v3.6.0
**Date:** February 19, 2026  
**Status:** ✅ STABLE — All features tested on live site  
**Zip:** `creativedbs-camp-mgmt-v3.6.0.zip` (266KB)  
**Git tag:** `v3.6.0`  
**Branch:** `main`

---

## What Was Built in This Session (v3.5.1 → v3.6.0)

### 1. Admin Dashboard Landing Page (`creativedbs-camp-mgmt.php`)

**New method `render_dashboard_page()`:**
- Stat bar with 5 tiles: Total Camps (+approved count), Parent Registrations, Favourites Saved, Messages (+unread count), Contact Submissions
- 5 activity cards with latest 5 rows each:
  - **Camp Registrations** — green dot=approved, grey=unapproved, date, state, "View All Camps →"
  - **Parent Registrations** — name, child name, email, relative time
  - **Parent Favourites** — camp name, saved by username
  - **Parent Messages** — amber dot=unread, camp name, message preview
  - **Contact Submissions** — separated below a "Contact Form" divider (not part of camp mgmt stat bar)
- Relative time helper `$ago()` closure (seconds/minutes/hours/days/date fallback)
- All inline CSS scoped to `.cdbs-dash`

**New method `render_main_page()`:**
- Dispatcher: if `?action=` param present → delegates to `render_admin_page()` (edit/delete passthrough)
- Otherwise renders the new dashboard landing page

**Updated `register_admin_menu()`:**
- Top-level callback changed from `render_admin_page` → `render_main_page`
- New "Overview" submenu (same slug, renders dashboard)
- New "All Camps" submenu (`SLUG-camps`, renders old camps list)
- All existing submenus (Camp Types, Durations/Weeks, Activities, Import/Export, Add Camp, Settings) preserved

**`keep_menu_expanded()` method:**
- Hooked to `admin_footer`
- JS snippet that forces the Camp Management menu open on all admin pages
- Menu position set to `2` (top of sidebar)

**Contact Submissions design separation:**
- Top stat bar: Contact Submissions tile REMOVED
- Contact Submissions card moved below the 4 camp management cards
- Separated by a styled pill divider: "Contact Form" label with HR lines

---

### 2. Admin Menu (`creativedbs-camp-mgmt.php`)
- `add_menu_page(..., position: 2)` — always at top of WP sidebar
- `keep_menu_expanded()`: JS forces `.wp-has-current-submenu`, `.wp-menu-open`, `.open` classes on every admin page load

---

### 3. Contact Form (`includes/Public/class-parent-camp-shortcodes.php`)
- Title (`<h3 class="pcs-contact-title">`) removed entirely (user adds heading via Elementor)
- Blank-page-after-submit bug FIXED: hidden `contact_redirect_url` field captures current page URL at render time; `handle_contact_form_submission()` reads it from POST and redirects there on success/failure
- Guest users see blurred form + overlay with Login / Create Free Account buttons
- Admin notification email is HTML styled with green gradient header

---

### 4. Parent Dashboard Messages Tab (`includes/Public/class-parent-dashboard.php`)
- Replaced Excel-style `<table>` with submission-style card layout matching "My Submissions" tab
- Card header: green gradient, camp name left, date right
- Full-width "MESSAGE" tile showing message body
- Footer badge: "Sent" (green) or "Unread" (amber/yellow `.pd-status-unread`)
- Unread cards get `.pd-card-unread` class (slightly different green gradient)

**Tab count badges:**
- Added `global $wpdb;` + 3 COUNT queries before tabs render
- Each tab shows a small circular count badge (`.pd-tab-count`)
- Grey pill on inactive tabs, green pill on active tab
- Badges hidden when count is 0

---

### 5. Parent Registration Form (`includes/Public/class-parent-registration.php`)
- **Pre-fill for logged-in users**: first name, last name, email from `wp_get_current_user()`; phone from `cdbs_parent_registrations` table (most recent submission)
- **Success message simplified**: "Thank you! Your request has been submitted. We will be in touch soon." — password reminder removed
- **Admin notification email**: Full HTML with green gradient header, info table (Parent, Email, Child rows), "View in Admin" button, `Content-Type: text/html` header

**Bug fixes:**
- Phone pre-fill queried wrong table `cdbs_parent_submissions` → fixed to `cdbs_parent_registrations`
- Transient key mismatch: handler used `md5($email)` but form renderer used `get_current_user_id()` → both now use `get_current_user_id()` for logged-in users
- Fallback redirect after registration: was `/parent-registration/` (404) → fixed to `/find-the-perfect-summer-camp/`

---

### 6. Camp Director Registration (`includes/Public/class-camp-signup-form.php`)
- **Set-password redirect bug FIXED**: `redirect_login_password_reset()` was sending to `/camp-set-password/` (non-existent page) → now sends to `/set-password/` (same page parents use)
- **Error fallback FIXED**: was `/camp-lost-password/` (non-existent) → now `/camp-login/`

---

### 7. Login Status Bar — New Shortcode `[cdbs_login_bar]` (`includes/Public/class-parent-camp-shortcodes.php`)

**When logged OUT:**
```
Log In  |  Register (Camp)  |  Register (Parent)
```
- Log In → `/camp-login/`
- Register (Camp) → `/get-listed-on-best-usa-summer-camps/`
- Register (Parent) → `/find-the-perfect-summer-camp/`

**When logged IN (camp role — Camp Director):**
```
● John  CAMP DIRECTOR  |  My Dashboard  |  Log Out
```
- My Dashboard → `/user-dashboard/`
- Log Out → `/camp-login/`

**When logged IN (parent role):**
```
● Tina  PARENT  |  My Dashboard  |  Log Out
```
- My Dashboard → `/parent-dashboard/`
- Log Out → `/camp-login/`

**Admins / other roles:** returns empty string (nothing shown)

**CSS (`.cdbs-login-bar`):**
- `display: inline-flex`, `gap: 5px`, `padding: 2px 15px`
- `border: 1px solid rgba(255,255,255,0.32)`, `border-radius: 999px`
- `background: transparent`
- Font: `Abel, sans-serif`, `12px`, `weight 400`, `letter-spacing: 0`
- All text/borders `#ffffff` (designed for dark header background)
- Green dot `#22c55e` for logged-in status
- Role pill: `rgba(255,255,255,0.15)` background, uppercase
- Mobile (≤767px): `width: 100% !important`, `border-radius: 8px`, `justify-content: center`

---

### 8. Shortcodes Guide Updated (`includes/Admin/class-shortcodes-guide.php`)
- `[cdbs_login_bar]` added to General / Utility section with full description, placement advice, and role routing info

---

## File Change Map

| File | Type | Changes |
|------|------|---------|
| `creativedbs-camp-mgmt.php` | Modified | Dashboard landing page, admin menu position, always-expanded JS, render_main_page dispatcher, Contact Submissions separation |
| `includes/Public/class-parent-camp-shortcodes.php` | New (untracked→tracked) | `[cdbs_login_bar]` shortcode, contact form title removed, redirect fix |
| `includes/Public/class-parent-dashboard.php` | New (untracked→tracked) | Messages card redesign, tab count badges, global $wpdb fix |
| `includes/Public/class-parent-registration.php` | New (untracked→tracked) | Pre-fill logged-in users, simplified success, HTML admin email, 3 bug fixes |
| `includes/Public/class-camp-signup-form.php` | Modified | /camp-set-password/ → /set-password/ redirect fix |
| `includes/Public/class-camp-dashboard.php` | Modified (minor) | Camp director dashboard (tracked as modified) |
| `includes/Admin/class-shortcodes-guide.php` | Modified | Added [cdbs_login_bar] entry |
| `includes/Admin/class-admin-parents.php` | New (untracked→tracked) | Admin parents management |
| `includes/migrations-parents.php` | New (untracked→tracked) | DB migration for parent tables |
| `assets/parent-camp-shortcodes.css` | New (untracked→tracked) | Contact form styles, guest overlay, login bar styles |
| `assets/parent-camp-shortcodes.js` | New (untracked→tracked) | Contact form JS |
| `assets/parent-dashboard.css` | New (untracked→tracked) | Parent dashboard styles including tab count badges |
| `assets/parent-dashboard.js` | New (untracked→tracked) | Parent dashboard JS |
| `assets/parent-registration-form.css` | New (untracked→tracked) | Parent registration form styles |
| `assets/parent-registration-form.js` | New (untracked→tracked) | Parent registration form JS |

---

## Database Tables Used

| Table | Purpose |
|-------|---------|
| `wp_camp_management` | Camp registrations (camp directors) |
| `wp_cdbs_parent_registrations` | Parent + camper submissions |
| `wp_cdbs_parent_favorites` | Parent saved camps |
| `wp_cdbs_messages` | Camp contact messages (parent → camp) |
| `wp_cdbs_contact_submissions` | General site contact form submissions |
| `wp_cdbs_parent_dynamic_options` | Admin-managed dropdown options |

---

## WordPress User Roles

| Role | Dashboard URL | Login URL |
|------|--------------|-----------|
| `camp` | `/user-dashboard/` | `/camp-login/` |
| `parent` | `/parent-dashboard/` | `/camp-login/` |
| `administrator` | `/wp-admin/` | `/camp-login/` |

---

## Key Page URLs (Live Site)

| Page | URL |
|------|-----|
| Login (all roles) | `/camp-login/` |
| Set/Reset Password | `/set-password/` |
| Camp Director Registration | `/get-listed-on-best-usa-summer-camps/` |
| Camp Director Dashboard | `/user-dashboard/` |
| Parent Registration | `/find-the-perfect-summer-camp/` |
| Parent Dashboard | `/parent-dashboard/` |
| Camp Search | `/find-the-perfect-american-summer-camp/` |

---

## Known Issues / Future Work
- Mobile full-width for `[cdbs_login_bar]`: CSS is correct (`width:100% !important`) but the Elementor column containing the widget also needs its width set to 100% on mobile in Elementor's responsive editor. This is an Elementor configuration step, not a code issue.
- `CDBS: Featured camps migration completed` fires repeatedly in debug.log — this is a pre-existing migration trigger issue (not introduced this session)
- `ERR_BLOCKED_BY_CLIENT` for cookie-notice plugin JS — browser ad blocker issue, not our plugin
