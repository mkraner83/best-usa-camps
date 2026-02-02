# RESTORE POINT v3.4.5
**Date:** February 2, 2026  
**Status:** ✅ STABLE - Custom Password Reset Pages with Theme Integration

## Summary

Version 3.4.5 implements custom password reset pages that integrate with the site's theme header and footer, replacing WordPress's default white pages. Includes styled HTML email templates and proper workflow integration.

## Changes from v3.4.2

### 1. Custom Password Reset Pages
- Created `[camp_password_reset]` shortcode for password reset request form (DELETED - using WP built-in)
- Created `[camp_set_password]` shortcode for password reset form (set new password)
- Redirects wp-login.php?action=rp/resetpass to custom themed page
- Uses WordPress's built-in lost password page (wp-login.php?action=lostpassword)

### 2. Auto-HTTPS on Director Dashboard
- Added blur event handlers to video_url field
- Added blur event handlers to social media fields (existing and newly created)
- Matches signup form auto-https functionality

### 3. HTML Email Template for Password Reset
- Converted plain text password reset email to HTML template
- Matches welcome email design with gradient header
- Includes green button, 24-hour expiration notice
- Professional layout with security message

### 4. Styling Matching Camp Login
- Custom CSS using "Annie Use Your Telescope" font for headings
- Exact color matching (#497C5E heading, #436e52 button)
- White card layout with proper padding and shadow
- Full responsive design

## Files Modified

### PHP Files
1. **includes/Public/class-camp-signup-form.php**
   - Added shortcodes: `camp_password_reset`, `camp_set_password`
   - Added methods: `render_password_reset_request()`, `render_password_reset_form()`
   - Added handlers: `handle_password_reset_request()`, `handle_password_reset()`
   - Added: `redirect_login_password_reset()` - redirects rp/resetpass to custom page
   - Added: `get_password_reset_email_template()` - HTML email template
   - Updated: `enqueue_styles()` - loads CSS for password reset shortcodes

2. **includes/Public/class-camp-dashboard.php**
   - Added auto-https blur handler for video_url field (lines 2044-2049)
   - Added `addHttpsToSocialField()` function (lines 2050-2056)
   - Applied auto-https to existing social media fields on page load (lines 2071-2073)
   - Applied auto-https to newly created social media fields (lines 2091-2095)

### CSS Files
3. **assets/camp-signup-form.css**
   - Added comprehensive password reset page styles
   - `.camp-login-form` wrapper styling
   - `.camp-login-form h2` - Annie Use Your Telescope font, 32px, #497C5E
   - `.camp-login-form input.input` - 12px padding, #ddd border
   - `.camp-login-form .button.button-primary` - #436e52 background, uppercase, 20px
   - `.camp-message` success/error styling
   - `.camp-login-links` styling
   - Full responsive breakpoints

### Plugin Core
4. **creativedbs-camp-mgmt.php**
   - Updated version to 3.4.5

## WordPress Setup Required

### Create One Page (other page deleted)

**Page: Set Password**
- Title: Set Password
- Slug: `set-password`
- Content: `[camp_set_password]`
- Status: Published

### Delete This Page
- Delete: "Reset Password" page (if created) - we use WordPress's built-in lost password page instead

## Password Reset Workflow

### Current Flow (v3.4.5)
1. User clicks "Lost your password?" on Camp Login page
2. → WordPress's built-in wp-login.php?action=lostpassword (styled by theme)
3. User enters email and receives reset link
4. Click email link → **Redirected to /set-password/** (custom themed page)
5. User enters new password → Redirects to login page
6. Success!

### Email Flow
1. Lost Password → Plain text email from WordPress (can be customized with theme)
2. Password Reset Request → **HTML email template** with green button

## Technical Details

### Shortcodes
- `[camp_set_password]` - Password reset form with key validation
- ~~`[camp_password_reset]`~~ - (Not used, deleted this page)

### CSS Classes Used
- `.camp-login-form` - Main wrapper (matches login page)
- `.login-username`, `.login-password` - Field wrappers
- `.login-submit` - Submit button wrapper
- `.camp-login-links` - Links below form
- `.camp-message` - Success/error messages

### Hooks & Filters
- `login_init` → `redirect_login_password_reset()` - Redirects rp/resetpass actions
- `password_reset_redirect` → `redirect_to_login_after_reset()` - Redirects to login after reset
- `template_redirect` → `redirect_password_reset_success()` - Redirects password=changed page

### Auto-HTTPS Implementation
- Video URL: Blur event on #video_url
- Social Media: Blur event on all .social-media-input fields
- Applied to existing fields on page load
- Applied to new fields when created dynamically

## Testing Checklist

- [x] Lost password link works from login page
- [x] WordPress built-in lost password page displays correctly
- [x] Email received with reset link
- [x] Reset link redirects to /set-password/ with key and login params
- [x] Set password form validates passwords match
- [x] Set password form validates 8+ character minimum
- [x] Invalid/expired keys show error message
- [x] Successful password reset redirects to login
- [x] HTML email template displays correctly
- [x] Styling matches Camp Login page
- [x] Auto-https works on dashboard video field
- [x] Auto-https works on dashboard social media fields
- [x] Responsive design works on mobile

## Known Issues
None

## Database Changes
None - uses WordPress core password reset functionality

## Backwards Compatibility
✅ Fully compatible with previous versions
✅ No database migrations required
✅ Can safely upgrade/downgrade

## Dependencies
- WordPress core password reset functions
- Custom fonts: "Annie Use Your Telescope" (from theme)
- Email delivery (recommend SMTP plugin for production)

## Performance Impact
Minimal - only loads CSS on pages with shortcodes

## Security Notes
- Uses WordPress's secure password reset key system
- Keys expire in 24 hours (WordPress default)
- Nonce verification on all form submissions
- Validates reset key before allowing password change
- Sanitizes all user inputs

## Git Tag
```bash
git add .
git commit -m "v3.4.5: Custom password reset pages with theme integration"
git tag -a v3.4.5 -m "Restore point: Custom password reset pages"
git push origin main --tags
```

## Rollback Instructions
If issues arise, rollback to v3.4.2:
```bash
git checkout v3.4.2
```

Or restore from previous stable version:
- RESTORE-POINT-v3.3.3.md
- RESTORE-POINT-v3.3.2.md

## File Manifest
```
creativedbs-camp-mgmt-v3.4.5.zip (212KB)
├── creativedbs-camp-mgmt.php (main plugin file)
├── includes/
│   └── Public/
│       ├── class-camp-signup-form.php (password reset logic)
│       └── class-camp-dashboard.php (auto-https for dashboard)
└── assets/
    └── camp-signup-form.css (password reset styling)
```

## Notes for Future Development

### Potential Improvements
1. Add password strength meter
2. Add reCAPTCHA to password reset request form
3. Send HTML email for lost password request (currently plain text from WP)
4. Add option to customize email templates from admin
5. Add logging for password reset attempts
6. Consider rate limiting password reset requests

### Code Quality
- All code follows WordPress coding standards
- Proper escaping and sanitization
- Nonce verification on all forms
- Secure password reset key handling
- Responsive CSS with mobile-first approach

## Support Information

### Troubleshooting

**Problem:** CSS not loading on password reset page
**Solution:** Hard refresh (Cmd+Shift+R) or clear cache

**Problem:** Reset link shows "invalid or expired"
**Solution:** Request new reset link (keys expire in 24 hours)

**Problem:** Email not received
**Solution:** Check spam folder, install SMTP plugin

**Problem:** 404 error on /set-password/
**Solution:** Go to Settings → Permalinks → Save Changes

### Contact
For issues or questions, review this restore point document and previous versions for context.

---
**End of Restore Point v3.4.5**
