#!/bin/bash

main() {
    if [ "$IS_WORKER" = "true" ]; then
        exec "$@"
    else
        wait_for_db
        run_migrations
        optimize_app
        run_server "$@"
    fi
}

wait_for_db() {
    echo "Waiting for DB to be ready"
    until php artisan migrate:status > /dev/null 2>&1; do
        echo "Waiting for DB to be ready..."
        sleep 1
    done
}

run_migrations() {
    ./artisan migrate --force
}

optimize_app() {
    ./artisan optimize:clear
    ./artisan optimize
}

run_server() {
    exec /usr/local/bin/docker-php-entrypoint "$@"
}

main "$@"