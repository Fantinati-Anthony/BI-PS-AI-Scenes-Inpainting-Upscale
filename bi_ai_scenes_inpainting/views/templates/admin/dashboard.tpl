{*
* BI - AI Scenes / Inpainting / Upscale - Dashboard.
*}
<div class="bi-scenes-page">
    <header class="bi-scenes-hero">
        <div class="bi-scenes-hero-text">
            <h1>{l s='AI Scenes / Inpainting / Upscale' d='Modules.BiAiScenesInpainting.AdminDashboard'}</h1>
            <p>{l s='Generate product scenes, inpaint regions and upscale images using Replicate models.' d='Modules.BiAiScenesInpainting.AdminDashboard'}</p>
        </div>
        <nav class="bi-scenes-hero-nav">
            <a class="btn btn-primary" href="{$scenes_link_generate|escape:'html':'UTF-8'}">
                {l s='Open the Studio' d='Modules.BiAiScenesInpainting.AdminDashboard'}
            </a>
            <a class="btn btn-outline" href="{$scenes_link_config|escape:'html':'UTF-8'}">
                {l s='Configuration' d='Modules.BiAiScenesInpainting.AdminDashboard'}
            </a>
        </nav>
    </header>

    <section class="bi-scenes-credentials {if $scenes_credentials_ok}is-ok{else}is-warn{/if}">
        <strong>{l s='Replicate API' d='Modules.BiAiScenesInpainting.AdminDashboard'}:</strong>
        <span>{$scenes_credentials_msg|escape:'html':'UTF-8'}</span>
    </section>

    <section class="bi-scenes-stats">
        <div class="bi-scenes-stat"><span class="num">{$scenes_counts.succeeded|intval}</span><span class="lbl">{l s='Succeeded' d='Modules.BiAiScenesInpainting.AdminDashboard'}</span></div>
        <div class="bi-scenes-stat"><span class="num">{$scenes_counts.processing|intval}</span><span class="lbl">{l s='Processing' d='Modules.BiAiScenesInpainting.AdminDashboard'}</span></div>
        <div class="bi-scenes-stat"><span class="num">{$scenes_counts.pending|intval}</span><span class="lbl">{l s='Pending' d='Modules.BiAiScenesInpainting.AdminDashboard'}</span></div>
        <div class="bi-scenes-stat"><span class="num">{$scenes_counts.failed|intval}</span><span class="lbl">{l s='Failed' d='Modules.BiAiScenesInpainting.AdminDashboard'}</span></div>
        <div class="bi-scenes-stat"><span class="num">{$scenes_counts.canceled|intval}</span><span class="lbl">{l s='Canceled' d='Modules.BiAiScenesInpainting.AdminDashboard'}</span></div>
    </section>

    <section class="bi-scenes-recent">
        <h2>{l s='Recent renders' d='Modules.BiAiScenesInpainting.AdminDashboard'}</h2>
        <div class="bi-scenes-grid">
            {foreach from=$scenes_recent item=row}
                <div class="bi-scenes-card">
                    <div class="thumb">
                        {if $row.image_url}
                            <img src="{$row.image_url|escape:'html':'UTF-8'}" alt="">
                        {else}
                            <div class="placeholder">{$row.status|escape:'html':'UTF-8'}</div>
                        {/if}
                    </div>
                    <div class="meta">
                        <div class="op {$row.operation|escape:'html':'UTF-8'}">{$row.operation|escape:'html':'UTF-8'}</div>
                        <div class="prov">{$row.provider_key|escape:'html':'UTF-8'}</div>
                        <div class="date">{$row.date_add|escape:'html':'UTF-8'}</div>
                    </div>
                </div>
            {foreachelse}
                <p class="muted">{l s='No renders yet. Open the Studio to start.' d='Modules.BiAiScenesInpainting.AdminDashboard'}</p>
            {/foreach}
        </div>
    </section>
</div>
