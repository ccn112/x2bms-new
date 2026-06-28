import { test, expect } from '@playwright/test';

// M2 — admin auth + RBAC + foundation Filament resources reachable (authed via storageState).
test.describe('M2 admin foundation', () => {
    test('admin can open foundation resources', async ({ page }) => {
        // Filament panel reachable.
        await page.goto('/admin');
        await expect(page).toHaveURL(/\/admin/);

        // Buildings resource shows seeded data (scoped to tenant).
        await page.goto('/admin/buildings');
        await expect(page.getByText('SG-A').first()).toBeVisible();

        // Apartments resource shows seeded data.
        await page.goto('/admin/apartments');
        await expect(page.getByText('A-0101').first()).toBeVisible();

        // Residents resource (Tier-1 completion) shows seeded data.
        await page.goto('/admin/residents');
        await expect(page.getByText('CD-0001').first()).toBeVisible();

        // Tenant resource shows the demo tenant.
        await page.goto('/admin/tenants');
        await expect(page.getByText('X2-BMS Demo Tenant').first()).toBeVisible();
    });
});
