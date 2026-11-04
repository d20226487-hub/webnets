<?php
/**
 * Generate ACF local-JSON field groups from every blocks/<name>/fields.json.
 * Output: dist/wp/acf-json/group_sx_<name>.json  (one group per block, located on that block)
 *
 * Usage: php wp/generate-acf-json.php
 */

$root = dirname(__DIR__);
$out = "$root/dist/wp/acf-json";
@mkdir($out, 0777, true);

function sx_acf_field(array $f, string $keyBase): array
{
    $key = "field_{$keyBase}_{$f['name']}";
    $acf = [
        'key' => $key,
        'label' => $f['label'] ?? ucfirst(str_replace('_', ' ', $f['name'])),
        'name' => $f['name'],
        'type' => $f['type'],
        'instructions' => $f['instructions'] ?? '',
        'required' => !empty($f['required']) ? 1 : 0,
    ];
    if (isset($f['default']) && is_scalar($f['default'])) {
        $acf['default_value'] = $f['default'];
    }
    switch ($f['type']) {
        case 'select':
            $acf['choices'] = array_combine($f['choices'], array_map('ucfirst', $f['choices']));
            $acf['return_format'] = 'value';
            if (!empty($f['multiple'])) {
                $acf['multiple'] = 1;
                $acf['ui'] = 1;
                $acf['default_value'] = $f['default'] ?? [];
            }
            break;
        case 'true_false':
            $acf['ui'] = 1;
            $acf['default_value'] = !empty($f['default']) ? 1 : 0;
            break;
        case 'link':
            $acf['return_format'] = 'array';
            break;
        case 'text':
        case 'textarea':
            if (isset($f['maxlength'])) {
                $acf['maxlength'] = $f['maxlength'];
            }
            break;
        case 'repeater':
            $acf['layout'] = 'block';
            $acf['max'] = $f['max'] ?? 0;
            $acf['sub_fields'] = array_map(fn($sf) => sx_acf_field($sf, "{$keyBase}_{$f['name']}"), $f['fields'] ?? []);
            break;
        case 'group':
            $acf['layout'] = 'block';
            $acf['sub_fields'] = array_map(fn($sf) => sx_acf_field($sf, "{$keyBase}_{$f['name']}"), $f['fields'] ?? []);
            break;
    }
    return $acf;
}

$count = 0;
foreach (glob("$root/blocks/*/fields.json") as $file) {
    $name = basename(dirname($file));
    $block = json_decode(file_get_contents(dirname($file) . '/block.json'), true);
    $fields = json_decode(file_get_contents($file), true)['fields'];
    $slug = 'sx_' . str_replace('-', '_', $name);

    $group = [
        'key' => "group_$slug",
        'title' => $block['title'],
        'fields' => array_map(fn($f) => sx_acf_field($f, $slug), $fields),
        'location' => [[['param' => 'block', 'operator' => '==', 'value' => $block['name']]]],
        'active' => true,
        'description' => 'Generated from blocks/' . $name . '/fields.json. Edit that file, not this one.',
        'modified' => filemtime($file),
    ];
    file_put_contents("$out/group_$slug.json", json_encode($group, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $count++;
}
echo "$count ACF field groups written to dist/wp/acf-json\n";
