// import { expect, test } from '../fixtures/index';

// test('Should navigate to race page', async ({ room }) => {
//   const { roomId, page1, page2, joinRoomContext, createRoomContext } = await room.createAndJoin(false, '3');

//   //@ Spectator joins room
//   const { page: page3, context: spectateContext } = await room.joinRoom(roomId);

//   //@ User 2 set to ready state
//   await page2.getByTestId('room-player').nth(1).getByRole('button', { name: 'Ready' }).click();
//   await expect(page2.getByTestId('room-player').nth(1)).toContainText('Ready');
//   await expect(page2.getByTestId('room-player').nth(1).getByRole('button', { name: 'Cancel' })).toBeVisible();

//   //@ User 1 and spectator receives ready status update
//   await expect(page1.getByTestId('player-status').nth(1)).toHaveText('Ready');
//   await expect(page3.getByTestId('player-status').nth(1)).toHaveText('Ready');

//   //@ Start race
//   await expect(page1.getByRole('button', { name: 'New race' })).toBeEnabled();
//   await page1.getByRole('button', { name: 'New race' }).click();

//   //@ User 1 and user 2 should navigate to race page
//   await page1.waitForURL(`/room/${roomId}/race`);
//   await page2.waitForURL(`/room/${roomId}/race`);

//   //@ User 1 should see race main content
//   await expect(page1.getByTestId('race-main-content')).toBeVisible();
//   await expect(page1.getByTestId('in-race-player')).toHaveCount(2);
//   await expect(page1.getByTestId('quote')).toBeVisible();
//   await expect(page1.getByTestId('typing-area')).toBeVisible();
//   // await expect(page1.getByTestId('typing-area')).toBeEditable();

//   //@ User 2 should see race main content
//   await expect(page2.getByTestId('race-main-content')).toBeVisible();
//   await expect(page2.getByTestId('in-race-player')).toHaveCount(2);
//   await expect(page2.getByTestId('quote')).toBeVisible();
//   await expect(page2.getByTestId('typing-area')).toBeVisible();
//   // await expect(page2.getByTestId('typing-area')).toBeEditable();

//   //@ Spectator should see In race status texts & spectate button
//   await expect(page3.getByTestId('player-status').filter({ hasText: 'In race' })).toHaveCount(2);
//   await expect(page3.getByRole('button', { name: 'Spectate' })).toBeVisible();
//   await expect(page3.getByRole('button', { name: 'Spectate' })).toBeEnabled();

//   //@ Close contexts
//   await createRoomContext.close();
//   await joinRoomContext.close();
//   await spectateContext.close();
// });
