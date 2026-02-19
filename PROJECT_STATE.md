# Project State: Best USA Camps

**Date:** February 20, 2026  
**Version:** 3.7.0 (Production Ready)  
**Git commit:** `d08c58e` (version bump pushed after)  
**Branch:** `main`  
**Restore Point:** `RESTORE-POINT-v3.7.0.md` (full change log for this session)  
**Previous Restore Point:** `RESTORE-POINT-v3.6.0.md` (February 19, 2026)  
**Zip:** `creativedbs-camp-mgmt-v3.7.0.zip`

## Structure
- Plugin: `creativedbs-camp-mgmt.php` (~2330 lines)
- Theme templates: `theme-templates/`
- Includes: `includes/` (core classes, dashboard, integrations)
- Assets: `assets/` (CSS, JavaScript)
- Documentation: .md files in root
- Archive: `_archive/` (historical files organized by type)

## Active Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[cdbs_login_bar]` | Header login/logout status bar. Shows Log In + Register links to guests; name, role, dashboard link, logout to logged-in users |
| `[camp_login_page]` | Unified login for all roles |
| `[camp_lost_password_page]` | Forgot password form |
| `[camp_set_password_page]` | Set/reset password (used by both camp directors and parents, URL: `/set-password/`) |
| `[camp_signup_form]` | Camp director registration |
| `[camp_dashboard]` | Camp director profile management dashboard |
| `[parent_registration_form]` | Parent + camper submission form (pre-fills for logged-in users) |
| `[parent_dashboard]` | Parent dashboard: My Submissions, My Favourites, Messages tabs with count badges |
| `[camps_list]` | Searchable/filterable camp list |
| `[featured_camps]`, `[best_day_camps]`, `[best_overnight_camps]`, `[best_girls_camps]`, `[best_boys_camps]`, `[latest_camps]`, `[single_camp]` | Featured camp display shortcodes |
| `[camp_logo]`, `[camp_name]`, `[camp_name_text]`, `[camp_subtitle]`, `[camp_rating]`, `[camp_contact_bar]`, `[camp_contact_info]`, `[camp_description]`, `[camp_activities]`, `[camp_types_weeks]`, `[camp_accommodations]`, `[camp_faqs]`, `[camp_sessions]`, `[camp_additional_info]`, `[camp_social_media]`, `[camp_video]` | Individual camp page shortcodes |
| `[contact_form]` | General site contact form |
| `[camp_favourite_button]` | Heart save button for parents on camp pages (auto-reads `camp_id` from WP post meta) |
| `[camp_contact_form]` | Message form on camp pages (auto-reads `camp_id` from WP post meta) |
| `[camp_page]` | All-in-one camp page layout: info tiles, weeks, types, activities, social, contact form, about, contact info, video, sessions, accommodations, FAQs, gallery — all sections hidden when no data |
| `[camp_livesearch]` | Nav/header autocomplete search: 280ms debounce, logo circles, keyboard navigation (↑↓ Enter Escape), approved-only results; attrs: `placeholder`, `show_all_link` |

## User Roles & Dashboards

| Role | Dashboard | Registration |
|------|-----------|-------------|
| `camp` (Camp Director) | `/user-dashboard/` | `/get-listed-on-best-usa-summer-camps/` |
| `parent` | `/parent-dashboard/` | `/find-the-perfect-summer-camp/` |
| `administrator` | `/wp-admin/` | — |
| All roles login | `/camp-login/` | — |
| Password reset | `/set-password/` | — |

## Admin Interface (Camp Management plugin menu)

- **Position:** 2 (top of WP sidebar), always expanded via `keep_menu_expanded()` JS
- **Overview (landing page):** Stat bar (Total Camps, Parent Registrations, Favourites, Messages, Contact Submissions) + 5 activity cards, Contact Form section separated by divider
- **All Camps:** Searchable camp list, edit/approve/delete
- **Camp Types, Durations/Weeks, Activities:** Option management
- **Import/Export:** CSV import/export
- **Add Camp:** Manual camp entry
- **📋 Shortcodes:** Full shortcodes guide (all shortcodes documented with copy-to-clipboard)
- **Settings:** Plugin settings

## Key Features Added in v3.7.0 (February 20, 2026)

1. **`[camp_livesearch]` shortcode** — autocomplete search for nav/header; vanilla JS (no jQuery); 280ms debounce; logo thumbnails; keyboard nav; approved-only; max 10 results; supports multiple widgets per page
2. **Admin Approved checkbox fix #1** — toggle JS scope extended to cover both Overview + All Camps admin pages
3. **Admin Approved checkbox fix #2** — Full Edit form (`?action=edit`) was missing the Approved checkbox entirely; every save was resetting `approved=0`; fixed by adding checkbox as first row of edit form
4. **`[camp_page]` typography polish** — Abel font (normal) + Amaranth font (bold spans); main title 40px, section headings 26px; `.cdbs-cp-divider` margin needs `!important` to beat Elementor; mobile scaling at ≤1024px and ≤480px
5. **Shortcodes guide updated** — `[camp_page]` and `[camp_livesearch]` fully documented in WP admin shortcodes guide

## Key Features Added in v3.6.0 (February 19, 2026)

1. **Admin dashboard landing page** — stat bar + 5 activity cards
2. **[cdbs_login_bar] shortcode** — header login status bar (guest + logged-in states)
3. **Parent dashboard tab count badges** — circular count pills on each tab
4. **Parent dashboard messages tab** — redesigned as cards (matching submissions style)
5. **Parent registration pre-fill** — fields auto-filled for logged-in users
6. **Camp director set-password redirect fixed** — now uses `/set-password/` (same as parents)
7. **Contact form redirect fix** — no more blank page after submit
8. **HTML admin notification emails** — both contact form and parent registration
9. **`[camp_page]` combined shortcode** — one shortcode replaces all Elementor layout sections on camp pages; auto-reads `camp_id` from WP post meta; all sections conditionally hidden when no data
10. **`[camp_favourite_button]` + `[camp_contact_form]` auto camp_id** — both shortcodes now auto-read `camp_id` from WP post meta, no manual attribute needed
11. **`[camp_page]` typography** — Abel font (normal) + Amaranth font (bold spans); main title 40px, section headings 26px; proportionally scaled on mobile

## Known Issues / Outstanding Items
- `[cdbs_login_bar]` mobile full-width: CSS is set (`width:100% !important`) — Elementor column containing the widget also needs width=100% set in Elementor's responsive editor (Elementor config, not code)
- `CDBS: Featured camps migration completed` fires repeatedly in debug.log — pre-existing migration trigger issue

## Structure
- Plugin: `creativedbs-camp-mgmt.php` (1973 lines)
- Theme templates: `theme-templates/`
- Includes: `includes/` (core classes, dashboard, integrations)
- Assets: `assets/` (CSS, JavaScript)
- Documentation: 12 essential .md files in root
- Archive: `_archive/` (33 historical files organized by type)

## Main Features

### Admin Interface
- Modern responsive card grid (280px min width, auto-fill columns)
- Real-time search filter (camp name, state)
- Sort options (Newest, Oldest, A-Z, Z-A)
- Dynamic camp counts (approved/total)
- Show Inactive Camps toggle filter
- Conditional edit flow (shows edit form OR camps list)

### Camp Dashboard
- Camp director profile management
- Photo uploads (25MB limit) with thumbnail gallery
- Logo upload (5MB limit) with circular display
- Accommodations, FAQs, Sessions AJAX modules
- Word count validation (180-300 words) with real-time feedback
- Custom login, lost password, and reset password pages (Astra theme)

### Frontend Display
- 15 shortcodes for camp information display
- Pagination system (20 camps per page with smart ellipsis)
- Rating system (0-5 stars, admin-controlled)
- Responsive card layouts with hover effects

### Notification System
- Daily 8 PM CET automated emails for camp updates
- Queue-based system (`wp_camp_notification_queue` table)
- Groups notifications by camp (one email per camp)
- Debug tool with manual send controls
- Settings page integration with auto-folder detection

### Data Integrity
- Fixed escaping bugs (`wp_unslash($_POST)` pattern)
- Clean storage and display of apostrophes, quotes, special characters
- Server-side and client-side validation
- Prepared statement database queries

## Status
- **Current:** v3.7.0 (Production Ready)
- **Latest zip:** `creativedbs-camp-mgmt-v3.7.0.zip`
- **Restore Point:** `RESTORE-POINT-v3.7.0.md` (comprehensive documentation)
- **Git branch:** `main` — commit `d08c58e`
- **All user-requested features:** ✅ Implemented and tested
- **All critical bugs:** ✅ Fixed and validated

## Recent Changes (v3.5.0)
- ✅ Admin UI redesigned from table to card grid
- ✅ Fixed all double-escaping issues (apostrophes, quotes, backslashes)
- ✅ Implemented camps_list pagination (20/page)
- ✅ Added daily notification system with debug tools
- ✅ Reduced word count minimum to 180 words
- ✅ Added inactive camps filter toggle
- ✅ Cleaned and organized project files

## Technical Debt
- None critical
- Consider server cron for 8 PM notifications (vs WP-Cron)
- Consider server-side pagination if camp count exceeds 500+

## Next Steps
- Monitor notification system reliability in production
- Gather user feedback on new card grid interface
- Consider future enhancements:
  - Inline quick-edit functionality
  - Bulk actions for camps
  - Advanced filter options
  - Export to CSV feature

## Documentation
- **Restore Point:** RESTORE-POINT-v3.5.0.md
- **Readme:** README.md
- **Build Instructions:** BUILD-INSTRUCTIONS.md
- **Shortcodes:** SHORTCODES-GUIDE.md
- **Notifications:** DAILY-NOTIFICATIONS-SUMMARY.md
- **Import/Export:** IMPORT-EXPORT-DOCUMENTATION.md
- **Cleanup Summary:** CLEANUP-SUMMARY.txt
- **Archive Contents:** _archive/README.md
