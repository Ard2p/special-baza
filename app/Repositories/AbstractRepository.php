<?php

namespace App\Repositories;

use App\Traits\Cacheables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

abstract class AbstractRepository
{
    /**
     * @var Model
     */
    protected $model;

    private $filterData = [];

    private $selectedColumns = ['*'];

    protected $collectionResource = null;

    protected $enableCache = true;

    /**
     * @var Builder
     */
    protected Builder $builder;

    public function __construct(Model $model = null)
    {
        $this->setModel($model);
    }

    function filter($data)
    {
        $this->filterData = $data;

        $this->builder->filter($this->filterData);

        return $this;
    }

    function findById($id)
    {
        $this->model = $this->builder->findOrFail($id);

        return $this->getModel();
    }

    function get($cacheRequest = false)
    {
        if (! $cacheRequest && $this->isUseCache()) {

            $this->enableCache = true;

            return $this->getCache();
        }
        $response = collect();

        $this->builder->chunk(1000, function ($items) use (&$response) {
            $response = $response->merge($this->resourceResponse($items, false));
        });
        $this->collectionResource = null;

        return $response;
    }

    private function resourceResponse($items, $clean = true)
    {
        if ($this->collectionResource) {
            $items = $items instanceof Model ? $this->collectionResource::make($items) : $this->collectionResource::collection($items);
        }

        if ($clean) {
            $this->collectionResource = null;
        }

        $this->refreshBuilder();

        return $items;
    }

    function paginate(int $perPage = 10)
    {

        $items = request()->filled('noPagination') ? $this->get($this->selectedColumns) : $this->builder->paginate($perPage, $this->selectedColumns);

        return $this->resourceResponse($items);
    }

    function query(callable $callable)
    {
        $callable($this->builder);

        return $this;
    }

    function noCache()
    {
        $this->enableCache = false;

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function create($attributes = [])
    {

        $this->model = new (get_class($this->model));

        $this->model->fill($this->prepareRequestData($attributes));

        $this->model->save();

        $this->refreshBuilder();

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    function update($attributes = [], $id = null)
    {
        if ($id) {
            $this->model = $this->findById($id);
        }

        $this->model->fill($this->prepareRequestData($attributes));

        $this->model->save();

        $this->refreshBuilder();

        return $this;
    }

    /**
     * @param false $forceDelete
     * @return $this
     */
    function delete($id = null, $forceDelete = false)
    {
        if ($id) {

            if ($this->builder::hasGlobalMacro('withTrashed')) {
                $this->builder->withTrashed();
            }

            $this->findById($id);
        }

        if ($this->model->deleted_at) {
            $forceDelete = true;
        }

        if ($forceDelete) {
            $this->model->forceDelete();
        } else {
            $this->model->delete();
        }

        return $this;
    }

    function bulkDelete($request): self
    {
        if ($request->ids && is_array($request->ids)) {
            $this->builder->whereIn('id', $request->ids)->delete();
        }
        if ($request->type === 'all') {
            $this->builder->delete();
        }

        return $this;
    }

    /**
     * Фильтрует массив оставляя только допустимые ключи для модели.
     *
     * @param Model $model
     * @param array $requestData
     * @return array
     */
    function pureRquestData(array $requestData)
    {
        $fillable = $this->model->getFillable();

        return array_filter($requestData, static function ($item) use ($fillable) {

            return in_array($item, $fillable);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param $data
     * @return array
     */
    protected function prepareRequestData($data)
    {
        return $this->pureRquestData($data);
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->resourceResponse($this->model);
    }

    /**
     * @param Model|string $model
     */
    public function setModel($model): self
    {
        $this->model = is_string($model) ? (new $model) : $model;

        $this->refreshBuilder();

        return $this;
    }

    private function refreshBuilder()
    {
        $this->builder = $this->model->newQuery();

        return $this;
    }

    function useResource($resourceClass)
    {
        $this->collectionResource = $resourceClass;

        return $this;
    }

    private function isUseCache(): bool
    {

        return $this->enableCache && in_array(Cacheables::class, class_uses_recursive(get_class($this->model)))

            && count(array_filter($this->filterData, function ($key) {
                    $lower = strtolower($key);

                    return in_array($lower, $this->model->skipCacheFields) && ! empty($this->filterData[$key]);
                }, ARRAY_FILTER_USE_KEY)) === 0;
    }

    private function getCache()
    {
        $sql = $this->builder->toSql();
        $bindings = serialize($this->builder->getBindings());

        $hash = sha1($sql.$bindings.$this->collectionResource);

        $cacheKey = $this->model->getCacheKey();

        return Cache::store('redis')->rememberForever("{$cacheKey}:{$hash}", function () {
            return $this->get(true);
        });
    }

    /**
     * @param string[] $selectedColumns
     */
    public function setSelectedColumns(array $selectedColumns): self
    {
        $this->selectedColumns = $selectedColumns;

        return $this;
    }
}
