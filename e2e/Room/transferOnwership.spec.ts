// import { expect, test } from '../fixtures/index';

// test('Should transfer ownership to another player', async ({ room }) => {
//   const { createRoomContext, joinRoomContext, page1, page2 } = await room.createAndJoin();

//   //@ Transfer ownership
//   const roomSettingIcon = page1.getByTestId('room-setting-icon');
//   await expect(roomSettingIcon).toBeVisible();
//   await roomSettingIcon.click();
//   const roomSettingDialog = page1.getByRole('dialog', { name: 'Room Setting' });
//   await expect(roomSettingDialog).toBeVisible();

//   const ownerMenuTrigger = page1.getByTestId('owner-menu-trigger');
//   await expect(ownerMenuTrigger).toBeVisible();
//   await ownerMenuTrigger.click();
//   await expect(page1.getByRole('menuitemradio')).toHaveCount(2);
//   await page1.getByRole('menuitemradio').nth(1).click();
//   await roomSettingDialog.getByRole('button', { name: 'Save' }).click();

//   //@ User 1 cannot see the setting icon anymore
//   await room.expectRoomMessage(page1, 'is the room owner now');
//   await expect(roomSettingIcon).not.toBeVisible();

//   //@ User 2 should see the setting icon
//   await room.expectRoomMessage(page2, 'is the room owner now');
//   await expect(page2.getByTestId('room-setting-icon')).toBeVisible();

//   //@ Close contexts
//   await createRoomContext.close();
//   await joinRoomContext.close();
// });

// test('Should transfer ownership to another player and leave the room', async ({ room }) => {
//   const { page1, page2, createRoomContext, joinRoomContext } = await room.createAndJoin();

//   //@ Transfer ownership and leave
//   await page1.goto('/');
//   const currentRoomCard = page1.getByTestId('current-room-card');
//   await expect(currentRoomCard).toBeVisible();
//   await currentRoomCard.getByRole('button', { name: 'Leave' }).click();
//   const leaveRoomDialog = page1.getByRole('alertdialog', { name: 'Leave room?' });
//   await expect(leaveRoomDialog).toBeVisible();
//   await leaveRoomDialog.getByRole('button', { name: 'Leave' }).click();

//   //@ User 1 should not see current room card & see available room
//   await expect(currentRoomCard).not.toBeVisible();
//   await expect(page1.getByTestId('room-card')).toBeVisible();

//   //@ User 2 should become owner & see leave room message
//   await room.expectRoomMessage(page2, 'leave');
//   await expect(page2.getByTestId('room-setting-icon')).toBeVisible();
//   await expect(page2.getByTestId('room-player')).toHaveCount(1);

//   //@ Close context
//   await createRoomContext.close();
//   await joinRoomContext.close();
// });

// test('Should transfer ownership to another player and join new room', async ({ room }) => {
//   const roomName1 = 'Room 1';
//   const roomName2 = 'Room 2';

//   //@ Create room 1
//   const { roomId: roomId1, page: page1, context: createRoomContext1 } = await room.createRoom(roomName1);

//   //@ Create room 2
//   const { roomId: roomId2, page: page2, context: createRoomContext2 } = await room.createRoom(roomName2);

//   //@ Join room
//   const { context: joinRoomContext, page: page3 } = await room.joinRoom(roomId1);

//   //@ Transfer ownership and join new room
//   await page1.goto('/');
//   await page1.getByTestId('room-card').filter({ hasText: roomId2 }).getByRole('button', { name: 'Join' }).click();
//   const joinRoomDialog = page1.getByRole('alertdialog', { name: 'Join room?' });
//   await expect(joinRoomDialog).toBeVisible();
//   await joinRoomDialog.getByRole('button', { name: 'Proceed' }).click();

//   //@ User 1 should join new room
//   await expect(page1.getByText(roomName2)).toBeVisible();

//   //@ Joiner should become owner & see leave room message
//   await room.expectRoomMessage(page3, 'leave');
//   await expect(page3.getByTestId('room-setting-icon')).toBeVisible();
//   await expect(page3.getByTestId('room-player')).toHaveCount(1);

//   //@ User 2 should see join room message
//   await room.expectRoomMessage(page2, 'joined');

//   //@ Close contexts
//   await createRoomContext1.close();
//   await createRoomContext2.close();
//   await joinRoomContext.close();
// });
