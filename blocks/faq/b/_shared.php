<?php
/** FAQ, implementation B: shared prep (title, FAQPage JSON-LD), same as A. */
$title = $props['title'] ?: t('faq.title');
sx_jsonld([
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($it) => [
        '@type' => 'Question',
        'name' => $it['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $it['a']],
    ], $props['items']),
]);
