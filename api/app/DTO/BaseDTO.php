<?php

namespace App\DTO;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class BaseDTOEntity
 */
abstract class BaseDTO
{
    /**
     * Mapping definition
     *
     * @return array
     */
    abstract public static function mapping(): array;

    /**
     * A collection of properties, that should be exported
     *
     * @var array $export
     */
    private array $export = [];

    /**
     * BaseDTOEntity constructor.
     *
     * @param array $payload
     */
    public function __construct(array $payload = [])
    {
        $this->process($payload);
    }

    /**
     * Converts all the properties listed in e
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        $config = static::mapping();

        foreach ($this->export as $item) {
            foreach ($config as $configLine) {
                if ($item === $configLine['target']) {
                    if (!isset($configLine['nullable'])) {
                        $result[$item] = $this->get($item);
                    } else {
                        if (null !== $this->get($item)) {
                              $result[$item] = $this->get($item);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param  $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param  string $name
     * @param  array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $isGetter = substr($name, 0, 3) === 'get';

        if ($isGetter) {
            $camelPropertyName = lcfirst(substr($name, 3));

            return $this->get(Str::snake($camelPropertyName));
        }

        throw new Exception('Only getters are allowed');
    }

    /**
     * @param $payload
     *
     * @return void
     */
    private function process($payload): void
    {
        $config = static::mapping();

        foreach ($config as $line) {
            $target = Arr::get($line, 'target');

            $this->export[] = $target;

            if ($resolver = Arr::get($line, 'resolve')) {
                $source = Arr::get($payload, $line['source']);
                $this->set($target, $resolver($source));
                continue;
            }

            if (!$source = Arr::get($line, 'source')) {
                $value = Arr::get($line, 'stub', false);
                $this->set($target, $value);
                continue;
            }

            $default = Arr::get($line, 'default', null);
            $source = Arr::get($payload, $line['source']);
            $this->set($target, isset($source) ? $source : $default);
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    private function set(string $key, $value): void
    {
        $this->{$key} = $value;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function get(string $key)
    {
        return $this->{$key};
    }
}
