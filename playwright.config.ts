import { defineConfig, devices } from '@playwright/test';

// X2-BMS screenshot harness. One spec per handoff batch under tests/Browser.
// Assumes the app is seeded (php artisan migrate:fresh --seed) and served on :8000.
export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    timeout: 60_000,
    retries: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.APP_TEST_URL ?? 'http://127.0.0.1:8000',
        viewport: { width: 1440, height: 900 },
        locale: 'vi-VN',
        screenshot: 'only-on-failure',
        navigationTimeout: 30_000,
    },
    expect: {
        timeout: 15_000,
        toHaveScreenshot: { maxDiffPixelRatio: 0.02 },
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'], storageState: '.auth/admin.json' },
            dependencies: ['setup'],
        },
    ],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: true,
        timeout: 60_000,
    },
});
