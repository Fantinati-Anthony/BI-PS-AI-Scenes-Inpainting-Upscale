/* BI - AI Scenes / Inpainting / Upscale - Studio (admin).
 * Op switching, mask drawing on canvas, AJAX generate/poll/cancel,
 * before/after slider, history list. Vanilla JS, no jQuery dep.
 */
(function () {
    'use strict';
    var root = document.querySelector('.bi-scenes-studio');
    if (!root) return;

    var ajaxUrl = root.dataset.ajaxUrl;
    var token = root.dataset.token;
    var brushColor = root.dataset.brush || '#ff3366';
    var brushOpacity = parseFloat(root.dataset.brushOpacity || '0.6');
    var currentOp = 'scene';
    var brushSize = 36;
    var maskHistory = [];
    var pollTimer = null;
    var currentPredictionId = null;

    var $ = root.querySelector.bind(root);
    var $$ = function (s) { return Array.prototype.slice.call(root.querySelectorAll(s)); };

    var els = {
        srcUrl: $('input[name="image_url"]'),
        srcImg: $('.bi-scenes-source-img'),
        canvas: $('.bi-scenes-mask-canvas'),
        provider: $('select[name="provider"]'),
        prompt: $('textarea[name="prompt"]'),
        negPrompt: $('textarea[name="negative_prompt"]'),
        before: $('.bi-scenes-before'),
        after: $('.bi-scenes-after'),
        slider: $('.bi-scenes-slider'),
        status: $('.bi-scenes-status'),
        history: $('.bi-scenes-history'),
    };
    var providers = JSON.parse(els.provider.dataset.providers || '{}');

    function applyOpVisibility() {
        $$('[data-only-op]').forEach(function (el) {
            el.style.display = (el.dataset.onlyOp === currentOp) ? '' : 'none';
        });
        $$('[data-hide-op]').forEach(function (el) {
            el.style.display = (el.dataset.hideOp === currentOp) ? 'none' : '';
        });
        var opts = providers[currentOp] || {};
        els.provider.innerHTML = '';
        Object.keys(opts).forEach(function (k) {
            var o = document.createElement('option');
            o.value = k; o.textContent = opts[k];
            els.provider.appendChild(o);
        });
    }

    $$('.bi-scenes-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            $$('.bi-scenes-tab').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            currentOp = btn.dataset.op;
            applyOpVisibility();
        });
    });
    applyOpVisibility();

    /* --- source image preview & canvas size --- */
    function loadSource(url) {
        if (!url) return;
        els.srcImg.onload = function () {
            var w = els.srcImg.naturalWidth, h = els.srcImg.naturalHeight;
            els.canvas.width = w;
            els.canvas.height = h;
            els.canvas.style.width = els.srcImg.clientWidth + 'px';
            els.canvas.style.height = els.srcImg.clientHeight + 'px';
            maskHistory = [];
            els.before.src = url;
        };
        els.srcImg.src = url;
    }
    els.srcUrl.addEventListener('change', function () { loadSource(els.srcUrl.value); });
    els.srcUrl.addEventListener('paste', function () {
        setTimeout(function () { loadSource(els.srcUrl.value); }, 0);
    });

    /* --- mask drawing --- */
    var ctx = els.canvas.getContext('2d');
    var drawing = false;
    function pos(e) {
        var r = els.canvas.getBoundingClientRect();
        var sx = els.canvas.width / r.width;
        var sy = els.canvas.height / r.height;
        return { x: (e.clientX - r.left) * sx, y: (e.clientY - r.top) * sy };
    }
    function startDraw(e) {
        if (currentOp !== 'inpaint') return;
        drawing = true;
        maskHistory.push(ctx.getImageData(0, 0, els.canvas.width, els.canvas.height));
        if (maskHistory.length > 20) maskHistory.shift();
        var p = pos(e);
        ctx.fillStyle = brushColor;
        ctx.globalAlpha = brushOpacity;
        ctx.beginPath(); ctx.arc(p.x, p.y, brushSize, 0, 2 * Math.PI); ctx.fill();
    }
    function moveDraw(e) {
        if (!drawing) return;
        var p = pos(e);
        ctx.fillStyle = brushColor;
        ctx.globalAlpha = brushOpacity;
        ctx.beginPath(); ctx.arc(p.x, p.y, brushSize, 0, 2 * Math.PI); ctx.fill();
    }
    function endDraw() { drawing = false; }
    els.canvas.addEventListener('mousedown', startDraw);
    els.canvas.addEventListener('mousemove', moveDraw);
    document.addEventListener('mouseup', endDraw);

    $$('.bi-scenes-mask-tools button').forEach(function (b) {
        b.addEventListener('click', function () {
            var a = b.dataset.action;
            if (a === 'brush-up') brushSize = Math.min(200, brushSize + 8);
            if (a === 'brush-down') brushSize = Math.max(4, brushSize - 8);
            if (a === 'undo' && maskHistory.length) ctx.putImageData(maskHistory.pop(), 0, 0);
            if (a === 'clear') { ctx.clearRect(0, 0, els.canvas.width, els.canvas.height); maskHistory = []; }
        });
    });

    /* --- mask → black/white PNG (transparent canvas → opaque mask) --- */
    function buildMaskBlob() {
        var off = document.createElement('canvas');
        off.width = els.canvas.width; off.height = els.canvas.height;
        var oc = off.getContext('2d');
        oc.fillStyle = '#000'; oc.fillRect(0, 0, off.width, off.height);
        var src = ctx.getImageData(0, 0, els.canvas.width, els.canvas.height);
        var dst = oc.getImageData(0, 0, off.width, off.height);
        for (var i = 0; i < src.data.length; i += 4) {
            if (src.data[i + 3] > 8) {
                dst.data[i] = 255; dst.data[i + 1] = 255; dst.data[i + 2] = 255; dst.data[i + 3] = 255;
            }
        }
        oc.putImageData(dst, 0, 0);
        return off.toDataURL('image/png');
    }

    /* --- AJAX --- */
    function ajax(action, data) {
        var fd = new FormData();
        fd.append('ajax', '1');
        fd.append('token', token);
        fd.append('ajax_action', action);
        Object.keys(data || {}).forEach(function (k) {
            if (data[k] === undefined || data[k] === null || data[k] === '') return;
            fd.append(k, data[k]);
        });
        return fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function setStatus(msg, kind) {
        els.status.textContent = msg || '';
        els.status.dataset.kind = kind || '';
    }

    function startGenerate() {
        var imgUrl = els.srcUrl.value.trim();
        var providerKey = els.provider.value;
        var payload = {
            provider: providerKey,
            prompt: els.prompt.value,
            negative_prompt: els.negPrompt.value,
            image_url: imgUrl,
            aspect_ratio: ($('select[name="aspect_ratio"]') || {}).value,
            output_format: ($('select[name="output_format"]') || {}).value,
            scale: ($('input[name="scale"]') || {}).value,
            strength: ($('input[name="strength"]') || {}).value,
            guidance_scale: ($('input[name="guidance_scale"]') || {}).value,
            seed: ($('input[name="seed"]') || {}).value,
            num_inference_steps: ($('input[name="num_inference_steps"]') || {}).value,
        };

        var maybeUploadMask = Promise.resolve(null);
        if (currentOp === 'inpaint') {
            maybeUploadMask = ajax('upload_mask', { data: buildMaskBlob() }).then(function (r) {
                if (!r || !r.success) throw new Error('Mask upload failed');
                payload.mask_url = r.url;
                payload.mask_filename = r.filename;
                return r;
            });
        }

        setStatus('Submitting...', 'info');
        maybeUploadMask
            .then(function () { return ajax('generate', payload); })
            .then(function (r) {
                if (r.success) return onSucceeded(r);
                if (r.pending && r.prediction_id) return startPolling(r.prediction_id);
                throw new Error(r.error || 'Unknown error');
            })
            .catch(function (e) { setStatus('Error: ' + e.message, 'error'); });
    }

    function startPolling(predictionId) {
        currentPredictionId = predictionId;
        setStatus('Processing... (' + predictionId + ')', 'info');
        clearInterval(pollTimer);
        pollTimer = setInterval(function () {
            ajax('poll', { prediction_id: predictionId }).then(function (r) {
                if (r.success) { clearInterval(pollTimer); onSucceeded(r); return; }
                if (r.error) { clearInterval(pollTimer); setStatus('Error: ' + r.error, 'error'); return; }
                if (!r.pending) { clearInterval(pollTimer); setStatus('Status: ' + r.status, 'warn'); return; }
            });
        }, 3000);
    }

    function onSucceeded(r) {
        setStatus('Done.', 'ok');
        if (r.image_url) {
            els.after.src = r.image_url;
            els.before.src = els.srcUrl.value || els.before.src;
        }
        loadHistory();
    }

    function cancelCurrent() {
        if (!currentPredictionId) return;
        ajax('cancel', { prediction_id: currentPredictionId }).then(function () {
            clearInterval(pollTimer);
            setStatus('Canceled.', 'warn');
        });
    }

    function loadHistory() {
        ajax('list', { operation: '' }).then(function (r) {
            if (!r || !r.rows) return;
            els.history.innerHTML = '';
            r.rows.slice(0, 30).forEach(function (row) {
                var div = document.createElement('div');
                div.className = 'bi-scenes-card';
                div.innerHTML = '<div class="thumb">' +
                    (row.image_url ? '<img src="' + row.image_url + '">' : '<div class="placeholder">' + row.status + '</div>') +
                    '</div><div class="meta"><span class="op ' + row.operation + '">' + row.operation + '</span>' +
                    '<span class="prov">' + row.provider_key + '</span></div>';
                els.history.appendChild(div);
            });
        });
    }
    loadHistory();

    $$('[data-action="generate"]').forEach(function (b) { b.addEventListener('click', startGenerate); });
    $$('[data-action="cancel"]').forEach(function (b) { b.addEventListener('click', cancelCurrent); });

    /* slider before/after */
    function updateSlider() {
        var v = parseInt(els.slider.value, 10);
        els.after.style.clipPath = 'inset(0 0 0 ' + v + '%)';
    }
    els.slider.addEventListener('input', updateSlider);
    updateSlider();
})();
