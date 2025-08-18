import { Browser } from '@playwright/test';
import { RoomFixture } from './room';

export class RaceFixture {
  constructor(private browser: Browser) {}

  async startRace() {
    const roomFixture = new RoomFixture(this.browser);

    const { roomId, page1, page2, createRoomContext, joinRoomContext } = await roomFixture.createAndJoin(false, '3');

    await page2.getByTestId('room-player').nth(1).getByRole('button', { name: 'Ready' }).click();

    await page1.getByRole('button', { name: 'New race' }).click();

    return { roomId, createRoomContext, joinRoomContext, page1, page2 };
  }
}
