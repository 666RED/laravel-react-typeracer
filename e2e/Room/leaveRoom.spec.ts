import { expect, test } from '../fixtures/index';

test('Should leave room', async ({ browser, room }) => {
  const { roomId, createRoomContext, joinRoomContext, page1, page2 } = await room.createAndJoin();

  //@ Viewer should see players 2 / 2, and join room button is disabled
  const viewRoomContext = await browser.newContext();
  const page3 = await viewRoomContext.newPage();
  await page3.goto('/');
  const roomCard = page3.getByTestId('room-card').filter({ hasText: roomId });
  await expect(roomCard).toContainText('Players: 2 / 2');
  await expect(roomCard.getByRole('button', { name: 'Join' })).toBeDisabled();

  //@ Joiner leaves room
  await page2.getByRole('button', { name: 'Leave' }).click();
  await expect(page2.getByRole('alertdialog', { name: 'Leave room?' })).toBeVisible();
  await page2.getByRole('button', { name: 'Leave' }).click();
  await page2.waitForURL('/');

  //@ Creator should see leave room message and updated ui
  await expect(page1.getByTestId('room-message-container')).toContainText(/leave/);
  await expect(page1.getByTestId('room-player')).toHaveCount(1);

  //@ Viewer should see Players 1 / 2, and join room button is enabled
  await expect(roomCard).toContainText('Players: 1 / 2');
  await expect(roomCard.getByRole('button', { name: 'Join' })).toBeEnabled();

  //@ Close contexts
  await createRoomContext.close();
  await joinRoomContext.close();
  await viewRoomContext.close();
});

test('Should leave room and delete room', async ({ browser, room }) => {
  const roomName = 'Room 1';
  const { context: createRoomContext, roomId, page: page1 } = await room.createRoom(roomName);

  const viewRoomContext = await browser.newContext();
  const page2 = await viewRoomContext.newPage();

  //@ Viewer should see room card
  await page2.goto('/');
  const roomCard = page2.getByTestId('room-card').filter({ hasText: roomId });
  await expect(roomCard).toBeVisible();

  //@ Creator leaves room
  await room.leaveRoom(page1);

  // //@ Viewer should not see room card
  await expect(roomCard).not.toBeVisible();

  //@ Close contexts
  await createRoomContext.close();
  await viewRoomContext.close();
});
