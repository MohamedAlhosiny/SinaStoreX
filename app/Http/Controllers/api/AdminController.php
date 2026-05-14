<?php

namespace App\Http\Controllers\Api;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Requests\adminRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\loginRequestAdmin;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponseTrait;

class AdminController extends Controller
{
    use ApiResponseTrait;


    public function register(adminRequest $request) {
        $data_admin = $request->validated();

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
        if ($admin) {

            return $this->successResponse($admin->name , "Admin created successfully" , 201);
        }

    }

    public function login(loginRequestAdmin $request){

        $loginRequestAdmin = $request->validated();

        $admin = Admin::where('email' , $request->email)->first();

        if(!$admin || !Hash::check($request->password , $admin->password)) {

            return $this->errorResponse(null , "The provided credentials are incorrect" , 401);

        }else {

            $ability =[ 'role:'.$admin->role] ;

            $token = $admin->createToken('tokenAdmin' , $ability)->plainTextToken;

            $data_admin = [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'token' => $token
            ];

            return $this->successResponse($data_admin , "Admin login successfully" , 200);


        }


    }

    public function index() {
        $admins = Admin::all(['id' , 'name' , 'email' , 'role' , 'created_at']);

        return $this->successResponse($admins , "list of all admins" , 200);
    }



    public function dashboardStats(){
        $stats = [
            'all-users' => User::count(), // count() is a method that counts the number of records in the database table associated with the User model and returns that count as an integer.
            'all-admins' => Admin::count(),
            'all-products' => Product::count(),
            'all-orders' => Order::count(),
            'total-revenue' => Order::where('status' , 'completed')->sum('totalPrice'),
            'top-selling-product' => Product::withCount('orders')
                                        ->orderByDesc('orders_count')
                                        ->first(['id' , 'name' , 'orders_count'])

        ];


        return $this->successResponse($stats , "dashboard statistics" , 200);
    }



    public function logout(){
        $admin = Auth::user();

        $admin->currentAccessToken()->delete();

        return $this->successResponse(null , "Admin logout successfully" , 200);
    }




    //for superadmin only
    public function destroy($id) {
        $admin = Admin::find($id);

        if (!$admin) {

            return $this->errorResponse(null , "Admin not found" , 404 );
        }

        $admin->delete();

      return   $this->successResponse(null , "Admin deleted successfully" , 200);

    }


}
