<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryRepository extends ResourceRepository
{

    /**
     * Create a new repository instance.
     *
     * @param  \App\Models\Category  $model
     * @return void
     */
    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    /**
     * Get a list of all categories or sub-categories.
     * @param int $parentId the id of the parent category, leave null for main
     * categories.
     * @param string $search search criteria in the "name" column, nullable
     * @return array
     */
    public function get(int $parentId = null, string $search = null)
    {
        $query = $parentId != null ?
            $this->model->where('parent_id', $parentId):
            $this->model->whereNull('parent_id');

        if($search != null) {
            $query->where('name', 'LIKE', '%'.$this->escapeLike($search).'%');
        }

        return $query->get();
    }

    /**
     * Resource relative behavior for saving a record.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $inputs
     * @return int id, the id of the saved resource
     */
    protected function save(Model $model, Array $inputs)
    {
        if(isset($inputs['parent_id'])) $model->parent_id = $inputs['parent_id'];
        $model->name = $inputs['name'];
        $model->description = $inputs['description'];
        $model->fa_icon = $inputs['fa_icon'];

        $model->save();
        return $model->id;
    }

}
