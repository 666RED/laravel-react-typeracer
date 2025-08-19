import type { LaravelOptions } from '@hyvor/laravel-playwright';
import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path, { dirname } from 'path';
import { fileURLToPath } from 'url';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
dotenv.config({ path: path.resolve(__dirname, `.env.${process.env.APP_ENV}`) });

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig<LaravelOptions>({
  testDir: './e2e',
  /* Run tests in files in parallel */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opt out of parallel tests on CI. */
  // workers: 1,
  workers: process.env.CI ? 1 : undefined, //@ may uncomment this
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: 'html',
  timeout: 1 * 60 * 1000,
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.APP_URL,

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    laravelBaseUrl: `${process.env.APP_URL}/playwright`,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'init setup',
      testMatch: /global\.setup\.ts/,
    },

    // {
    //   name: 'auth setup',
    //   testMatch: /auth\.setup\.ts/,
    //   dependencies: ['init setup'],
    // },

    // {
    //   name: 'teardown',
    //   testMatch: /global\.teardown\.ts/,
    // },

    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
      dependencies: ['init setup'],

      // teardown: 'teardown',
    },

    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    //   dependencies: ['init setup'],
    //   // teardown: 'teardown',
    // },

    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    //   dependencies: ['init setup'],
    //   // teardown: 'teardown',
    // },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /* Run your local dev server before starting the tests */
  webServer: [
    {
      command: 'php artisan serve',
      url: process.env.APP_URL,
      reuseExistingServer: !process.env.CI,
    },
  ],
});
