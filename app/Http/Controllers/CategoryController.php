<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\Category\CategoryCreateRequest;
use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    
    /**
     * @var \App\Repositories\CategoryRepository
     */
    private $categoryRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Repositories\CategoryRepository  $categoryRepository
     * @return void
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Http\Requests\SearchRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function index(SearchRequest $request)
    {
        return $this->categoryRepository->get(null, $request->input('search'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Category\CategoryCreateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryCreateRequest $request)
    {
        $category = $this->categoryRepository->store($request->all());
        return response()->json($category, Response::HTTP_CREATED);
    }

    /**
     * Display the specified category with its subcategories.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        return $category->load('subs');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Category\CategoryUpdateRequest  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $this->categoryRepository->update($category, filterEmpty($request));
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
