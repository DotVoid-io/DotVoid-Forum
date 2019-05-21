<?php
namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\BasicThread;
use App\Models\Category;
use App\Repositories\BasicThreadRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BasicThreadController extends Controller
{

    /**
     * @var \App\Repositories\BasicThreadRepository
     */
    private $basicThreadRepository;

    /**
     * Create a new controller instance.
     *
     * @param \App\Repositories\CategoryRepository $categoryRepository
     * @return void
     */
    public function __construct(BasicThreadRepository $categoryRepository)
    {
        $this->basicThreadRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Http\Requests\SearchRequest  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function index(SearchRequest $request, Category $category)
    {
        return $this->basicThreadRepository->get($category->id, $request->input('search'), ['id', 'created_at', 'updated_at', 'title', 'locked_at', 'pinned_at', 'is_question', 'author_id']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) // TODO BasicThreadCreate request
    {
        $thread = $this->basicThreadRepository->store($request->all());
        return response()->json($thread, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\BasicThread  $thread
     * @return \Illuminate\Http\Response
     */
    public function show(BasicThread $thread)
    {
        return $thread->load('thread');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\BasicThread  $thread
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BasicThread $thread) // TODO BasicThreadUpdate request with authorization
    {
        $this->basicThreadRepository->update($thread, filterEmpty($request)); // TODO update thread itself too
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\BasicThread  $thread
     * @return \Illuminate\Http\Response
     */
    public function destroy(BasicThread $thread)
    {
        $thread->delete();
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
