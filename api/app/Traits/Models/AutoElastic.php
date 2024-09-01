<?php

namespace App\Traits\Models;

use App\Jobs\ElasticUpdateAssignment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use DateTime;
use Elasticquent\ElasticquentResultCollection;
use Elasticquent\ElasticquentTrait;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Environment;

trait AutoElastic
{
    use ElasticquentTrait {
        createIndex as baseCreateIndex;
    }

    public function getIndexDocumentData()
    {
        $resource = 'App\Http\Resources\Elastic\\' . class_basename(static::class) . 'Elastic';
        if (!class_exists($resource)) {
            throw new \InvalidArgumentException("class $resource does not exist");
        }

        return (new $resource($this))->resolve();
    }

    public function getIndexName()
    {
        $model = static::class;
        if (in_array(OnTenant::class, class_uses_recursive($model))) {
            $connection = Environment::getTenantConnectionName();
            $id =  DB::connection($connection)->getDatabaseName();
        } else {
            $id = DB::connection('mysql')->getDatabaseName();
        }

        $pos = strrpos($this->getTable(), '.');
        $table = $pos === false ? $this->getTable() : substr($this->getTable(), $pos + 1);
        return $id . '_' . $table;
      //return $this->getTable();
    }

    public function getTypeName()
    {
        return '_doc';
    }

    public static function resultConvertEpochToDateTime(array &$array)
    {
        $instance = new static;
        $dates = $instance->dates;

        for ($i = 0; $i < count($array); $i++) {
            foreach ($dates as $dateKeyString) {
                $dateKeys = explode('.', $dateKeyString);                                           // Get nested keys array

                $res = &$array[$i];                                                                         // Get [object] item from array

                for ($j = 0; $j < count($dateKeys) - 1; $j++) {
                    if (array_key_exists($dateKeys[$j], $res)) {
                        $res = &$res[$dateKeys[$j]];                                                        // Get Second last nested item
                    }
                }

                $last = count($dateKeys) - 1;

                if (array_key_exists($dateKeys[$last], $res)) {                                              // If second last nested item is type object and has last key, convert last nested item
                    self::convertEpochToDateTime($res[$dateKeys[$last]], $instance);
                } else {                                                                                    // If second last nested item is type array, iterate over children
                    for ($j = 0; $j < count($res); $j++) {
                        if (array_key_exists($j, $res) && array_key_exists($dateKeys[$last], $res[$j])) {    // If children have last key, convert corresponding value
                                self::convertEpochToDateTime($res[$j][$dateKeys[$last]], $instance);
                        }
                    }
                }
            }
        }
    }

    private static function convertEpochToDateTime(&$obj, $model)
    {
        try {
            $obj = ($obj === null) ? null : $model->asDateTime($obj);
        } catch (\Exception $exception) {
        }
    }

    public function scopeFromSearch(Builder $query, ElasticquentResultCollection $results)
    {
        return $query->whereIn('id', $results->pluck('id'))->orderBySearch($results);
    }

    /*public function clean($item){
        $copy = $item->replicate();
        foreach($copy->getFillable() as $fillable){
            $copy->$fillable = trim(preg_replace('/\s+/', ' ', strip_tags($copy->$fillable)));
        }
        return $copy;
    }

    public function cleanAddress($addresses){
        $addresses = $addresses->map(function($item, $key){
            if(!empty($item)) {
                $item = $item->toPropertyArray();
                $location = $item['location'];
                $item = ['location' => $location];
                return $item;
            }
            return null;
        });

        return $addresses;
    }*/

    public function scopeOrderBySearch(Builder $query, ElasticquentResultCollection $results)
    {
        return $query->orderByRaw('FIELD(id, \'' . implode('\', \'', $results->pluck('id')->toArray()) . '\')');
    }

    /**
     * Index Documents
     *
     * Index all documents in an Eloquent model.
     *
     * @return array
     */
    public static function addAllToIndexWithoutScopes()
    {
        $instance = new static;

        $all = $instance->newQueryWithoutScopes()->get();

        return $all->each(function ($item) {
            $item->addToIndex();
        });
    }

    public static function bootAutoElastic()
    {
        $entityModels = [Quote::class, PurchaseOrder::class, Order::class, Project::class, Invoice::class];

        static::created(function ($model) {
            if (property_exists($model, 'mappingProperties') && !$model->mappingExists()) {
                $model->putMapping();
            }
            $model->addToIndex();
        });

        static::saved(function ($model) use ($entityModels) {
            if (in_array(get_class($model), $entityModels) && !config('settings.seeding')) {
                $companyId = getTenantWithConnection();
                ElasticUpdateAssignment::dispatch($companyId, get_class($model), $model->id)->onQueue('low');
            } else {
                try {
                    $model->updateIndex();
                } catch (Missing404Exception $e) {
                    Log::error($e->getMessage());
                }
            }
        });

        static::deleting(
            function ($model) {
                if ((in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model)) && $model->isForceDeleting()) || !in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
                    try {
                        $model->removeFromIndex();
                    } catch (Missing404Exception $e) {
                        Log::error($e->getMessage());
                    }
                }
            }
        );

        static::deleted(function ($model) {
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                try {
                    $model->updateIndex();
                } catch (Missing404Exception $e) {
                    Log::error($e->getMessage());
                }
            }
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                try {
                    $model->updateIndex();
                } catch (Missing404Exception $e) {
                    Log::error($e->getMessage());
                }
            });
        }
    }

    public static function countQuery($query = null)
    {
        $instance = new static;
        $params = $instance->getBasicEsParams(false);

        if (!empty($query)) {
            $params['body']['query'] = $query;
        }

        $result = $instance->getElasticSearchClient()->count($params);

        return $result['count'] ?? 0;
    }

    public static function createIndex($shards = 1, $replicas = 0)
    {
        return static::baseCreateIndex($shards, $replicas);
    }

    /**
     * Search By Query
     *
     * Search with a query array
     *
     * @param array $query
     * @param array $aggregations
     * @param array $postFilter
     * @param array $sourceFields
     * @param int $limit
     * @param int $offset
     * @param array $sort
     * @param array $collapse
     * @param int $size
     * @return ElasticquentResultCollection
     */
    public static function searchByQuery($query = null, $aggregations = null, $postFilter = null, $sourceFields = null, $limit = null, $offset = null, $sort = null, $collapse = null, $size = null)
    {
        $instance = new static;

        $params = $instance->getBasicEsParams(true, $limit, $offset);

        if (!empty($sourceFields)) {
            $params['body']['_source']['include'] = $sourceFields;
        }

        if (!empty($query)) {
            $params['body']['query'] = $query;
        }

        if (!empty($aggregations)) {
            $params['body']['aggs'] = $aggregations;
        }

        if (!empty($postFilter)) {
            $params['body']['post_filter'] = $postFilter;
        }

        if (!empty($collapse)) {
            $params['body']['collapse'] = $collapse;
        }

        if (!empty($sort)) {
            $params['body']['sort'] = $sort;
        }

        if (!empty($size)) {
            $params['body']['size'] = $size;
        }

        $result = $instance->getElasticSearchClient()->search($params);

        return static::hydrateElasticsearchResult($result);
    }

    public static function searchBySingleQuery($query = null, $limit = null, $offset = null)
    {
        $instance = new static;

        $params = $instance->getBasicEsParams(true, $limit, $offset);

        if (!empty($query)) {
            $params['body'] = $query;
        }

        $result = $instance->getElasticSearchClient()->search($params);

        return $result;
    }

    /**
     * Search By Aggregations
     *
     * Search with a query array
     *
     * @param array $aggregations
     * @return array
     */
    public static function searchByAggregations($aggregations)
    {
        $instance = new static;

        $params = $instance->getBasicEsParams(true, 0, 0);

        $params['body']['aggs'] = $aggregations;

        $result = $instance->getElasticSearchClient()->search($params);

        return $result['aggregations'];
    }

    /**
     * Count Terms
     *
     * Count the given Terms
     *
     * @param array $terms
     * @return array
     */
    public static function countTerms(...$terms)
    {
        $query = [];

        foreach ($terms as $term) {
            $field = is_array($term) ? $term[0] : $term;
            $name = is_array($term) ? Str::plural($term[0]) . '#' . class_basename($term[1]) : Str::plural($term);

            $subQuery = [
            $name => [
                'terms' => [
                    'field' => $field
                ]
            ]
            ];

            $query = array_merge($query, $subQuery);
        }

        $result = self::searchByAggregations($query);
        $result = self::remapAggregation($result);
        $result = self::cleanCountTermsOutput($result);

        return $result;
    }

    /**
     * RemapAggregation
     *
     * Returns raw elasticsearch suggest result
     *
     * @param array $aggArray
     * @return array
     */
    public static function remapAggregation($aggArray)
    {
        return array_map(function ($n) {
            return $n['buckets'];
        }, $aggArray);
    }

    /**
     * CleanCountTermsOutput
     *
     * Return a clean result from the RemapAggregation result
     *
     * @param $cto
     * @return array
     */
    public static function cleanCountTermsOutput($cto)
    {
        $className = class_basename(static::class);

        $result = [];
        $counter = 0;

        foreach ($cto as $key => $ct) {
            $subCounter = 0;

            $enumName = Str::contains($key, '#') ? explode('#', $key)[1] : $className . Str::studly(Str::singular($key));
            $getKey = 'App\Enums\\' . $enumName . '::getKey';

            $subArray = [];
            foreach ($ct as $item) {
                $subCounter += $item['doc_count'];

                $subArray = array_merge($subArray, [
                [
                'name' => $getKey((int)$item['key']),
                'count' => $item['doc_count'],
                ]
                ]);
            }

            $result = array_merge($result, [
            [
                'name' => Str::camel(explode('#', $key)[0]),
                'sub_types' => $subArray,
                'count' => $subCounter,
            ]
            ]);


            if (array_key_first($cto) === $key) {
                  $counter += $subCounter;
            }
        }

        return [
          'sub_types' => $result,
          'count' => $counter
        ];
    }

    /**
     * Suggest
     *
     * Returns raw elasticsearch suggest result
     *
     * @param null $prefix
     * @param null $field
     * @param null $fuzziness
     * @param null $sourceFields
     * @param int $size
     * @param bool $skip_duplicates
     * @param null $filter
     * @return mixed
     */
    public static function suggest($prefix = null, $field = null, $fuzziness = null, $sourceFields = null, $size = 5, $skip_duplicates = false, $filter = null)
    {
        $instance = new static;

        $params = $instance->getBasicEsParams();

        if (!empty($sourceFields)) {
            $params['body']['_source']['include'] = $sourceFields;
        }

        if (!($prefix === null) && !($field === null)) {
            $params['body']['suggest'] = [
            Str::singular($instance->getIndexName()) . '-suggest' => [
              'prefix' => $prefix,
              'completion' => [
                  'field' => $field,
                  'size' => $size,
                  'skip_duplicates' => $skip_duplicates,
              ],
            ],
            ];
        }
        if (!($fuzziness === null)) {
            $params['body']['suggest'][Str::singular($instance->getIndexName()) . '-suggest']['completion']['fuzzy']['fuzziness'] = $fuzziness;
        }

        if (!empty($filter)) {
            $params['body']['suggest'][Str::singular($instance->getIndexName()) . '-suggest']['completion']['contexts'][$filter['field']] = [$filter['value']];
        }
        $result = $instance->getElasticSearchClient()->search($params);

        return $result;
    }

    /**
     * Updates many documents at once
     * @param null $query
     * @return array
     */
    public static function updateBySingleQuery($query = null)
    {
        $instance = new static;

        $params = $instance->getBasicEsParams(true);

        if (!empty($query)) {
            $params['body'] = $query;
        }

        $result = $instance->getElasticSearchClient()->updateByQuery($params);

        return $result;
    }

    /**
     * Search all tenants
     */
    public static function searchAllTenantsQuery($class, $query = null, $limit = null)
    {
        $instance = new static;
        if ($class == 'orders') {
            $params = array(
            'index' =>  ['*_' . $class, '-*purchase_orders'],
            'type'  => '_doc'
            );
        } else {
            $params = array(
            'index' => '*_' . $class,
            'type'  => '_doc'
            );
        }

        if (!empty($query)) {
            $params['body'] = $query;
        }
        if (is_numeric($limit)) {
            $params['size'] = $limit;
        }

        $result = $instance->getElasticSearchClient()->search($params);

        return $result;
    }

    /**
     * Delete many documents at once
     * @param null $query
     * @return array
     */
    public static function deleteBySingleQuery($query = null)
    {
        $instance = new static;

        $params = $instance->getBasicEsParams(true);

        if (!empty($query)) {
            $params['body'] = $query;
        }

        $instance->getElasticSearchClient()->deleteByQuery($params);
    }
}
