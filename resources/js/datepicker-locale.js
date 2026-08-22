/*
 * flowbite-datepicker（管理画面・招待状サイトの両方が使う）を日本語で表示する。
 *
 * ロケールは静的な辞書に登録する方式で、未登録の言語コードを指定すると黙って
 * 英語にフォールバックする。そのため、datepickerを初期化するより先に読み込むこと。
 * 内容は flowbite-datepicker/js/i18n/locales/ja.js と同じ。依存パッケージの内部パスを
 * 直接importするとバージョン差で壊れるため、この15行だけを持つ。
 */
import { Datepicker } from 'flowbite-datepicker';

Object.assign(Datepicker.locales, {
    ja: {
        days: ['日曜', '月曜', '火曜', '水曜', '木曜', '金曜', '土曜'],
        daysShort: ['日', '月', '火', '水', '木', '金', '土'],
        daysMin: ['日', '月', '火', '水', '木', '金', '土'],
        months: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        monthsShort: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        today: '今日',
        format: 'yyyy/mm/dd',
        titleFormat: 'y年mm月',
        clear: 'クリア',
    },
});
