import { Page } from '@playwright/test';
import { expect } from './index';

export class ToasterFixture {
  constructor(private page: Page) {}

  locator() {
    return this.page.getByRole('region', { name: 'Notifications alt+T' }).getByRole('listitem');
  }

  async expectText(message: string) {
    await expect(this.locator()).toContainText(message);
  }

  async waitFor(message: string) {
    await expect(this.locator()).toBeVisible();
    await expect(this.locator()).toContainText(message);
  }
}
