// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// The suite runs against a disposable WordPress the run sets up itself
// (see `npm run wp:up`). PLAYWRIGHT_BASE_URL points at it.
const baseURL = process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'http://127.0.0.1:8881';

module.exports = defineConfig({
  testDir: './tests',
  globalSetup: require.resolve('./tests/global-setup.js'),
  globalTeardown: require.resolve('./tests/global-teardown.js'),
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  reporter: 'list',
  timeout: 90_000,
  // The tests change site-wide settings (how many spots are left), so they run one at a time.
  workers: 1,
  use: { baseURL },
  projects: [{ name: 'wordpress', use: { ...devices['Desktop Chrome'] } }],
});
