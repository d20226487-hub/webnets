<?php
/**
 * Real app screenshots of a job (spec "shots": {main: {id, focus, alt}, more: [{id, focus, alt}, …]}), uploaded in
 * the job form. The browser prepares each one (780 px wide and a 390 px copy, WebP or JPEG); the server checks and
 * stores them in data/shots/. On app sites the main screenshot takes each section's most prominent phone, the others
 * fill the rest: never twice in one section, in a different order per section and per site. A section shows no
 * more phones than there are screenshots (layouts that need more are not used).
 */

const GEN_SHOTS_MORE = 8;           // secondary screenshots at most
const GEN_SHOTS_FOCUS = ['top', 'center', 'bottom'];
const GEN_SHOTS_BYTES = 1500000;    // per file

function gen_shots_dir(): string
{
    global $GEN_ROOT;
    return "$GEN_ROOT/data/shots";
}

/** A stored screenshot: ['ext', 'w', 'h'] (the 780 px file), or null. */
function gen_shot_meta(string $id): ?array
{
    if (!preg_match('/^sh[0-9a-f]{16}$/', $id) || !is_file(gen_shots_dir() . "/$id.json")) {
        return null;
    }
    return json_decode((string)file_get_contents(gen_shots_dir() . "/$id.json"), true) ?: null;
}

/** The file of a stored screenshot: the 780 px one, or the 390 px copy ($small). */
function gen_shot_file(string $id, bool $small = false): ?string
{
    $m = gen_shot_meta($id);
    return $m ? gen_shots_dir() . "/$id" . ($small ? '-s' : '') . ".{$m['ext']}" : null;
}

/** An upload from the job form: {big, small: data URLs (image/webp or image/jpeg)}. Returns the new id, or throws. */
function gen_shot_save(array $b): string
{
    $files = [];
    foreach (['big' => [600, 800], 'small' => [300, 400]] as $k => [$minW, $maxW]) {
        if (!preg_match('#^data:image/(webp|jpeg);base64,([A-Za-z0-9+/=]+)$#', (string)($b[$k] ?? ''), $m)) {
            throw new InvalidArgumentException('The screenshot is not a prepared WebP or JPEG image.');
        }
        $raw = base64_decode($m[2], true);
        if ($raw === false || strlen($raw) > GEN_SHOTS_BYTES) {
            throw new InvalidArgumentException('The screenshot is too large.');
        }
        $info = @getimagesizefromstring($raw); // checks the image itself, not just its name
        $type = $m[1] === 'webp' ? IMAGETYPE_WEBP : IMAGETYPE_JPEG;
        if (!$info || $info[2] !== $type) {
            throw new InvalidArgumentException('The screenshot is not a readable image.');
        }
        [$w, $h] = $info;
        if ($w < $minW || $w > $maxW || $h <= $w) {
            throw new InvalidArgumentException('Screenshots must be portrait (taller than wide) and at least 600 px wide.');
        }
        $files[$k] = [$raw, $m[1] === 'webp' ? 'webp' : 'jpg', $w, $h];
    }
    if ($files['big'][1] !== $files['small'][1]) {
        throw new InvalidArgumentException('The screenshot copies differ in format.');
    }
    gen_mkdir(gen_shots_dir());
    $id = 'sh' . bin2hex(random_bytes(8));
    $ext = $files['big'][1];
    file_put_contents(gen_shots_dir() . "/$id.$ext", $files['big'][0]);
    file_put_contents(gen_shots_dir() . "/$id-s.$ext", $files['small'][0]);
    file_put_contents(gen_shots_dir() . "/$id.json", json_encode(['ext' => $ext, 'w' => $files['big'][2], 'h' => $files['big'][3]]));
    return $id;
}

/** The job form's screenshots, checked: [shots or null, error or null]. */
function gen_shots_input($in): array
{
    if (!is_array($in) || empty($in['main']['id'])) {
        return [null, is_array($in) && !empty($in['more']) ? 'Add the main screenshot first: it takes the most prominent phone.' : null];
    }
    $one = function ($x): ?array {
        if (!is_array($x) || !gen_shot_meta((string)($x['id'] ?? ''))) {
            return null;
        }
        return ['id' => $x['id'], 'focus' => in_array($x['focus'] ?? '', GEN_SHOTS_FOCUS, true) ? $x['focus'] : 'top',
            'alt' => mb_substr(trim((string)($x['alt'] ?? '')), 0, 120)];
    };
    $main = $one($in['main']);
    if (!$main) {
        return [null, 'The main screenshot was not found. Upload it again.'];
    }
    $more = [];
    foreach (array_slice((array)($in['more'] ?? []), 0, GEN_SHOTS_MORE) as $x) {
        if (($s = $one($x)) && $s['id'] !== $main['id'] && !in_array($s['id'], array_column($more, 'id'), true)) {
            $more[] = $s;
        }
    }
    return [['main' => $main, 'more' => $more], null];
}

/** How many different screenshots a job has (0 without). */
function gen_shots_count(array $spec): int
{
    return empty($spec['shots']['main']) ? 0 : 1 + count($spec['shots']['more'] ?? []);
}

/** A screenshot as phone settings on a site: [image, small, focus, alt], the files in the site's img/ folder. */
function gen_shot_props(array $s): array
{
    $m = gen_shot_meta($s['id']);
    return ['image' => "img/{$s['id']}.{$m['ext']}", 'small' => "img/{$s['id']}-s.{$m['ext']}", 'focus' => $s['focus']] + ($s['alt'] !== '' ? ['alt' => $s['alt']] : []);
}

/**
 * The job's screenshots put into a site config: in every app hero (the main one in its large phone, others in its
 * small or back phones) and every screenshots section (the main one in the most prominent phone: the fan's middle,
 * the first of a carousel or grid). A section shows at most as many phones as there are screenshots; a fan needs
 * three, so with fewer the section is pinned to a carousel or a grid. Returns the config and the screenshot ids used.
 */
function gen_shots_apply(array $cfg, array $spec, string $siteId): array
{
    $n = gen_shots_count($spec);
    if (!$n || empty($cfg['content']) || !is_array($cfg['content'])) {
        return [$cfg, []];
    }
    $main = $spec['shots']['main'];
    $more = $spec['shots']['more'] ?? [];
    $used = [];
    $sec = 0;
    $others = function () use (&$sec, $more, $siteId): array { // the secondary ones in this section's own order
        $k = count($more) ? crc32("$siteId|shots|" . $sec++) % count($more) : 0;
        return array_merge(array_slice($more, $k), array_slice($more, 0, $k));
    };
    $walk = function (array &$node, array $path) use (&$walk, &$cfg, &$used, $main, $others, $n, $siteId) {
        if (isset($node['screen']) && is_string($node['screen'])) { // an app hero
            $node = gen_shot_props($main) + $node;
            $used[$main['id']] = true;
            $node['images'] = [];
            foreach (array_slice($others(), 0, 3) as $s) {
                $node['images'][] = gen_shot_props($s);
                $used[$s['id']] = true;
            }
            return;
        }
        if (isset($node['screens']) && is_array($node['screens']) && array_is_list($node['screens']) && $node['screens'] && is_array($node['screens'][0])) { // a screenshots section
            $want = min(count($node['screens']), $n);
            $node['screens'] = array_slice($node['screens'], 0, $want);
            $order = array_merge([$main], array_slice($others(), 0, $want - 1)); // the main one first: a carousel's and a grid's most prominent (a fan moves it to its middle)
            foreach ($node['screens'] as $i => $scr) {
                $node['screens'][$i] = gen_shot_props($order[$i]) + ($i === 0 ? ['main' => true] : []) + $scr;
                $used[$order[$i]['id']] = true;
            }
            if ($want < 3) { // a fan needs three phones
                $id = end($path);
                $pick = ['carousel', 'grid'][crc32("$siteId|shotsvariant") % 2];
                if (count($path) > 2) {
                    $cfg['variants'][$path[1]][$id] = $pick;
                } else {
                    $cfg['variants'][$id] = $pick;
                }
            }
            return;
        }
        foreach ($node as $k => &$v) {
            if (is_array($v)) {
                $walk($v, array_merge($path, [(string)$k]));
            }
        }
    };
    $walk($cfg['content'], ['content']);
    return [$cfg, array_keys($used)];
}

/** The screenshots a site uses, copied into its img/ folder (an earlier build's are removed first). */
function gen_shots_copy(string $siteId, array $ids): void
{
    global $GEN_ROOT;
    $dir = "$GEN_ROOT/dist/generated/$siteId/img";
    foreach (glob("$dir/sh*") ?: [] as $old) {
        @unlink($old);
    }
    if (!$ids) {
        return;
    }
    gen_mkdir($dir);
    foreach ($ids as $id) {
        foreach ([false, true] as $small) {
            if ($f = gen_shot_file($id, $small)) {
                copy($f, "$dir/" . basename($f));
            }
        }
    }
}
