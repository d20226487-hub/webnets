# AI brief settings

The AI brief (developer skeleton panel, `build/ai-brief.php`) is assembled from these files:

- `system.md`: the system prompt (role, target stack, acceptance checklist). Placeholders: {scope}, {site}, {type}, {angle}, {language}.
- `types/<type>.md`: guidance per site type.
- `markets/<market>.md`: market context and the rules the AI must not break.

Edit them in the local settings page (http://localhost:8080/settings/ with `build/serve-local.bat`) or in any editor, then rebuild the review pages.
