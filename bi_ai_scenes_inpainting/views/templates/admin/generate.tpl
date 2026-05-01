{*
* BI - AI Scenes / Inpainting / Upscale - Studio.
* Three-panel layout: source image / mask drawer + prompt / output gallery.
*}
<div class="bi-scenes-page bi-scenes-studio"
     data-ajax-url="{$scenes_ajax_url|escape:'html':'UTF-8'}"
     data-token="{$scenes_token|escape:'html':'UTF-8'}"
     data-brush="{$scenes_brush_color|escape:'html':'UTF-8'}"
     data-brush-opacity="{$scenes_brush_opacity|escape:'html':'UTF-8'}">

    <header class="bi-scenes-hero">
        <h1>{l s='Studio - Scenes / Inpaint / Upscale' d='Modules.BiAiScenesInpainting.AdminGenerate'}</h1>
        <div class="bi-scenes-tabs" role="tablist">
            <button type="button" class="bi-scenes-tab is-active" data-op="scene">{l s='Scene staging' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
            <button type="button" class="bi-scenes-tab" data-op="inpaint">{l s='Inpainting' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
            <button type="button" class="bi-scenes-tab" data-op="upscale">{l s='Upscale' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
        </div>
    </header>

    <section class="bi-scenes-products">
        <div class="bi-scenes-products-head">
            <h2>{l s='Products' d='Modules.BiAiScenesInpainting.AdminGenerate'}</h2>
            <input type="search" class="bi-scenes-search" placeholder="{l s='Search by name, reference or ID' d='Modules.BiAiScenesInpainting.AdminGenerate'}">
            <select class="bi-scenes-status-filter">
                <option value="">{l s='All statuses' d='Modules.BiAiScenesInpainting.AdminGenerate'}</option>
                <option value="none">{l s='No render yet' d='Modules.BiAiScenesInpainting.AdminGenerate'}</option>
                <option value="succeeded">{l s='Succeeded' d='Modules.BiAiScenesInpainting.AdminGenerate'}</option>
                <option value="processing">{l s='Processing' d='Modules.BiAiScenesInpainting.AdminGenerate'}</option>
                <option value="failed">{l s='Failed' d='Modules.BiAiScenesInpainting.AdminGenerate'}</option>
            </select>
            <button type="button" class="btn btn-outline" data-action="reload-products">{l s='Reload' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
        </div>

        <div class="bi-scenes-bulk">
            <label class="bi-scenes-select-all"><input type="checkbox" class="bi-scenes-select-page"> {l s='Select page' d='Modules.BiAiScenesInpainting.AdminGenerate'}</label>
            <span class="bi-scenes-bulk-count" data-count="0">0 {l s='selected' d='Modules.BiAiScenesInpainting.AdminGenerate'}</span>
            <button type="button" class="btn btn-primary" data-action="queue-selected">
                {l s='Queue selected with current parameters' d='Modules.BiAiScenesInpainting.AdminGenerate'}
            </button>
        </div>

        <div class="bi-scenes-products-table-wrap">
            <table class="bi-scenes-products-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>{l s='Image' d='Modules.BiAiScenesInpainting.AdminGenerate'}</th>
                        <th>{l s='ID' d='Modules.BiAiScenesInpainting.AdminGenerate'}</th>
                        <th>{l s='Name' d='Modules.BiAiScenesInpainting.AdminGenerate'}</th>
                        <th>{l s='Reference' d='Modules.BiAiScenesInpainting.AdminGenerate'}</th>
                        <th>{l s='Last render' d='Modules.BiAiScenesInpainting.AdminGenerate'}</th>
                        <th>{l s='Actions' d='Modules.BiAiScenesInpainting.AdminGenerate'}</th>
                    </tr>
                </thead>
                <tbody class="bi-scenes-products-tbody"></tbody>
            </table>
        </div>
        <div class="bi-scenes-pagination">
            <button type="button" class="btn btn-outline" data-page-prev>&larr;</button>
            <span class="bi-scenes-page-info"></span>
            <button type="button" class="btn btn-outline" data-page-next>&rarr;</button>
        </div>

        <div class="bi-scenes-batch-progress">
            <span class="bi-scenes-batch-label"></span>
            <progress class="bi-scenes-batch-bar" max="1" value="0"></progress>
            <button type="button" class="btn btn-outline" data-action="batch-pump-toggle">{l s='Run queue' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
        </div>
    </section>

    <div class="bi-scenes-cols">
        <section class="bi-scenes-col bi-scenes-source">
            <h2>{l s='Source' d='Modules.BiAiScenesInpainting.AdminGenerate'}</h2>
            <label class="bi-scenes-uploader">
                <span>{l s='Image URL (filled automatically when you pick a product image)' d='Modules.BiAiScenesInpainting.AdminGenerate'}</span>
                <input type="url" name="image_url" placeholder="https://...">
            </label>
            <div class="bi-scenes-canvas-wrap">
                <img class="bi-scenes-source-img" alt="">
                <canvas class="bi-scenes-mask-canvas"></canvas>
            </div>
            <div class="bi-scenes-mask-tools" data-only-op="inpaint">
                <button type="button" class="btn btn-outline" data-action="brush-up">{l s='Brush +' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
                <button type="button" class="btn btn-outline" data-action="brush-down">{l s='Brush -' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
                <button type="button" class="btn btn-outline" data-action="undo">{l s='Undo' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
                <button type="button" class="btn btn-outline" data-action="clear">{l s='Clear mask' d='Modules.BiAiScenesInpainting.AdminGenerate'}</button>
            </div>
        </section>

        <section class="bi-scenes-col bi-scenes-controls">
            <h2>{l s='Parameters' d='Modules.BiAiScenesInpainting.AdminGenerate'}</h2>

            <label>{l s='Provider' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                <select name="provider" data-providers='{ldelim}"scene":{$scenes_providers.scene|json_encode nofilter},"inpaint":{$scenes_providers.inpaint|json_encode nofilter},"upscale":{$scenes_providers.upscale|json_encode nofilter}{rdelim}'></select>
            </label>

            <label data-hide-op="upscale">{l s='Prompt' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                <textarea name="prompt" rows="3" placeholder="{l s='A photorealistic product photo on a marble countertop, soft daylight, 50mm lens, ...' d='Modules.BiAiScenesInpainting.AdminGenerate'}">{$scenes_defaults.prompt|escape:'html':'UTF-8'}</textarea>
            </label>
            <label data-hide-op="upscale">{l s='Negative prompt' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                <textarea name="negative_prompt" rows="2">{$scenes_defaults.negative_prompt|escape:'html':'UTF-8'}</textarea>
            </label>

            <div class="bi-scenes-row" data-only-op="scene">
                <label>{l s='Aspect ratio' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                    <select name="aspect_ratio">
                        {foreach from=['1:1','3:4','4:3','9:16','16:9'] item=ar}
                            <option value="{$ar|escape:'html':'UTF-8'}" {if $scenes_defaults.aspect_ratio eq $ar}selected{/if}>{$ar|escape:'html':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </label>
                <label>{l s='Output' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                    <select name="output_format">
                        <option value="png" {if $scenes_defaults.output_format eq 'png'}selected{/if}>png</option>
                        <option value="jpg" {if $scenes_defaults.output_format eq 'jpg'}selected{/if}>jpg</option>
                        <option value="webp" {if $scenes_defaults.output_format eq 'webp'}selected{/if}>webp</option>
                    </select>
                </label>
            </div>

            <div class="bi-scenes-row" data-only-op="upscale">
                <label>{l s='Scale' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                    <input type="number" name="scale" min="2" max="8" value="{$scenes_defaults.upscale_factor|intval}">
                </label>
            </div>

            <div class="bi-scenes-row" data-only-op="inpaint">
                <label>{l s='Strength' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                    <input type="number" step="0.05" name="strength" min="0" max="1" value="0.85">
                </label>
                <label>{l s='Guidance' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                    <input type="number" step="0.5" name="guidance_scale" min="1" max="20" value="7">
                </label>
            </div>

            <details>
                <summary>{l s='Advanced' d='Modules.BiAiScenesInpainting.AdminGenerate'}</summary>
                <label>{l s='Seed' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                    <input type="number" name="seed" placeholder="random">
                </label>
                <label>{l s='Inference steps' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                    <input type="number" name="num_inference_steps" min="1" max="50" placeholder="auto">
                </label>
            </details>

            <div class="bi-scenes-actions">
                <button type="button" class="btn btn-primary" data-action="generate">
                    {l s='Generate' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                </button>
                <button type="button" class="btn btn-outline" data-action="cancel">
                    {l s='Cancel' d='Modules.BiAiScenesInpainting.AdminGenerate'}
                </button>
            </div>
            <p class="bi-scenes-status" role="status"></p>
        </section>

        <section class="bi-scenes-col bi-scenes-output">
            <h2>{l s='Output' d='Modules.BiAiScenesInpainting.AdminGenerate'}</h2>
            <div class="bi-scenes-compare">
                <img class="bi-scenes-before" alt="">
                <img class="bi-scenes-after"  alt="">
                <input class="bi-scenes-slider" type="range" min="0" max="100" value="50">
            </div>

            <h3>{l s='History' d='Modules.BiAiScenesInpainting.AdminGenerate'}</h3>
            <div class="bi-scenes-history"></div>
        </section>
    </div>
</div>
