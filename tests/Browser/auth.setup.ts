import { test as setup } from '@playwright/test';

const authFile = '.auth/admin.json';

// Logs in once; all specs reuse this storage state (avoids per-test login load).
setup('authenticate as admin', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('input[type="email"]').fill('x2bms@x2bms.vn');
    await page.locator('input[type="password"]').fill('Bms@2026!');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 30_000 });
    await page.context().storageState({ path: authFile });
});
