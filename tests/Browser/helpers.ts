import { Page, expect } from '@playwright/test';

/** Log in through the Filament panel (web guard) so guarded pages are reachable. */
export async function loginAsAdmin(page: Page) {
    await page.goto('/admin/login');
    await page.locator('input[type="email"]').fill('x2bms@x2bms.vn');
    await page.locator('input[type="password"]').fill('Bms@2026!');
    await page.locator('button[type="submit"]').click();
    // Wait until the Filament (Livewire) login redirect lands off the login page.
    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 30_000 });
    await page.waitForLoadState('networkidle');
}
