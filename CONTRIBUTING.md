# Contributing

Thank you for your interest in contributing!

## Development Setup

```bash
git clone https://github.com/Fantinati-Anthony/BI-PS-AI-Image-to-3D-Product-Viewer-AR-Augmented-Reality.git
cd BI-PS-AI-Image-to-3D-Product-Viewer-AR-Augmented-Reality
composer install
```

## Quality Gates

Before opening a pull request, run all checks:

```bash
composer cs-check    # PHP-CS-Fixer dry run (PSR-12)
composer analyse     # PHPStan level 6
composer test        # PHPUnit (73+ tests)
```

## Coding Standards

- PSR-12 (enforced by PHP-CS-Fixer)
- Class names use the `BiAi3d` or `BiAiImage3d` prefix
- Public methods must be type-hinted (PHP 7.4+ syntax)
- New features must include unit tests covering happy + error paths
- Security-sensitive code (file uploads, AJAX) requires extra reviewer approval

## Commit Convention

We use [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` new feature
- `fix:` bug fix
- `chore:` tooling / docs
- `refactor:` code change without behaviour change
- `test:` new or updated tests
- `perf:` performance improvement
- `security:` hardening / vulnerability fix
