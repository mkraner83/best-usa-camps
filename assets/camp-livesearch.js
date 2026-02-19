/**
 * camp-livesearch.js
 * Lightweight autocomplete for [camp_livesearch] shortcode.
 * No jQuery dependency.
 */
(function () {
	'use strict';

	var ajaxUrl = (typeof CDBS_LS !== 'undefined') ? CDBS_LS.ajax_url : '/wp-admin/admin-ajax.php';

	// ── Highlight matching text ──────────────────────────────────────────────
	function highlight(text, query) {
		if (!query) return escHtml(text);
		var re = new RegExp('(' + escRe(query) + ')', 'gi');
		return escHtml(text).replace(re, '<span class="cdbs-ls-match">$1</span>');
	}
	function escHtml(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}
	function escRe(s) {
		return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	// ── Debounce ─────────────────────────────────────────────────────────────
	function debounce(fn, ms) {
		var t;
		return function () {
			clearTimeout(t);
			var args = arguments;
			var ctx = this;
			t = setTimeout(function () { fn.apply(ctx, args); }, ms);
		};
	}

	// ── Init one widget ──────────────────────────────────────────────────────
	function initWidget(wrap) {
		var input    = wrap.querySelector('.cdbs-ls-input');
		var dropdown = wrap.querySelector('.cdbs-ls-dropdown');
		var clearBtn = wrap.querySelector('.cdbs-ls-clear');
		var nonce    = wrap.getAttribute('data-nonce') || (typeof CDBS_LS !== 'undefined' ? CDBS_LS.nonce : '');

		if (!input || !dropdown) return;

		var currentQ   = '';
		var activeIdx  = -1;
		var xhr        = null;

		// ── Render dropdown ────────────────────────────────────────────────
		function renderResults(items, q) {
			activeIdx = -1;
			if (!items || items.length === 0) {
				dropdown.innerHTML = '<div class="cdbs-ls-status">No camps found for "' + escHtml(q) + '"</div>';
			} else {
				var html = '';
				items.forEach(function (item) {
					var logoHtml;
					if (item.logo) {
						logoHtml = '<div class="cdbs-ls-logo"><img src="' + escHtml(item.logo) + '" alt="" loading="lazy" onerror="this.parentNode.innerHTML=\'<span class=cdbs-ls-logo-placeholder>' + escHtml((item.name || '?').charAt(0)) + '</span>\'"></div>';
					} else {
						logoHtml = '<div class="cdbs-ls-logo"><span class="cdbs-ls-logo-placeholder">' + escHtml((item.name || '?').charAt(0)) + '</span></div>';
					}

					var stateHtml = item.state ? '<div class="cdbs-ls-state">' + escHtml(item.state) + '</div>' : '';
					var href = item.url ? ' href="' + escHtml(item.url) + '"' : '';

					html += '<a class="cdbs-ls-item"' + href + ' role="option" tabindex="-1" data-url="' + escHtml(item.url || '') + '">'
						+ logoHtml
						+ '<div class="cdbs-ls-info">'
						+ '<div class="cdbs-ls-name">' + highlight(item.name, q) + '</div>'
						+ stateHtml
						+ '</div>'
						+ '</a>';
				});
				dropdown.innerHTML = html;
			}
			openDropdown();
		}

		function showLoading() {
			dropdown.innerHTML = '<div class="cdbs-ls-status">Searching…</div>';
			openDropdown();
		}

		function openDropdown() {
			dropdown.classList.add('cdbs-ls-open');
		}
		function closeDropdown() {
			dropdown.classList.remove('cdbs-ls-open');
			activeIdx = -1;
		}

		// ── Keyboard navigation ────────────────────────────────────────────
		function getItems() {
			return dropdown.querySelectorAll('.cdbs-ls-item');
		}
		function setActive(idx) {
			var items = getItems();
			items.forEach(function (el) { el.classList.remove('cdbs-ls-active'); });
			if (idx >= 0 && idx < items.length) {
				items[idx].classList.add('cdbs-ls-active');
				items[idx].scrollIntoView({ block: 'nearest' });
			}
			activeIdx = idx;
		}

		// ── AJAX fetch ─────────────────────────────────────────────────────
		var doSearch = debounce(function (q) {
			if (q.length < 2) { closeDropdown(); return; }
			currentQ = q;
			if (xhr) { try { xhr.abort(); } catch(e){} }
			showLoading();
			xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				if (xhr.status === 200) {
					try {
						var res = JSON.parse(xhr.responseText);
						if (res.success) {
							renderResults(res.data, currentQ);
						} else {
							closeDropdown();
						}
					} catch(e) { closeDropdown(); }
				}
			};
			xhr.onerror = function () { closeDropdown(); };
			xhr.send('action=cdbs_livesearch&nonce=' + encodeURIComponent(nonce) + '&q=' + encodeURIComponent(q));
		}, 280);

		// ── Events ─────────────────────────────────────────────────────────
		input.addEventListener('input', function () {
			var val = input.value.trim();
			clearBtn.style.display = val ? '' : 'none';
			if (val.length < 2) {
				closeDropdown();
				return;
			}
			doSearch(val);
		});

		input.addEventListener('keydown', function (e) {
			var items = getItems();
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				setActive(Math.min(activeIdx + 1, items.length - 1));
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				setActive(Math.max(activeIdx - 1, 0));
			} else if (e.key === 'Enter') {
				if (activeIdx >= 0 && items[activeIdx]) {
					var url = items[activeIdx].getAttribute('data-url');
					if (url) { window.location.href = url; }
				}
			} else if (e.key === 'Escape') {
				closeDropdown();
				input.blur();
			}
		});

		// Clear button
		if (clearBtn) {
			clearBtn.addEventListener('click', function () {
				input.value = '';
				clearBtn.style.display = 'none';
				closeDropdown();
				input.focus();
			});
		}

		// Click on result (delegated)
		dropdown.addEventListener('mousedown', function (e) {
			var item = e.target.closest('.cdbs-ls-item');
			if (item) {
				e.preventDefault();
				var url = item.getAttribute('data-url');
				if (url) { window.location.href = url; }
			}
		});

		// Click outside closes
		document.addEventListener('mousedown', function (e) {
			if (!wrap.contains(e.target)) {
				closeDropdown();
			}
		});

		// Focus reopens if there is content
		input.addEventListener('focus', function () {
			if (input.value.trim().length >= 2 && dropdown.innerHTML.trim()) {
				openDropdown();
			}
		});
	}

	// ── Boot all widgets on page ─────────────────────────────────────────────
	function boot() {
		var widgets = document.querySelectorAll('.cdbs-ls-wrap');
		widgets.forEach(initWidget);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

})();
