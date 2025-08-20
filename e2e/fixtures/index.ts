import { test as base } from '@hyvor/laravel-playwright';
import { AuthFixture } from './auth';
import { RaceFixture } from './race';
import { RoomFixture } from './room';
import { ToasterFixture } from './toaster';

type Fixtures = {
  toaster: ToasterFixture;
  room: RoomFixture;
  forEachTest: void;
  auth: AuthFixture;
  race: RaceFixture;
};

export const test = base.extend<Fixtures>({
  //@ Refresh redis for each test
  forEachTest: [
    async ({ laravel }, use) => {
      await laravel.callFunction('Illuminate\\Support\\Facades\\Redis::flushdb');
      await use();
    },
    { auto: true },
  ],

  page: async ({ page }, use) => {
    const originalGoto = page.goto.bind(page);
    page.goto = (url, options) => {
      return originalGoto(url, { waitUntil: 'domcontentloaded', ...options });
    };

    await use(page);
  },

  auth: async ({ page }, use) => {
    await use(new AuthFixture(page));
  },

  toaster: async ({ page }, use) => {
    await use(new ToasterFixture(page));
  },

  room: async ({ browser }, use) => {
    await use(new RoomFixture(browser));
  },

  race: async ({ browser }, use) => {
    await use(new RaceFixture(browser));
  },
});

export { expect } from '@playwright/test';
