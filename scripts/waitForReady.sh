#!/bin/bash

echo "Waiting for Laravel to be ready..."

until php artisan migrate:status | grep -q 'cache'; do
  echo "Waiting for cache table..."
  sleep 2
done

echo "Laravel is ready. Starting process..."
exec "$@"