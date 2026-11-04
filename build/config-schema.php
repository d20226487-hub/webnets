<?php
/**
 * Generate a JSON Schema (draft 2020-12) for a site type's config, from its blueprint and the
 * blocks' fields.json. Content pipelines (LLM or scripted) target this schema; the builder's
 * own validation uses the same fields.json, so the two never drift.
 *
 * Usage: php build/config-schema.php landing   ->  dist/schemas/landing.schema.json
 */

$root = dirname(__DIR__);
require "$root/lib/site.php";
require "$root/lib/blueprint.php";

$type = $argv[1] ?? 'landing';
$bp = sx_blueprint($type, $root);

// Block metadata needs a render context for market-aware variant lists; use an unrestricted one.
sx_context([
    'root' => $root,
    'market' => ['id' => '*', 'verticals' => ['sports' => true, 'casino' => true]],
    'locale' => 'en',
    'brand' => ['name' => '', 'kind' => ''],
]);

function sx_field_schema(array $f): array
{
    $s = ['description' => trim(($f['label'] ?? $f['name']) . (isset($f['instructions']) ? '. ' . $f['instructions'] : ''))];
    switch ($f['type']) {
        case 'text':
        case 'textarea':
        case 'wysiwyg':
        case 'url':
            $s['type'] = 'string';
            if (isset($f['maxlength'])) {
                $s['maxLength'] = $f['maxlength'];
            }
            if ($f['type'] === 'url') {
                $s['format'] = 'uri-reference';
            }
            break;
        case 'select':
            $enum = array_values(array_diff($f['choices'], ['auto']));
            $s += !empty($f['multiple']) ? ['type' => 'array', 'items' => ['enum' => $enum], 'uniqueItems' => true] : ['enum' => $enum];
            break;
        case 'true_false':
            $s['type'] = 'boolean';
            break;
        case 'link':
            $s += ['type' => 'object', 'required' => ['label', 'url'], 'additionalProperties' => false,
                'properties' => ['label' => ['type' => 'string'], 'url' => ['type' => 'string', 'format' => 'uri-reference']]];
            break;
        case 'repeater':
            $s += ['type' => 'array', 'items' => sx_object_schema($f['fields'] ?? [])];
            if (isset($f['min'])) {
                $s['minItems'] = $f['min'];
            }
            if (!empty($f['max'])) {
                $s['maxItems'] = $f['max'];
            }
            break;
        case 'group':
            $s += sx_object_schema($f['fields'] ?? []);
            break;
    }
    return $s;
}

function sx_object_schema(array $fields): array
{
    $props = [];
    $req = [];
    foreach ($fields as $f) {
        if ($f['name'] === 'variant') {
            continue;
        }
        $props[$f['name']] = sx_field_schema($f);
        if (!empty($f['required'])) {
            $req[] = $f['name'];
        }
    }
    return ['type' => 'object', 'properties' => $props] + ($req ? ['required' => $req] : []);
}

/** [content schemas, variant enums, required ids] for a list of sections. */
function sx_sections_schema(array $sections, string $root): array
{
    $content = $variants = $required = [];
    foreach ($sections as $s) {
        if (!is_dir("$root/blocks/{$s['block']}")) {
            continue;
        }
        $meta = sx_block_meta($s['block']);
        $obj = sx_object_schema($meta['fields']);
        $obj['description'] = "{$s['rule']} section, block '{$s['block']}'. " . implode(' ', $meta['block']['sx']['guardrails'] ?? []);
        $content[$s['id']] = $obj;
        $variants[$s['id']] = ['enum' => array_merge(['auto'], $s['lock'] ?? $meta['block']['sx']['variants'])];
        if ($s['rule'] === 'required' && !empty($obj['required'])) {
            $required[] = $s['id'];
        }
    }
    return [$content, $variants, $required];
}

$frame = array_merge(
    [['id' => 'header', 'block' => $bp['frame']['header']['block'], 'lock' => $bp['frame']['header']['variants'] ?? null, 'rule' => 'required']],
    [['id' => 'footer', 'block' => $bp['frame']['footer']['block'], 'lock' => $bp['frame']['footer']['variants'] ?? null, 'rule' => 'required']],
    array_map(fn($o) => ['id' => $o['block'], 'block' => $o['block'], 'lock' => $o['variants'] ?? null, 'rule' => 'required'], $bp['frame']['overlay'] ?? [])
);
$pageMeta = ['type' => 'object', 'required' => ['title', 'description'], 'properties' => ['title' => ['type' => 'string', 'maxLength' => 65], 'description' => ['type' => 'string', 'maxLength' => 160]]];
$extra = [];
if (isset($bp['pages'])) {
    // Multi-page: content.<page>.<section>, variants.<page>.<section>, meta.<page>; frame parts shared by all pages.
    [$content, $variants] = sx_sections_schema($frame, $root);
    $required = [];
    $meta = [];
    foreach ($bp['pages'] as $pid => $pg) {
        [$c, $v, $r] = sx_sections_schema($pg['sections'], $root);
        $rule = $pg['rule'] . (isset($pg['requires_vertical']) ? ", needs {$pg['requires_vertical']}" : '') . (!empty($pg['requires_app']) ? ', needs an app' : '')
            . (!empty($pg['requires_promo']) ? ', needs an allowed promotion type' : '') . (isset($pg['required_in']) ? ', required in ' . implode('/', $pg['required_in']) : '');
        $content[$pid] = ['type' => 'object', 'description' => "Page '$pid' ($rule).", 'properties' => $c] + ($r ? ['required' => $r] : []);
        $variants[$pid] = ['type' => 'object', 'properties' => $v, 'additionalProperties' => false];
        $meta[$pid] = $pageMeta;
        if ($pg['rule'] === 'required') {
            $required[] = $pid;
        }
    }
    $extra = [
        'meta' => ['type' => 'object', 'description' => 'Title and description per page (unique per page).', 'properties' => $meta],
        'pages_on' => ['type' => 'object', 'description' => 'Force optional pages on/off.', 'additionalProperties' => ['type' => 'boolean']],
        'paging' => ['type' => 'object', 'description' => 'Items per page for paginated pages, by page id (default from the blueprint).', 'additionalProperties' => ['type' => 'integer', 'minimum' => 1]],
        'nav' => ['type' => 'object', 'description' => 'Override nav labels per page id.', 'additionalProperties' => ['type' => 'string']],
        'urls' => ['enum' => ['pretty', 'files'], 'description' => 'pretty = /payments/ (default), files = payments.html'],
        'base' => ['type' => 'string', 'description' => 'Subfolder for pretty URLs, default "/".'],
        'order' => ['type' => 'object', 'description' => 'Pin the middle-zone order per page.', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
        'sections' => ['type' => 'object', 'description' => 'Force optional sections on/off per page.', 'additionalProperties' => ['type' => 'object', 'additionalProperties' => ['type' => 'boolean']]],
    ];
} else {
    [$content, $variants, $required] = sx_sections_schema(array_merge(array_slice($frame, 0, 1), $bp['sections'], array_slice($frame, 1)), $root);
}

$schema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => "satellite-system/$type.schema.json",
    'title' => "Site config: {$bp['title']}",
    'description' => "Generated by build/config-schema.php from blueprints/$type.json and blocks/*/fields.json. Brief: {$bp['brief']}.",
    'type' => 'object',
    'required' => ['id', 'type', 'brand', 'market', 'preset', 'seed', 'links', 'content'],
    'properties' => [
        'id' => ['type' => 'string', 'pattern' => '^[a-z0-9-]+$'],
        'type' => ['const' => $type],
        'angle' => ['enum' => array_keys($bp['angles']), 'description' => 'Omit to let the seed pick one the market allows.'],
        'brand' => ['enum' => array_keys(array_diff_key(sx_json("$root/brands/operators.json"), ['_note' => 1]))],
        'market' => ['enum' => array_map(fn($f) => basename($f, '.json'), glob("$root/markets/*.json"))],
        'locale' => ['enum' => ['en', 'ru', 'kk', 'pt'], 'description' => 'Omit for the market default.'],
        'preset' => ['enum' => array_keys(sx_all_presets($root))],
        'seed' => ['type' => 'string', 'minLength' => 3],
        'prefix' => ['type' => 'string', 'description' => '"auto" (recommended) or a class prefix ending in "-".'],
        'links' => ['type' => 'object', 'required' => ['go'], 'properties' => ['go' => ['type' => 'string', 'format' => 'uri']]],
        'cta_label' => ['type' => 'string', 'description' => 'A strings.json key or literal text. Omit for the brand default.'],
        'tokens' => ['type' => 'object', 'description' => 'Partial preset override, e.g. {"color": {"accent": "#FF5A1F"}}.'],
        'site' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]],
        'page' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string', 'maxLength' => 65], 'description' => ['type' => 'string', 'maxLength' => 160]]],
        'variants' => ['type' => 'object', 'properties' => $variants, 'additionalProperties' => false],
        'sections' => ['type' => 'object', 'description' => 'Force optional sections on/off.', 'additionalProperties' => ['type' => 'boolean']],
        'order' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Pin the middle-zone order.'],
        'content' => ['type' => 'object', 'properties' => $content, 'required' => $required],
    ],
];
$schema['properties'] = $extra + $schema['properties'];
if (!empty($bp['compares_brands'])) {
    // Multi-brand sites: the site is branded as a publisher and lists several operators with their own links.
    $schema['required'] = array_values(array_diff($schema['required'], ['brand'])) + [99 => 'publisher', 100 => 'brands'];
    $schema['required'] = array_values($schema['required']);
    unset($schema['properties']['brand']);
    $ops = array_keys(array_diff_key(sx_json("$root/brands/operators.json"), ['_note' => 1]));
    $schema['properties']['publisher'] = ['enum' => array_keys(array_diff_key(sx_json("$root/brands/publishers.json"), ['_note' => 1]))];
    $schema['properties']['brands'] = ['type' => 'array', 'minItems' => 3, 'uniqueItems' => true, 'items' => ['enum' => $ops], 'description' => 'Compared operators; each needs facts and a rating for the market.'];
    $schema['properties']['featured'] = ['enum' => $ops, 'description' => 'One paid slot, shown labelled; never changes scores or ranks.'];
    $schema['properties']['links'] = ['type' => 'object', 'required' => ['brands'], 'properties' => ['brands' => ['type' => 'object', 'additionalProperties' => ['type' => 'string', 'format' => 'uri']]]];
}
if (!empty($bp['articles'])) {
    // News sites: branded as a publisher; articles live in their own files (docs/schemas/article.schema.json).
    $schema['required'] = array_values(array_merge(array_diff($schema['required'], ['brand']), ['publisher', 'articles']));
    unset($schema['properties']['brand']);
    $schema['properties']['publisher'] = ['enum' => array_keys(array_diff_key(sx_json("$root/brands/publishers.json"), ['_note' => 1]))];
    $schema['properties']['articles'] = ['type' => 'string', 'description' => 'Folder of article files, e.g. content/<site>/articles. Each file follows docs/schemas/article.schema.json.'];
    $schema['properties']['links'] = ['type' => 'object', 'properties' => ['brands' => ['type' => 'object', 'description' => 'Tracked link per brand that PR articles promote.', 'additionalProperties' => ['type' => 'string', 'format' => 'uri']]]];
}
if (isset($bp['pages'])) {
    unset($schema['properties']['page']);
    $schema['required'][] = 'meta';
    $schema['properties']['site']['properties']['url'] = ['type' => 'string', 'format' => 'uri', 'description' => 'Required on multi-page sites: canonical links and sitemap.'];
}

@mkdir("$root/dist/schemas", 0777, true);
$out = "$root/dist/schemas/$type.schema.json";
file_put_contents($out, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
printf("%s written (%d %s)\n", substr($out, strlen($root) + 1), isset($bp['pages']) ? count($bp['pages']) : count($content), isset($bp['pages']) ? 'pages' : 'sections');
