import { expect, test } from '../fixtures/index';

test('Should navigate to login page', async ({ page }) => {
  await page.goto('/');
  const loginNav = page.getByRole('link', { name: 'Login' });
  await expect(loginNav).toBeVisible();
  await loginNav.click();
  await page.waitForURL('/login');
  await expect(page.getByTestId('login-form')).toBeVisible();
});

test('Should login successfully', async ({ page }) => {
  await page.goto('/login');

  await expect(page.getByRole('button', { name: 'Login' })).toBeDisabled();

  await page.getByRole('textbox', { name: 'Email:' }).click();
  await page.getByRole('textbox', { name: 'Email:' }).fill('test@gmail.com');
  await page.getByRole('textbox', { name: 'Password:' }).click();
  await page.getByRole('textbox', { name: 'Password:' }).fill('12341234');

  await expect(page.getByRole('button', { name: 'Login' })).not.toBeDisabled();

  await page.getByRole('button', { name: 'Login' }).click();

  await page.waitForURL('/');

  await expect(page.getByRole('link', { name: 'Profile' })).toBeVisible();

  await expect(page.getByRole('button', { name: 'Logout' })).toBeVisible();
});

test.describe('Should fail to login', () => {
  test('Email not exist', async ({ page, toaster }) => {
    await page.goto('/login');

    await page.getByRole('textbox', { name: 'Email:' }).click();
    await page.getByRole('textbox', { name: 'Email:' }).fill('test123@gmail.com');
    await page.getByRole('textbox', { name: 'Password:' }).click();
    await page.getByRole('textbox', { name: 'Password:' }).fill('12341234');
    await page.getByRole('button', { name: 'Login' }).click();

    await toaster.waitFor('Incorrect credentials');
    await expect(page.getByRole('textbox', { name: 'Password:' })).toBeEmpty();
  });

  test('Password not correct', async ({ page, toaster }) => {
    await page.goto('/login');

    await page.getByRole('textbox', { name: 'Email:' }).click();
    await page.getByRole('textbox', { name: 'Email:' }).fill('test@gmail.com');
    await page.getByRole('textbox', { name: 'Password:' }).click();
    await page.getByRole('textbox', { name: 'Password:' }).fill('12341235');
    await page.getByRole('button', { name: 'Login' }).click();

    await toaster.waitFor('Incorrect credentials');
    await expect(page.getByRole('textbox', { name: 'Password:' })).toBeEmpty();
  });
});
