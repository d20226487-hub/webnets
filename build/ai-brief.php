<?php
/**
 * AI brief for a whole site, with the full text of every field: a Markdown prompt for rebuilding the template with an
 * AI coding tool (structure, content, design tokens, rules, acceptance checklist). See lib/aibrief.php.
 * A config with "variations" is briefed as its main combination (variations are ignored).
 *
 * Usage: php build/ai-brief.php sites/demos/<id>.json   -> dist/ai/<id>.md
 */

$root = dirname(__DIR__);
require "$root/lib/page.php";
require "$root/lib/aibrief.php";

$path = $argv[1] ?? null;
if (!$path) {
    fwrite(STDERR, "Usage: php build/ai-brief.php <site.json>\n");
    exit(2);
}
$cfg = sx_json($path);
unset($cfg['variations'], $cfg['variation_labels']);
$cfg['urls'] = 'files';
$cfg['_devspec'] = true;
$pages = array_values(array_filter(sx_build_site($cfg, $root), fn($p) => empty($p['asset'])));
@mkdir("$root/dist/ai", 0777, true);
$out = "$root/dist/ai/{$cfg['id']}.md";
file_put_contents($out, sx_ai_brief($pages, null, true, true));
printf("dist/ai/%s.md (%d pages, %.1f KB)\n", $cfg['id'], count($pages), filesize($out) / 1024);
