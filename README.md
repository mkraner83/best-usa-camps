# Best USA Camps

WordPress plugin and theme templates for managing summer camp profiles, user dashboards, and custom login flows.

## Current Version: 2.8.3

## Key Features
- **Camp Management Dashboard** - Camp directors can manage their profiles, photos, accommodations, FAQs, and sessions
- **Frontend Shortcodes** - 12 shortcodes for displaying camp data on public pages (see SHORTCODES-GUIDE.md)
- **Rating System** - Admin-controlled star ratings (0-5) displayed on frontend
- **Custom Login/Password Pages** - Astra theme templates for camp director authentication
- **AJAX Module System** - Real-time updates for accommodations, FAQs, and sessions
- **Ninja Forms Integration** - Camp registration and signup forms
- **Storage Tracking** - Photo and logo upload management

## Frontend Shortcodes (15 Total)
Display camp information on public pages using shortcodes:

**Header & Identity:**
- `[camp_logo]` - Camp logo with size options
- `[camp_name]` - SEO-optimized H1 heading
- `[camp_name_text]` - **NEW** Plain text name for SEO titles/meta
- `[camp_subtitle]` - Auto-generated from types + location
- `[camp_rating]` - Star rating display (0-5)

**Contact Information:**
- `[camp_contact_bar]` - Horizontal contact bar with Google Maps links
- `[camp_contact_info]` - **NEW** Vertical sidebar contact display

**Content Sections:**
- `[camp_description]` - About camp (220-word limit)
- `[camp_activities]` - Activity tags
- `[camp_types_weeks]` - Camp types and weeks
- `[camp_accommodations]` - **ENHANCED** Dynamic columns, gradient design, 90-word limit
- `[camp_faqs]` - **ENHANCED** Green accordion toggles, all closed by default
- `[camp_sessions]` - **ENHANCED** Dynamic columns, gradient design, 90-word limit
- `[camp_additional_info]` - FontAwesome icons, green cards (directors, dates, rates)

**Full Documentation:** [SHORTCODES-GUIDE.md](./SHORTCODES-GUIDE.md)

## Quick Start
See [README-INSTRUCTIONS.md](./README-INSTRUCTIONS.md) for installation, setup, and usage details.

## Latest Updates (v2.8.3 - January 15, 2026)
- ✅ **NEW:** [camp_contact_info] sidebar shortcode with Google Maps integration
- ✅ **NEW:** [camp_name_text] for SEO titles and meta descriptions
- ✅ **ENHANCED:** Sessions & accommodations with dynamic columns (1-3 based on count)
- ✅ **ENHANCED:** Gradient backgrounds (#F5F5F5 → #D0D0D0) with 2px green borders
- ✅ **ENHANCED:** FAQ toggles now green (#497C5E) with all closed by default
- ✅ **ENHANCED:** FontAwesome 6.5.1 icons on info cards with CDN fallback
- ✅ Word limits: 220 words (camp description), 90 words (sessions/cabins)
- ✅ Real-time word counters with visual warnings in admin
- ✅ Google Maps clickable addresses in contact shortcodes
- ✅ Rating persistence fix (directors can't overwrite admin ratings)
- ✅ Responsive mobile design (grids stack to single column)
- ✅ Amaranth font for headers (26px, 600 weight)
- ✅ 100% width FAQ containers

## Requirements
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
