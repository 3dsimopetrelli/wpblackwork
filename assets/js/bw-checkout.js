(function () {
    function updateQuantity(input, delta) {
        var current = parseFloat(input.value || 0);
        var step = parseFloat(input.getAttribute('step')) || 1;
        var min = input.hasAttribute('min') ? parseFloat(input.getAttribute('min')) : 0;
        var max = input.hasAttribute('max') ? parseFloat(input.getAttribute('max')) : Infinity;
        var next = current + delta * step;

        if (isNaN(next)) {
            next = 0;
        }

        if (next < min) {
            next = min;
        }

        if (next > max) {
            next = max;
        }

        if (next !== current) {
            input.value = next;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.bw-qty-btn');

        if (!button) {
            return;
        }

        var shell = button.closest('.bw-qty-shell');
        var input = shell ? shell.querySelector('input.qty') : null;

        if (!input) {
            return;
        }

        var delta = button.classList.contains('bw-qty-btn--minus') ? -1 : 1;
        updateQuantity(input, delta);
    });
})();
