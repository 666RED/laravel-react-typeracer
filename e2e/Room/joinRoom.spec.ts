import { expect, test } from '../fixtures/index';
import { ToasterFixture } from '../fixtures/toaster';

test.describe('Should join room', async () => {
  test('Join public room', async ({ browser, room }) => {
    const roomName = 'Room 1';

    //@ Create room setup
    const { roomId, context: createRoomContext, page: page1 } = await room.createRoom(roomName);

    const joinRoomContext = await browser.newContext();
    const joinRoomPage = await joinRoomContext.newPage();
    const lobbyContext = await browser.newContext();
    const lobbyPage = await lobbyContext.newPage();

    await joinRoomPage.goto('/');
    await lobbyPage.goto('/');

    //@ Start assertions
    const roomCard = joinRoomPage.getByTestId('room-card').filter({ hasText: roomId });

    await expect(roomCard).toBeVisible();

    const joinRoomButton = roomCard.getByRole('button', { name: 'Join' });
    await expect(joinRoomButton).toBeEnabled();
    await joinRoomButton.click();
    await joinRoomPage.waitForURL(`/room/${roomId}`);

    //@ Room owner should see join room message
    await room.expectRoomMessage(page1, 'joined');

    //@ Viewer should see the room card update and not able to click the join room button
    await expect(lobbyPage.getByTestId('room-card').filter({ hasText: roomId }).getByRole('paragraph').nth(2)).toContainText('Players: 2 / 2');
    await expect(lobbyPage.getByTestId('room-card').filter({ hasText: roomId }).getByRole('button', { name: 'Join' })).toBeDisabled();

    //@ Joiner assertions
    await expect(joinRoomPage.getByTestId('room-setting-icon')).not.toBeVisible();

    await expect(joinRoomPage.getByText(roomName)).toBeVisible();
    await expect(joinRoomPage.getByRole('button', { name: 'Leave' })).toBeVisible();
    await expect(joinRoomPage.getByRole('button', { name: 'Delete' })).not.toBeVisible();
    await expect(joinRoomPage.getByRole('button', { name: 'Leaderboard' })).toBeVisible();
    await expect(joinRoomPage.getByTestId('start-race-button')).not.toBeVisible();
    await expect(joinRoomPage.getByTestId('room-message-container')).toBeVisible();

    //@ close contexts
    await createRoomContext.close();
    await joinRoomContext.close();
    await lobbyContext.close();
  });

  test('Join private room', async ({ browser, room }) => {
    const roomName = 'Room 1';

    //@ Create room
    const { roomId, context: createRoomContext, page: page1 } = await room.createRoom(roomName, true);

    //@ Join private room
    const joinRoomContext = await browser.newContext();
    const page2 = await joinRoomContext.newPage();
    await page2.goto('/');
    const roomCard = page2.getByTestId('room-card').filter({ hasText: roomId });
    await expect(roomCard).not.toBeVisible();
    await page2.getByRole('textbox', { name: 'Enter Room ID' }).fill(roomId);
    await page2.locator('form').getByRole('button', { name: 'Join' }).click();
    await page2.waitForURL(`/room/${roomId}`);

    //@ Room owner should see join room message
    await room.expectRoomMessage(page1, 'joined');

    //@ Joiner assertions
    await expect(page2.getByTestId('room-setting-icon')).not.toBeVisible();

    await expect(page2.getByText(roomName)).toBeVisible();
    await expect(page2.getByRole('button', { name: 'Leave' })).toBeVisible();
    await expect(page2.getByRole('button', { name: 'Delete' })).not.toBeVisible();
    await expect(page2.getByRole('button', { name: 'Leaderboard' })).toBeVisible();
    await expect(page2.getByTestId('start-race-button')).not.toBeVisible();
    await expect(page2.getByTestId('room-message-container')).toBeVisible();

    //@ close contexts
    await createRoomContext.close();
    await joinRoomContext.close();
  });
});

test('Should join back current room', async ({ room }) => {
  const roomName = 'Room 1';
  //@ Create room
  const { page, roomId, context } = await room.createRoom(roomName);

  await page.goto('/');
  const joinButton = page.getByTestId('current-room-card').filter({ hasText: roomId }).getByRole('button', { name: 'Join' });

  await expect(joinButton).toBeEnabled();
  await joinButton.click();
  await page.waitForURL(`/room/${roomId}`);

  await context.close();
});

test.describe('Should not join room', () => {
  test('Room is full', async ({ browser, room }) => {
    //@ Create room and join room
    const { roomId, createRoomContext, joinRoomContext } = await room.createAndJoin();

    //@ Third player should not click the join room button
    const lobbyContext = await browser.newContext();
    const lobbyPage = await lobbyContext.newPage();
    await lobbyPage.goto('/');
    await expect(lobbyPage.getByTestId('room-card').filter({ hasText: roomId }).getByRole('button', { name: 'Join' })).toBeDisabled();

    await createRoomContext.close();
    await joinRoomContext.close();
    await lobbyContext.close();
  });

  test('Room not found', async ({ room }) => {
    const roomName = 'Room 1';
    const { page, context } = await room.createRoom(roomName);

    await page.goto('/');

    await page.waitForURL('/');

    await page.goto('/room/123');

    await page.waitForURL('/');

    const toaster = new ToasterFixture(page);

    await toaster.waitFor('Room not found');

    await context.close();
  });

  test('Do not join room through join room button', async ({ browser, room }) => {
    const roomName = 'Room 1';

    //@ Create room
    const { roomId, context: context1 } = await room.createRoom(roomName);

    const context2 = await browser.newContext();
    const page2 = await context2.newPage();
    await page2.goto(`/room/${roomId}`);
    const toasterFixture = new ToasterFixture(page2);
    await toasterFixture.waitFor('Join a room first');

    //@ Close contexts
    await context1.close();
    await context2.close();
  });
});

test('Should leave previous room and join new room', async ({ browser, room }) => {
  const roomName1 = 'Room 1';
  const roomName2 = 'Room 2';

  //@ User 1 creates room
  const { roomId: room1Id, context: createRoomContext1, page: page1 } = await room.createRoom(roomName1);

  //@ User 2 creates room
  const { roomId: room2Id, context: createRoomContext2, page: page2 } = await room.createRoom(roomName2);

  //@ Joiner joins first room
  const { context: joinRoomContext, page: page3 } = await room.joinRoom(room1Id);

  //@ Viewer should see 2 room cards
  const viewRoomContext = await browser.newContext();
  const page4 = await viewRoomContext.newPage();

  await page4.goto('/');
  const roomCard1 = page4.getByTestId('room-card').filter({ hasText: room1Id });
  const roomCard2 = page4.getByTestId('room-card').filter({ hasText: room2Id });

  await expect(page4.getByTestId('room-card')).toHaveCount(2);
  await expect(roomCard1).toContainText('Players: 2 / 2');
  await expect(roomCard2).toContainText('Players: 1 / 2');

  //@ Joiner leaves previous room and join new room
  await page3.goto('/');
  await expect(page3.getByTestId('current-room-card')).toBeVisible();
  await page3.getByTestId('room-card').filter({ hasText: room2Id }).getByRole('button', { name: 'Join' }).click();

  const joinRoomDialog = page3.getByRole('alertdialog', { name: 'Join room?' });
  await expect(joinRoomDialog).toBeVisible();
  await joinRoomDialog.getByRole('button', { name: 'Proceed' }).click();
  await page3.waitForURL(`/room/${room2Id}`);
  await expect(page3.getByText('Room 2')).toBeVisible();

  //@ User 1 should see leave room message
  await room.expectRoomMessage(page1, 'leave');

  //@ User 2 should see join room message & 2 player cards
  await room.expectRoomMessage(page2, 'joined');
  await expect(page2.getByTestId('room-player')).toHaveCount(2);

  //@ Viewer should see updated cards in lobby
  await expect(roomCard1).toContainText('Players: 1 / 2');
  await expect(roomCard2).toContainText('Players: 2 / 2');
  await expect(roomCard1.getByRole('button', { name: 'Join' })).toBeEnabled();
  await expect(roomCard2.getByRole('button', { name: 'Join' })).toBeDisabled();

  //@ Close contexts
  await createRoomContext1.close();
  await createRoomContext2.close();
  await joinRoomContext.close();
  await viewRoomContext.close();
});

test('Should delete previous room and join new room', async ({ browser, room }) => {
  const roomName1 = 'Room 1';
  const roomName2 = 'Room 2';

  //@ User 1 creates room
  const { context: createRoomContext1, page: page1, roomId: room1Id } = await room.createRoom(roomName1);

  //@ User 2 creates room
  const { context: createRoomContext2, page: page2, roomId: room2Id } = await room.createRoom(roomName2);

  //@ Viewer should see 2 room cards
  const viewRoomContext = await browser.newContext();
  const page3 = await viewRoomContext.newPage();

  await page3.goto('/');
  const roomCard1 = page3.getByTestId('room-card').filter({ hasText: room1Id });
  const roomCard2 = page3.getByTestId('room-card').filter({ hasText: room2Id });

  await expect(page3.getByTestId('room-card')).toHaveCount(2);

  //@ User 1 deletes previous room and join new room
  await page1.goto('/');
  await expect(page1.getByTestId('current-room-card')).toBeVisible();
  await page1.getByTestId('room-card').filter({ hasText: room2Id }).getByRole('button', { name: 'Join' }).click();

  const joinRoomDialog = page1.getByRole('alertdialog', { name: 'Join room?' });
  await expect(joinRoomDialog).toBeVisible();
  await joinRoomDialog.getByRole('button', { name: 'Proceed' }).click();
  await page1.waitForURL(`/room/${room2Id}`);
  await expect(page1.getByText('Room 2')).toBeVisible();

  //@ User 2 should see join room message & 2 player cards
  await room.expectRoomMessage(page2, 'joined');
  await expect(page2.getByTestId('room-player')).toHaveCount(2);

  //@ Viewer should see updated cards in lobby
  await expect(roomCard1).not.toBeVisible();
  await expect(roomCard2).toContainText('Players: 2 / 2');
  await expect(roomCard2.getByRole('button', { name: 'Join' })).toBeDisabled();

  //@ Close contexts
  await createRoomContext1.close();
  await createRoomContext2.close();
  await viewRoomContext.close();
});
