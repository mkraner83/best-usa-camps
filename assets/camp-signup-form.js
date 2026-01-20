/**
 * Camp Sign-Up Form JavaScript
 * Handles date picker, currency formatting, and form validation
 */
document.addEventListener('DOMContentLoaded', function() {
	
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
	
	// Email validation enhancement
	const emailField = document.getElementById('email');
	if (emailField) {
		emailField.addEventListener('blur', function() {
			const email = this.value.trim();
			if (email === '') return;
			
			// Enhanced email validation
			const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
			if (!emailPattern.test(email)) {
				this.setCustomValidity('Please enter a valid email address');
				this.reportValidity();
			} else {
				this.setCustomValidity('');
			}
		});
		
		emailField.addEventListener('input', function() {
			this.setCustomValidity(''); // Clear custom validity on input
		});
	}
	
	// Form submission - clean up currency values
	const form = document.querySelector('.camp-signup-form');
	if (form) {
		form.addEventListener('submit', function(e) {
			// Remove currency formatting before submission
			if (minPriceField && minPriceField.value) {
				minPriceField.value = minPriceField.value.replace(/[$,]/g, '');
			}
			if (maxPriceField && maxPriceField.value) {
				maxPriceField.value = maxPriceField.value.replace(/[$,]/g, '');
			}
		});
	}

	// About Camp word count (300 word limit)
	const aboutCampField = document.getElementById('about_camp_signup');
	const wordCountDisplay = document.getElementById('word-count-signup');
	
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
			
			// Change color based on word count
			if (words > 300) {
				wordCountDisplay.style.color = '#d63638';
				wordCountDisplay.parentElement.style.color = '#d63638';
			} else if (words > 270) {
				wordCountDisplay.style.color = '#dba617';
				wordCountDisplay.parentElement.style.color = '#dba617';
			} else {
				wordCountDisplay.style.color = '#666';
				wordCountDisplay.parentElement.style.color = '#666';
			}
		}
		
		aboutCampField.addEventListener('input', updateWordCount);
		aboutCampField.addEventListener('paste', function() {
			setTimeout(updateWordCount, 10);
		});
		
		// Initial count
		updateWordCount();
		
		// Prevent form submission if over 300 words
		if (form) {
			form.addEventListener('submit', function(e) {
				const words = countWords(aboutCampField.value);
				if (words > 300) {
					e.preventDefault();
					alert('About Camp description must be 300 words or less. Current: ' + words + ' words');
					aboutCampField.focus();
					return false;
				}
			});
		}
	}
});