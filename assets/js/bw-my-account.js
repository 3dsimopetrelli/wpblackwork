(function() {
    const tabs = document.querySelectorAll('.bw-tab');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const targetSelector = tab.getAttribute('data-target');
            if (!targetSelector) {
                return;
            }

            const container = tab.closest('.bw-settings');
            if (!container) {
                return;
            }

            container.querySelectorAll('.bw-tab').forEach((btn) => btn.classList.remove('is-active'));
            container.querySelectorAll('.bw-tab-panel').forEach((panel) => panel.classList.remove('is-active'));

            tab.classList.add('is-active');
            const targetPanel = container.querySelector(targetSelector);
            if (targetPanel) {
                targetPanel.classList.add('is-active');
            }
        });
    });
})();
