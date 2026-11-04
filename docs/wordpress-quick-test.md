# Test a site type on WordPress in 10 minutes

The **Satellite page** template renders a complete site type (landing, app page, …) inside WordPress from
a pasted site config. It uses the same renderer as the static builder (`lib/page.php`), so the page is identical
to `php build/build.php` output. No ACF and no block editing needed.

## 1. Get a WordPress install

Any local or staging WordPress with PHP **8.2.35+** works. Easiest options:

- **LocalWP** (localwp.com): one click, local only.
- **wp-env** (`npx @wordpress/env start`): needs Docker.
- Your XAMPP: works, but update its PHP first (the current build is affected by PHP2Shell), and keep it on localhost.

## 2. Install the system into your theme

1. Copy the whole `satellite-system/` folder into the active theme and rename it to `sx`:
   `wp-content/themes/<your-theme>/sx/`
2. Add one line to the theme's `functions.php`:

   ```php
   require get_template_directory() . '/sx/wp/sx-page-template.php';
   ```

   (For ACF blocks as well, also require `sx/wp/sx-blocks.php`. Not needed for this test.)

## 3. Create a test page

1. **Pages → Add New.** Give it any title.
2. In the sidebar, **Page Attributes → Template → Satellite page.**
   (Block editor: Page tab → Template. Classic editor: Page Attributes box.)
3. Scroll to the **Satellite site config** box and paste a config, for example the contents of
   `sx/sites/demos/app-a-vantro-au.json` or `sx/sites/demos/b-nyxa-kz.json`.
4. **Publish → View page.**

Each page can hold a different config, so one test install can show every type, brand and market side by side.

## What to expect

- The page is a standalone document: the theme's header, footer and plugin scripts are **not** loaded.
  That is deliberate: you see exactly what a satellite site ships.
- Build warnings (missing links, content over length, market rules) appear in a yellow bar at the top,
  **for logged-in editors only**. Visitors never see it.
- Invalid JSON shows a message instead of the page, and the config box shows the JSON error when you edit the page.
- Fonts load from Google Fonts; nothing else is fetched from outside.

## Try a variation

Edit the pasted JSON and update the page:

- `"preset": "candy-pop"`: new visual direction
- `"seed": "anything-new"`: reshuffles variants and section order within the rules
- `"market": "br"` (with matching brand and links): compliance, age and payments change automatically

## Limits of this test mode

- One page per config (multi-page types come in a later phase).
- Not for production as-is: production sites should be static builds or the ACF block route.
- Tested against stubbed WordPress functions only; this is the first run on a real install. Report anything odd.
