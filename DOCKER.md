# Run with Docker (local development)

Install Docker Desktop and start it using Linux containers. From PowerShell:

```powershell
cd C:\Users\USER\Desktop\resteruant\restaurant
docker compose up --build -d
docker compose logs -f app
```

Wait for dependency installation and migrations to finish, then open http://localhost:8000.
If `.env` sets `DOCKER_APP_PORT`, use that port instead. This workspace uses
`DOCKER_APP_PORT=8008` because other local containers already occupy port 8000.
The first build requires internet access to download PHP, Composer and dependencies.
PHP and Composer do not need to be installed on your computer.

The container creates `.env` from `.env.example` if missing and generates an app key
only when the existing key is empty. Existing `.env` settings remain available to
Laravel. Compose overrides the database connection to SQLite and sets its absolute
container path, even if `.env` contains a MySQL database name such as `laravel`.
Startup applies pending migrations to your existing database; back up
`database/database.sqlite` before starting if it contains important data.

Your source, SQLite database, logs and uploads stay in the project folder.
Composer dependencies use a separate Docker volume, so Windows dependencies do not
conflict with Linux. Startup runs `composer install` against `composer.lock`.
Source edits appear immediately. This application's Blade pages and public assets
do not need npm or Vite.

Create your staff account interactively:

```powershell
docker compose exec app php artisan restaurant:manager
```

Useful commands:

```powershell
docker compose ps
docker compose exec -e DB_DATABASE=:memory: app php artisan test
docker compose exec app php artisan meals:sync
docker compose exec app php artisan queue:work --tries=3
docker compose down
```

Run the queue command in a separate terminal if you use background jobs.
`docker compose down` preserves the project database and the dependency volume.
After changing dependencies, restart the container to install them. After changing
the Dockerfile, run `docker compose up --build -d` again.

To change the port, add `DOCKER_APP_PORT=8080` to `.env`, then run
`docker compose up -d` and open http://localhost:8080.

This setup is for local development: it uses a source bind mount, development
dependencies and your local application settings. Public deployment needs a
separate production configuration with HTTPS, secrets and database backups.
