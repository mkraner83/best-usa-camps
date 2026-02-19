/**
 * Parent Registration Form JS  (assets/parent-registration-form.js)
 * - Client-side validation feedback
 * - Checkbox "at least one" enforcement on submit
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('parent-registration-form');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            var valid = true;
            var firstError = null;

            // Required checkboxes
            var checkboxGroups = [
                { id: 'session-length-group', label: 'Preferred Session Length' },
                { id: 'preferred-location-group', label: 'Preferred Location' },
                { id: 'preferred-program-group', label: 'Preferred Program' },
            ];

            checkboxGroups.forEach(function (group) {
                var el = document.getElementById(group.id);
                if (!el) return;
                var checked = el.querySelectorAll('input[type="checkbox"]:checked');
                var wrapper = el.parentElement;
                var existing = wrapper.querySelector('.prf-checkbox-error');

                if (checked.length === 0) {
                    valid = false;
                    if (!existing) {
                        var msg = document.createElement('p');
                        msg.className = 'prf-checkbox-error';
                        msg.style.color = '#ef4444';
                        msg.style.fontSize = '13px';
                        msg.style.marginTop = '6px';
                        msg.textContent = 'Please select at least one option for "' + group.label + '".';
                        wrapper.appendChild(msg);
                    }
                    if (!firstError) firstError = el;
                } else if (existing) {
                    existing.remove();
                }
            });

            if (!valid) {
                e.preventDefault();
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        // Remove error markers when user starts checking boxes
        var allCheckboxes = form.querySelectorAll('.prf-checkbox-group input[type="checkbox"]');
        allCheckboxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                var group = cb.closest('.prf-checkbox-group');
                if (!group) return;
                var checked = group.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length > 0) {
                    var err = group.parentElement.querySelector('.prf-checkbox-error');
                    if (err) err.remove();
                }
            });
        });
    });
})();
