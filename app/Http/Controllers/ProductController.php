<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource; // Added
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource with optional filters.
     *
     * @param  IndexProductRequest  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $cacheKey = 'products_filtered_' . md5(serialize($request->all()));

        return Cache::remember($cacheKey, 60*5, function () use ($request) {
            $query = Product::query();

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('min_price') && $request->has('max_price')) {
                $query->filterByPrice($request->min_price, $request->max_price);
            } else {
                if ($request->has('min_price')) {
                    $query->where('price', '>=', $request->min_price);
                }

                if ($request->has('max_price')) {
                    $query->where('price', '<=', $request->max_price);
                }
            }

            if ($request->has('search')) {
                $query->searchByName($request->search);
            }

            return ProductResource::collection($query->get());
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \App\Http\Resources\ProductResource
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreProductRequest  $request
     * @return \App\Http\Resources\ProductResource
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = Product::create($request->all());
        Cache::flush(); // Clear cache on product creation
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateProductRequest  $request
     * @param  \App\Models\Product  $product
     * @return \App\Http\Resources\ProductResource
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->all());
        Cache::flush(); // Clear cache on product update
        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        Cache::flush(); // Clear cache on product deletion
        return response()->json(null, 204);
    }
}
