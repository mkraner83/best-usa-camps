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
});