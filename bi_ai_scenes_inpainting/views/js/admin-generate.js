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

    /* ============================================================
     * Products panel: paginated list, image picker, bulk queueing.
     * ============================================================ */
    var prodEls = {
        tbody: $('.bi-scenes-products-tbody'),
        search: $('.bi-scenes-search'),
        statusFilter: $('.bi-scenes-status-filter'),
        selectPage: $('.bi-scenes-select-page'),
        bulkCount: $('.bi-scenes-bulk-count'),
        pageInfo: $('.bi-scenes-page-info'),
        progressLabel: $('.bi-scenes-batch-label'),
        progressBar: $('.bi-scenes-batch-bar'),
    };
    var prodState = { page: 1, perPage: 20, total: 0, rows: [] };
    var pickedByProduct = {}; // id_product -> { id_product_attribute, image_url }

    function pickedKey(idProduct, idPa) { return idProduct + ':' + (idPa || 0); }

    function selectedItems() {
        var out = [];
        Array.prototype.forEach.call(prodEls.tbody.querySelectorAll('input.bi-scenes-row-cb:checked'), function (cb) {
            var idP = parseInt(cb.value, 10);
            var pick = pickedByProduct[idP];
            if (!pick || !pick.image_url) return;
            out.push({ id_product: idP, id_product_attribute: pick.id_product_attribute || 0, image_url: pick.image_url });
        });
        return out;
    }

    function refreshBulkCount() {
        var n = selectedItems().length;
        prodEls.bulkCount.dataset.count = n;
        prodEls.bulkCount.textContent = n + ' ' + prodEls.bulkCount.textContent.replace(/^\d+ /, '');
    }

    function renderProductsTable() {
        prodEls.tbody.innerHTML = '';
        prodState.rows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.dataset.idProduct = row.id_product;
            var pickedUrl = (pickedByProduct[row.id_product] && pickedByProduct[row.id_product].image_url) ||
                            (row.cover_url || '');
            if (pickedUrl && !pickedByProduct[row.id_product]) {
                pickedByProduct[row.id_product] = { id_product_attribute: 0, image_url: pickedUrl };
            }
            var statusBadge = row.render_status
                ? '<span class="bi-scenes-status-badge ' + row.render_status + '">' + row.render_operation + ' / ' + row.render_status + '</span>'
                : '<span class="bi-scenes-status-badge none">—</span>';
            var renderThumb = row.render_url ? '<a href="' + row.render_url + '" target="_blank"><img class="bi-scenes-mini" src="' + row.render_url + '"></a>' : '';
            tr.innerHTML =
                '<td><input type="checkbox" class="bi-scenes-row-cb" value="' + row.id_product + '"></td>' +
                '<td><img class="bi-scenes-cover" src="' + (pickedUrl || '') + '" alt=""></td>' +
                '<td>' + row.id_product + '</td>' +
                '<td>' + (row.name || '') + (row.has_combinations ? ' <span class="bi-scenes-combo-badge">v</span>' : '') + '</td>' +
                '<td>' + (row.reference || '') + '</td>' +
                '<td>' + statusBadge + ' ' + renderThumb + '</td>' +
                '<td>' +
                    '<button type="button" class="btn btn-outline btn-xs" data-row-action="pick-image">' + 'Image' + '</button> ' +
                    '<button type="button" class="btn btn-primary btn-xs" data-row-action="run-now">' + 'Run' + '</button>' +
                '</td>';
            prodEls.tbody.appendChild(tr);
        });
        prodEls.pageInfo.textContent = prodState.page + ' / ' + Math.max(1, Math.ceil(prodState.total / prodState.perPage));
        refreshBulkCount();
    }

    function loadProducts() {
        ajax('list_products', {
            page: prodState.page,
            per_page: prodState.perPage,
            search: prodEls.search.value,
            status: prodEls.statusFilter.value,
        }).then(function (r) {
            if (!r || !r.success) return;
            prodState.rows = r.rows;
            prodState.total = r.total;
            renderProductsTable();
        });
    }

    prodEls.search.addEventListener('input', function () {
        clearTimeout(prodEls.search._t);
        prodEls.search._t = setTimeout(function () { prodState.page = 1; loadProducts(); }, 250);
    });
    prodEls.statusFilter.addEventListener('change', function () { prodState.page = 1; loadProducts(); });
    $$('[data-action="reload-products"]').forEach(function (b) { b.addEventListener('click', loadProducts); });
    $$('[data-page-prev]').forEach(function (b) { b.addEventListener('click', function () { if (prodState.page > 1) { prodState.page--; loadProducts(); } }); });
    $$('[data-page-next]').forEach(function (b) { b.addEventListener('click', function () { if (prodState.page * prodState.perPage < prodState.total) { prodState.page++; loadProducts(); } }); });

    prodEls.selectPage.addEventListener('change', function () {
        var on = prodEls.selectPage.checked;
        Array.prototype.forEach.call(prodEls.tbody.querySelectorAll('input.bi-scenes-row-cb'), function (cb) { cb.checked = on; });
        refreshBulkCount();
    });
    prodEls.tbody.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('bi-scenes-row-cb')) refreshBulkCount();
    });

    /* Per-row actions */
    prodEls.tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var tr = btn.closest('tr');
        var idP = parseInt(tr.dataset.idProduct, 10);
        if (btn.dataset.rowAction === 'pick-image') openImagePicker(idP, tr);
        if (btn.dataset.rowAction === 'run-now') runRowNow(idP);
    });

    function openImagePicker(idProduct, tr) {
        ajax('product_images', { id_product: idProduct }).then(function (r) {
            if (!r || !r.success) return;
            var modal = document.createElement('div');
            modal.className = 'bi-scenes-modal';
            var html = '<div class="bi-scenes-modal-backdrop" data-close-modal></div>' +
                '<div class="bi-scenes-modal-dialog"><header><h2>' + r.name + ' (#' + r.id_product + ')</h2>' +
                '<button type="button" class="bi-scenes-modal-close" data-close-modal>&times;</button></header>' +
                '<div class="bi-scenes-modal-body">' +
                '<h3>Product images</h3><div class="bi-scenes-img-grid">';
            r.images.forEach(function (img) {
                html += '<button type="button" class="bi-scenes-pick" data-id-pa="0" data-url="' + img.url + '">' +
                        '<img src="' + img.thumb + '"><span>#' + img.id_image + (img.cover ? ' ★' : '') + '</span></button>';
            });
            html += '</div>';
            if (r.combinations && r.combinations.length) {
                html += '<h3>Combinations</h3><div class="bi-scenes-img-grid">';
                r.combinations.forEach(function (c) {
                    if (!c.thumb) return;
                    var url = c.thumb.replace(/-small_default/, '-large_default');
                    html += '<button type="button" class="bi-scenes-pick" data-id-pa="' + c.id_product_attribute + '" data-url="' + url + '">' +
                            '<img src="' + c.thumb + '"><span>' + (c.reference || ('#' + c.id_product_attribute)) + '<br>' +
                            (c.attributes || []).join(' / ') + '</span></button>';
                });
                html += '</div>';
            }
            html += '</div></div>';
            modal.innerHTML = html;
            modal.style.position = 'fixed'; modal.style.inset = '0'; modal.style.zIndex = '99999';
            document.body.appendChild(modal);
            modal.querySelectorAll('[data-close-modal]').forEach(function (el) { el.addEventListener('click', function () { modal.remove(); }); });
            modal.querySelectorAll('.bi-scenes-pick').forEach(function (b) {
                b.addEventListener('click', function () {
                    var idPa = parseInt(b.dataset.idPa, 10) || 0;
                    pickedByProduct[idProduct] = { id_product_attribute: idPa, image_url: b.dataset.url };
                    var cover = tr.querySelector('.bi-scenes-cover');
                    if (cover) cover.src = b.dataset.url;
                    modal.remove();
                });
            });
        });
    }

    function runRowNow(idProduct) {
        var pick = pickedByProduct[idProduct];
        if (!pick || !pick.image_url) {
            setStatus('Pick a product image first.', 'warn');
            return;
        }
        els.srcUrl.value = pick.image_url;
        loadSource(pick.image_url);
        var providerKey = els.provider.value;
        ajax('generate', {
            provider: providerKey,
            prompt: els.prompt.value,
            negative_prompt: els.negPrompt.value,
            image_url: pick.image_url,
            id_product: idProduct,
            id_product_attribute: pick.id_product_attribute || 0,
            aspect_ratio: ($('select[name="aspect_ratio"]') || {}).value,
            output_format: ($('select[name="output_format"]') || {}).value,
            scale: ($('input[name="scale"]') || {}).value,
            strength: ($('input[name="strength"]') || {}).value,
            guidance_scale: ($('input[name="guidance_scale"]') || {}).value,
            seed: ($('input[name="seed"]') || {}).value,
        }).then(function (r) {
            if (r && r.success) onSucceeded(r);
            else if (r && r.pending) startPolling(r.prediction_id);
            else if (r && r.error) setStatus('Error: ' + r.error, 'error');
        });
    }

    /* Bulk queue */
    $$('[data-action="queue-selected"]').forEach(function (b) {
        b.addEventListener('click', function () {
            var items = selectedItems();
            if (!items.length) { setStatus('Select rows + pick an image per row first.', 'warn'); return; }
            ajax('queue_batch', {
                items: JSON.stringify(items),
                operation: currentOp,
                provider: els.provider.value,
                prompt: els.prompt.value,
                negative_prompt: els.negPrompt.value,
                aspect_ratio: ($('select[name="aspect_ratio"]') || {}).value,
                output_format: ($('select[name="output_format"]') || {}).value,
                scale: ($('input[name="scale"]') || {}).value,
                strength: ($('input[name="strength"]') || {}).value,
                guidance_scale: ($('input[name="guidance_scale"]') || {}).value,
                num_inference_steps: ($('input[name="num_inference_steps"]') || {}).value,
            }).then(function (r) {
                if (r && r.success) {
                    setStatus('Queued ' + r.queued + ' items. Click "Run queue" to process.', 'info');
                    refreshBatchStatus();
                } else if (r && r.error) {
                    setStatus('Error: ' + r.error, 'error');
                }
            });
        });
    });

    var batchPumpRunning = false;
    function pumpOne() {
        if (!batchPumpRunning) return;
        ajax('process_batch_item', {}).then(function (r) {
            if (!r || !r.success || r.done) {
                batchPumpRunning = false;
                refreshBatchStatus();
                loadProducts();
                return;
            }
            refreshBatchStatus();
            // Tiny delay to avoid hammering
            setTimeout(pumpOne, 800);
        });
    }
    function refreshBatchStatus() {
        ajax('batch_status', {}).then(function (r) {
            if (!r || !r.success) return;
            var c = r.counts;
            var total = c.queued + c.processing + c.completed + c.failed + c.canceled;
            var done = c.completed + c.failed + c.canceled;
            prodEls.progressBar.max = Math.max(1, total);
            prodEls.progressBar.value = done;
            prodEls.progressLabel.textContent = done + ' / ' + total + ' (queued ' + c.queued + ', processing ' + c.processing + ', completed ' + c.completed + ', failed ' + c.failed + ')';
        });
    }
    $$('[data-action="batch-pump-toggle"]').forEach(function (b) {
        b.addEventListener('click', function () {
            batchPumpRunning = !batchPumpRunning;
            b.textContent = batchPumpRunning ? 'Pause queue' : 'Run queue';
            if (batchPumpRunning) pumpOne();
        });
    });

    loadProducts();
    refreshBatchStatus();
})();
