import { expect, test } from '../fixtures/index';

// test('Should navigate to register page', async ({ page }) => {
//   await page.goto('/');

//   const registerNav = page.getByRole('link', { name: 'Register' });
//   await expect(registerNav).toBeVisible();
//   await registerNav.click();
//   await page.waitForURL('/register');
//   await expect(page.getByTestId('register-form')).toBeVisible();
// });

test('Should register new user', async ({ page, toaster }) => {
  await page.goto('/register');

  const registerBtn = page.getByRole('button', { name: 'Register' });
  await expect(registerBtn).toBeDisabled();

  await page.getByRole('textbox', { name: 'Name:' }).click();
  await page.getByRole('textbox', { name: 'Name:' }).fill('Test name');
  await page.getByRole('textbox', { name: 'Email:' }).click();
  await page.getByRole('textbox', { name: 'Email:' }).fill('test2@gmail.com');

  await page.getByRole('textbox', { name: 'Password:' }).click();
  await page.getByRole('textbox', { name: 'Password:' }).fill('12341234');
  await page.getByRole('textbox', { name: 'Should match password' }).click();
  await page.getByRole('textbox', { name: 'Should match password' }).fill('12341234');

  await expect(registerBtn).not.toBeDisabled();

  await page.getByRole('button', { name: 'Register' }).click();
  await toaster.waitFor('Registered successfully');
  await page.waitForURL('/');
});

test('check asset loading', async ({ page }) => {
  await page.goto('/');
  const requests = [];

  page.on('request', (req) => {
    requests.push(req.url());
  });

  // wait until page is loaded
  await page.waitForLoadState('networkidle');

  console.log('Requests:', requests);
});

// test.describe('Should fail to register new user', () => {
//   test('Email already existed', async ({ page, toaster }) => {
//     await page.goto('/register');

//     await page.getByRole('textbox', { name: 'Name:' }).click();
//     await page.getByRole('textbox', { name: 'Name:' }).fill('Test name');
//     await page.getByRole('textbox', { name: 'Email:' }).click();
//     await page.getByRole('textbox', { name: 'Email:' }).fill('test@gmail.com');
//     await page.getByRole('textbox', { name: 'Password:' }).click();
//     await page.getByRole('textbox', { name: 'Password:' }).fill('12341234');
//     await page.getByRole('textbox', { name: 'Should match password' }).click();
//     await page.getByRole('textbox', { name: 'Should match password' }).fill('12341234');

//     await page.getByRole('button', { name: 'Register' }).click();

//     await toaster.waitFor('The email has already been taken.');
//     await expect(page.locator('p', { hasText: 'The email has already been taken.' })).toBeVisible();
//     await expect(page.getByRole('textbox', { name: 'Password:' })).toBeEmpty();
//     await expect(page.getByRole('textbox', { name: 'Should match password' })).toBeEmpty();
//   });

//   test('Password != confirmed password', async ({ page }) => {
//     await page.goto('/register');

//     await page.getByRole('textbox', { name: 'Name:' }).click();
//     await page.getByRole('textbox', { name: 'Name:' }).fill('Test name');
//     await page.getByRole('textbox', { name: 'Email:' }).click();
//     await page.getByRole('textbox', { name: 'Email:' }).fill('test3@gmail.com');
//     await page.getByRole('textbox', { name: 'Password:' }).click();
//     await page.getByRole('textbox', { name: 'Password:' }).fill('12341234');
//     await page.getByRole('textbox', { name: 'Should match password' }).click();
//     await page.getByRole('textbox', { name: 'Should match password' }).fill('12341235');

//     await page.getByRole('button', { name: 'Register' }).click();

//     await expect(page.locator('p', { hasText: 'The password field confirmation does not match.' })).toBeVisible();
//     await expect(page.getByRole('textbox', { name: 'Password:' })).toBeEmpty();
//     await expect(page.getByRole('textbox', { name: 'Should match password' })).toBeEmpty();
//   });
// });
