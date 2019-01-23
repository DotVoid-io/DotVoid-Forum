<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * The **Repository Pattern** is an addition to the MVC pattern.
 * It fits right between the Controller and the Model so the controller never interacts directly with the Model.
 *
 * The aim is to:
 * - Lighten controllers by moving the query building and logic into the repositories.
 * - Improve readability and maintainability.
 * - Reduce code redundancy as the super-class `ResourceRepository` contains most frequent queries.
 *
 */
abstract class ResourceRepository
{

    protected $model;

    /**
     * Get the model this repository is using
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get all the existing non-deleted recordings.
     *
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @return array
     */
    public function getAll($columns = ['*'])
    {
        return $this->model->all($columns);
    }

    /**
     * Get all the deleted recordings.
     *
     * @param  bool  $only, select only the deleted ones if true, select all existing records if set to false. Defaults to true.
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @return array
     */
    public function getAllTrashed(bool $only = true, $columns = ['*'])
    {
        $query = $this->getAll($columns);
        return $only ?
            $query->onlyTrashed()->get():
            $query->withTrashed()->get();
    }

    /**
     * Get all the existing recordings ordered according to the given column.
     *
     * @param  string  $orderColumn
     * @param  string  $order (ex.: 'asc', 'desc')
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @return array
     */
    public function getAllOrdered(string $orderColumn, string $order, $columns = ['*'])
    {
        return $this->model->select($columns)->orderBy($orderColumn, $order)->get();
    }

    /**
     * Get all the deleted recordings ordered according to the given column.
     *
     * @param  string  $orderColumn
     * @param  string  $order (ex.: 'asc', 'desc')
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @param  bool  $only, select only the deleted ones if true, select all existing records if set to false. Default to true.
     * @return array
     */
    public function getAllOrderedTrashed(string $orderColumn, string $order, $columns = ['*'], bool $only = true)
    {
        $query = $this->model->select($columns)->orderBy($orderColumn, $order);
        return $only ?
            $query->onlyTrashed()->get():
            $query->withTrashed()->get();
    }

    /**
     * Get the recordings matching the given WHERE clause
     *
     * @param  string  $column
     * @param  string $operator
     * @param  mixed  $value
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @param  int  $limit, defaults to 100
     * @return array
     */
    public function getWhere(string $column, string $operator, $value, $columns = ['*'], int $limit = 100)
    {
        $search = $operator == 'LIKE' ? '%'.$this->escape_like($value).'%' : $value;
        return $this->model->select($columns)->where($column, $operator, $search)->take($limit)->get();
    }

    /**
     * Get the deleted recordings matching the given WHERE clause
     *
     * @param  string  $column
     * @param  string $operator
     * @param  mixed  $value
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @param  int  $limit, defaults to 100
     * @param  bool  $only, select only the deleted ones if true, select all existing records if set to false. Default to true.
     * @return array
     */
    public function getWhereTrashed(string $column, string $operator, $value, $columns = ['*'], int $limit = 100, bool $only = true)
    {
        $search = $operator == 'LIKE' ? '%'.$this->escape_like($value).'%' : $value;
        $query = $this->model->select($columns)->where($column, $operator, $search)->take($limit)->get();
        $query = $only ?
            $query->onlyTrashed():
            $query->withTrashed();

        return $query->get();
    }

    /**
     * Get a single recording by its id.
     *
     * @param  int  $id
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getById(int $id, $columns = ['*'])
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * Get a single deleted recording by its id.
     *
     * @param  int  $id
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @param  bool  $only, select only the deleted ones if true, select all existing records if set to false. Default to true.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getByIdTrashed(int $id, $columns = ['*'], bool $only = true)
    {
        return $only ?
            $this->model->onlyTrashed()->findOrFail($id, $columns):
            $this->model->withTrashed()->findOrFail($id, $columns);
    }

    /**
     * Get a paginate of the recordings.
     *
     * @param  int  $n the amount of recordings per page
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @return array
     */
    public function getPaginate(int $n, $columns = ['*'])
    {
        return $this->model->select($columns)->paginate($n);
    }

    /**
     * Get a paginate of the deleted recordings.
     *
     * @param  int  $n the amount of recordings per page
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @param  bool  $only, select only the deleted ones if true, select all existing records if set to false. Default to true.
     * @return array
     */
    public function getPaginateTrashed(int $n, $columns = ['*'], bool $only = true)
    {
        $paginate = $this->model->select($columns);
        $paginate = $only ?
            $paginate->onlyTrashed():
            $paginate->withTrashed();
        return $paginate->paginate($n);
    }

    /**
     * Get a paginate of the recordings ordered according to the given column.
     *
     * @param  int  $n the amount of recordings per page
     * @param  string  $orderColumn
     * @param  string  $order (ex.: 'asc', 'desc')
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @return array
     */
    public function getPaginateOrdered(int $n, string $orderColumn, string $order, $columns = ['*'])
    {
        return $this->model->select($columns)->orderBy($orderColumn,$order)->paginate($n);
    }

    /**
     * Get a paginate of the deleted recordings ordered according to the given column.
     *
     * @param  int  $n the amount of recordings per page
     * @param  string  $orderColumn
     * @param  string  $order (ex.: 'asc', 'desc')
     * @param  array|mixed  $columns the columns to select with optional alias, defaults to '*'
     * @param  bool  $only, select only the deleted ones if true, select all existing records if set to false. Default to true.
     * @return array
     */
    public function getPaginateOrderedTrashed(int $n, string $orderColumn, string $order, $columns = ['*'], bool $only = true)
    {
        $paginate = $this->model->select($columns)->orderBy($orderColumn,$order);
        $paginate = $only ?
            $paginate->onlyTrashed():
            $paginate->withTrashed();
        return $paginate->paginate($n);
    }

    /**
     * Get if a record exists with the given id
     *
     * @param  int  $id
     * @return bool
     */
    public function exists(int $id)
    {
        return $this->model->where($this->model->getKeyName(), $id)->exists();
    }

    /**
     * Get if a deleted record exists with the given id
     *
     * @param  int  $id
     * @param  bool  $only, select only the deleted ones if true, select all existing records if set to false. Default to true.
     * @return bool
     */
    public function existsTrashed(int $id, bool $only = true)
    {
        $query = $this->model->where($this->model->getKeyName(), $id);
        $query = $only ?
            $query->onlyTrashed():
            $query->withTrashed();

        return $query->exists();
    }

    /**
     * Resource relative behavior for saving a record.
     *
     * @param  Model  $model
     * @param  array  $inputs
     * @return int  the id of the saved resource
     */
    protected abstract function save(Model $model, Array $inputs);

    /**
     * Create and save a new record from the inputs.
     *
     * @param array $inputs
     * @return \Illuminate\Database\Eloquent\Model resource, the generated record
     */
    public function store(Array $inputs)
    {
        $resource = new $this->model;

        $this->save($resource, $inputs);

        return $resource;
    }

    /**
     * Update a record by its id
     *
     * @param  int  $id, the id of the record to update
     * @param  array  $inputs
     * @return void
     */
    public function updateById(int $id, Array $inputs)
    {
        $this->update($this->getById($id), $inputs);
    }

    /**
     * Update a record
     *
     * @param  \Illuminate\Database\Eloquent\Model  $record
     * @param  array  $inputs
     * @return void
     */
    public function update(Model $record, Array $inputs)
    {
        $record->update($inputs);
    }

    /**
     * Delete a record by its id
     *
     * @param  int  $id, the id of the record to delete
     * @return void
     */
    public function destroyById(int $id, bool $force = false)
    {
        $record = $this->getById($id, [$this->model->getKeyName()]);
        $this->destroy($record, $force);
    }
    /**
     * Delete a record
     *
     * @param  \Illuminate\Database\Eloquent\Model  $record
     * @return void
     */
    public function destroy(Model $record, bool $force = false)
    {
        $force ? $record->forceDelete() : $record->delete();
    }

    /**
     * Restore a deleted record
     *
     * @param  int  $id, the id of the record to restore
     * @return void
     */
    public function restore($id)
    {
        $this->getByIdTrashed($id)->restore();
    }

    /**
     * Escape special characters for a LIKE query.
     *
     * @param string $value
     * @param string $char
     *
     * @return string
     */
    private function escape_like(string $value, string $char = '\\')
    {
        return str_replace(
            [$char, '%', '_'],
            [$char.$char, $char.'%', $char.'_'],
            $value
        );
    }

}
