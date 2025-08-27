#!/bin/sh
set -e

main() {
  if [ "$IS_WORKER" = "true" ]; then
    exec "$@"
  else
    wait_for_db
    run_migrations
    run_server "$@"
  fi
}

wait_for_db() {
  echo "Waiting for PostgreSQL to be ready..."
  until pg_isready -h db -p 5432 -U "$DB_USERNAME"; do
    >&2 echo "Postgres is unavailable - sleeping"
    sleep 2
  done
  echo "PostgreSQL is up - continuing"
}

run_migrations() {
    php artisan migrate --force
}

run_server() {
  exec /usr/local/bin/docker-php-entrypoint "$@"
}

main "$@"
