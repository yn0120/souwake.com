/*
 * 出欠回答の編集フォーム。
 *
 * - ご住所の国に応じて「都道府県」と「州」の入力欄を出し分ける
 *   （使わない側はdisabledにして送信しない。同じprefectureカラムへ入るため）
 * - 同伴者（連名）の行を追加・削除し、「○人目」の番号を振り直す
 */
(function () {
    'use strict';

    var config = window.weddingRsvpFormConfig || {};
    var maxCompanions = config.maxCompanions || 0;
    var companionIndex = config.companionCount || 0;

    var countrySelect = document.getElementById('country');
    var companionArea = document.getElementById('companionArea');
    var companionRows = document.getElementById('companionRows');
    var companionAddBtn = document.getElementById('companionAdd');
    var companionTemplate = document.getElementById('companionRowTemplate');

    function toggleCountry() {
        if (! countrySelect) {
            return;
        }
        var isUnitedStates = countrySelect.value === config.countryUs;

        [
            { selector: '[data-country="' + config.countryUs + '"]', shown: isUnitedStates },
            { selector: '[data-country="' + config.countryJp + '"]', shown: ! isUnitedStates },
        ].forEach(function (group) {
            document.querySelectorAll(group.selector).forEach(function (row) {
                row.style.display = group.shown ? '' : 'none';
                row.querySelectorAll('input, select').forEach(function (field) {
                    field.disabled = ! group.shown;
                });
            });
        });
    }

    function renumberCompanions() {
        companionRows.querySelectorAll('[data-companion-row]').forEach(function (row, index) {
            row.querySelectorAll('[data-companion-number]').forEach(function (el) {
                el.textContent = String(index + 1);
            });
        });
    }

    function addCompanion() {
        if (companionRows.querySelectorAll('[data-companion-row]').length >= maxCompanions) {
            window.alert('同伴者は' + maxCompanions + '名までご入力いただけます。');

            return;
        }

        companionRows.insertAdjacentHTML('beforeend', companionTemplate.innerHTML
            .replace(/__INDEX__/g, companionIndex)
            .replace(/__NUMBER__/g, companionIndex + 1));
        companionIndex++;
        renumberCompanions();
    }

    if (countrySelect) {
        countrySelect.addEventListener('change', toggleCountry);
        toggleCountry();
    }

    if (companionRows && companionTemplate) {
        companionAddBtn.addEventListener('click', addCompanion);

        companionRows.addEventListener('click', function (e) {
            if (! e.target.closest('[data-companion-remove]')) {
                return;
            }
            e.target.closest('[data-companion-row]').remove();
            renumberCompanions();
        });

        // 同伴者「あり」に変えたときは1名分の入力行を用意する
        document.querySelectorAll('input[name="companion_flag"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var hasCompanion = document.querySelector('input[name="companion_flag"]:checked').value === '1';
                companionArea.style.display = hasCompanion ? '' : 'none';
                if (hasCompanion && companionRows.querySelectorAll('[data-companion-row]').length === 0) {
                    addCompanion();
                }
            });
        });
    }
})();
