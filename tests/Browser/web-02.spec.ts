import { test, expect } from '@playwright/test';

// WEB-02 — Cư dân & Căn hộ (authenticated via storageState setup project)
test.describe('WEB-02 residents & apartments', () => {
    test('WEB-02-01 resident directory', async ({ page }) => {
        await page.goto('/residents');
        await expect(page.getByText('Danh sách cư dân').first()).toBeVisible();
        await expect(page.getByText('Tổng cư dân')).toBeVisible();
        await expect(page.getByText('CD-0001').first()).toBeVisible();
        await expect(page).toHaveScreenshot('web-02-01-resident-directory.png', { fullPage: true });
    });

    test('WEB-02-02 apartment directory + profile', async ({ page }) => {
        await page.goto('/apartments');
        await expect(page.getByText('Hồ sơ căn hộ').first()).toBeVisible();
        await expect(page.getByText('A-0101').first()).toBeVisible();

        await page.goto('/apartments/1/profile');
        await expect(page.getByText('Thông tin căn hộ').first()).toBeVisible();
        await expect(page.getByText('Người trong hộ').first()).toBeVisible();
        await expect(page.getByText('Phương tiện').first()).toBeVisible();
        await expect(page).toHaveScreenshot('web-02-02-apartment-profile.png', { fullPage: true });
    });

    test('WEB-02-03 vehicles & cards', async ({ page }) => {
        await page.goto('/vehicles-cards');
        await expect(page.getByText('Phương tiện & thẻ').first()).toBeVisible();
        await expect(page.getByText('Tổng phương tiện')).toBeVisible();
        await expect(page.getByText('Thẻ ra vào & sinh trắc')).toBeVisible();
        await expect(page).toHaveScreenshot('web-02-03-vehicles-cards.png', { fullPage: true });
    });

    test('WEB-02-04 resident approval queue + approve workflow', async ({ page }) => {
        await page.goto('/resident-approvals');
        await page.waitForLoadState('networkidle');
        await expect(page.getByText('Hồ sơ chờ duyệt').first()).toBeVisible();
        await expect(page).toHaveScreenshot('web-02-04-approval-queue.png', { fullPage: true });

        // Working decision: approving one applicant removes one card from the queue.
        const approveButtons = page.getByRole('button', { name: 'Duyệt', exact: true });
        const before = await approveButtons.count();
        await approveButtons.first().click();
        await expect(approveButtons).toHaveCount(before - 1);
    });
});
