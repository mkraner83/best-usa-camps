jQuery(document).ready(function($) {
	'use strict';

	console.log('Featured Camps Admin JS Loaded!');
	console.log('jQuery:', typeof jQuery);
	console.log('featuredCampsAdmin:', typeof featuredCampsAdmin !== 'undefined' ? featuredCampsAdmin : 'NOT DEFINED');

	var currentCategory = $('.featured-tab-content').data('category');
	console.log('Current category:', currentCategory);
	var selectedCamps = [];

	// Make camps list sortable
	$('.sortable-camps').sortable({
		handle: '.drag-handle',
		placeholder: 'camp-item-placeholder',
		update: function(event, ui) {
			saveCampOrder();
		}
	});

	// Save camp order via AJAX
	function saveCampOrder() {
		var campIds = [];
		$('.sortable-camps .camp-item').each(function() {
			campIds.push($(this).data('camp-id'));
		});

		$.ajax({
			url: featuredCampsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'update_featured_camps',
				nonce: featuredCampsAdmin.nonce,
				category: currentCategory,
				camp_ids: campIds,
				action_type: 'reorder'
			},
			success: function(response) {
				// Show brief success indicator
				showNotice('Order updated successfully', 'success');
			},
			error: function() {
				showNotice('Failed to update order', 'error');
			}
		});
	}

	// Open add camps modal
	$('.add-camp-btn').on('click', function() {
		selectedCamps = [];
		$('.featured-search-modal').fadeIn(200);
		$('.camp-search-input').focus();
		
		// Check if buttons exist after modal opens
		setTimeout(function() {
			console.log('Show All Camps button exists:', $('.show-all-camps-btn').length);
			console.log('Add Selected Camps button exists:', $('.add-selected-camps').length);
		}, 300);
	});

	// Close modal
	$('.modal-close, .modal-overlay').on('click', function() {
		$('.featured-search-modal').fadeOut(200);
	});

	// Prevent modal content clicks from closing modal
	$('.modal-content').on('click', function(e) {
		e.stopPropagation();
	});

	// Search camps
	$('.search-camps-btn').on('click', function(e) {
		console.log('Search button clicked');
		e.preventDefault();
		searchCamps();
	});

	// Enter key in search input
	$('.camp-search-input').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			searchCamps();
		}
	});

	// Show all camps button - using both direct and delegated handlers
	$('.show-all-camps-btn').on('click', function(e) {
		console.log('Show All Camps clicked (direct handler)');
		e.preventDefault();
		e.stopPropagation();
		showAllCamps();
	});
	
	$(document).on('click', '.show-all-camps-btn', function(e) {
		console.log('Show All Camps clicked (delegated handler)');
		console.log('Event:', e);
		console.log('Target:', e.target);
		e.preventDefault();
		e.stopPropagation();
		showAllCamps();
	});

	// Camp type filter buttons
	$('.filter-day-camps').on('click', function(e) {
		console.log('Filter Day Camps clicked');
		e.preventDefault();
		filterCampsByTypeId(1); // Day Camp ID
	});

	$('.filter-overnight-camps').on('click', function(e) {
		console.log('Filter Overnight Camps clicked');
		e.preventDefault();
		filterCampsByTypeId(3); // Overnight Camp ID
	});

	$('.filter-girls-camps').on('click', function(e) {
		console.log('Filter Girls Camps clicked');
		e.preventDefault();
		filterCampsByTypeId(20); // Girls Camp ID
	});

	$('.filter-boys-camps').on('click', function(e) {
		console.log('Filter Boys Camps clicked');
		e.preventDefault();
		filterCampsByTypeId(19); // Boys Camp ID
	});

	// Show all camps function
	function showAllCamps() {
		console.log('showAllCamps called, category:', currentCategory);
		$('.camp-search-input').val('');
		$('.camp-state-filter').val('');
		
		$('.search-results').html('<p class="search-loading">Loading all camps...</p>');

		$.ajax({
			url: featuredCampsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'search_camps_for_featured',
				nonce: featuredCampsAdmin.nonce,
				search: '',
				state: '',
				category: currentCategory
			},
			success: function(response) {
				console.log('Show All Camps response:', response);
				if (response.success && response.data.length > 0) {
					renderSearchResults(response.data);
				} else {
					$('.search-results').html('<p class="search-no-results">No camps found</p>');
				}
			},
			error: function() {
				console.log('Show All Camps error');
				$('.search-results').html('<p class="search-no-results">Error loading camps. Please try again.</p>');
			}
		});
	}

	// Filter camps by type
	function filterCampsByType(campType) {
		console.log('filterCampsByType called, type:', campType);
		$('.camp-search-input').val('');
		$('.camp-state-filter').val('');
		
		$('.search-results').html('<p class="search-loading">Loading ' + campType + 's...</p>');

		$.ajax({
			url: featuredCampsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'filter_camps_by_type',
				nonce: featuredCampsAdmin.nonce,
				camp_type: campType,
				category: currentCategory
			},
			success: function(response) {
				console.log('Filter camps response:', response);
				if (response.success && response.data.length > 0) {
					renderSearchResults(response.data);
				} else {
					$('.search-results').html('<p class="search-no-results">No ' + campType + 's found</p>');
				}
			},
			error: function() {
				console.log('Filter camps error');
				$('.search-results').html('<p class="search-no-results">Error loading camps. Please try again.</p>');
			}
		});
	}

	// Filter camps by type ID
	function filterCampsByTypeId(typeId) {
		console.log('filterCampsByTypeId called, type ID:', typeId);
		$('.camp-search-input').val('');
		$('.camp-state-filter').val('');
		
		var typeNames = {1: 'Day Camp', 3: 'Overnight Camp', 20: 'Girls Camp', 19: 'Boys Camp'};
		var typeName = typeNames[typeId] || 'Camp';
		
		$('.search-results').html('<p class="search-loading">Loading ' + typeName + 's...</p>');

		$.ajax({
			url: featuredCampsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'filter_camps_by_type_id',
				nonce: featuredCampsAdmin.nonce,
				type_id: typeId,
				category: currentCategory
			},
			success: function(response) {
				console.log('Filter camps response:', response);
				if (response.success && response.data.length > 0) {
					renderSearchResults(response.data);
				} else {
					$('.search-results').html('<p class="search-no-results">No ' + typeName + 's found</p>');
				}
			},
			error: function() {
				console.log('Filter camps error');
				$('.search-results').html('<p class="search-no-results">Error loading camps. Please try again.</p>');
			}
		});
	}

	// Search function
	function searchCamps() {
		var searchTerm = $('.camp-search-input').val().trim();
		var state = $('.camp-state-filter').val();

		if (!searchTerm && !state) {
			$('.search-results').html('<p class="search-prompt">Please enter a camp name or select a state to search</p>');
			return;
		}

		$('.search-results').html('<p class="search-loading">Searching camps...</p>');

		$.ajax({
			url: featuredCampsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'search_camps_for_featured',
				nonce: featuredCampsAdmin.nonce,
				search: searchTerm,
				state: state,
				category: currentCategory
			},
			success: function(response) {
				if (response.success && response.data.length > 0) {
					renderSearchResults(response.data);
				} else {
					$('.search-results').html('<p class="search-no-results">No camps found matching your search</p>');
				}
			},
			error: function() {
				$('.search-results').html('<p class="search-no-results">Error searching camps. Please try again.</p>');
			}
		});
	}

	// Render search results
	function renderSearchResults(camps) {
		var html = '<ul class="search-results-list">';
		
		camps.forEach(function(camp) {
			var logo = camp.logo || '';
			var photos = camp.photos ? camp.photos.split(',') : [];
			var image = logo || (photos[0] ? photos[0].trim() : '');
			var isSelected = camp.is_selected == 1;
			var imageHtml = image 
				? '<img src="' + image + '" alt="' + camp.camp_name + '" class="camp-thumb" />'
				: '<div class="camp-thumb no-image"><span class="dashicons dashicons-camera"></span></div>';

			html += '<li class="search-result-item' + (isSelected ? ' selected' : '') + '" data-camp-id="' + camp.id + '">';
			html += '<input type="checkbox" ' + (isSelected ? 'checked' : '') + ' />';
			html += '<div class="camp-item-content">';
			html += imageHtml;
			html += '<div class="camp-info">';
			html += '<strong>' + camp.camp_name + '</strong>';
			html += '<span class="camp-location">' + camp.city + ', ' + camp.state + '</span>';
			html += '</div></div></li>';
		});
		
		html += '</ul>';
		html += '<div style="margin-top: 20px; text-align: right;">';
		html += '<button type="button" class="button button-primary add-selected-camps">Add Selected Camps</button>';
		html += '</div>';
		
		$('.search-results').html(html);
	
	// Attach direct handler to the newly created button
	$('.add-selected-camps').off('click').on('click', function(e) {
		console.log('Add Selected Camps clicked (direct handler)');
		e.preventDefault();
		e.stopPropagation();
		
		var campIds = [];
		$('.search-result-item input[type="checkbox"]:checked').each(function() {
			campIds.push($(this).closest('.search-result-item').data('camp-id'));
		});

		console.log('Camp IDs:', campIds);

		if (campIds.length === 0) {
			showNotice('Please select at least one camp', 'warning');
			return;
		}

		// Add camps via AJAX
		$.ajax({
			url: featuredCampsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'update_featured_camps',
				nonce: featuredCampsAdmin.nonce,
				category: currentCategory,
				camp_ids: campIds,
				action_type: 'add'
			},
			success: function(response) {
				console.log('Add camps response:', response);
				if (response.success) {
					showNotice('Camps added successfully', 'success');
					// Reload page to show new camps
					setTimeout(function() {
						location.reload();
					}, 1000);
				}
			},
			error: function() {
				showNotice('Failed to add camps', 'error');
			}
		});
	});
}

// Toggle camp selection
$(document).on('click', '.search-result-item', function(e) {
	if (e.target.tagName !== 'INPUT') {
		var checkbox = $(this).find('input[type="checkbox"]');
		checkbox.prop('checked', !checkbox.prop('checked'));
	}
	
	if ($(this).find('input[type="checkbox"]').prop('checked')) {
		$(this).addClass('selected');
	} else {
		$(this).removeClass('selected');
	}
});

// Remove camp from featured list
$(document).on('click', '.remove-camp-btn', function(e) {
	e.preventDefault();
	e.stopPropagation();
	
	if (!confirm('Are you sure you want to remove this camp from this category?')) {
		return;
	}

	var campId = $(this).data('camp-id');
	var $item = $(this).closest('.camp-item');

	$.ajax({
		url: featuredCampsAdmin.ajaxUrl,
		type: 'POST',
		data: {
			action: 'update_featured_camps',
			nonce: featuredCampsAdmin.nonce,
			category: currentCategory,
			camp_ids: [campId],
			action_type: 'remove'
		},
		success: function(response) {
			if (response.success) {
				$item.fadeOut(300, function() {
						$(this).remove();
						
						// Show "no camps" message if list is empty
						if ($('.sortable-camps .camp-item').length === 0) {
							$('.selected-camps-list').html('<p class="no-camps">No camps selected yet. Click "Add Camps" to get started.</p>');
						}
					});
					showNotice('Camp removed successfully', 'success');
				}
			},
			error: function() {
				showNotice('Failed to remove camp', 'error');
			}
		});
	});

	// Show notice
	function showNotice(message, type) {
		var noticeClass = 'notice-' + type;
		var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
		
		$('.featured-camps-admin h1').after(notice);
		
		setTimeout(function() {
			notice.fadeOut(300, function() {
				$(this).remove();
			});
		}, 3000);
	}
});
