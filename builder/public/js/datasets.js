// Shared data collections the builder edits: where each lives, its shape, and the form for one item.
// Shapes: "map" (object keyed by id, under `root` or the whole file), "list" (array under `root`),
// "file" (one item per file), "readonly". Keys an item has that the form does not know are kept as they are.

const PROMO_TYPES = ['welcome_bonus', 'deposit_bonus', 'free_bets', 'promo_code', 'odds_boost', 'cashback', 'loyalty', 'tournaments'];
const TOPICS = ['payouts', 'verification', 'support', 'app', 'odds', 'deposits'];

export function collections(meta) {
  const markets = Object.keys(meta.markets);
  const brands = Object.keys(meta.brands);
  const payments = [...new Set(Object.values(meta.markets).flatMap((m) => m.payments))];
  const criteria = Object.keys(meta.types.casinos?.criteria || {});
  const verticals = ['sports', 'live', 'casino', 'live-casino'];

  return [
    { id: 'brands', group: 'Brands and studios', title: 'Brands', hint: 'Operators: identity, licence, markets, app.', files: (p) => p === 'brands/operators.json', shape: 'map',
      label: (it, k) => it.name || k, keyLabel: 'brand id',
      fields: [
        { name: 'name', type: 'text', label: 'Name', required: true },
        { name: 'kind', type: 'select', label: 'Kind', choices: ['sportsbook', 'casino', 'hybrid'] },
        { name: 'personality', type: 'textarea', label: 'Personality (internal note)' },
        { name: 'verticals', type: 'select', multiple: true, label: 'Products', choices: verticals },
        { name: 'markets', type: 'select', multiple: true, label: 'Licensed markets', choices: markets },
        { name: 'license_no', type: 'text', label: 'Licence number', instructions: 'Comparison and PR pages require it for every listed market.' },
        { name: 'brand_color', type: 'color', label: 'Brand colour' },
        { name: 'logo', type: 'text', label: 'Logo file', instructions: 'Path under brands/, e.g. logos/vantro.svg (a 32×32 SVG).' },
        { name: 'suggested_presets', type: 'select', multiple: true, label: 'Suggested styles', choices: Object.keys(meta.presets) },
        { name: 'tagline', type: 'locale', label: 'Tagline' },
        { name: 'facts', type: 'group', label: 'Facts', fields: [
          { name: 'payout_minutes', type: 'number', label: 'Typical payout (minutes)' },
          { name: 'games', type: 'number', label: 'Games' },
          { name: 'markets_per_match', type: 'number', label: 'Markets per match' },
          { name: 'founded', type: 'number', label: 'Founded (year)' },
          { name: 'rating', type: 'number', label: 'Rating', nullable: true },
          { name: 'reviews', type: 'number', label: 'Rating count', nullable: true },
          { name: 'rating_source', type: 'text', label: 'Rating source', nullable: true, instructions: 'Ratings show only with a named source.' }] },
        { name: 'app', type: 'json', label: 'App listing (advanced)', instructions: 'Store listing data used by app pages: platforms, version, sizes, languages, changelog.' },
      ] },
    { id: 'publishers', group: 'Brands and studios', title: 'Publishers', hint: 'Identities of comparison and news sites.', files: (p) => p === 'brands/publishers.json', shape: 'map',
      label: (it, k) => it.name || k, keyLabel: 'publisher id',
      fields: [
        { name: 'name', type: 'text', label: 'Name', required: true },
        { name: 'verticals', type: 'select', multiple: true, label: 'Covers', choices: verticals },
        { name: 'markets', type: 'select', multiple: true, label: 'Markets', choices: markets },
        { name: 'brand_color', type: 'color', label: 'Colour' },
        { name: 'logo', type: 'text', label: 'Logo file' },
        { name: 'tagline', type: 'locale', label: 'Tagline' },
      ] },
    { id: 'studios', group: 'Brands and studios', title: 'Game studios', hint: 'Studio facts and games, shared by all brands.', files: (p) => p === 'brands/providers.json', shape: 'map',
      label: (it, k) => it.name || k, keyLabel: 'studio id',
      fields: [
        { name: 'name', type: 'text', label: 'Name', required: true },
        { name: 'founded', type: 'number', label: 'Founded (year)' },
        { name: 'games_total', type: 'number', label: 'Games in the whole portfolio' },
        { name: 'colors', type: 'strings', label: 'Colours (main, dark)' },
        { name: 'signature', type: 'locale', label: 'Known for' },
        { name: 'personality', type: 'textarea', label: 'Personality (internal note)' },
        { name: 'games', type: 'repeater', label: 'Games', fields: [
          { name: 'id', type: 'text', label: 'Game id', required: true },
          { name: 'name', type: 'text', label: 'Name', required: true },
          { name: 'rtp', type: 'number', label: 'Studio RTP %', step: '0.01' },
          { name: 'volatility', type: 'select', label: 'Volatility', choices: ['low', 'medium', 'high', 'very-high'] },
          { name: 'max_win', type: 'number', label: 'Max win (× stake)' },
          { name: 'released', type: 'date', label: 'Released' },
          { name: 'tags', type: 'strings', label: 'Tags' }] },
      ] },
    { id: 'catalogs', group: 'Brands and studios', title: 'Game catalogs', hint: 'Which studios and games a brand carries per market, with its RTP version.', files: (p) => p.startsWith('brands/catalog/'), shape: 'file',
      newFile: { label: 'Brand', choices: brands, path: (k) => `brands/catalog/${k}.json`, data: () => ({ markets: {} }) },
      fields: [{ name: 'markets', type: 'map', label: 'Markets', keyLabel: 'market', keyChoices: markets, fields: [
        { name: 'providers', type: 'map', label: 'Studios', keyLabel: 'studio', keyChoices: Object.keys(meta.providers), fields: [
          { name: 'games', type: 'repeater', label: 'Games', fields: [
            { name: 'id', type: 'text', label: 'Game id (from the studio)', required: true },
            { name: 'rtp', type: 'number', label: 'RTP this brand runs here %', nullable: true, step: '0.01', instructions: 'Empty = unknown; the page then says "Set by {brand}".' },
            { name: 'added', type: 'date', label: 'Added to the brand' },
            { name: 'rank', type: 'number', label: 'Popularity rank (1 = most played)' }] }] }] }] },
    { id: 'promotions', group: 'Offers and ratings', title: 'Promotions', hint: 'Offer feed per brand. Shown only where the market allows the type.', files: (p) => p.startsWith('brands/promotions/'), shape: 'list', root: 'promotions',
      label: (it) => it.id || 'new offer', newFile: { label: 'Brand', choices: brands, path: (k) => `brands/promotions/${k}.json`, data: () => ({ promotions: [] }) },
      fields: [
        { name: 'id', type: 'text', label: 'Offer id', required: true },
        { name: 'type', type: 'select', label: 'Type', choices: PROMO_TYPES },
        { name: 'markets', type: 'select', multiple: true, label: 'Markets', choices: markets },
        { name: 'title', type: 'locale', label: 'Title' },
        { name: 'value', type: 'group', label: 'Value', fields: [
          { name: 'kind', type: 'select', label: 'Kind', choices: ['match', 'boost', 'cashback', 'free_bets', 'free_spins', 'prize_pool'] },
          { name: 'percent', type: 'number', label: 'Percent' }, { name: 'max', type: 'number', label: 'Maximum' },
          { name: 'amount', type: 'number', label: 'Amount' }, { name: 'count', type: 'number', label: 'Count (spins, bets)' }] },
        { name: 'code', type: 'text', label: 'Code', nullable: true, instructions: 'Empty = no code needed.' },
        { name: 'code_scope', type: 'select', label: 'Code scope', choices: ['public', 'exclusive'] },
        { name: 'terms', type: 'group', label: 'Terms', fields: [
          { name: 'min_deposit', type: 'number', label: 'Minimum deposit' }, { name: 'wagering', type: 'number', label: 'Wagering (×)', nullable: true },
          { name: 'min_odds', type: 'text', label: 'Minimum odds' }, { name: 'max_bet', type: 'number', label: 'Maximum bet', nullable: true },
          { name: 'expiry_days', type: 'number', label: 'Expires after (days)' },
          { name: 'eligible', type: 'locale', label: 'Who can claim' }, { name: 'excluded_payments', type: 'locale', label: 'Excluded payments' }, { name: 'games', type: 'locale', label: 'Counts on' }] },
        { name: 'valid_from', type: 'date', label: 'Valid from' }, { name: 'valid_to', type: 'date', label: 'Valid to' },
        { name: 'verified_at', type: 'date', label: 'Verified on', instructions: 'Older than 14 days gives a build warning.' },
        { name: 'terms_url', type: 'url', label: 'Full terms URL' },
      ] },
    { id: 'facts', group: 'Offers and ratings', title: 'Facts', hint: 'Per-market facts for comparison sites. Re-check every 30 days.', files: (p) => p.startsWith('brands/facts/'), shape: 'map', root: 'brands',
      label: (it, k) => meta.brands[k]?.name || k, keyLabel: 'brand id', keyChoices: brands,
      newFile: { label: 'Market', choices: markets, path: (k) => `brands/facts/${k}.json`, data: () => ({ brands: {} }) },
      fields: [
        { name: 'checked_at', type: 'date', label: 'Checked on', required: true },
        { name: 'domain', type: 'text', label: 'Site domain' },
        { name: 'min_deposit', type: 'number', label: 'Minimum deposit' },
        { name: 'withdrawal_minutes', type: 'kv', label: 'Withdrawal time per method (minutes)', valueType: 'number', keyChoices: payments, keyPlaceholder: 'method, e.g. pix' },
        { name: 'payments', type: 'select', multiple: true, label: 'Payment methods', choices: payments },
        { name: 'support', type: 'group', label: 'Support', fields: [{ name: 'chat', type: 'text', label: 'Chat hours' }, { name: 'email', type: 'true_false', label: 'E-mail' }, { name: 'phone', type: 'true_false', label: 'Phone' }] },
        { name: 'games', type: 'number', label: 'Games' }, { name: 'live_tables', type: 'number', label: 'Live tables' },
        { name: 'app', type: 'true_false', label: 'Native app' },
      ] },
    { id: 'ratings', group: 'Offers and ratings', title: 'Ratings', hint: 'Editorial scores with a reason each. The overall score is computed.', files: (p) => p.startsWith('brands/ratings/'), shape: 'map', root: 'brands',
      label: (it, k) => meta.brands[k]?.name || k, keyLabel: 'brand id', keyChoices: brands,
      newFile: { label: 'Market', choices: markets, path: (k) => `brands/ratings/${k}.json`, data: () => ({ brands: {} }) },
      fields: [
        { name: 'tested_at', type: 'date', label: 'Tested on', required: true, instructions: 'Older than 90 days gives a build warning.' },
        { name: 'summary', type: 'text', label: 'One-line summary', maxlength: 90 },
        { name: 'verdict', type: 'textarea', label: 'Verdict', maxlength: 320 },
        { name: 'scores', type: 'map', label: 'Scores', keyLabel: 'criterion', keyChoices: criteria, fields: [
          { name: 'score', type: 'number', label: 'Score (0–10)', min: 0, max: 10, step: '0.5', required: true },
          { name: 'reason', type: 'text', label: 'Reason (shown next to the score)', required: true }] },
        { name: 'pros', type: 'strings', label: 'Strengths' },
        { name: 'cons', type: 'strings', label: 'Weaknesses', min: 1 },
      ] },
    { id: 'reviews', group: 'Content', title: 'Reviews', hint: 'Player reviews per brand. Only "published" ones show; "seed" ones need a demo site.', files: (p) => p.startsWith('brands/reviews/'), shape: 'list', root: 'reviews',
      label: (it) => `${'★'.repeat(it.rating || 0)} ${it.title || it.id || 'new review'}`, newFile: { label: 'Brand', choices: brands, path: (k) => `brands/reviews/${k}.json`, data: () => ({ reviews: [] }) },
      fields: [
        { name: 'id', type: 'text', label: 'Id', required: true },
        { name: 'status', type: 'select', label: 'Status', choices: ['pending', 'published', 'rejected'] },
        { name: 'source', type: 'select', label: 'Source', choices: ['site-form', 'import', 'seed'] },
        { name: 'market', type: 'select', label: 'Market', choices: markets },
        { name: 'locale', type: 'select', label: 'Language', choices: ['en', 'ru', 'kk', 'pt'] },
        { name: 'rating', type: 'number', label: 'Rating (1–5)', min: 1, max: 5, step: '1' },
        { name: 'topics', type: 'select', multiple: true, label: 'Topics', choices: TOPICS },
        { name: 'name', type: 'text', label: 'Display name (first name + initial)' }, { name: 'city', type: 'text', label: 'City' },
        { name: 'date', type: 'date', label: 'Date' }, { name: 'title', type: 'text', label: 'Title' }, { name: 'text', type: 'textarea', label: 'Review' },
        { name: 'reply', type: 'group', label: 'Brand reply', fields: [{ name: 'date', type: 'date', label: 'Date' }, { name: 'text', type: 'textarea', label: 'Reply' }] },
      ] },
    { id: 'articles', group: 'Content', title: 'Articles', hint: 'News, guides and labelled PR. One file per article; future dates are scheduled.', files: (p) => /^content\/[^/]+\/articles\//.test(p), shape: 'file',
      label: (it, k) => it.title || k, slugFile: true,
      newFile: { label: 'Site folder', dirs: true, path: (dir, slug) => `${dir}/articles/${slug}.json`, data: (slug) => ({ slug, kind: 'news', title: '', dek: '', date: new Date().toISOString().slice(0, 10), author: '', body: '<p></p>' }) },
      fields: [
        { name: 'slug', type: 'text', label: 'Slug (same as the file name)', required: true },
        { name: 'kind', type: 'select', label: 'Kind', choices: ['news', 'guide', 'pr'], instructions: 'pr = paid article: needs a sponsor and is labelled Sponsored everywhere.' },
        { name: 'title', type: 'text', label: 'Headline', required: true, maxlength: 90 },
        { name: 'dek', type: 'textarea', label: 'Summary under the headline', required: true, maxlength: 200 },
        { name: 'date', type: 'date', label: 'Publish date', required: true },
        { name: 'updated', type: 'date', label: 'Updated on' },
        { name: 'author', type: 'text', label: 'Author id (from authors.json)', required: true },
        { name: 'sponsor', type: 'text', label: 'Sponsor (pr only)' },
        { name: 'brand', type: 'select', label: 'Brand promoted (pr only)', choices: ['', ...brands] },
        { name: 'body', type: 'wysiwyg', label: 'Body', required: true },
        { name: 'sample', type: 'true_false', label: 'Sample (made-up demo content)' },
      ] },
    { id: 'authors', group: 'Content', title: 'Authors', hint: 'Bylines for a news site.', files: (p) => /^content\/[^/]+\/authors\.json$/.test(p), shape: 'map',
      label: (it, k) => it.name || k, keyLabel: 'author id',
      fields: [{ name: 'name', type: 'text', label: 'Name', required: true }, { name: 'role', type: 'text', label: 'Role' }] },
    { id: 'markets', group: 'Rules', title: 'Markets', hint: 'Compliance rules per market. Read-only here.', files: (p) => p.startsWith('markets/'), shape: 'readonly' },
  ];
}
