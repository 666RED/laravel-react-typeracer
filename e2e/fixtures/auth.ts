import { Page } from '@playwright/test';

export class AuthFixture {
  constructor(private page: Page) {}

  async login() {
    await this.page.goto('http://localhost:8000/login');

    await this.page.getByRole('textbox', { name: 'Email:' }).click();
    await this.page.getByRole('textbox', { name: 'Email:' }).fill('test@gmail.com');
    await this.page.getByRole('textbox', { name: 'Password:' }).click();
    await this.page.getByRole('textbox', { name: 'Password:' }).fill('12341234');

    await this.page.getByRole('button', { name: 'Login' }).click();

    await this.page.waitForURL('/');
  }
}
