import { test } from '@playwright/test';

// Temporary spike — resident detail page matching the approved design.
test('spike: resident detail design', async ({ page }) => {
    await page.goto('/admin/residents/1/detail');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    await page.screenshot({ path: 'test-results/spike-detail-design.png', fullPage: true });
});
