<?php
namespace Tests\Unit;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Faker\Generator as Faker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResourceRepositoryTest extends TestCase
{

    use RefreshDatabase, WithFaker;

    /**
     *
     * @var \Tests\Unit\GenericRepository
     */
    private $repository;

    /**
     *
     * {@inheritdoc}
     * @see \Illuminate\Foundation\Testing\TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        app(Factory::class)->define(GenericModel::class, function (Faker $faker) {
            return [
                'name' => $faker->word,
                'number' => $faker->numberBetween(1, 100)
            ];
        });

        Schema::create('generic_models', function (Blueprint $table) {
            $table->temporary();
            $table->increments('id');
            $table->string('name');
            $table->integer('number');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->repository = new GenericRepository(new GenericModel());
    }

    public function testGetModel()
    {
        $model = $this->repository->getModel();
        $this->assertNotNull($model);
        $this->assertInstanceOf(\Tests\Unit\GenericModel::class, $model);
    }

    public function testGetAll()
    {
        factory(GenericModel::class, 10)->create();
        $records = $this->repository->getAll();
        $this->assertCount(10, $records);

        foreach ($records as $r) {
            $this->assertNotEmpty($r->name);
            $this->assertNotEmpty($r->number);
        }

        $records = $this->repository->getAll('number');
        $this->assertCount(10, $records);

        foreach ($records as $r) {
            $this->assertEmpty($r->name);
            $this->assertNotEmpty($r->number);
        }
    }

    public function testGetAllTrashed()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 1)->create([
            'deleted_at' => $this->faker->dateTime
        ]);
        // Only trashed
        $records = $this->repository->getAllTrashed();
        $this->assertCount(1, $records);

        // With trashed
        $records = $this->repository->getAllTrashed(false);
        $this->assertCount(11, $records);
    }

    public function testGetAllOrdered()
    {
        factory(GenericModel::class, 10)->create();
        $records = $this->repository->getAllOrdered('name', 'asc', 'name');

        $previous = '';
        foreach ($records as $r) {
            $this->assertLessThanOrEqual(0, strcmp($previous, $r->name));
            $previous = $r->name;
        }

        $records = $this->repository->getAllOrdered('name', 'desc', 'name');

        $previous = 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz';
        foreach ($records as $r) {
            $this->assertGreaterThanOrEqual(0, strcmp($previous, $r->name));
            $previous = $r->name;
        }
    }

    public function testGetAllOrderedTrashed()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 5)->create([
            'deleted_at' => $this->faker->dateTime
        ]);

        $records = $this->repository->getAllOrderedTrashed('name', 'asc', 'name');
        $this->assertCount(5, $records);

        $previous = '';
        foreach ($records as $r) {
            $this->assertLessThanOrEqual(0, strcmp($previous, $r->name));
            $previous = $r->name;
        }

        $records = $this->repository->getAllOrderedTrashed('name', 'asc', 'name', false);
        $this->assertCount(15, $records);

        $previous = '';
        foreach ($records as $r) {
            $this->assertLessThanOrEqual(0, strcmp($previous, $r->name));
            $previous = $r->name;
        }
    }

    public function testGetWhere()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 2)->create([
            'name' => 'testvalue'
        ]);
        factory(GenericModel::class, 3)->create([
            'number' => -1 // factory generates [1-100] so -1 is unique
        ]);
        // Where LIKE
        $records = $this->repository->getWhere('name', 'LIKE', 'testval');
        $this->assertCount(2, $records);
        foreach ($records as $r) {
            $this->assertEquals('testvalue', $r->name);
        }

        // Classic WHERE
        $records = $this->repository->getWhere('number', '=', - 1);
        $this->assertCount(3, $records);
        foreach ($records as $r) {
            $this->assertEquals(-1, $r->number);
        }
    }

    public function testGetWhereTrashed()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 2)->create([
            'name' => 'testvalue'
        ]);
        factory(GenericModel::class, 2)->create([
            'name' => 'testvalue',
            'deleted_at' => $this->faker->dateTime
        ]);
        factory(GenericModel::class, 3)->create([
            'number' => -1 // factory generates [1-100] so -1 is unique
        ]);
        factory(GenericModel::class, 3)->create([
            'number' => -1,
            'deleted_at' => $this->faker->dateTime
        ]);
        // Where LIKE
        $records = $this->repository->getWhereTrashed('name', 'LIKE', 'testval');
        $this->assertCount(2, $records);
        foreach ($records as $r) {
            $this->assertEquals('testvalue', $r->name);
        }

        $records = $this->repository->getWhereTrashed('name', 'LIKE', 'testval', '*', 100, false);
        $this->assertCount(4, $records);
        foreach ($records as $r) {
            $this->assertEquals('testvalue', $r->name);
        }

        // Classic WHERE
        $records = $this->repository->getWhereTrashed('number', '=', - 1);
        $this->assertCount(3, $records);
        foreach ($records as $r) {
            $this->assertEquals(-1, $r->number);
        }

        $records = $this->repository->getWhereTrashed('number', '=', - 1, '*', 100, false);
        $this->assertCount(6, $records);
        foreach ($records as $r) {
            $this->assertEquals(-1, $r->number);
        }
    }

    public function testGetById()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 1)->create([
            'name' => 'testvalue'
        ]);

        $record = $this->repository->getById(11, ['name']);
        $this->assertNotNull($record);
        $this->assertEquals('testvalue', $record->name);
        $this->assertEmpty($record->number);
    }

    public function testGetByIdDoesntExist()
    {
        factory(GenericModel::class, 10)->create();

        $this->expectException(ModelNotFoundException::class);
        $this->repository->getById(11, ['name']);
    }

    public function testGetByIdTrashedDoesntExist()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 1)->create([
            'name' => 'testvalue'
        ]);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->getByIdTrashed(11, ['name']);
    }

    public function testGetByIdTrashedWith()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 1)->create([
            'name' => 'testvalue'
        ]);
        $record = $this->repository->getByIdTrashed(11, ['name'], false);
        $this->assertEquals('testvalue', $record->name);
    }

    public function testGetByIdTrashedOnly()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 1)->create([
            'name' => 'testvalue',
            'deleted_at' => $this->faker->dateTime
        ]);
        $record = $this->repository->getByIdTrashed(11, ['name']);
        $this->assertEquals('testvalue', $record->name);
    }

    public function testExists()
    {
        factory(GenericModel::class, 10)->create();
        $this->assertFalse($this->repository->exists(11));
        $this->assertTrue($this->repository->exists(10));
    }

    public function testExistsTrashed()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 1)->create([
            'deleted_at' => $this->faker->dateTime
        ]);
        $this->assertFalse($this->repository->existsTrashed(12));
        $this->assertFalse($this->repository->existsTrashed(10));
        $this->assertTrue($this->repository->existsTrashed(11));

        $this->assertFalse($this->repository->existsTrashed(12, false));
        $this->assertTrue($this->repository->existsTrashed(10, false));
        $this->assertTrue($this->repository->existsTrashed(11, false));
    }

    public function testDestroyById()
    {
        factory(GenericModel::class, 10)->create();
        
        $this->repository->destroyById(10);
        $this->assertFalse($this->repository->exists(10));
        $this->assertTrue($this->repository->existsTrashed(10));

        $this->repository->destroyById(9, true);
        $this->assertFalse($this->repository->existsTrashed(9));

        $this->expectException(ModelNotFoundException::class);
        $this->repository->destroyById(9, true);
    }

    public function testRestore()
    {
        factory(GenericModel::class, 10)->create();
        
        $this->repository->destroyById(10);
        $this->assertFalse($this->repository->exists(10));
        $this->repository->restore(10);
        $this->assertTrue($this->repository->exists(10));
        
        $this->expectException(ModelNotFoundException::class);
        $this->repository->restore(11);
    }

    public function testUpdateById()
    {
        factory(GenericModel::class, 1)->create();

        $recordBefore = $this->repository->getById(1);
        $this->repository->updateById(1, [
            'name' => 'testvalue'
        ]);

        $recordAfter = $this->repository->getById(1);

        $this->assertEquals('testvalue', $recordAfter->name);
        $this->assertEquals($recordBefore->number, $recordAfter->number);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->updateById(2, []);
    }

    public function testStore()
    {
        $resource = $this->repository->store([
            'name' => 'testvalue',
            'number' => 0
        ]);

        $this->assertEquals('testvalue', $resource->name);
        $this->assertEquals(0, $resource->number);

        $record = $this->repository->getById(1);
        $this->assertEquals('testvalue', $record->name);
        $this->assertEquals(0, $record->number);
    }

    public function testPaginate()
    {
        factory(GenericModel::class, 14)->create();

        $paginate = $this->repository->getPaginate(5);

        $this->assertEquals(3, $paginate->lastPage());
        $this->assertEquals(14, $paginate->total());
        $this->assertCount(5, $paginate->items());
    }

    public function testPaginateWhere()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 14)->create([
            'name' => 'testvalue'
        ]);
        factory(GenericModel::class, 6)->create([
            'number' => -1 // factory generates [1-100] so -1 is unique
        ]);

        $paginate = $this->repository->getPaginateWhere('name', 'LIKE', 'testval', 5);
        $this->assertEquals(3, $paginate->lastPage());
        $this->assertEquals(14, $paginate->total());
        $this->assertCount(5, $paginate->items());

        foreach ($paginate->items() as $r) {
            $this->assertEquals('testvalue', $r->name);
        }

        $paginate = $this->repository->getPaginateWhere('number', '=', -1, 5);
        $this->assertEquals(2, $paginate->lastPage());
        $this->assertEquals(6, $paginate->total());
        $this->assertCount(5, $paginate->items());

        foreach ($paginate->items() as $r) {
            $this->assertEquals(-1, $r->number);
        }
    }

    public function testPaginateTrashed()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 10)->create([
            'deleted_at' => $this->faker->dateTime
        ]);

        $paginate = $this->repository->getPaginateTrashed(5);
        $this->assertEquals(2, $paginate->lastPage());
        $this->assertEquals(10, $paginate->total());
        $this->assertCount(5, $paginate->items());

        $paginate = $this->repository->getPaginateTrashed(5, '*', false);
        $this->assertEquals(4, $paginate->lastPage());
        $this->assertEquals(20, $paginate->total());
        $this->assertCount(5, $paginate->items());
    }

    public function testPaginateOrdered()
    {
        factory(GenericModel::class, 14)->create();

        $paginate = $this->repository->getPaginateOrdered('name', 'asc', 5);
        $this->assertEquals(3, $paginate->lastPage());
        $this->assertEquals(14, $paginate->total());
        $this->assertCount(5, $paginate->items());

        $previous = '';
        foreach ($paginate->items() as $r) {
            $this->assertLessThanOrEqual(0, strcmp($previous, $r->name));
            $previous = $r->name;
        }
    }

    public function testPaginateeWhereTrashed()
    {
        factory(GenericModel::class, 10)->create();
        factory(GenericModel::class, 10)->create([
            'deleted_at' => $this->faker->dateTime
        ]);
        factory(GenericModel::class, 6)->create([
            'name' => 'testvalue',
            'deleted_at' => $this->faker->dateTime
        ]);
        factory(GenericModel::class, 5)->create([
            'name' => 'testvalue',
        ]);
        factory(GenericModel::class, 6)->create([
            'number' => -1,
            'deleted_at' => $this->faker->dateTime
        ]);
        factory(GenericModel::class, 5)->create([
            'number' => -1,
        ]);

        $paginate = $this->repository->getPaginateWhereTrashed('name', 'LIKE', 'testval', 5);
        $this->assertEquals(2, $paginate->lastPage());
        $this->assertEquals(6, $paginate->total());
        $this->assertCount(5, $paginate->items());
        foreach ($paginate->items() as $r) {
            $this->assertEquals('testvalue', $r->name);
        }

        $paginate = $this->repository->getPaginateWhereTrashed('name', 'LIKE', 'testval', 5, '*', false);
        $this->assertEquals(3, $paginate->lastPage());
        $this->assertEquals(11, $paginate->total());
        $this->assertCount(5, $paginate->items());
        foreach ($paginate->items() as $r) {
            $this->assertEquals('testvalue', $r->name);
        }

        $paginate = $this->repository->getPaginateWhereTrashed('number', '=', -1, 5);
        $this->assertEquals(2, $paginate->lastPage());
        $this->assertEquals(6, $paginate->total());
        $this->assertCount(5, $paginate->items());
        foreach ($paginate->items() as $r) {
            $this->assertEquals(-1, $r->number);
        }

        $paginate = $this->repository->getPaginateWhereTrashed('number', '=', -1, 5, '*', false);
        $this->assertEquals(3, $paginate->lastPage());
        $this->assertEquals(11, $paginate->total());
        $this->assertCount(5, $paginate->items());
        foreach ($paginate->items() as $r) {
            $this->assertEquals(-1, $r->number);
        }
    }

    public function testPaginateOrderedTrashed()
    {
        factory(GenericModel::class, 6)->create();
        factory(GenericModel::class, 6)->create([
            'deleted_at' => $this->faker->dateTime
        ]);

        $paginate = $this->repository->getPaginateOrderedTrashed('name', 'asc', 5);
        $this->assertEquals(2, $paginate->lastPage());
        $this->assertEquals(6, $paginate->total());
        $this->assertCount(5, $paginate->items());

        $previous = '';
        foreach ($paginate->items() as $r) {
            $this->assertLessThanOrEqual(0, strcmp($previous, $r->name));
            $previous = $r->name;
        }

        $paginate = $this->repository->getPaginateOrderedTrashed('name', 'asc', 5, '*', false);
        $this->assertEquals(3, $paginate->lastPage());
        $this->assertEquals(12, $paginate->total());
        $this->assertCount(5, $paginate->items());

        $previous = '';
        foreach ($paginate->items() as $r) {
            $this->assertLessThanOrEqual(0, strcmp($previous, $r->name));
            $previous = $r->name;
        }
    }
}
