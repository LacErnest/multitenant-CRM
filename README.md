Welcome to the CRM! This guide will help you get up and running quickly.


## Local environment

1. Clone the repository and navigate to its root directory

2. Configure git hooks to use the `.githooks` directory in the repo:
   ```
   git config --local core.hooksPath .githooks/
   ```

3. In GitLab, create a Person Access Token (PAT) with the grants `read_repository` and `read_registry`.

4. Create an environment configuration file from the sample:
   ```
   cp docker/.docker-compose.env.dist docker/.docker-compose.env
   ```

5. Set your GitLab PAT as the variable `GITLAB_COMPOSER_TOKEN` in `docker/.docker-compose.env`

6. Create a local docker compose configuration file:
   ```
   cp docker/docker-compose-dev.yml docker/docker-compose.yml
   ```

7. Prepare and fill/edit `.env` file for the API:
   ```
   cp api/.env.example api/.env
   ```

8. For added convenience, update `/etc/hosts` with the following line to use `oz-finance.local` instead of the default `localhost:port` address:
   ```
   localhost:8080 oz-finance.local
   ```

9. Build and start containers:
   ```
   cd docker
   make build
   make up
   ```

10. Prepare seed data with a user for yourself by editing `api/database/seeds/UserSeeder.php`

11. Run database migrations & seed data:
   ```
   make cli
   php artisan migrate:fresh --seed
   ```

12. Start the frontend app:
   ```
   cd ../angular
   nvm install (or manually install the desired Node.js version)
   npm i
   npm run dev
   ```

13. Profit. The application will be accessible at `http://localhost:4200` or `http://oz-finance.local:4200`

### Know issues

In certain Linux environments, the Docker bridge network used during `docker-compose build` may cause some private dependencies download to fail (root cause not known). This can be solved by setting `network: host` in the services that make use of such depdendencies. Example:
```
oz-finance-app:
   <<: *php-base
   image: oz-finance/api
   container_name: ozf_fpm
   build:
      <<: *build-php
      network: host
```

### Other useful commands

```
# generate new app key
php artisan key:generate

# generate new JwT secret
php artisan jwt:secret

# run migrations
php artisan migrate:fresh --seed

# refresh elasticsearch indexes
php artisan maintenance:elastic_refresh

# attach sales persons and lead gens to existent projects and quotes:
php artisan maintenance:update_project_quote_sales_persons
```