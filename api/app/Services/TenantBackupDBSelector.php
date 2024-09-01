<?php


namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\DbDumper;
use Tenancy\Support\PasswordGenerator;

class TenantBackupDBSelector extends DbDumperFactory
{
    public static function getTenantDatabaseConnections(): Collection
    {
        $companies = Company::all();

        return $companies->map(function ($tenant) {
            return self::createConnection($tenant);
        });
    }

    public static function createConnection($tenant): DbDumper
    {
        $dbConfig = config('database.connections.mysql');

        $dbDumper = static::forDriver($dbConfig['driver'])
          ->setHost(Arr::first(Arr::wrap($dbConfig['host'] ?? '')))
          ->setDbName($tenant->getTenantKey())
          ->setUserName($tenant->getTenantKey())
          ->setPassword((new \Tenancy\Support\PasswordGenerator)->__invoke($tenant));
        if ($dbDumper instanceof MySql) {
            $dbDumper->setDefaultCharacterSet($dbConfig['charset'] ?? '');
        }
        if (isset($dbConfig['port'])) {
            $dbDumper = $dbDumper->setPort($dbConfig['port']);
        }
        if (isset($dbConfig['dump'])) {
            $dbDumper = static::processExtraDumpParameters($dbConfig['dump'], $dbDumper);
        }

        return $dbDumper;
    }
}
