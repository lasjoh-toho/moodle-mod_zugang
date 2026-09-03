/**
 * Reveal / countdown / delete behaviour for mod_zugang's view page.
 *
 * Deliberately plain JS (no AMD/webpack build step) so the plugin has
 * zero build tooling and "just works" once installed. Loaded via
 * $PAGE->requires->js() and started by $PAGE->requires->js_init_call()
 * calling M.mod_zugang.init() with (cmid, revealseconds, sesskey).
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.mod_zugang = M.mod_zugang || {};

M.mod_zugang.init = function(cmid, revealseconds, sesskey) {
    'use strict';

    var ajaxBase = M.cfg.wwwroot + '/mod/zugang/ajax/';

    function post(url, params) {
        var body = Object.keys(params).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body,
            credentials: 'same-origin'
        }).then(function(r) {
            return r.json();
        });
    }

    document.querySelectorAll('.zugang-reveal-widget').forEach(function(widget) {
        var entryId = widget.getAttribute('data-entryid');
        var revealBtn = widget.querySelector('.zugang-reveal-btn');
        var deleteBtn = widget.querySelector('.zugang-delete-btn');
        var display = widget.querySelector('.zugang-password-display');
        var countdown = widget.querySelector('.zugang-countdown');
        var timerHandle = null;
        var closeHandle = null;

        function closePanel() {
            if (timerHandle) {
                window.clearInterval(timerHandle);
            }
            display.style.display = 'none';
            display.textContent = '';
            countdown.style.display = 'none';
            revealBtn.style.display = '';
            revealBtn.disabled = false;
        }

        revealBtn.addEventListener('click', function() {
            revealBtn.disabled = true;
            post(ajaxBase + 'reveal.php', {cmid: cmid, entryid: entryId, sesskey: sesskey}).then(function(data) {
                if (data.error) {
                    revealBtn.disabled = false;
                    window.alert(data.error);
                    return;
                }
                display.textContent = data.password;
                display.style.display = '';
                revealBtn.style.display = 'none';
                if (deleteBtn) {
                    deleteBtn.style.display = '';
                }

                var remaining = revealseconds;
                countdown.style.display = '';
                countdown.textContent = remaining + 's';
                timerHandle = window.setInterval(function() {
                    remaining -= 1;
                    countdown.textContent = Math.max(remaining, 0) + 's';
                    if (remaining <= 0) {
                        closePanel();
                    }
                }, 1000);
            }).catch(function() {
                revealBtn.disabled = false;
            });
        });

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (!window.confirm(deleteBtn.getAttribute('data-confirm') || 'Sind Sie sicher?')) {
                    return;
                }
                deleteBtn.disabled = true;
                post(ajaxBase + 'delete.php', {cmid: cmid, entryid: entryId, sesskey: sesskey}).then(function(data) {
                    if (data.error) {
                        deleteBtn.disabled = false;
                        window.alert(data.error);
                        return;
                    }
                    closePanel();
                    widget.innerHTML = '';
                    widget.textContent = M.util.get_string('passworddeletedconfirm', 'mod_zugang');
                });
            });
        }
    });
};
