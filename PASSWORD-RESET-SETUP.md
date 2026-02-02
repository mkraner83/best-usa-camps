# Custom Password Reset Pages Setup Guide
**Version 3.4.4**

## What's New

Custom password reset pages that use your theme's header and footer, replacing WordPress's default white pages.

## Setup Instructions

### 1. Create Two New Pages in WordPress

#### Page 1: Reset Password (Request Link)
- **Title:** Reset Password
- **Slug:** `reset-password`
- **Content:** Add this shortcode:
```
[camp_password_reset]
```
- **Publish** the page

#### Page 2: Set Password (Reset Form)
- **Title:** Set Password  
- **Slug:** `set-password`
- **Content:** Add this shortcode:
```
[camp_set_password]
```
- **Publish** the page

### 2. URLs

After setup, these will be your password reset pages:
- **Request Reset Link:** https://bestusacamps.com/reset-password/
- **Set New Password:** https://bestusacamps.com/set-password/

### 3. How It Works

**Old Flow (White WordPress Pages):**
1. User clicks "Forgot Password" → White wp-login.php page
2. User receives email → Clicks link → White wp-login.php page
3. User sets password → White confirmation page

**New Flow (With Your Theme):**
1. User clicks "Forgot Password" → YOUR themed /reset-password/ page
2. User receives email → Clicks link → YOUR themed /set-password/ page
3. User sets password → Redirects to login page

### 4. Automatic Redirects

The plugin automatically redirects these WordPress URLs:
- `wp-login.php?action=lostpassword` → `/reset-password/`
- `wp-login.php?action=rp` → `/set-password/`
- `wp-login.php?action=resetpass` → `/set-password/`

### 5. Update Login Page Links

If you have a custom login page, update the "Forgot Password" link to:
```html
<a href="/reset-password/">Forgot Password?</a>
```

## Features

✅ Full theme header/footer integration
✅ Branded styling matching signup form
✅ Email validation
✅ Password strength requirements (8+ characters)
✅ Password confirmation matching
✅ Secure reset key validation
✅ Automatic expiration (24 hours)
✅ User-friendly error messages

## Testing

1. Go to `/reset-password/`
2. Enter a valid camp director email
3. Check email for reset link
4. Click link → Should go to `/set-password/`
5. Set new password
6. Should redirect to login page

## Styling

The password reset pages use the same green theme (#497C5E) as your signup form and are fully responsive.

## Email Template

The password reset email includes:
- Personalized greeting
- Reset link
- 24-hour expiration notice
- Instructions

You can customize the email in:
`includes/Public/class-camp-signup-form.php` → `handle_password_reset_request()` method (around line 865)

## Troubleshooting

**Problem:** Pages show 404 errors
**Solution:** Go to Settings → Permalinks and click "Save Changes" to flush rewrite rules

**Problem:** Redirects not working
**Solution:** Clear your browser cache and try in incognito mode

**Problem:** Email not received
**Solution:** Check spam folder or configure SMTP plugin for better email delivery
