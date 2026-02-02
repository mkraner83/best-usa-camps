# Version 3.4.3 Release Notes
**Date:** February 2, 2026

## Changes

### 1. Password Reset Page Auto-Redirect
**Issue:** After camp directors set their password (during signup or password reset), WordPress showed a white confirmation page with "Your password has been reset. Log in" message before redirecting.

**Solution:** Added `template_redirect` action hook to detect the password reset success page (`?password=changed`) and immediately redirect to the login page without showing the intermediate white page.

**Files Modified:**
- `includes/Public/class-camp-signup-form.php`
  - Added `redirect_password_reset_success()` method (lines 737-744)
  - Added `template_redirect` hook in constructor (line 23)

### 2. Auto-HTTPS for Director Dashboard URLs
**Issue:** The auto-https functionality (automatically prepending `https://` to URLs) was working on the signup form but not on the director dashboard for social media and video URLs.

**Solution:** Added blur event handlers to the dashboard JavaScript that automatically prepend `https://` to video and social media URL fields when users tab out, if the protocol is missing. This applies to both existing fields on page load and newly created social media fields.

**Files Modified:**
- `includes/Public/class-camp-dashboard.php`
  - Added `addHttpsToSocialField()` function (lines 2050-2056)
  - Added auto-https blur handler for video_url field (lines 2044-2049)
  - Applied auto-https to existing social media fields on page load (lines 2071-2073)
  - Applied auto-https to newly created social media fields (lines 2091-2095)

## Benefits

1. **Improved UX:** Directors no longer see the white WordPress confirmation page after setting their password - they're redirected immediately to login
2. **Consistency:** Auto-https now works consistently across both signup form and director dashboard
3. **Data Quality:** Ensures all social media and video URLs are properly formatted with https:// protocol

## Testing Recommendations

1. Test password reset flow:
   - Submit signup form
   - Set password on the password reset page
   - Verify you're redirected directly to login (no white page)

2. Test auto-https on dashboard:
   - Login to director dashboard
   - Enter video URL without `https://` (e.g., "youtube.com/watch?v=...")
   - Tab out of field - verify `https://` is added
   - Add social media links without `https://`
   - Tab out - verify `https://` is added to each field

## Version History
- v3.4.2: Updated welcome email with "Login to Director Dashboard" button
- v3.4.1: Logo restrictions (JPG/JPEG/PNG only)
- v3.4.0: Pre-submission popup workflow
- v3.3.3: Word count validation and auto-https on signup form
