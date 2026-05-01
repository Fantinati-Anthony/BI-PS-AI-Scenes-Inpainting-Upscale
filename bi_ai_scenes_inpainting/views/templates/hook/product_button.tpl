{*
* BI - AI Scenes / Inpainting / Upscale - product front-office button + modal.
*}
{if $scenes_view.has_render}
    <button type="button" class="bi-scenes-fo-btn" data-toggle="bi-scenes-modal"
            style="background:{$scenes_button_bg|escape:'html':'UTF-8'};color:{$scenes_button_fg|escape:'html':'UTF-8'};">
        {$scenes_button_label|escape:'html':'UTF-8'}
    </button>
    <div class="bi-scenes-modal" hidden>
        <div class="bi-scenes-modal-backdrop" data-close-modal></div>
        <div class="bi-scenes-modal-dialog" role="dialog" aria-modal="true">
            <header>
                <h2>{l s='AI Scene preview' d='Modules.BiAiScenesInpainting.Shop'}</h2>
                <button type="button" class="bi-scenes-modal-close" data-close-modal>&times;</button>
            </header>
            <div class="bi-scenes-modal-body">
                <div class="bi-scenes-compare">
                    {if $scenes_view.source_url}<img class="bi-scenes-before" src="{$scenes_view.source_url|escape:'html':'UTF-8'}" alt="">{/if}
                    <img class="bi-scenes-after" src="{$scenes_view.image_url|escape:'html':'UTF-8'}" alt="">
                    <input class="bi-scenes-slider" type="range" min="0" max="100" value="50">
                </div>
            </div>
        </div>
    </div>
{/if}
