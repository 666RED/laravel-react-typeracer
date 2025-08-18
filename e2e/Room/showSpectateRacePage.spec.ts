import { expect, test } from '../fixtures/index';

test('Should show spectate race page', async ({ race, room }) => {
  const { roomId, createRoomContext, joinRoomContext } = await race.startRace();

  //@ Spectator
  const { context: spectateContext, page: page3 } = await room.joinRoom(roomId);
  const spectateButton = page3.getByRole('button', { name: 'Spectate' });
  await expect(spectateButton).toBeVisible();
  await expect(page3.getByTestId('player-status').filter({ hasText: 'In race' })).toHaveCount(2);
  await spectateButton.click();
  await page3.waitForURL(`/room/${roomId}/spectate`);
  await expect(page3.getByTestId('spectate-title')).toBeVisible();
  await expect(page3.getByTestId('spectate-main-content')).toBeVisible();
  await expect(page3.getByTestId('in-race-player')).toHaveCount(2);
  await expect(page3.getByRole('button', { name: 'Return waiting room' })).toBeVisible();
  await expect(page3.getByRole('button', { name: 'Return waiting room' })).toBeEnabled();

  //@ Close contexts
  await createRoomContext.close();
  await joinRoomContext.close();
  await spectateContext.close();
});

test('Should show in race and completed player status', async ({ room, race }) => {
  const { createRoomContext, joinRoomContext, roomId } = await race.startRace();

  //@ Spectator
  const { page: page3, context: spectateContext } = await room.joinRoom(roomId);
  const spectateButton = page3.getByRole('button', { name: 'Spectate' });
  await expect(spectateButton).toBeVisible();
  await expect(page3.getByTestId('player-status').filter({ hasText: 'In race' })).toHaveCount(2);
  await spectateButton.click();
  await page3.waitForURL(`/room/${roomId}/spectate`);
  await expect(page3.getByTestId('spectate-title')).toBeVisible();
  await expect(page3.getByTestId('spectate-main-content')).toBeVisible();
  await expect(page3.getByTestId('in-race-player')).toHaveCount(2);
  await expect(page3.getByRole('button', { name: 'Return waiting room' })).toBeVisible();
  await expect(page3.getByRole('button', { name: 'Return waiting room' })).toBeEnabled();

  //@ Close contexts
  await createRoomContext.close();
  await joinRoomContext.close();
  await spectateContext.close();
});
