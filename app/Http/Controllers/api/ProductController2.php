<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\productRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;
use Dotenv\Util\Str;
use Illuminate\Database\QueryException;
use App\Traits\ApiResponseTrait;
use GuzzleHttp\Handler\Proxy;

class ProductController2 extends Controller
{
    use ApiResponseTrait;

    public function index()
    {

        // $products = Product::paginate(5);
        $products = Product::withAggregate('category', 'name')->get();


        return $this->successResponse($products, 'all products retrieved successfully', 200);
    }







    public function show(string $id)
    {

        $product = Product::select('id', 'name', 'description', 'price', 'status', 'category_id')->withAggregate('category', 'name')->find($id);

        if (!$product) {

            return $this->errorResponse(null, 'sorry this product not found to show', 404);
        } else {


            return $this->successResponse($product, 'this product is ' . $product->name, 200);
        }
    }

    public function store(productRequest $request)
    {
        $productValidate = $request->validated();

        $category = Category::find($request->category_id);

        if (!$category) {

            return $this->errorResponse(null, 'category not found to select', 404);
        }
        try {

            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'category_id' => $request->category_id,
                // 'status' => $request->status ?? false
            ]);

            if ($product) {

                $productWithCategory = Product::withAggregate('category', 'name')->find($product->id);

                return $this->successResponse($productWithCategory, 'product stored successfully', 201);
            }
        } catch (QueryException $e) {
            // if duplicate entry error
            if ($e->errorInfo[1] == 1062) {

                return $this->errorResponse(null, "Cannot store {$request->name} this product already exist", 409);
            } else {
                return response()->json([
                    'message' => 'database error , please try again later0',
                    'error' => $e->getMessage(),
                    'success' => 500,
                    'status' => 500
                ], 500);
            }
        }
    }





    public function changeStatus(string $id)
    {
        $product = Product::find($id);

        $oldStatus = $product->status;

        if ($product->status == 'unactive') {
            $product->status = 'active';
        } else {
            $product->status = 'unactive';
        }

        $product->save();

        // $productAfterUpdated = Product::find($id);
        // $productAfterUpdated = Product::where('id' , $id)->get();
        $productAfterUpdated = Product::with('category')->find($id);

        $data =  [
            'product_id' => $productAfterUpdated->id,
            'product_name' => $productAfterUpdated->name,
            'product_description' => $productAfterUpdated->description,
            'product_price' => $productAfterUpdated->price,
            'product_status' => $productAfterUpdated->status,
            'category_id' => $productAfterUpdated->category->id,
            'category_name' => $productAfterUpdated->category->name
        ];

        return $this->successResponse($data, 'status is changed successfully from ' . $oldStatus . ' to ' . $product->status, 200);
    }


    public function update(Request $request, string $id)
    {

        $product = Product::find($id);

        $oldProduct = Product::where('id', $id)->withAggregate('category', 'name')->get(['name', 'description', 'price', 'category_id']);
        if (!$product) {

            return $this->errorResponse(null, 'this product not found to update', 404);
        }

        $request->validate([
            'name' => 'string|min:3',
            'description' => 'nullable|min:5|string',
            'price' => 'numeric',
            'category_id' => 'exists:categories,id'
        ]);



        $product->update([
            'name' => $request->name ?? $product->name,
            'description' => $request->description ?? $product->description,
            'price' => $request->price ?? $product->price,
            'category_id' => $request->category_id ?? $product->category_id
        ]);
        $product = Product::find($id);

        $productUpdated = Product::withAggregate('category', 'name')->find($request->id);

        $response = [
            'message' => 'product updated successfully',
            'success' => true,
            'data' => [
                'oldData' => $oldProduct,
                'newData' => $productUpdated
            ],
            'status' => 200
        ];

        return response()->json($response, 200);
    }

    public function destroy(string $id)
    {
        $product = Product::find($id);
        // dd($product);
        if (!$product) {

            return $this->errorResponse(null, 'this product not found to delete', 404);
        } else {

            $product->delete();

            return $this->successResponse(null, 'this product deleted successfully', 204);
        }
    }

    //for users

    public function listActiveProducts()
    {
        $products = Product::where('status', 'active')->with(['category' => function ($query) {
            $query->select('id', 'name');
        }])
            ->select('id', 'name', 'description', 'price', 'category_id')->get();

        if (count($products) == 0) {

            return $this->errorResponse(null, 'no active products found', 404);
        }
        return $this->successResponse($products, 'active products retrieved successfully', 200);
    }

    public function searchProductByName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3'
        ]);
        $name = $request->input('name');

        $products = Product::where('name', 'LIKE', "%$name%")->get();

        if ($products->isEmpty()){
            return $this->errorResponse(null, 'no products found matching the search criteria', 404);
        }

        $activeProducts = $products->where('status', 'active')->values();
        $unactiveProducts = $products->where('status', 'unactive');


        if($activeProducts->isEmpty()) {
            return $this->errorResponse(null, 'no active products found matching the search criteria', 404);
        }

        if ($activeProducts->count() != 0) {
            return response()->json([
                'message' => 'result of search about products',
                'data' => $activeProducts,
                'meta' =>$unactiveProducts->isNotEmpty() ? [
                    'message' => "if exist unactive products matching your search",
                    'unactive_products' => $unactiveProducts->pluck('name'), ] : null

                ],200);
        }



    }

}
