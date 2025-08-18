import { Browser, expect, Page } from '@playwright/test';

export class RoomFixture {
  constructor(private browser: Browser) {}

  async createRoom(roomName: string, isPrivate = false, maxPlayer = '') {
    const createRoomContext = await this.browser.newContext();
    const page1 = await createRoomContext.newPage();
    await page1.goto('/');
    await page1.getByRole('button', { name: 'Create new room' }).click();
    await page1.getByRole('textbox', { name: 'Name' }).fill(roomName);

    if (isPrivate) {
      await page1.getByRole('switch', { name: 'Private room' }).check();
    }

    if (maxPlayer !== '') {
      await page1.getByTestId('max-player-menu-trigger').click();
      await page1.getByRole('menuitemradio', { name: maxPlayer }).click();
    }

    await page1.getByTestId('create-room-button').click();
    await page1.waitForURL(/\/room\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/);

    const url = page1.url();

    const match = url.match(/\/room\/([0-9a-fA-F-]{36})/);

    if (!match) {
      throw new Error('Room ID not found in URL');
    }

    return { roomId: match[1], context: createRoomContext, page: page1 };
  }

  async joinRoom(roomId: string) {
    const joinRoomContext = await this.browser.newContext();
    const page2 = await joinRoomContext.newPage();

    await page2.goto('/');
    await page2.getByTestId('room-card').filter({ hasText: roomId }).getByRole('button', { name: 'Join' }).click();
    await page2.waitForURL(`/room/${roomId}`);

    return { context: joinRoomContext, page: page2 };
  }

  async createAndJoin(isPrivate = false, maxPlayer = '') {
    const roomName = 'Room 1';
    const { roomId, context: createRoomContext, page: page1 } = await this.createRoom(roomName, isPrivate, maxPlayer);

    const { context: joinRoomContext, page: page2 } = await this.joinRoom(roomId);

    return { roomId, createRoomContext, joinRoomContext, page1, page2, roomName };
  }

  async leaveRoom(page: Page) {
    await page.getByRole('button', { name: 'Leave' }).click();
    await page.getByRole('alertdialog', { name: 'Leave room?' }).getByRole('button', { name: 'Leave' }).click();
    await page.waitForURL('/');
  }

  async expectRoomMessage(page: Page, message: string) {
    await expect(page.getByTestId('room-message-container')).toContainText(message);
  }

  async transferOwnership(page: Page) {
    await page.getByTestId('room-setting-icon').click();
    await page.getByTestId('owner-menu-trigger').click();
    await page.getByRole('menuitemradio').nth(1).click();
    await page.getByRole('dialog', { name: 'Room Setting' }).getByRole('button', { name: 'Save' }).click();
  }
}
