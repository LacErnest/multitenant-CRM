@setup
    $repository = 'git@gitlab.cyrextech.net:dev-awesome/oz-finance.git';
    $release = date('YmdHis');
    if($env == 'testing') {
        $releases_dir = '/var/www/stage.finance.emgoz.studio/releases';
        $app_dir = '/var/www/stage.finance.emgoz.studio';
        $url = 'https://stage.finance.emgoz.studio/api/';
        $health_url = 'https://stage.finance.emgoz.studio';
        $deploy_servers = ['web' => 'deployer@10.10.101.67'];
    } elseif($env == 'production') {
        $releases_dir = '/var/www/mt.emgoz.studio/releases';
        $app_dir = '/var/www/mt.emgoz.studio';
        $url = 'https://mt.emgoz.studio/api/';
        $health_url = 'https://mt.emgoz.studio';
        $deploy_servers = ['web' => 'deployer@10.10.101.22'];
    }
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@servers($deploy_servers)

@story('deploy')
    clone_repository
    run_composer
    update_symlinks
    clear_cache
    run_migration
    build_frontend
    link_app
    restart_queue
    health_check
    deployment_cleanup
@endstory

@story('rollback')
    deployment_rollback
    health_check
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 10 {{ $repository }} --branch={{ $branch }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
    git reset --hard {{ $commit }}
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}/api
    git config --global url.ssh://git@gitlab.cyrextech.net/.insteadOf https://gitlab.cyrextech.net/
    @if($env == 'production' || $env == 'alpha')
        composer install --no-dev --no-interaction --prefer-dist --no-scripts -q -o;
    @else
        composer install --no-interaction --prefer-dist --no-scripts -q -o;
    @endif
@endtask

@task('clear_cache')
    echo "Starting cache clear ({{ $release }})"
    php {{ $new_release_dir }}/api/artisan view:clear --quiet
    php {{ $new_release_dir }}/api/artisan cache:clear --quiet
    php {{ $new_release_dir }}/api/artisan config:cache --quiet
@endtask

@task('deployment_cleanup')
    echo "Clean up old deployments ({{ $release }})"
    cd {{ $releases_dir }}
    find . -maxdepth 1 -name "20*" | sort | head -n -5 | xargs rm -Rf
@endtask

@task('deployment_rollback')
    echo "Roll back to $(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1)"
    cd {{ $releases_dir }}
    ln -nfs {{ $releases_dir }}/$(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1) {{ $app_dir }}/current
@endtask

@task('restart_queue')
    echo "Restart queue"
    @if($env == 'production')
        supervisorctl restart worker:*
    @else
        supervisorctl restart stage-finance-oz-worker:*
    @endif
@endtask

@task('run_migration')
    echo "Starting migration ({{ $release }})"
    @if($env == 'production' || $env == 'alpha')
        php {{ $new_release_dir }}/api/artisan migrate --force --no-interaction
        php {{ $new_release_dir }}/api/artisan tenant:migrate_tenant --all
    @else
        php {{ $new_release_dir }}/api/artisan tenant:migrate_refresh --force --no-interaction
    @endif
@endtask

@task('health_check')
    @if (!empty($health_url) )
        if [ "$(curl --write-out "%{http_code}\n" --silent --output /dev/null {{ $health_url }})" == "200" ]; then
            printf "\033[0;32mHealth check to {{ $health_url }} OK\033[0m\n"
        else
            printf "\033[1;31mHealth check to {{ $health_url }} FAILED\033[0m\n"
        fi
    @else
        echo "No health check set"
    @endif
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/api/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/api/storage

    php {{ $new_release_dir }}/api/artisan storage:link

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/api/.env

    echo 'Linking .htaccess file'
    ln -nfs {{ $app_dir }}/.htaccess {{ $new_release_dir }}/api/public/.htaccess
@endtask

@task('link_app')
    echo "Setting permissions in storage directory"
    chown -R --from=:deployer :www-data {{ $app_dir }}/storage
    find {{ $app_dir }}/storage -user deployer -exec chmod 0775 {} \;

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current
@endtask

@task('build_frontend')
    echo "Building frontend"
    cd {{ $new_release_dir }}/angular
    npm install --force
    @if($env == 'production')
        ng build --prod
    @else
        npm run build:stage
    @endif
    cp -rf {{ $new_release_dir }}/angular/dist/oz-finance/* {{ $new_release_dir }}/api/public/
@endtask

