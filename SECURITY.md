# Security Policy

## Reporting a Vulnerability

Please report security vulnerabilities **privately** to security@fantinati.com.
Do NOT open a public GitHub issue for security problems.

We will acknowledge receipt within 48 hours and provide an initial assessment
within 5 business days.

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.0.x   | yes       |
| < 1.0   | no        |

## Threat Model

This module integrates with Replicate (TRELLIS, Meshy, Tripo, RMBG) to
generate 3D models from product images. The main attack surfaces are:

1. **API key disclosure** - the Replicate API key is stored encrypted at rest
   via `BiAi3dApiKeyVault` (AES-256-GCM, key derived from `_COOKIE_KEY_`).
2. **Malicious GLB upload** - uploaded `.glb` files are validated by
   `BiAiImage3dGlbManager` (magic bytes, version, size, in-memory ZIP scan).
3. **AJAX abuse** - admin AJAX actions are rate-limited via the
   `bi_ai_3d_rate_limit` table (default 30 req/min/employee/action).
4. **CSRF** - PrestaShop's built-in CSRF tokens (`getAdminTokenLite`) are used
   on every admin form.
5. **XSS via widget** - `BiAi3dWidgetService` sanitises every input
   (CSS colors, IDs, enums) before rendering Smarty templates.
6. **CSP** - a strict Content-Security-Policy can be enabled via the
   `BiAi3dCspHelper::emit()` helper (toggle: `BI_AI_3D_CSP_ENABLED`).

## Hardening Checklist

- [x] API key encrypted at rest (`BiAi3dApiKeyVault`)
- [x] All outbound HTTP via central client with timeouts (`BiAi3dHttpClient`)
- [x] GLB file validation (header + ZIP integrity)
- [x] Rate-limited AJAX endpoints
- [x] CSRF tokens on every admin form
- [x] CSP emission helper (`BiAi3dCspHelper`)
- [x] PHPStan level 6 + 73+ unit tests in CI
