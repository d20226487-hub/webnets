<?php
/**
 * Turn a built site page into a review page for sharing (claude.ai Artifact): keeps <title>, fonts,
 * JSON-LD, styles and body content, drops the doctype/html/head/body wrapper the host adds itself.
 *
 * Usage: php build/export-review.php dist/sites/<id>/index.html dist/review/<id>.html ["Review title"]
 */

[$_, $in, $out, $title] = $argv + [null, null, null, null];
if (!$in || !$out) {
    fwrite(STDERR, "Usage: php build/export-review.php <built.html> <out.html>\n");
    exit(2);
}
$html = file_get_contents($in);
preg_match('#<head>(.*?)</head>#s', $html, $head);
preg_match('#<body[^>]*>(.*?)</body>#s', $html, $body);
$headInner = preg_replace('#<meta (charset|name="viewport")[^>]*>\s*#', '', $head[1]);
if ($title) {
    // Review pages get a short name for the gallery instead of the SEO title.
    $headInner = preg_replace('#<title>.*?</title>#s', '<title>' . htmlspecialchars($title) . '</title>', $headInner);
}
// single-page review links get the phone-preview button too (multi-page exports add it per page already)
if (!str_contains($body[1], 'sx-devtoggle')) {
    require_once dirname(__DIR__) . '/lib/page.php';
    preg_match('#<html[^>]*lang="([a-z]{2})#', $html, $lang);
    $body[1] .= sx_review_device_toggle('index.html', $lang[1] ?? 'en');
}
@mkdir(dirname($out), 0777, true);
file_put_contents($out, trim($headInner) . "\n" . trim($body[1]) . "\n");
printf("%s -> %s (%s KB)\n", $in, $out, round(filesize($out) / 1024, 1));
