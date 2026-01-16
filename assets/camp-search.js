/**
 * Camp Search JavaScript
 * Handles AJAX search, filtering, sorting, infinite scroll, and URL parameters
 */
(function($) {
    'use strict';

    // Search state
    let currentPage = 1;
    let isLoading = false;
    let searchTimeout = null;
    let currentFilters = {};
    let totalResults = 0;
    let hasMore = false;

    // Initialize on document ready
    $(document).ready(function() {
        // Check if campSearchData is available
        if (typeof campSearchData === 'undefined') {
            console.error('Camp search data not loaded');
            $('#search-loading').hide();
            $('#no-results').show();
            return;
        }

        initializeSearch();
        initializeFilters();
        initializePriceSliders();
        initializeURLParameters();
        performSearch(); // Load initial results
    });

    /**
     * Initialize search functionality
     */
    function initializeSearch() {
        // Search input with debounce (300ms)
        $('#camp-search-input').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                resetAndSearch();
            }, 300);
        });

        // Search button
        $('#camp-search-btn').on('click', function() {
            resetAndSearch();
        });

        // Clear button
        $('#camp-clear-btn').on('click', function() {
            clearAllFilters();
            resetAndSearch();
        });

        // Sort dropdown
        $('#sort-by').on('change', function() {
            resetAndSearch();
        });

        // Load more button
        $('#load-more-btn').on('click', function() {
            currentPage++;
            performSearch(true);
        });

        // Enter key on search input
        $('#camp-search-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                resetAndSearch();
            }
        });
    }

    /**
     * Initialize filter controls
     */
    function initializeFilters() {
        // Auto-apply on filter changes with debounce
        let filterTimeout = null;
        
        // State dropdown
        $('#filter-state').on('change', function() {
            resetAndSearch();
        });
        
        // Date filters
        $('#filter-start-date, #filter-end-date').on('change', function() {
            resetAndSearch();
        });
        
        // Price sliders - debounce to avoid too many requests
        $('#filter-min-price, #filter-max-price').on('change', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() {
                resetAndSearch();
            }, 500);
        });
        
        // Checkboxes (camp types, weeks, activities)
        $('input[name="camp_type"], input[name="weeks"], input[name="activities"]').on('change', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() {
                resetAndSearch();
            }, 300);
        });

        // Mobile filter toggle
        $('.filters-toggle-mobile').on('click', function() {
            $('.filters-container').toggleClass('active');
        });
    }

    /**
     * Initialize price range sliders
     */
    function initializePriceSliders() {
        const minSlider = $('#filter-min-price');
        const maxSlider = $('#filter-max-price');
        const minDisplay = $('#min-price-display');
        const maxDisplay = $('#max-price-display');

        minSlider.on('input', function() {
            let minVal = parseInt($(this).val());
            let maxVal = parseInt(maxSlider.val());

            if (minVal > maxVal - 100) {
                minVal = maxVal - 100;
                $(this).val(minVal);
            }

            minDisplay.text(formatNumber(minVal));
        });

        maxSlider.on('input', function() {
            let maxVal = parseInt($(this).val());
            let minVal = parseInt(minSlider.val());

            if (maxVal < minVal + 100) {
                maxVal = minVal + 100;
                $(this).val(maxVal);
            }

            maxDisplay.text(formatNumber(maxVal));
        });
    }

    /**
     * Initialize from URL parameters
     */
    function initializeURLParameters() {
        const urlParams = new URLSearchParams(window.location.search);

        // Set search input
        if (urlParams.has('search')) {
            $('#camp-search-input').val(urlParams.get('search'));
        }

        // Set state filter
        if (urlParams.has('state')) {
            $('#filter-state').val(urlParams.get('state'));
        }

        // Set date filters
        if (urlParams.has('start_date')) {
            $('#filter-start-date').val(urlParams.get('start_date'));
        }
        if (urlParams.has('end_date')) {
            $('#filter-end-date').val(urlParams.get('end_date'));
        }

        // Set price filters
        if (urlParams.has('min_price')) {
            $('#filter-min-price').val(urlParams.get('min_price')).trigger('input');
        }
        if (urlParams.has('max_price')) {
            $('#filter-max-price').val(urlParams.get('max_price')).trigger('input');
        }

        // Set camp types
        if (urlParams.has('camp_types')) {
            const types = urlParams.get('camp_types').split(',');
            types.forEach(type => {
                $('input[name="camp_type"][value="' + type + '"]').prop('checked', true);
            });
        }

        // Set weeks
        if (urlParams.has('weeks')) {
            const weeks = urlParams.get('weeks').split(',');
            weeks.forEach(week => {
                $('input[name="weeks"][value="' + week + '"]').prop('checked', true);
            });
        }

        // Set activities
        if (urlParams.has('activities')) {
            const activities = urlParams.get('activities').split(',');
            activities.forEach(activity => {
                $('input[name="activities"][value="' + activity + '"]').prop('checked', true);
            });
        }

        // Set sort
        if (urlParams.has('sort')) {
            $('#sort-by').val(urlParams.get('sort'));
        }
    }

    /**
     * Update URL with current search parameters
     */
    function updateURLParameters() {
        const params = new URLSearchParams();

        // Search
        const search = $('#camp-search-input').val().trim();
        if (search) {
            params.set('search', search);
        }

        // State
        const state = $('#filter-state').val();
        if (state) {
            params.set('state', state);
        }

        // Dates
        const startDate = $('#filter-start-date').val();
        if (startDate) {
            params.set('start_date', startDate);
        }
        const endDate = $('#filter-end-date').val();
        if (endDate) {
            params.set('end_date', endDate);
        }

        // Prices
        const minPrice = $('#filter-min-price').val();
        const maxPrice = $('#filter-max-price').val();
        if (minPrice != campSearchData.minPrice) {
            params.set('min_price', minPrice);
        }
        if (maxPrice != campSearchData.maxPrice) {
            params.set('max_price', maxPrice);
        }

        // Camp types
        const campTypes = [];
        $('input[name="camp_type"]:checked').each(function() {
            campTypes.push($(this).val());
        });
        if (campTypes.length > 0) {
            params.set('camp_types', campTypes.join(','));
        }

        // Weeks
        const weeks = [];
        $('input[name="weeks"]:checked').each(function() {
            weeks.push($(this).val());
        });
        if (weeks.length > 0) {
            params.set('weeks', weeks.join(','));
        }

        // Activities
        const activities = [];
        $('input[name="activities"]:checked').each(function() {
            activities.push($(this).val());
        });
        if (activities.length > 0) {
            params.set('activities', activities.join(','));
        }

        // Sort
        const sort = $('#sort-by').val();
        if (sort !== 'random') {
            params.set('sort', sort);
        }

        // Update URL without reloading
        const newURL = params.toString() ? 
            window.location.pathname + '?' + params.toString() : 
            window.location.pathname;
        
        window.history.pushState({}, '', newURL);
    }

    /**
     * Reset page and perform new search
     */
    function resetAndSearch() {
        currentPage = 1;
        performSearch(false);
    }

    /**
     * Collect all filters and perform search
     */
    function performSearch(append = false) {
        if (isLoading) return;

        isLoading = true;

        // Show loading
        if (!append) {
            $('#search-loading').show();
            $('#camp-results-grid').hide();
            $('#no-results').hide();
            $('#load-more-btn').hide();
        } else {
            $('#load-more-btn').html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        }

        // Collect filters
        const searchData = {
            action: 'camp_search',
            search: $('#camp-search-input').val().trim(),
            state: $('#filter-state').val(),
            start_date: $('#filter-start-date').val(),
            end_date: $('#filter-end-date').val(),
            min_price: $('#filter-min-price').val(),
            max_price: $('#filter-max-price').val(),
            camp_types: [],
            weeks: [],
            activities: [],
            sort_by: $('#sort-by').val(),
            page: currentPage,
            per_page: campSearchData.resultsPerPage
        };

        // Collect checked camp types
        $('input[name="camp_type"]:checked').each(function() {
            searchData.camp_types.push($(this).val());
        });

        // Collect checked weeks
        $('input[name="weeks"]:checked').each(function() {
            searchData.weeks.push($(this).val());
        });

        // Collect checked activities
        $('input[name="activities"]:checked').each(function() {
            searchData.activities.push($(this).val());
        });

        // Update URL
        updateURLParameters();

        console.log('Performing search with data:', searchData);

        // Perform AJAX request
        $.ajax({
            url: campSearchData.ajaxUrl,
            type: 'POST',
            data: searchData,
            success: function(response) {
                console.log('Search response:', response);
                
                if (response.success) {
                    totalResults = response.data.total;
                    hasMore = response.data.has_more;

                    // Update results count
                    $('#results-count').text(totalResults);

                    // Render camps
                    if (response.data.camps.length > 0) {
                        if (append) {
                            appendCamps(response.data.camps);
                        } else {
                            renderCamps(response.data.camps);
                        }
                        
                        $('#camp-results-grid').show();
                        $('#no-results').hide();

                        // Show/hide load more button
                        if (hasMore) {
                            $('#load-more-btn').show();
                        } else {
                            $('#load-more-btn').hide();
                        }
                    } else {
                        if (!append) {
                            $('#camp-results-grid').html('').hide();
                            $('#no-results').show();
                            $('#load-more-btn').hide();
                        }
                    }

                    // Close mobile filters
                    if (window.innerWidth <= 768) {
                        $('.filters-container').removeClass('active');
                    }
                }

                $('#search-loading').hide();
                $('#load-more-btn').html('<i class="fa fa-plus-circle"></i> Load More Camps').prop('disabled', false);
                isLoading = false;
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                $('#search-loading').hide();
                $('#load-more-btn').html('<i class="fa fa-plus-circle"></i> Load More Camps').prop('disabled', false);
                isLoading = false;
                $('#no-results').html('<i class="fa fa-exclamation-triangle"></i><h3>Error loading camps</h3><p>Please check the console for details or try refreshing the page.</p>').show();
            }
        });
    }

    /**
     * Render camps (replace existing)
     */
    function renderCamps(camps) {
        const $grid = $('#camp-results-grid');
        $grid.empty();

        camps.forEach(camp => {
            $grid.append(createCampCard(camp));
        });
    }

    /**
     * Append camps (for infinite scroll)
     */
    function appendCamps(camps) {
        const $grid = $('#camp-results-grid');

        camps.forEach(camp => {
            $grid.append(createCampCard(camp));
        });
    }

    /**
     * Create camp card HTML
     */
    function createCampCard(camp) {
        const logoHTML = camp.logo ? 
            `<img src="${escapeHtml(camp.logo)}" alt="${escapeHtml(camp.name)} Logo">` :
            '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #999;">No Logo</div>';

        const ratingHTML = renderStarRating(camp.rating);

        const typesHTML = camp.camp_types ? 
            camp.camp_types.split(',').slice(0, 3).map(type => 
                `<span class="camp-type-tag">${escapeHtml(type.trim())}</span>`
            ).join('') : '';

        const priceHTML = camp.min_price && camp.max_price ?
            (camp.min_price === camp.max_price ?
                `$${formatNumber(camp.min_price)}` :
                `$${formatNumber(camp.min_price)} - $${formatNumber(camp.max_price)}`) :
            (camp.min_price ? `From $${formatNumber(camp.min_price)}` : 'Price varies');

        return `
            <div class="camp-card">
                <div class="camp-card-content">
                    <div class="camp-card-logo">
                        ${logoHTML}
                    </div>
                    <div class="camp-card-details">
                        <h3 class="camp-card-name">${escapeHtml(camp.name)}</h3>
                        <div class="camp-card-location">
                            <i class="fa fa-map-marker-alt"></i>
                            ${escapeHtml(camp.city)}, ${escapeHtml(camp.state)}
                        </div>
                        <div class="camp-card-rating">
                            ${ratingHTML}
                        </div>
                        <div class="camp-card-types">
                            ${typesHTML}
                        </div>
                        <div class="camp-card-price">
                            ${priceHTML}
                        </div>
                    </div>
                </div>
                <div class="camp-card-footer">
                    <a href="${escapeHtml(camp.url)}" class="camp-card-link">
                        View Details <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        `;
    }

    /**
     * Render star rating
     */
    function renderStarRating(rating) {
        // Convert to number and validate
        const numRating = parseFloat(rating);
        
        if (!numRating || isNaN(numRating) || numRating <= 0) {
            return '<span style="color: #999; font-size: 12px;">No rating yet</span>';
        }

        let stars = '';
        const fullStars = Math.floor(numRating);
        const hasHalfStar = (numRating % 1) >= 0.5;

        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                stars += '<i class="fa fa-star camp-star"></i>';
            } else if (i === fullStars + 1 && hasHalfStar) {
                stars += '<i class="fa fa-star-half-alt camp-star"></i>';
            } else {
                stars += '<i class="fa fa-star camp-star empty"></i>';
            }
        }

        stars += ` <span style="color: #666; font-size: 12px; margin-left: 5px;">(${numRating.toFixed(1)})</span>`;

        return stars;
    }

    /**
     * Clear all filters
     */
    function clearAllFilters() {
        $('#camp-search-input').val('');
        $('#filter-state').val('');
        $('#filter-start-date').val('');
        $('#filter-end-date').val('');
        $('#filter-min-price').val(campSearchData.minPrice).trigger('input');
        $('#filter-max-price').val(campSearchData.maxPrice).trigger('input');
        $('input[name="camp_type"]').prop('checked', false);
        $('input[name="weeks"]').prop('checked', false);
        $('input[name="activities"]').prop('checked', false);
        $('#sort-by').val('random');
    }

    /**
     * Format number with commas
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

})(jQuery);
