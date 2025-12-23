# Project State: Best USA Camps

**Date:** December 23, 2025

## Structure
- Plugin: `creativedbs-camp-mgmt.php`
- Theme templates: `theme-templates/`
- Includes: `includes/` (core classes, dashboard, integrations)
- Assets: `assets/` (CSS)
- Documentation: `README-INSTRUCTIONS.md`, `README.md`

## Main Features
- Camp dashboard for camp users (profile, uploads, activities)
- Custom login, lost password, and reset password pages (Astra theme)
- Storage tracking for photos (25MB) and logo (5MB)
- Ninja Forms integration for camp registration
- Password reset flow fully handled in custom pages
- Automatic redirect from WordPress login/reset to custom pages
- Email templates use custom reset URLs

## Status
- **Stable:** v2.5.0 (restore point)
- **Latest zip:** creativedbs-camp-mgmt-v2.5.0.zip
- **All plugin files reverted to restore point except for template login fixes**
- **Custom login templates working and redirecting correctly**
- **No lost password link in plugin error messages (per user request)**

## Next Steps
- Further customization of dashboard or templates as needed
- Monitor for user feedback and bug reports
