/*
 * JSでマークアップを生成するページ（パスワード管理など）が使うクラス文字列。
 *
 * Blade側の対になる定義は resources/views/components/office/ 配下。
 * ボタンや入力欄の見た目を変える時は、両方を合わせること
 * （色そのものは office.css の @theme を変えれば両方追従する）。
 */

export const INPUT_CLASS = 'w-full rounded-lg border border-default bg-white px-3 py-1.5 text-sm '
    + 'text-heading placeholder:text-body focus:border-brand focus:ring-2 focus:ring-brand-medium';

const BUTTON_BASE = 'inline-flex cursor-pointer items-center justify-center gap-1 rounded-lg border '
    + 'px-3 py-1.5 text-xs font-medium transition-colors focus:outline-none focus:ring-2 '
    + 'disabled:cursor-not-allowed disabled:opacity-60';

export const BUTTON_CLASS = {
    warning: `${BUTTON_BASE} border-warning text-warning hover:bg-warning hover:text-white focus:ring-warning-medium`,
    success: `${BUTTON_BASE} border-success text-success hover:bg-success hover:text-white focus:ring-success-medium`,
    danger: `${BUTTON_BASE} border-danger text-danger hover:bg-danger hover:text-white focus:ring-danger-medium`,
    dark: `${BUTTON_BASE} border-dark text-dark hover:bg-dark hover:text-white focus:ring-neutral-quaternary`,
    secondary: `${BUTTON_BASE} border-default-strong text-body hover:bg-neutral-tertiary focus:ring-neutral-tertiary`,
    link: 'cursor-pointer text-xs font-medium text-brand underline hover:text-brand-strong',
};
