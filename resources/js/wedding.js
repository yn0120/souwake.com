document.addEventListener('DOMContentLoaded', () => {
    // スクロールで要素をふわっと表示
    const revealTargets = document.querySelectorAll('.wedding-reveal');
    if ('IntersectionObserver' in window && revealTargets.length) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.15 }
        );
        revealTargets.forEach((el) => observer.observe(el));
    } else {
        revealTargets.forEach((el) => el.classList.add('is-visible'));
    }

    // 出欠：欠席選択時は沖縄リゾート婚特有の項目を隠す
    const attendanceRadios = document.querySelectorAll('input[name="attendance"]');
    const attendingOnlySection = document.getElementById('attending-only-fields');
    const toggleAttendingSection = () => {
        const selected = document.querySelector('input[name="attendance"]:checked');
        if (!attendingOnlySection) {
            return;
        }
        attendingOnlySection.classList.toggle('hidden', !selected || selected.value !== 'attending');
    };
    attendanceRadios.forEach((radio) => radio.addEventListener('change', toggleAttendingSection));
    toggleAttendingSection();

    // 同伴者：ありを選択したときのみ詳細項目を表示
    const companionRadios = document.querySelectorAll('input[name="companion_flag"]');
    const companionFields = document.getElementById('companion-fields');
    const toggleCompanionFields = () => {
        const selected = document.querySelector('input[name="companion_flag"]:checked');
        if (!companionFields) {
            return;
        }
        companionFields.classList.toggle('hidden', !selected || selected.value !== '1');
    };
    companionRadios.forEach((radio) => radio.addEventListener('change', toggleCompanionFields));
    toggleCompanionFields();

    // ナビの目次からのスムーズスクロール
    document.querySelectorAll('a[data-scroll]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (!target) {
                return;
            }
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});
