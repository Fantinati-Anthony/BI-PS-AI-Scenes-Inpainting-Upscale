/* BI - AI Scenes / Inpainting / Upscale - front-office.
 * Tiny modal opener + before/after slider for the product page.
 */
(function () {
    'use strict';
    var btn = document.querySelector('.bi-scenes-fo-btn');
    var modal = document.querySelector('.bi-scenes-modal');
    if (!btn || !modal) return;

    function open() { modal.hidden = false; document.body.style.overflow = 'hidden'; }
    function close() { modal.hidden = true; document.body.style.overflow = ''; }

    btn.addEventListener('click', open);
    Array.prototype.forEach.call(modal.querySelectorAll('[data-close-modal]'), function (el) {
        el.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });

    var slider = modal.querySelector('.bi-scenes-slider');
    var after = modal.querySelector('.bi-scenes-after');
    if (slider && after) {
        var update = function () { after.style.clipPath = 'inset(0 0 0 ' + slider.value + '%)'; };
        slider.addEventListener('input', update);
        update();
    }
})();
