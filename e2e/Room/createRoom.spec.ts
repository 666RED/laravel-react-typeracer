import { test } from '@hyvor/laravel-playwright';
import { expect } from '@playwright/test';

test('Should show create room dialog', async ({ page }) => {
  await page.goto('/');
  await expect(page.getByRole('button', { name: 'Create new room' })).toBeVisible();
  await page.getByRole('button', { name: 'Create new room' }).click();
  await expect(page.getByRole('dialog', { name: 'Create new room' })).toBeVisible();

  await expect(page.getByRole('textbox', { name: 'Room ID' })).not.toBeEmpty();
  await expect(page.getByRole('textbox', { name: 'Room ID' })).not.toBeEditable();
  await expect(page.getByRole('textbox', { name: 'Name' })).toBeFocused();
  await expect(page.getByRole('textbox', { name: 'Name' })).toBeEmpty();
  await expect(page.getByTestId('create-room-button')).toBeDisabled();

  //@ Player count selector (2 - 5)
  await page.getByRole('button', { name: '2' }).click();
  await expect(page.getByRole('menu', { name: '2' })).toBeVisible();
  await expect(page.getByRole('menuitemradio', { name: '2' })).toBeVisible();
  await expect(page.getByRole('menuitemradio', { name: '3' })).toBeVisible();
  await expect(page.getByRole('menuitemradio', { name: '4' })).toBeVisible();
  await expect(page.getByRole('menuitemradio', { name: '5' })).toBeVisible();
});

test.describe('Create room testing', () => {
  test('Should create public room', async ({ browser }) => {
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    await page1.goto('/');
    await page2.goto('/');

    //@ User1 creates room
    await page1.getByRole('button', { name: 'Create new room' }).click();

    await expect(page1.getByTestId('create-room-button')).toBeDisabled();

    const testRoomName = 'Test room';

    await page1.getByRole('textbox', { name: 'Name' }).fill(testRoomName);

    await expect(page1.getByTestId('create-room-button')).toBeEnabled();

    await page1.getByTestId('create-room-button').click();
    await page1.waitForURL(/\/room\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/);

    //@ User2 should see the room at lobby
    await expect(page2.getByTestId('room-card').filter({ hasText: testRoomName })).toBeVisible();

    //@ User1 expect continue...
    await expect(page1.getByTestId('room-setting-icon')).toBeVisible();

    await expect(page1.getByText(testRoomName)).toBeVisible();
    await expect(page1.getByRole('button', { name: 'Leave' })).toBeVisible();
    await expect(page1.getByRole('button', { name: 'Delete' })).toBeVisible();
    await expect(page1.getByRole('button', { name: 'Leaderboard' })).toBeVisible();
    await expect(page1.getByTestId('start-race-button')).toBeVisible();
    await expect(page1.getByTestId('room-message-container')).toBeVisible();

    //@ Close browser contexts
    await context1.close();
    await context2.close();
  });

  test('Should create private room', async ({ browser }) => {
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    await page1.goto('/');
    await page2.goto('/');

    //@ User1 creates room
    await page1.getByRole('button', { name: 'Create new room' }).click();

    const testRoomName = 'Test room 2';

    await page1.getByRole('textbox', { name: 'Name' }).fill(testRoomName);
    await page1.getByRole('switch', { name: 'Private room' }).setChecked(true); //@ Set room to private

    await page1.getByTestId('create-room-button').click();
    await page1.waitForURL(/\/room\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/);

    //@ User2 should not see the room at lobby
    await expect(page2.getByTestId('room-card').filter({ hasText: testRoomName })).not.toBeVisible();

    //@ Close browser contexts
    await context1.close();
    await context2.close();
  });
});
