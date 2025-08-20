import { test as teardown } from '@hyvor/laravel-playwright';

teardown('nothing here', async ({ laravel }) => {
  // await laravel.artisan('migrate:refresh', ['--seed']);
  // await laravel.callFunction('Redis::flushdb');
});
