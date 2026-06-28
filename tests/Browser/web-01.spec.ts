import { test, expect } from '@playwright/test';

// WEB-01 — Tổng quan & Điều hành (authenticated via storageState setup project)
test.describe('WEB-01 operational overview', () => {
    test('WEB-01-01 operational dashboard renders seeded sample data', async ({ page }) => {
        await page.goto('/dashboard');

        // Sample data from the approved image must be present (seed-driven, not hardcoded).
        await expect(page.getByText('Sunshine Garden - Tòa A').first()).toBeVisible();
        await expect(page.getByText('Nguyễn Minh Anh').first()).toBeVisible();
        await expect(page.getByText('Tỷ lệ thu phí')).toBeVisible();
        await expect(page.getByText('96.2%')).toBeVisible();
        await expect(page.getByText('2,45 tỷ')).toBeVisible();
        await expect(page.getByText('Trợ lý AI X2AI')).toBeVisible();
        // Donut total
        await expect(page.getByText('132', { exact: true }).first()).toBeVisible();

        await expect(page).toHaveScreenshot('web-01-01-operational-dashboard.png', {
            fullPage: true,
        });
    });
});
