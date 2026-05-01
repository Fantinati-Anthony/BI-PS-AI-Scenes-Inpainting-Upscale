# Changelog

All notable changes to the **BI - AI Image to 3D Viewer & AR** module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.2.0] - 2026-04-19

### Added
- `BiAi3dUrlGuard`: SSRF protection layer validating every outbound URL
  before cURL is invoked. Blocks private/reserved IPs, cloud metadata
  endpoints (AWS 169.254.169.254, GCP metadata.google.internal, Azure,
  Alibaba, Oracle), non-http(s) schemes (file://, gopher://, data://,
  php://) and, when configured, enforces a provider host allowlist
  (typically `['api.replicate.com', 'replicate.delivery']`).
- Unit test suite for the URL guard (`tests/BiAi3dUrlGuardTest.php`,
  12 cases covering schemes, IPs, metadata hostnames, allowlist,
  throwing variant and dev escape hatch).
- Multistore integration guide (`docs/MULTISHOP.md`) with per-shop /
  shared scope matrix and migration procedure.
- End-user troubleshooting guide (`docs/TROUBLESHOOTING.md`) covering
  install, generation pipeline, viewer/AR, CSP and admin UI issues.
- GLB optimization guide (`docs/GLB-OPTIMIZATION.md`) with size
  budgets, Draco / meshopt / KTX2 pipelines, TRELLIS tuning, gzip/CDN
  serving and LCP checklist.

### Changed
- Module licence header aligned with repository licence (Academic Free
  License 3.0 - AFL-3.0). Previously the main module file declared
  `@license Proprietary` while README/composer.json already stated
  AFL-3.0.
- `BiAi3dHttpClient` now routes every outbound URL through
  `BiAi3dUrlGuard` and returns a structured `['success' => false, ...]`
  envelope when the URL is rejected, mirroring the existing error
  surface.
- `BiAi3dHttpClient::downloadFile()` now streams the response through
  a `CURLOPT_WRITEFUNCTION` callback enforcing a hard 100 MB byte cap
  (aligned with `BiAiImage3dGlbManager::MAX_GLB_FILE_SIZE`) so a
  malicious provider response cannot silently fill disk before the
  post-download size check runs.
- `BiAi3dHttpClient` locks `CURLOPT_PROTOCOLS` and
  `CURLOPT_REDIR_PROTOCOLS` to `HTTP|HTTPS` so a 3xx response from a
  tampered provider cannot pivot to `file://` / `gopher://`.
- `BiAi3dHttpClient` sets `CURLOPT_CONNECTTIMEOUT = 15s` to avoid
  hanging the admin UI on unreachable providers.
- README now has a dedicated Security section, a Multistore section
  and links to the three new docs.
- `.gitignore` now excludes release `.zip` artefacts so the GitHub
  Actions-built zip is never accidentally committed.

### Security
- Mitigated SSRF and blind SSRF via cloud-metadata endpoints by
  routing every outbound fetch through `BiAi3dUrlGuard`.
- Capped GLB downloads to a deterministic byte limit enforced
  mid-flight (not just post-download), preventing disk-exhaustion DoS
  from an adversarial provider.
- Locked cURL redirects to http/https only.

### Removed
- Release zip (`bi_ai_3dviewer.zip`) removed from VCS. The zip is built
  by GitHub Actions on merge to `main` and is no longer tracked.

## [1.1.0] - 2026-03-17

### Added
- **Fullscreen AR viewer page** - The dedicated AR page now covers the entire viewport, hiding PrestaShop header/footer/breadcrumb for an immersive experience.
- **AR distance phrase** - A human-friendly distance recommendation is displayed above the AR buttons based on the object's real dimensions (6 tiers: 20 cm, 30 cm, 1 m, 2 m, 3 m, 5 m).
- **AR page color customization** - New admin section (2.7) with 11 color fields to customize the AR viewer page: header, viewer, controls bar, buttons, help button, and tutorial panel.
- **Dynamic camera orbit** - The AR viewer page now adapts camera distance based on the model's actual size (orbit = 2.5x, min = 0.3x, max = 8x of the largest dimension).
- **Dimension warnings** - A confirmation dialog warns when entering extreme dimensions (< 1 mm or > 5 m) in the resize UI before applying.
- **`ar-scale="fixed"` on all viewers** - All display modes (inline, modal, tab, badge) now include `ar-scale="fixed"` and `ar-placement="floor"` so AR always uses real-world dimensions.

### Changed
- **AR launch button icon** - Replaced the phone/rectangle SVG with a proper 3D cube icon.
- **Bottom controls layout** - Restructured with distance phrase on top row, buttons below.
- Renumbered "Other Color Overrides" section from 2.7 to 2.8 in admin display settings.

---

## [1.0.0] - 2026-03-13

Initial release.

### Added

**Core Features**
- AI-powered 3D model generation from product images via Replicate API (TRELLIS)
- Interactive 3D viewer with Google model-viewer web component
- Augmented Reality (AR) support with QR code sharing (WebXR, Scene Viewer, Quick Look)
- Background removal preprocessing (RMBG via Bria model) with caching
- 5 display modes: Button + Modal, Inline, Tab, Badge Right, Badge Left
- Product listing 3D/AR indicators (button, icon, badge styles)
- Batch generation for multiple products
- Manual GLB file upload support
- Real-scale AR dimensions with height-based scaling
- Per-combination/attribute 3D models with group linking
- 3D model controls: brightness, shadows, auto-rotate, camera orbit, screenshot, fullscreen
- Customer group access control
- Multi-shop support
- 3D product gallery page
- Widget support for page builders (Creative Elements, etc.)
- QuickView ON/OFF toggle for product quick-view 3D display
- Listing inline mode: replace product images with interactive 3D on category pages
- Full backup/restore system (JSON settings + ZIP with GLB files)
- Comprehensive admin dashboard with generation statistics
- Configurable rate limiting per employee (`AI3D_RATE_LIMIT`, default: 30 req/min)
- Automatic cleanup of stuck "processing" models (>10 min threshold)
- 7 languages: French, German, Spanish, Italian, Dutch, Polish, Portuguese (1268 keys each)

**Security**
- CSRF token validation on all 23 admin AJAX endpoints
- Path traversal prevention with `realpath()` validation
- XSS protection with comprehensive input sanitization (colors, CSS classes, IDs, enums)
- GLB binary validation (magic bytes, version, declared length)
- `is_uploaded_file()` checks before processing uploads
- URL scheme whitelist (http/https only)
- TOCTOU-safe in-memory GLB validation for ZIP imports
- Database-backed rate limiting for API calls

**API Error Handling**
- HTTP 5xx server error retry with exponential backoff
- HTTP 429 rate limit retry with exponential backoff
- cURL error logging to PrestaShop logs
- Consecutive poll error detection in TRELLIS provider (fails after 3)
- RMBG poll error/timeout/failure logging
- Exception handling wrapper around API calls

**Accessibility**
- `aria-label` on all interactive elements (buttons, links, modals, selects)
- `aria-modal` and `role="dialog"` on all modal dialogs
- `aria-pressed` on toggle buttons
- `aria-hidden="true"` on decorative SVG icons
- `role="progressbar"` with proper ARIA attributes on loading indicators
- `aria-live="polite"` on loading containers

**Developer**
- GitHub Actions CI/CD (PHPUnit PHP 7.4-8.4, PHPStan, PHP-CS-Fixer)
- PHPStan static analysis: level 5, 0 errors
- PHP-CS-Fixer: PSR-12 compliance
- API documentation (`docs/API.md`): 23 admin + 2 front endpoints
- Developer guide (`README.dev.md`)
- User guides (`docs/readme_en.md`, `docs/readme_fr.md`)
