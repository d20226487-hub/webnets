<?php
/**
 * AI brief downloads: one site's whole-site brief as a text file, or a job's briefs as one zip (a folder per site type
 * when several types are chosen).
 */

/** Project information fields: the AI briefs of the project's sites use them to write relevant text. */
const GEN_PROJECT_INFO = ['about', 'audience', 'tone', 'facts', 'keywords', 'avoid', 'below'];

/** Project information as stored: known fields only, trimmed text, at most 3000 characters each; below is none, useful or every. */
function gen_project_info($in): array
{
    $out = [];
    foreach (GEN_PROJECT_INFO as $k) {
        $v = is_array($in) && is_string($in[$k] ?? null) ? trim(str_replace("\r", '', $in[$k])) : '';
        $out[$k] = mb_substr($v, 0, 3000);
    }
    $out['below'] = in_array($out['below'], ['useful', 'every'], true) ? $out['below'] : 'none';
    return $out;
}

/** The brief part written from the project information and its documents ($docs: gen_pfiles_brief()), or '' when there is neither. */
function gen_project_brief(array $info, string $docs = ''): string
{
    $fields = [
        'about' => 'About the project',
        'audience' => 'Audience',
        'tone' => 'Tone of voice',
        'facts' => 'Facts and messages to use',
        'keywords' => 'Topics and search phrases to cover',
        'avoid' => 'Avoid',
    ];
    $filled = array_filter(array_intersect_key($info, $fields), fn($v) => $v !== '');
    if (!$filled && $info['below'] === 'none' && $docs === '') {
        return '';
    }
    $L = ['## Project information', ''];
    $L[] = "_Written by the project owner for every site of this project. Use it to write the site's text so it is relevant to the project:"
        . " headings, intros, section and card texts, FAQ answers, the title tag and the meta description. Write in the site's language"
        . ' (see Site context) even when this information is in another language. Do not invent facts beyond this information and the page content;'
        . ' where it conflicts with the market guidance, the market guidance wins._';
    $L[] = '';
    foreach ($fields as $k => $label) {
        if (($info[$k] ?? '') === '') {
            continue;
        }
        $lines = array_values(array_filter(array_map('trim', explode("\n", $info[$k])), 'strlen'));
        if (count($lines) === 1) {
            $L[] = "- $label: {$lines[0]}";
        } else {
            $L[] = "- $label:";
            foreach ($lines as $x) {
                $L[] = '  - ' . preg_replace('/^[-*•]\s*/u', '', $x);
            }
        }
    }
    if ($docs !== '') {
        if (end($L) !== '') {
            $L[] = '';
        }
        $L[] = rtrim($docs);
    }
    $L[] = '';
    $L[] = '### Text below sections';
    $L[] = '';
    $L[] = match ($info['below']) {
        'useful' => '- Where a section would gain from explanation (a ranking, bonuses, payments, an app install, a comparison), add a short text block'
            . " right below it: 1–3 paragraphs, 60–150 words, written from the project information and the section's content, with an H2 or H3 only"
            . ' when it helps the page outline. Do not add one below the header, the footer, the age gate or the compliance blocks, and not after every section.',
        'every' => '- Add a short text block right below every main content section: 1–2 paragraphs, 50–120 words, written from the project information'
            . " and the section's content, each saying something new (no repeated phrases between blocks). Not below the header, the footer,"
            . ' the age gate or the compliance blocks.',
        default => "- Do not add text outside the sections: write the project's text into the sections themselves.",
    };
    return implode("\n", $L) . "\n";
}

/** A brief with the project information put in before its Site context (or at the end when there is none). */
function gen_brief_with_project(string $text, string $part): string
{
    if ($part === '') {
        return $text;
    }
    $at = strpos($text, "\n## Site context");
    return $at === false ? rtrim($text) . "\n\n" . $part : substr($text, 0, $at + 1) . $part . "\n" . substr($text, $at + 1);
}

/** The project information brief part for a job's sites. */
function gen_job_project_brief(PDO $db, int $jobId): string
{
    $q = $db->prepare('SELECT p.id, p.info FROM projects p JOIN jobs j ON j.project_id = p.id WHERE j.id = ?');
    $q->execute([$jobId]);
    $p = $q->fetch() ?: ['id' => 0, 'info' => '{}'];
    return gen_project_brief(gen_project_info(json_decode((string)$p['info'], true) ?: []), gen_pfiles_brief($db, (int)$p['id']));
}

/** A built site's whole-site AI brief (system prompt and rebuild brief in one document), or null when it is not built. */
function gen_site_brief(string $siteId): ?string
{
    global $GEN_ROOT;
    $dir = "$GEN_ROOT/dist/generated/$siteId";
    if (is_file("$dir/ai-brief.txt")) {
        return file_get_contents("$dir/ai-brief.txt");
    }
    // one-page sites built before they got their own file: the page's brief from its developer data is the site's brief
    $html = @file_get_contents("$dir/index.html");
    if ($html === false || !preg_match('#<script type="application/json" id="sx-devdata">(.*?)</script>#s', $html, $m)) {
        return null;
    }
    $d = json_decode($m[1], true);
    return isset($d['brief']) ? ($d['system'] ?? '') . "\n\n---\n\n" . $d['brief'] : null;
}

/** File name for one site's brief inside a zip: <site id>-ai-brief.txt. */
function gen_brief_name(string $siteId): string
{
    return preg_replace('/[^a-z0-9-]/i', '-', $siteId) . '-ai-brief.txt';
}

/** A name part safe in file names on any system: letters (any script), digits, - and . kept; runs of anything else become one -. */
function gen_file_part(string $s): string
{
    return trim(preg_replace('/[^\p{L}\p{N}.]+/u', '-', $s), '-.') ?: 'untitled';
}

/** Download names: <project>_<job name>_<date of the download>, e.g. BoostWin-1_30-Сателлитов_2026-11-09. */
function gen_brief_stem(PDO $db, int $jobId): string
{
    $q = $db->prepare('SELECT j.name, p.name AS project FROM jobs j JOIN projects p ON p.id = j.project_id WHERE j.id = ?');
    $q->execute([$jobId]);
    $r = $q->fetch() ?: ['project' => 'project', 'name' => "job-$jobId"];
    return gen_file_part($r['project']) . '_' . gen_file_part($r['name']) . '_' . date('Y-m-d');
}

/** Content-Disposition for a download: the UTF-8 name, with a plain ASCII one for old clients. */
function gen_attachment(string $name): void
{
    $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);
    header("Content-Disposition: attachment; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($name));
}

/** A zip archive of [path => text] (deflated when zlib is there, else stored). ZipArchive is not needed. */
function gen_zip(array $files): string
{
    $out = '';
    $dir = '';
    $time = getdate();
    $dosTime = ($time['hours'] << 11) | ($time['minutes'] << 5) | intdiv($time['seconds'], 2);
    $dosDate = (($time['year'] - 1980) << 9) | ($time['mon'] << 5) | $time['mday'];
    foreach ($files as $name => $data) {
        $crc = crc32($data);
        $deflate = function_exists('gzdeflate');
        $body = $deflate ? gzdeflate($data, 6) : $data;
        $method = $deflate ? 8 : 0;
        $head = pack('vvvvvVVVvv', 20, 0x0800, $method, $dosTime, $dosDate, $crc, strlen($body), strlen($data), strlen($name), 0);
        $offset = strlen($out);
        $out .= "PK\x03\x04" . $head . $name . $body;
        $dir .= "PK\x01\x02" . pack('v', 20) . $head . pack('vvvVV', 0, 0, 0, 0, $offset) . $name;
    }
    return $out . $dir . "PK\x05\x06" . pack('vvvvVVv', 0, 0, count($files), count($files), strlen($dir), strlen($out), 0);
}

/** Sends one site's brief as a download. */
function gen_send_site_brief(PDO $db, string $siteId): void
{
    $q = $db->prepare("SELECT job_id FROM sites WHERE id = ? AND status = 'built'");
    $q->execute([$siteId]);
    $jobId = $q->fetchColumn();
    $text = $jobId ? gen_site_brief($siteId) : null; // the project information goes in at download time: edits need no rebuild
    $text = $text === null ? null : gen_brief_with_project($text, gen_job_project_brief($db, (int)$jobId));
    if ($text === null) {
        gen_json(['error' => 'This site has no AI brief yet. Build it first.'], 404);
        return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    gen_attachment(gen_brief_stem($db, (int)$jobId) . '_' . gen_brief_name($siteId));
    echo $text;
}

/** Sends a job's briefs as a zip: the chosen types (all when none are given), a folder per type when there are several. */
function gen_send_job_briefs(PDO $db, int $jobId, array $types): void
{
    $q = $db->prepare("SELECT id, type FROM sites WHERE job_id = ? AND status = 'built' ORDER BY type, n");
    $q->execute([$jobId]);
    $rows = array_values(array_filter($q->fetchAll(), fn($r) => !$types || in_array($r['type'], $types, true)));
    $folders = count(array_unique(array_column($rows, 'type'))) > 1;
    $stem = gen_brief_stem($db, $jobId) . (count($types) === 1 ? '_' . gen_file_part($types[0]) : '');
    $files = [];
    $part = gen_job_project_brief($db, $jobId);
    foreach ($rows as $r) {
        $text = gen_site_brief($r['id']);
        if ($text !== null) {
            $text = gen_brief_with_project($text, $part);
            $files[$stem . '/' . ($folders ? $r['type'] . '/' : '') . gen_brief_name($r['id'])] = $text; // unzips into one folder named like the zip
        }
    }
    if (!$files) {
        gen_json(['error' => 'No built site of these types has an AI brief.'], 404);
        return;
    }
    header('Content-Type: application/zip');
    gen_attachment("$stem.zip");
    echo gen_zip($files);
}
