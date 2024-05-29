<?php

namespace App\Traits;

trait Sortable
{
    /**
     * Если колонка пристуствует в fillable и sortable
     * @param $column
     * @return bool
     */
    public function checkSortable($column)
    {
        return in_array($column, array_merge($this->getModel()->getFillable(), $this->sortable));
    }

    function getSortableColumn($column)
    {
        if (!$column) {
            return null;
        }

        $this->setSortable([]);
        //Если в запросе пристуствует вложеная сортировка например created_by.id
        if (count($splitRelations = explode('.', $column)) > 1) {
            [$column] = $splitRelations;
        }

        $columnId = "{$column}_id";

        $column = $this->checkSortable($column)
            ? $column
            : null;
        $columnId = $this->checkSortable($columnId)
            ? $columnId
            : null;

        return $column
            ?: $columnId;
    }

    function setSortable(array $items)
    {
        return $this->sortable = array_merge($this->sortable ?? [], $items);
    }

    /**
     * Сортировка по колонкам
     * Сортировка по убыванию|возрастанию
     * @param $column
     * @return mixed
     */
    public function sortBy($column)
    {
        $column = $this->getSortableColumn($column);

        return $column
            ? $this->orderBy($column, $this->input('sortDir', 'DESC'))
            : $this->orderBy('created_at', 'DESC');
    }
}
