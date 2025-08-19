// import { Response } from '@playwright/test';
// import { expect, test } from '../fixtures/index';
// import { ToasterFixture } from '../fixtures/toaster';

// test('Should delete room', async ({ room }) => {
//   const { createRoomContext, joinRoomContext, page1, page2 } = await room.createAndJoin();
//   await page2.waitForResponse((res: Response) => {
//     return res.url().includes('/broadcasting/auth') && res.request().method() === 'POST';
//   });

//   //@ User 1 deletes room
//   const deleteRoomButton = page1.getByRole('button', { name: 'Delete' });
//   await expect(deleteRoomButton).toBeVisible();
//   await deleteRoomButton.click();

//   const deleteRoomDialog = page1.getByRole('alertdialog', { name: 'Delete room?' });
//   await expect(deleteRoomDialog).toBeVisible();
//   await deleteRoomDialog.getByRole('button', { name: 'Delete' }).click();
//   await page1.waitForURL('/');

//   //@ User 2 should removed from the room and receive message

//   await page2.waitForURL('/');
//   const joinerToasterFixture = new ToasterFixture(page2);
//   await joinerToasterFixture.waitFor('Current room deleted by room owner');

//   //@ Current room for creator and joiner are missing
//   await expect(page1.getByTestId('current-room-card')).not.toBeVisible();
//   await expect(page2.getByTestId('current-room-card')).not.toBeVisible();

//   //@ Close contexts
//   await createRoomContext.close();
//   await joinRoomContext.close();
// });
