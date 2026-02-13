/**
 * Camp Sign-Up Form JavaScript
 * Handles date picker, currency formatting, social media fields, and form validation
 */
document.addEventListener('DOMContentLoaded', function() {
	
	// Show success popup if flag is set (after form submission)
	if (typeof campSignupData !== 'undefined' && campSignupData.showSuccessPopup) {
		const popup = document.getElementById('camp-success-popup');
		if (popup) {
			// Update popup content for post-submission success
			const popupHeader = popup.querySelector('.camp-popup-header h2');
			const popupSubtext = popup.querySelector('.camp-popup-header p');
			const popupBtn = document.getElementById('close-popup-btn');
			
			if (popupHeader) {
				popupHeader.textContent = '🏕️ Thank You for Creating Your Camp Profile!';
			}
			if (popupSubtext) {
				popupSubtext.textContent = '✅ Your logo has been uploaded successfully!';
			}
			if (popupBtn) {
				popupBtn.textContent = 'Got It! Close This Message';
			}
			
			popup.style.display = 'flex';
		}
	}
	
	// Close popup button - submit form when clicked
	const closePopupBtn = document.getElementById('close-popup-btn');
	if (closePopupBtn) {
		closePopupBtn.addEventListener('click', function() {
			// Check if form is pending submission
			const form = document.querySelector('.camp-signup-form');
			const popup = document.getElementById('camp-success-popup');
			
			if (form && form.dataset.pendingSubmit === 'true') {
				// Before submission - user clicked to proceed with upload
				// Hide popup
				if (popup) {
					popup.style.display = 'none';
				}
				// Show loading overlay
				const loadingOverlay = document.getElementById('camp-loading-overlay');
				if (loadingOverlay) {
					loadingOverlay.style.display = 'flex';
				}
				// Mark as ready to submit (allow submit event to proceed)
				form.dataset.pendingSubmit = 'submitting';
				
				// Trigger actual form submission by clicking the submit button
				const submitBtn = document.getElementById('camp-submit-btn');
				if (submitBtn) {
					submitBtn.click();
				}
			} else {
				// After successful submission - redirect to password setup
				if (popup) {
					popup.style.display = 'none';
				}
				if (typeof campSignupData !== 'undefined' && campSignupData.passwordResetUrl) {
					window.location.href = campSignupData.passwordResetUrl;
				}
			}
		});
	}
	
	// Close popup when clicking overlay
	const popupOverlay = document.getElementById('camp-success-popup');
	if (popupOverlay) {
		popupOverlay.addEventListener('click', function(e) {
			if (e.target === this) {
				this.style.display = 'none';
				// Redirect to password setup page
				if (typeof campSignupData !== 'undefined' && campSignupData.passwordResetUrl) {
					window.location.href = campSignupData.passwordResetUrl;
				}
			}
		});
	}
	
	// Social Media Fields Management
	const socialContainer = document.getElementById('social-media-container');
	const addSocialBtn = document.getElementById('add-social-btn');
	let socialFieldCount = 1;
	const maxSocialFields = 5;
	
	if (addSocialBtn && socialContainer) {
		// Add new social media field
		addSocialBtn.addEventListener('click', function() {
			if (socialFieldCount >= maxSocialFields) {
				return;
			}
			
			socialFieldCount++;
			
			const newField = document.createElement('div');
			newField.className = 'social-media-field';
			newField.innerHTML = `
				<input type="url" name="social_media[]" placeholder="https://instagram.com/yourcamp" class="social-media-input">
				<button type="button" class="remove-social-btn">&times;</button>
			`;
			
			socialContainer.appendChild(newField);
			
			// Add https auto-formatting to the new field
			const newInput = newField.querySelector('.social-media-input');
			if (newInput) {
				addHttpsToSocialField(newInput);
			}
			
			// Update first field's remove button visibility
			updateRemoveButtons();
			
			// Disable add button if max reached
			if (socialFieldCount >= maxSocialFields) {
				addSocialBtn.disabled = true;
				addSocialBtn.textContent = 'Maximum 5 Links';
			}
			
			// Attach remove handler to new button
			const removeBtn = newField.querySelector('.remove-social-btn');
			removeBtn.addEventListener('click', function() {
				newField.remove();
				socialFieldCount--;
				addSocialBtn.disabled = false;
				addSocialBtn.textContent = '+ Add Another Social Link';
				updateRemoveButtons();
			});
		});
		
		// Function to update remove button visibility
		function updateRemoveButtons() {
			const fields = socialContainer.querySelectorAll('.social-media-field');
			fields.forEach((field, index) => {
				const removeBtn = field.querySelector('.remove-social-btn');
				if (removeBtn) {
					removeBtn.style.display = fields.length > 1 ? 'block' : 'none';
				}
			});
		}
		
		// Initial setup for first field
		updateRemoveButtons();
	}
	
	// Initialize date pickers
	const openingDayPicker = flatpickr("#opening_day", {
		dateFormat: "Y-m-d",
		minDate: "today",
		onChange: function(selectedDates, dateStr, instance) {
			// When opening day is selected, set closing day to same date
			if (selectedDates.length > 0) {
				document.getElementById('closing_day')._flatpickr.setDate(selectedDates[0]);
				document.getElementById('closing_day')._flatpickr.set('minDate', selectedDates[0]);
			}
		}
	});
	
	const closingDayPicker = flatpickr("#closing_day", {
		dateFormat: "Y-m-d",
		minDate: "today"
	});
	
	// Currency formatting for rate fields
	function formatCurrency(input) {
		let value = input.value.replace(/[^\d.]/g, '');
		if (value === '') return;
		
		// Ensure only one decimal point
		const parts = value.split('.');
		if (parts.length > 2) {
			value = parts[0] + '.' + parts.slice(1).join('');
		}
		
		// Limit to 2 decimal places
		if (parts[1] && parts[1].length > 2) {
			value = parseFloat(value).toFixed(2);
		}
		
		// Format as currency
		const numValue = parseFloat(value);
		if (!isNaN(numValue)) {
			input.value = '$' + numValue.toLocaleString('en-US', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 2
			});
		}
	}
	
	// Add currency formatting to rate fields
	const minPriceField = document.getElementById('minprice_2026');
	const maxPriceField = document.getElementById('maxprice_2026');
	
	if (minPriceField) {
		minPriceField.addEventListener('blur', function() {
			formatCurrency(this);
		});
		
		minPriceField.addEventListener('keyup', function(e) {
			if (e.key === 'Enter' || e.key === 'Tab') {
				formatCurrency(this);
			}
		});
	}
	
	if (maxPriceField) {
		maxPriceField.addEventListener('blur', function() {
			formatCurrency(this);
		});
		
		maxPriceField.addEventListener('keyup', function(e) {
			if (e.key === 'Enter' || e.key === 'Tab') {
				formatCurrency(this);
			}
		});
	}
	
	// Website URL validation and formatting
	const websiteField = document.getElementById('website');
	if (websiteField) {
		websiteField.addEventListener('blur', function() {
			let url = this.value.trim();
			if (url === '') return;
			
			// Add https:// if no protocol is specified
			if (!url.match(/^https?:\/\//i)) {
				url = 'https://' + url;
				this.value = url;
			}
			
			// Basic URL validation
			try {
				new URL(url);
			} catch (e) {
				this.setCustomValidity('Please enter a valid website URL');
				this.reportValidity();
			}
		});
		
		websiteField.addEventListener('input', function() {
			this.setCustomValidity(''); // Clear custom validity on input
		});
	}
	
	// Video URL auto-formatting
	const videoField = document.getElementById('video_url');
	if (videoField) {
		videoField.addEventListener('blur', function() {
			let url = this.value.trim();
			if (url === '') return;
			
			// Add https:// if no protocol is specified
			if (!url.match(/^https?:\/\//i)) {
				url = 'https://' + url;
				this.value = url;
			}
		});
	}
	
	// Social media fields auto-formatting
	function addHttpsToSocialField(field) {
		if (!field) return;
		field.addEventListener('blur', function() {
			let url = this.value.trim();
			if (url === '') return;
			
			// Add https:// if no protocol is specified
			if (!url.match(/^https?:\/\//i)) {
				url = 'https://' + url;
				this.value = url;
			}
		});
	}
	
	// Add https handler to existing social media fields
	const existingSocialFields = document.querySelectorAll('.social-media-input');
	existingSocialFields.forEach(function(field) {
		addHttpsToSocialField(field);
	});
	
	// Prevent form submission on Enter in activities field
	const activitiesField = document.getElementById('activities_field');
	if (activitiesField) {
		activitiesField.addEventListener('keydown', function(e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				this.blur(); // Remove focus from field
			}
		});
	}
	
	// Form submission - validate, clean data, then show popup or submit
	const form = document.querySelector('.camp-signup-form');
	if (form) {
		form.addEventListener('submit', function(e) {
			// 1. FIRST: Validate word count if about camp field exists
			if (aboutCampField && wordCountDisplay) {
				const words = countWords(aboutCampField.value);
				if (words < 180) {
					e.preventDefault();
					if (warningDisplay) {
						warningDisplay.textContent = '● Too few words (minimum 180)';
						warningDisplay.style.color = '#d63638';
						warningDisplay.style.display = 'inline';
					}
					alert('About Camp description must be at least 180 words. Current: ' + words + ' words');
					aboutCampField.focus();
					return false;
				}
				if (words > 300) {
					e.preventDefault();
					if (warningDisplay) {
						warningDisplay.textContent = '● Too many words (maximum 300)';
						warningDisplay.style.color = '#d63638';
						warningDisplay.style.display = 'inline';
					}
					alert('About Camp description must be 300 words or less. Current: ' + words + ' words');
					aboutCampField.focus();
					return false;
				}
			}
			
			// 2. Validate logo file size (5MB max)
			const logoInput = document.querySelector('input[name="logo"]');
			if (logoInput && logoInput.files.length > 0) {
				const fileSize = logoInput.files[0].size;
				const maxSize = 5 * 1024 * 1024; // 5MB in bytes
				if (fileSize > maxSize) {
					e.preventDefault();
					alert('Logo file size must be 5MB or less. Current file size: ' + (fileSize / (1024 * 1024)).toFixed(2) + 'MB');
					logoInput.focus();
					return false;
				}
			}
			
			// 3. Remove currency formatting before submission
			if (minPriceField && minPriceField.value) {
				minPriceField.value = minPriceField.value.replace(/[$,]/g, '');
			}
			if (maxPriceField && maxPriceField.value) {
				maxPriceField.value = maxPriceField.value.replace(/[$,]/g, '');
			}
			
			// 4. Auto-add https:// to URLs if missing
			const websiteField = document.getElementById('website');
			if (websiteField && websiteField.value && !websiteField.value.match(/^https?:\/\//i)) {
				websiteField.value = 'https://' + websiteField.value;
			}
			
			const videoField = document.getElementById('video_url');
			if (videoField && videoField.value && !videoField.value.match(/^https?:\/\//i)) {
				videoField.value = 'https://' + videoField.value;
			}
			
			const socialFields = document.querySelectorAll('.social-media-input');
			socialFields.forEach(function(field) {
				if (field.value && !field.value.match(/^https?:\/\//i)) {
					field.value = 'https://' + field.value;
				}
			});
			
			// 5. Check submission state
			if (form.dataset.pendingSubmit === 'submitting') {
				// Allow form to submit (loading overlay already showing)
				return true;
			} else {
				// Initial submission - show popup first (only if validation passed)
				e.preventDefault();
				
				// Mark form as pending submission
				form.dataset.pendingSubmit = 'true';
				
				// Show popup immediately
				const popup = document.getElementById('camp-success-popup');
				if (popup) {
					popup.style.display = 'flex';
				}
				
				return false;
			}
		});
	}

	// About Camp word count (180 minimum, 300 maximum)
	const aboutCampField = document.getElementById('about_camp_signup');
	const wordCountDisplay = document.getElementById('word-count-signup');
	const warningDisplay = document.getElementById('word-limit-warning-signup');
	
	if (aboutCampField && wordCountDisplay) {
		function countWords(text) {
			text = text.trim();
			if (text === '') return 0;
			return text.split(/\s+/).length;
		}
		
		function updateWordCount() {
			const text = aboutCampField.value;
			const words = countWords(text);
			wordCountDisplay.textContent = words;
			
			// Change color and show warnings based on word count
			if (words < 180) {
				wordCountDisplay.style.color = '#d63638';
				wordCountDisplay.style.fontWeight = '700';
				if (warningDisplay) {
					warningDisplay.textContent = '● Too few words (minimum 180)';
					warningDisplay.style.display = 'inline';
				}
			} else if (words > 300) {
				wordCountDisplay.style.color = '#d63638';
				wordCountDisplay.style.fontWeight = '700';
				if (warningDisplay) {
					warningDisplay.textContent = '● Too many words (maximum 300)';
					warningDisplay.style.display = 'inline';
				}
			} else if (words > 270) {
				wordCountDisplay.style.color = '#dba617';
				wordCountDisplay.style.fontWeight = '700';
				if (warningDisplay) {
					warningDisplay.textContent = '● Approaching maximum';
					warningDisplay.style.color = '#dba617';
					warningDisplay.style.display = 'inline';
				}
			} else {
				wordCountDisplay.style.color = '#28a745';
				wordCountDisplay.style.fontWeight = '600';
				if (warningDisplay) {
					warningDisplay.style.display = 'none';
				}
			}
		}
		
		aboutCampField.addEventListener('input', updateWordCount);
		aboutCampField.addEventListener('paste', function() {
			setTimeout(updateWordCount, 10);
		});
		
		// Initial count
		updateWordCount();
	}
});