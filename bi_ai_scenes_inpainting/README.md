# BI - AI Scenes / Inpainting / Upscale

PrestaShop module that generates product scenes (mise en scène), inpaints
local regions, and upscales images using Replicate.

It mirrors the look-and-feel of the BI 3D viewer module (admin layout,
batch table, modal, theme, multilingual catalogs) but swaps the 3D
pipeline for image-only operations.

## Supported Replicate models

| Operation | Provider key | Replicate model |
|---|---|---|
| Scene staging | `flux2_pro` | `black-forest-labs/flux-2-pro` |
| Scene staging | `flux2_dev` | `black-forest-labs/flux-2-dev` |
| Inpainting | `sd_inpainting` | `stability-ai/stable-diffusion-inpainting` |
| Inpainting | `flux_inpainting` | `zsxkib/flux-dev-inpainting` |
| Inpainting + ControlNet | `flux_inpainting_cn` | `zsxkib/flux-dev-inpainting-controlnet` |
| Upscale | `google_upscaler` | `google/upscaler` |
| Upscale | `crystal_upscaler` | `philz1337x/crystal-upscaler` |
| Upscale | `recraft_upscaler` | `recraft-ai/recraft-crisp-upscaled` |

## Architecture

```
classes/
  BiAiScenesApiInterface.php              Common provider interface
  BiAiScenesReplicateProviderTrait.php    Shared Replicate plumbing
  BiAiScenes{Flux2Pro,Flux2Dev,SdInpainting,FluxInpainting,
             FluxInpaintingControlnet,GoogleUpscaler,
             CrystalUpscaler,RecraftUpscaler}Provider.php
  BiAiScenesGenerationService.php         Orchestrator (start / poll / cancel)
  BiAiScenesRender.php                    ObjectModel for stored renders
  BiAiScenesImageManager.php              File storage for renders + masks
  BiAiScenesRenderService.php             View-models for FO templates
  BiAiScenesHttpClient.php                cURL wrapper for Replicate
  BiAiScenesConfiguration.php             Config keys + defaults
  BiAiScenesInstaller.php                 SQL + hooks + tabs
controllers/admin/
  AdminBiAiScenesParentController.php
  AdminBiAiScenesDashboardController.php
  AdminBiAiScenesConfigController.php
  AdminBiAiScenesGenerateController.php   Studio + AJAX endpoints
sql/install.sql, sql/uninstall.sql        4 tables (renders / log / batch / rate_limit)
views/                                    Admin + front templates / CSS / JS
translations/                             FR + EN catalogs
```

## Studio (admin)

Three-tab workspace:

- **Scene staging** — text-to-image with optional product image conditioning.
- **Inpainting** — paint a mask on the canvas, set a prompt, send.
- **Upscale** — pick a factor 2x → 8x.

Common features: provider picker, advanced parameters, AJAX generate / poll
(3 s) / cancel, before/after slider, history grid.

## Front-office

If a render exists for a product, the module displays an "AI scenes" button
on the product page. Clicking it opens a modal with a before/after slider
between the source image and the generated render.

## Install

1. Drop the `bi_ai_scenes_inpainting/` directory into `modules/` and install
   from the back-office.
2. Open **BI - AI Scenes → Configuration** and set your Replicate API key
   plus default providers per operation.
3. Open **BI - AI Scenes → Scenes / Inpaint / Upscale** to start generating.

Compatible PrestaShop 1.7.x → 9.x.
