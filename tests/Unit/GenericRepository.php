<?php
namespace Tests\Unit;

use App\Repositories\ResourceRepository;
use Illuminate\Database\Eloquent\Model;

class GenericRepository extends ResourceRepository
{

    /**
     * Create a new repository instance.
     *
     * @param \Tests\Unit\GenericModel $model
     * @return void
     */
    public function __construct(GenericModel $model)
    {
        $this->model = $model;
    }

    /**
     * Resource relative behavior for saving a record.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $inputs
     * @return int the id of the saved resource
     */
    protected function save(Model $model, Array $inputs)
    {
        $model->name   = $inputs['name'];
        $model->number = $inputs['number'];

        $model->save();
        return $model->id;
    }
}