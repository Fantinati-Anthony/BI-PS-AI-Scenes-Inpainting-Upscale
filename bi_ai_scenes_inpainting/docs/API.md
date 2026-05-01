# Replicate API surface

All providers go through the Replicate Predictions API
(`https://api.replicate.com/v1/predictions`) and are configured via the
shared `SCENES_REPLICATE_API_KEY` configuration value. See README.md for
the full model list.

Common payload shape:

```json
{ "version": "<sha or owner/name>", "input": { "prompt": "...", "image": "...", "mask": "..." } }
```

Async lifecycle: providers return a `prediction_id`. The studio polls
`AdminBiAiScenesGenerate` with `ajax_action=poll` every 3 s and stores the
final image into `views/img/custom/renders/`.
