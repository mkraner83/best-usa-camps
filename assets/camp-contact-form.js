/**
 * Contact Form Client-Side Validation
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		const form = document.getElementById('contactForm');
		
		if (!form) {
			return;
		}

		const emailField = document.getElementById('email');
		const emailConfirmField = document.getElementById('email_confirm');
		const messageField = document.getElementById('message');

		// Real-time word count validation
		function validateWordCount() {
			if (messageField) {
				const text = messageField.value.trim();
				const wordCount = text ? text.split(/\s+/).filter(word => word.length > 0).length : 0;
				
				// Find or create word count display
				let wordCountDisplay = messageField.parentElement.querySelector('.word-count-display');
				if (!wordCountDisplay) {
					wordCountDisplay = document.createElement('small');
					wordCountDisplay.className = 'word-count-display';
					wordCountDisplay.style.cssText = 'display: block; margin-top: 5px;';
					
					// Insert after textarea but before error message if exists
					const errorMsg = messageField.parentElement.querySelector('.error-message');
					if (errorMsg) {
						messageField.parentElement.insertBefore(wordCountDisplay, errorMsg);
					} else {
						const helperText = messageField.parentElement.querySelector('small');
						if (helperText && helperText !== wordCountDisplay) {
							helperText.insertAdjacentElement('afterend', wordCountDisplay);
						}
					}
				}
				
				wordCountDisplay.textContent = wordCount + ' / 200 words';
				wordCountDisplay.style.color = wordCount > 200 ? '#dc3545' : '#666';
				
				if (wordCount > 200) {
					messageField.classList.add('error');
				} else {
					messageField.classList.remove('error');
				}
				
				return wordCount <= 200;
			}
			return true;
		}

		// Real-time email match validation
		function validateEmailMatch() {
			if (emailField.value && emailConfirmField.value) {
				if (emailField.value !== emailConfirmField.value) {
					emailConfirmField.classList.add('error');
					
					// Remove existing error message if any
					const existingError = emailConfirmField.parentElement.querySelector('.error-message');
					if (existingError) {
						existingError.remove();
					}
					
					// Add error message
					const errorMsg = document.createElement('span');
					errorMsg.className = 'error-message';
					errorMsg.textContent = 'Email addresses must match';
					emailConfirmField.parentElement.appendChild(errorMsg);
				} else {
					emailConfirmField.classList.remove('error');
					const existingError = emailConfirmField.parentElement.querySelector('.error-message');
					if (existingError) {
						existingError.remove();
					}
				}
			}
		}

		if (emailField && emailConfirmField) {
			emailConfirmField.addEventListener('input', validateEmailMatch);
			emailField.addEventListener('input', validateEmailMatch);
		}
		
		if (messageField) {
			messageField.addEventListener('input', validateWordCount);
			// Initial validation on page load
			validateWordCount();
		}

		// Form submission validation
		form.addEventListener('submit', function(e) {
			// Word count check
			if (!validateWordCount()) {
				e.preventDefault();
				messageField.focus();
				return false;
			}
			
			// Email match check
			if (emailField.value !== emailConfirmField.value) {
				e.preventDefault();
				emailConfirmField.focus();
				validateEmailMatch();
				return false;
			}

			// Show loading state
			const submitBtn = form.querySelector('.contact-submit-btn');
			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.textContent = 'Sending...';
			}
		});
	});
})();
