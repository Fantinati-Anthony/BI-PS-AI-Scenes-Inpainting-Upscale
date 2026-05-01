{*
* BI - AI Scenes / Inpainting / Upscale - product page extra tab.
*}
<div class="bi-scenes-product-tab">
    <h3>{l s='AI Scenes' d='Modules.BiAiScenesInpainting.ProductTab'}</h3>
    {if $scenes_product_rows|@count}
        <div class="bi-scenes-grid">
            {foreach from=$scenes_product_rows item=r}
                <a class="bi-scenes-card" href="{$r.public_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
                    {if $r.public_url}<img src="{$r.public_url|escape:'html':'UTF-8'}" alt="">{/if}
                    <div class="meta">
                        <span class="op {$r.operation|escape:'html':'UTF-8'}">{$r.operation|escape:'html':'UTF-8'}</span>
                        <span class="prov">{$r.provider_key|escape:'html':'UTF-8'}</span>
                    </div>
                </a>
            {/foreach}
        </div>
    {else}
        <p class="muted">{l s='No render yet for this product.' d='Modules.BiAiScenesInpainting.ProductTab'}</p>
    {/if}
    <a class="btn btn-primary" href="{$scenes_link_generate|escape:'html':'UTF-8'}?id_product={$scenes_id_product|intval}">
        {l s='Open the Studio' d='Modules.BiAiScenesInpainting.ProductTab'}
    </a>
</div>
