{*
* BI - AI Scenes / Inpainting / Upscale - Configuration form.
*}
<div class="bi-scenes-page">
    <header class="bi-scenes-hero">
        <h1>{l s='Configuration' d='Modules.BiAiScenesInpainting.AdminConfigA'}</h1>
        <p>{l s='Set your Replicate API key, select default providers and customize the modal theme.' d='Modules.BiAiScenesInpainting.AdminConfigA'}</p>
    </header>

    <form method="post" action="{$scenes_form_action|escape:'html':'UTF-8'}" class="bi-scenes-form">
        <fieldset>
            <legend>{l s='API credentials' d='Modules.BiAiScenesInpainting.AdminConfigA'}</legend>
            <label>{l s='Replicate API key' d='Modules.BiAiScenesInpainting.AdminConfigA'}
                <input type="password" name="SCENES_REPLICATE_API_KEY"
                       value="{$scenes_values.SCENES_REPLICATE_API_KEY|escape:'html':'UTF-8'}" autocomplete="off">
            </label>
        </fieldset>

        <fieldset>
            <legend>{l s='Default providers' d='Modules.BiAiScenesInpainting.AdminConfigA'}</legend>
            <label>{l s='Scene staging' d='Modules.BiAiScenesInpainting.AdminConfigA'}
                <select name="SCENES_PROVIDER_SCENE">
                    {foreach from=$scenes_providers_scene key=k item=label}
                        <option value="{$k|escape:'html':'UTF-8'}" {if $scenes_values.SCENES_PROVIDER_SCENE eq $k}selected{/if}>{$label|escape:'html':'UTF-8'}</option>
                    {/foreach}
                </select>
            </label>
            <label>{l s='Inpainting' d='Modules.BiAiScenesInpainting.AdminConfigA'}
                <select name="SCENES_PROVIDER_INPAINT">
                    {foreach from=$scenes_providers_inpaint key=k item=label}
                        <option value="{$k|escape:'html':'UTF-8'}" {if $scenes_values.SCENES_PROVIDER_INPAINT eq $k}selected{/if}>{$label|escape:'html':'UTF-8'}</option>
                    {/foreach}
                </select>
            </label>
            <label>{l s='Upscale' d='Modules.BiAiScenesInpainting.AdminConfigA'}
                <select name="SCENES_PROVIDER_UPSCALE">
                    {foreach from=$scenes_providers_upscale key=k item=label}
                        <option value="{$k|escape:'html':'UTF-8'}" {if $scenes_values.SCENES_PROVIDER_UPSCALE eq $k}selected{/if}>{$label|escape:'html':'UTF-8'}</option>
                    {/foreach}
                </select>
            </label>
        </fieldset>

        <fieldset>
            <legend>{l s='Defaults' d='Modules.BiAiScenesInpainting.AdminConfigB'}</legend>
            <label>{l s='Default prompt' d='Modules.BiAiScenesInpainting.AdminConfigB'}
                <textarea name="SCENES_DEFAULT_PROMPT" rows="3">{$scenes_values.SCENES_DEFAULT_PROMPT|escape:'html':'UTF-8'}</textarea>
            </label>
            <label>{l s='Default negative prompt' d='Modules.BiAiScenesInpainting.AdminConfigB'}
                <textarea name="SCENES_DEFAULT_NEGATIVE_PROMPT" rows="2">{$scenes_values.SCENES_DEFAULT_NEGATIVE_PROMPT|escape:'html':'UTF-8'}</textarea>
            </label>
            <label>{l s='Aspect ratio' d='Modules.BiAiScenesInpainting.AdminConfigB'}
                <select name="SCENES_DEFAULT_ASPECT_RATIO">
                    {foreach from=['1:1','3:4','4:3','9:16','16:9'] item=ar}
                        <option value="{$ar|escape:'html':'UTF-8'}" {if $scenes_values.SCENES_DEFAULT_ASPECT_RATIO eq $ar}selected{/if}>{$ar|escape:'html':'UTF-8'}</option>
                    {/foreach}
                </select>
            </label>
            <label>{l s='Output format' d='Modules.BiAiScenesInpainting.AdminConfigB'}
                <select name="SCENES_DEFAULT_OUTPUT_FORMAT">
                    {foreach from=['png','jpg','webp'] item=of}
                        <option value="{$of|escape:'html':'UTF-8'}" {if $scenes_values.SCENES_DEFAULT_OUTPUT_FORMAT eq $of}selected{/if}>{$of|escape:'html':'UTF-8'}</option>
                    {/foreach}
                </select>
            </label>
            <label>{l s='Default upscale factor' d='Modules.BiAiScenesInpainting.AdminConfigB'}
                <input type="number" name="SCENES_DEFAULT_UPSCALE_FACTOR" min="2" max="8" value="{$scenes_values.SCENES_DEFAULT_UPSCALE_FACTOR|intval}">
            </label>
        </fieldset>

        <fieldset>
            <legend>{l s='Theme' d='Modules.BiAiScenesInpainting.AdminConfigC'}</legend>
            <label>{l s='Accent color' d='Modules.BiAiScenesInpainting.AdminConfigC'}
                <input type="color" name="SCENES_ACCENT_COLOR" value="{$scenes_values.SCENES_ACCENT_COLOR|escape:'html':'UTF-8'}">
            </label>
            <label>{l s='Modal background' d='Modules.BiAiScenesInpainting.AdminConfigC'}
                <input type="color" name="SCENES_MODAL_BG_COLOR" value="{$scenes_values.SCENES_MODAL_BG_COLOR|escape:'html':'UTF-8'}">
            </label>
            <label>{l s='Modal header background' d='Modules.BiAiScenesInpainting.AdminConfigC'}
                <input type="color" name="SCENES_MODAL_HEADER_BG" value="{$scenes_values.SCENES_MODAL_HEADER_BG|escape:'html':'UTF-8'}">
            </label>
            <label>{l s='Modal header text' d='Modules.BiAiScenesInpainting.AdminConfigC'}
                <input type="color" name="SCENES_MODAL_HEADER_TEXT" value="{$scenes_values.SCENES_MODAL_HEADER_TEXT|escape:'html':'UTF-8'}">
            </label>
            <label>{l s='Modal border radius (px)' d='Modules.BiAiScenesInpainting.AdminConfigC'}
                <input type="number" name="SCENES_MODAL_BORDER_RADIUS" min="0" max="40" value="{$scenes_values.SCENES_MODAL_BORDER_RADIUS|intval}">
            </label>
            <label>{l s='Mask brush color' d='Modules.BiAiScenesInpainting.AdminConfigC'}
                <input type="color" name="SCENES_MASK_BRUSH_COLOR" value="{$scenes_values.SCENES_MASK_BRUSH_COLOR|escape:'html':'UTF-8'}">
            </label>
        </fieldset>

        <fieldset>
            <legend>{l s='Quotas / display' d='Modules.BiAiScenesInpainting.AdminConfigD'}</legend>
            <label>{l s='Rate limit (requests / window)' d='Modules.BiAiScenesInpainting.AdminConfigD'}
                <input type="number" name="SCENES_RATE_LIMIT" min="1" max="1000" value="{$scenes_values.SCENES_RATE_LIMIT|intval}">
            </label>
            <label>{l s='Rate window (seconds)' d='Modules.BiAiScenesInpainting.AdminConfigD'}
                <input type="number" name="SCENES_RATE_WINDOW" min="10" max="3600" value="{$scenes_values.SCENES_RATE_WINDOW|intval}">
            </label>
            <label>{l s='Max input image size (bytes)' d='Modules.BiAiScenesInpainting.AdminConfigD'}
                <input type="number" name="SCENES_MAX_INPUT_SIZE" min="1024" value="{$scenes_values.SCENES_MAX_INPUT_SIZE|intval}">
            </label>
            <label>{l s='Display mode' d='Modules.BiAiScenesInpainting.AdminConfigD'}
                <select name="SCENES_DISPLAY_MODE">
                    <option value="modal" {if $scenes_values.SCENES_DISPLAY_MODE eq 'modal'}selected{/if}>modal</option>
                    <option value="inline" {if $scenes_values.SCENES_DISPLAY_MODE eq 'inline'}selected{/if}>inline</option>
                </select>
            </label>
            <label>{l s='Front-office button label' d='Modules.BiAiScenesInpainting.AdminConfigD'}
                <input type="text" name="SCENES_BUTTON_LABEL" value="{$scenes_values.SCENES_BUTTON_LABEL|escape:'html':'UTF-8'}">
            </label>
            <label>{l s='Enable CSP' d='Modules.BiAiScenesInpainting.AdminConfigD'}
                <input type="checkbox" name="SCENES_CSP_ENABLED" value="1" {if $scenes_values.SCENES_CSP_ENABLED}checked{/if}>
            </label>
        </fieldset>

        <div class="bi-scenes-actions">
            <button type="submit" name="submitBiAiScenesConfig" class="btn btn-primary">
                {l s='Save settings' d='Modules.BiAiScenesInpainting.AdminConfigA'}
            </button>
        </div>
    </form>
</div>
