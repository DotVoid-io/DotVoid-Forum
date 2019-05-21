<?php

namespace App\Repositories;

use App\Models\BasicThread;
use Illuminate\Database\Eloquent\Model;

class BasicThreadRepository extends ResourceRepository
{

    /**
     * Create a new repository instance.
     *
     * @param  \App\Models\BasicThread  $model
     * @return void
     */
    public function __construct(BasicThread $model)
    {
        $this->model = $model;
    }

    /**
     * Get an ordered list of all basic threads with thread relation.
     * @param int $categoryId
     * @param string $search
     * @param array $columns
     * @param int $n
     */
    public function get(int $categoryId, string $search = null, array $columns = ['*'], int $n = ResourceRepository::AMOUNT_PER_PAGE) {

        $query = $this->model->with('thread');

        if($search != null) {
            $query->where('title', 'LIKE', '%'.$this->escapeLike($search).'%');
        }

        return $query->select($columns)->orderBy('updated_at', 'desc')->paginate($n);
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
        $model->is_question = isset($inputs['is_question']);
        $model->category_id = $inputs['category_id'];
        $model->author_id = $inputs['author_id'];

        $model->save();
        return $model->id;
    }

}
