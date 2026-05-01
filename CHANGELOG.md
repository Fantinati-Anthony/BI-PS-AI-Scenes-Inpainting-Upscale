# Changelog

All notable changes to the **BI - AI Scenes / Inpainting / Upscale**
module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.0] - 2026-05-01

### Added
- Initial release of `bi_ai_scenes_inpainting` (mirrors the BI 3D viewer
  module's UX but for image-only operations).
- `BiAiScenesApiInterface` + `BiAiScenesReplicateProviderTrait` as the
  shared base for all Replicate providers.
- 8 concrete providers covering 3 operation families:
  - **Scene staging**: `flux2_pro`, `flux2_dev`
  - **Inpainting**: `sd_inpainting`, `flux_inpainting`,
    `flux_inpainting_cn` (FLUX-Dev Inpainting + ControlNet)
  - **Upscale**: `google_upscaler`, `crystal_upscaler`, `recraft_upscaler`
- `BiAiScenesGenerationService` orchestrates the start / poll / cancel
  lifecycle and persists results into `bi_ai_scenes_renders` via the
  `BiAiScenesRender` ObjectModel.
- `BiAiScenesImageManager` stores generated images and base64 masks under
  `views/img/custom/{renders,masks}/`.
- Studio workspace (`AdminBiAiScenesGenerate`) with three tabs (scene /
  inpaint / upscale), provider picker, mask drawing canvas,
  before/after slider, history grid, and AJAX endpoints.
- Dashboard (`AdminBiAiScenesDashboard`) with API status, counts and
  recent renders.
- Configuration controller exposing API key, default provider per
  operation, defaults (prompt, ratio, format, scale), theme (modal
  colors, brush) and rate limits.
- Front-office product button + modal with before/after slider via
  `displayProductActions`.
- SQL schema: `bi_ai_scenes_renders`, `bi_ai_scenes_generation_log`,
  `bi_ai_scenes_batch_queue`, `bi_ai_scenes_rate_limit`.
- FR + EN translation catalogs split into 8 sub-domains.
