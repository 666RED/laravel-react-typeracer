import { test as setup } from '@playwright/test';

const authFile = 'playwright/.auth/user.json';

setup('Authenticate', async ({ page }) => {
  await page.goto('http://localhost:8000/login');

  await page.getByRole('textbox', { name: 'Email:' }).click();
  await page.getByRole('textbox', { name: 'Email:' }).fill('test@gmail.com');
  await page.getByRole('textbox', { name: 'Password:' }).click();
  await page.getByRole('textbox', { name: 'Password:' }).fill('12341234');

  await page.getByRole('button', { name: 'Login' }).click();

  await page.waitForURL('/');

  await page.context().storageState({ path: authFile });
});
