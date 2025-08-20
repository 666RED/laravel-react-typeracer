import { expect, test } from '../fixtures/index';
import { ToasterFixture } from '../fixtures/toaster';

test('Should update room name', async ({ browser, room }) => {
  const updatedRoomName = 'Update room';

  const { roomId, page1, page2, createRoomContext, joinRoomContext, roomName } = await room.createAndJoin();

  //@ View room
  const viewRoomContext = await browser.newContext();
  const page3 = await viewRoomContext.newPage();
  await page3.goto('/');
  const page3RoomCard = page3.getByTestId('room-card').filter({ hasText: roomId });
  await expect(page3RoomCard).toBeVisible();
  await expect(page3RoomCard).toContainText(roomName);

  //@ Update room name
  await page1.getByTestId('room-setting-icon').click();
  const roomSettingDialog = page1.getByRole('dialog', { name: 'Room Setting' });
  await expect(roomSettingDialog).toBeVisible();
  await expect(roomSettingDialog.getByRole('textbox', { name: 'Name' })).toHaveValue(roomName);
  await roomSettingDialog.getByRole('textbox', { name: 'Name' }).fill(updatedRoomName);
  await roomSettingDialog.getByRole('button', { name: 'Save' }).click();
  await expect(page1.getByTestId('room-name')).toHaveText(updatedRoomName);
  const page1Toaster = new ToasterFixture(page1);
  await page1Toaster.waitFor('Room setting updated');

  //@ Joiner should see updated room name
  await expect(page2.getByTestId('room-name')).toHaveText(updatedRoomName);

  //@ Viewer should see updated room name in lobby
  await expect(page3RoomCard).toContainText(updatedRoomName);

  //@ Close contexts
  await createRoomContext.close();
  await joinRoomContext.close();
  await viewRoomContext.close();
});

test('Should update room from private to public', async ({ browser, room }) => {
  const roomName = 'Room 1';

  //@ Create room
  const { roomId, page: page1, context: createRoomContext } = await room.createRoom(roomName, true);

  //@ View room (not visible because room is private)
  const viewRoomContext = await browser.newContext();
  const page2 = await viewRoomContext.newPage();
  await page2.goto('/');
  const page2RoomCard = page2.getByTestId('room-card').filter({ hasText: roomId });
  await expect(page2RoomCard).not.toBeVisible();

  //@ Update room private setting
  await page1.getByTestId('room-setting-icon').click();
  const roomSettingDialog = page1.getByRole('dialog', { name: 'Room Setting' });
  await expect(roomSettingDialog).toBeVisible();
  const privateSettingSwitch = roomSettingDialog.getByRole('switch', { name: 'Private room' });
  await expect(privateSettingSwitch).toBeChecked();
  await privateSettingSwitch.uncheck();
  await roomSettingDialog.getByRole('button', { name: 'Save' }).click();

  //@ Page 1 assertion
  const page1Toaster = new ToasterFixture(page1);
  await page1Toaster.waitFor('Room setting updated');
  await page1.getByTestId('room-setting-icon').click();
  await expect(privateSettingSwitch).not.toBeChecked();

  //@ Viewer should see room in lobby
  await expect(page2RoomCard).toBeVisible();

  //@ Close contexts
  await createRoomContext.close();
  await viewRoomContext.close();
});

test('Should update room from public to private', async ({ browser, room }) => {
  const roomName = 'Room 1';

  //@ Create room
  const { roomId, page: page1, context: createRoomContext } = await room.createRoom(roomName);

  //@ View room
  const viewRoomContext = await browser.newContext();
  const page2 = await viewRoomContext.newPage();
  await page2.goto('/');
  const page2RoomCard = page2.getByTestId('room-card').filter({ hasText: roomId });
  await expect(page2RoomCard).toBeVisible();

  //@ Update room private setting
  await page1.getByTestId('room-setting-icon').click();
  const roomSettingDialog = page1.getByRole('dialog', { name: 'Room Setting' });
  await expect(roomSettingDialog).toBeVisible();
  const privateSettingSwitch = roomSettingDialog.getByRole('switch', { name: 'Private room' });
  await expect(privateSettingSwitch).not.toBeChecked();
  await privateSettingSwitch.check();
  await roomSettingDialog.getByRole('button', { name: 'Save' }).click();

  //@ Page 1 assertion
  const page1Toaster = new ToasterFixture(page1);
  await page1Toaster.waitFor('Room setting updated');
  await page1.getByTestId('room-setting-icon').click();
  await expect(privateSettingSwitch).toBeChecked();

  //@ Viewer should see room in lobby
  await expect(page2RoomCard).not.toBeVisible();

  //@ Close contexts
  await createRoomContext.close();
  await viewRoomContext.close();
});

test('Should update room max player', async ({ browser, room }) => {
  const { roomId, page1, createRoomContext, joinRoomContext } = await room.createAndJoin();

  //@ View room
  const viewRoomContext = await browser.newContext();
  const page3 = await viewRoomContext.newPage();
  await page3.goto('/');
  const page3RoomCard = page3.getByTestId('room-card').filter({ hasText: roomId });
  await expect(page3RoomCard).toBeVisible();
  await expect(page3RoomCard).toContainText('Players: 2 / 2');
  await expect(page3RoomCard.getByRole('button', { name: 'Join' })).toBeDisabled();

  //@ Update room max player
  await page1.getByTestId('room-setting-icon').click();
  const roomSettingDialog = page1.getByRole('dialog', { name: 'Room Setting' });
  await expect(roomSettingDialog).toBeVisible();
  await page1.getByTestId('max-player-menu-trigger').click();
  await expect(page1.getByRole('menuitemradio', { name: '2' })).toBeEnabled();
  await expect(page1.getByRole('menuitemradio', { name: '3' })).toBeEnabled();
  await expect(page1.getByRole('menuitemradio', { name: '4' })).toBeEnabled();
  await expect(page1.getByRole('menuitemradio', { name: '5' })).toBeEnabled();
  await page1.getByRole('menuitemradio', { name: '3' }).click();
  await roomSettingDialog.getByRole('button', { name: 'Save' }).click();
  await page1.waitForURL(`/room/${roomId}`);
  const page1Toaster = new ToasterFixture(page1);
  await page1Toaster.waitFor('Room setting updated');

  //@ Viewer should see updated room in lobby
  await expect(page3RoomCard).toContainText('Players: 2 / 3');
  await expect(page3RoomCard.getByRole('button', { name: 'Join' })).toBeEnabled();
  await page3RoomCard.getByRole('button', { name: 'Join' }).click();
  await page3.waitForURL(`/room/${roomId}`);

  //@ Page 1 assertion
  await page1.getByTestId('room-setting-icon').click();
  await page1.getByTestId('max-player-menu-trigger').click();
  await expect(page1.getByRole('menuitemradio', { name: '2' })).toBeDisabled();
  await expect(page1.getByRole('menuitemradio', { name: '3' })).toBeEnabled();
  await expect(page1.getByRole('menuitemradio', { name: '4' })).toBeEnabled();
  await expect(page1.getByRole('menuitemradio', { name: '5' })).toBeEnabled();

  //@ Close contexts
  await createRoomContext.close();
  await joinRoomContext.close();
  await viewRoomContext.close();
});
