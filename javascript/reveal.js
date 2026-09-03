/**
 * Reveal / countdown / delete behaviour for mod_zugang's view page.
 *
 * Deliberately plain JS (no AMD/webpack build step) so the plugin has
 * zero build tooling and "just works" once installed. Loaded via
 * $PAGE->requires->js() and started by $PAGE->requires->js_init_call()
 * calling M.mod_zugang.init() with (cmid, revealseconds).
 *
 * IMPORTANT: Moodle's js_init_call() ALWAYS prepends the YUI Y instance
 * as the first argument to whatever function it calls — this is
 * documented, longstanding Moodle behaviour, not something we opt into.
 * The init function signature below accounts for that; Y itself is
 * unused since this file talks to the DOM/fetch directly rather than
 * through YUI.
 *
 * The sesskey is deliberately NOT taken from the value passed into
 * init() at page-render time — if the tab was left open for a while
 * (very plausible: a teacher/admin builds and tests a page, then leaves
 * it open), that snapshot can go stale relative to the live session
 * well before the rest of the page does. M.cfg.sesskey is Moodle's own
 * live config object and is read fresh on every click instead.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.mod_zugang = M.mod_zugang || {};

M.mod_zugang.init = function(Y, cmid, revealseconds) {
    'use strict';

    var ajaxBase = M.cfg.wwwroot + '/mod/zugang/ajax/';

    function post(url, params) {
        params.sesskey = M.cfg.sesskey;
        var body = Object.keys(params).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body,
            credentials: 'same-origin'
        }).then(function(r) {
            return r.json().catch(function() {
                // The server didn't return JSON at all — most likely Moodle's
                // own session-expired HTML page rather than our endpoint.
                return {error: M.util.get_string('sessionexpired', 'mod_zugang'), reload: true};
            });
        });
    }

    document.querySelectorAll('.zugang-reveal-widget').forEach(function(widget) {
        var entryId = widget.getAttribute('data-entryid');
        var revealBtn = widget.querySelector('.zugang-reveal-btn');
        var deleteBtn = widget.querySelector('.zugang-delete-btn');
        var display = widget.querySelector('.zugang-password-display');
        var countdown = widget.querySelector('.zugang-countdown');
        var timerHandle = null;

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
            post(ajaxBase + 'reveal.php', {cmid: cmid, entryid: entryId}).then(function(data) {
                if (data.error) {
                    revealBtn.disabled = false;
                    if (data.reload && window.confirm(data.error)) {
                        window.location.reload();
                    } else if (!data.reload) {
                        window.alert(data.error);
                    }
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
                post(ajaxBase + 'delete.php', {cmid: cmid, entryid: entryId}).then(function(data) {
                    if (data.error) {
                        deleteBtn.disabled = false;
                        if (data.reload && window.confirm(data.error)) {
                            window.location.reload();
                        } else if (!data.reload) {
                            window.alert(data.error);
                        }
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
