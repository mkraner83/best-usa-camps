# Development Notes

This repository includes development tooling scaffolding:

- `phpcs.xml.dist` with WordPress Coding Standards.
- `.editorconfig` and `.gitignore`.
- `composer.json` for installing PHPCS + WPCS locally.

## Suggested Workflow

1. Install dev tools:
   ```bash
   composer install
   ```
2. Run lints:
   ```bash
   composer run lint
   composer run lint:fix
   ```
3. Address findings iteratively, starting with security (nonces, sanitization/escaping) in the hotspots listed in the audit.
