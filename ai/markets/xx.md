<!-- Market guidance for Demoland (demo only). Facts were copied from markets/xx.json when this file was created; edit freely. -->

### Market context

- Market: Demoland (demo only) (`xx`), languages en, ru, pt, currency EUR
- Minimum age: 18+
- Products allowed: sports, casino
- Promotions allowed: welcome_bonus, deposit_bonus, free_bets, promo_code, odds_boost, cashback, loyalty, tournaments
- Payment methods: visa-debit, mastercard-debit, apple-pay, bank
- App install paths: iOS app-store, Android play
- Notes: Every promotion type is allowed here so the full promo-page design can be reviewed. Mirror the first real market that uses the promo type.

### Mandated texts (from the market settings)

- Age notice: 18+ only. Offers are subject to terms.
- Self-exclusion: Self-exclusion and deposit-limit tools are available in every account.

### Rules you must not break

- Every page opens with an age gate (18+) before any gambling content.
- Every page has the responsible-gambling panel (usually in the footer) with the market text: age notice, licence line, taglines, self-exclusion and helpline where given. Never type this text yourself; it comes from the market settings.
- DEMO-ONLY market: never ship it to a real site; the build refuses it unless the config has "demo": true.
- Every promotion type is allowed so promo designs can be reviewed; label offers and keep their terms visible.
- Use euros and the demo payment methods only.
