#!/bin/bash

main() {
    if [ "$IS_WORKER" = "true" ]; then
        exec "$@"
    else
        wait_for_db
        prepare_file_permissions
        run_migrations
        optimize_app
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

prepare_file_permissions() {
    chmod a+x ./artisan
}

run_migrations() {
    php artisan migrate:fresh --seed
}

optimize_app() {
    php artisan optimize:clear
    php artisan optimize
}

run_server() {
    exec /usr/local/bin/docker-php-entrypoint "$@"
}

main "$@"