/*
| Lighthouse budgets (Phase 4.8c; Test Plan NFT-02 and §7.2, Theme Guide §13, UI/UX Part B F-2):
| mobile, simulated 4G, the eight NFT-01 pages. Performance ≥ 80, the most visited pages
| (admission, waiver calculator, programs, program, scholarships) ≥ 90; CLS < 0.1.
|
|   LHCI_BASE_URL=https://staging.example.edu npx lhci autorun
|
| Under WSL set CHROME_PATH to a Linux Chromium (e.g. Playwright's ~/.cache/ms-playwright/chromium-*/chrome-linux*/chrome),
| otherwise the Windows Chrome is started and leaves its profile folders in the project root.
|
| Measure a production-like server (gzip, APP_DEBUG=false, OPcache): `php artisan serve` has no
| compression and debug Livewire is unminified, which costs 30–50 points that production does not lose.
|
| Paths are DIU's data; another university sets LHCI_PAGES (comma separated) instead.
*/
const base = (process.env.LHCI_BASE_URL || 'http://127.0.0.1:8080').replace(/\/$/, '');

const pages = (process.env.LHCI_PAGES || [
    '/',
    '/admission',
    '/waiver-calculator',
    '/programs',
    '/fsit/cse/bse-in-cse',
    '/scholarships',
    '/news/the-power-of-listening-why-being-open-to-different-perspectives-can-help-you-succeed',
    '/faculties/fsit',
].join(',')).split(',').map((path) => base + path.trim());

const mostVisited = '.*/(admission|waiver-calculator|programs|scholarships|fsit/cse/[^/]+)$';

module.exports = {
    ci: {
        collect: {
            url: pages,
            numberOfRuns: Number(process.env.LHCI_RUNS || 3),
            settings: {
                formFactor: 'mobile',
                throttlingMethod: 'simulate',
                chromeFlags: '--no-sandbox --headless=new',
                onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
            },
        },
        assert: {
            assertMatrix: [
                {
                    matchingUrlPattern: '.*',
                    assertions: {
                        'categories:performance': ['error', { minScore: 0.8, aggregationMethod: 'median-run' }],
                        'categories:accessibility': ['error', { minScore: 0.9 }],   // the gate is axe in tests/Browser (NFT-07); page text may skip heading levels
                        'categories:best-practices': ['warn', { minScore: 0.9 }],
                        'categories:seo': ['warn', { minScore: 0.9 }],
                        'cumulative-layout-shift': ['error', { maxNumericValue: 0.1, aggregationMethod: 'median-run' }],
                        'largest-contentful-paint': ['warn', { maxNumericValue: 3000, aggregationMethod: 'median-run' }],
                    },
                },
                {
                    matchingUrlPattern: mostVisited,
                    assertions: {
                        'categories:performance': ['error', { minScore: 0.9, aggregationMethod: 'median-run' }],
                    },
                },
            ],
        },
        upload: {
            target: 'filesystem',
            outputDir: 'storage/lighthouse',
        },
    },
};
