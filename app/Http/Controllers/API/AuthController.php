<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;


class AuthController extends Controller
{
    public function register(Request $request)
    {
     
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
            'role' => 'in:admin,user'
        ]);

        if ($validator->fails()) {
            $error_messages = $validator->errors()->all();
            return response()->json(["status" => "failed", "error" => true, "message" => $error_messages],422);
        }        

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->role ?? 'user');

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            "status" => "success",
            "error" => false,
            'message' => 'User registered successfully.',
            'token' => $token,
            "data" => $user
        ],201);

    }

    public function login(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6',            
        ], [
            'email.exists' => 'We couldn’t find your email address.',
        ]);

        if ($validator->fails()) {
            $error_messages = $validator->errors()->all();
            return response()->json(["status" => "failed", "error" => true, "message" => $error_messages],422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            "status" => "success",
            "error" => false,
            'message' => 'Login successful.',
            'token' => $token,
            "data" => $user
        ]);
    }

    public function show(Request $request)
    {
        
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                "status" => "failed",
                "error" => true,
                "message" => ["User not found."]
            ], 404);
        }

        return response()->json([
            "status" => "success",
            "error" => false,
            "message" => "User details retrieved successfully.",
            "data" => [
                'user' => $user->load('roles'),
                'borrowed_books' => $user->borrowings()
                    ->with('book')
                    ->get()
                    ->map(function($borrowing) {
                    return [
                        'id' => $borrowing->id,
                        'book_id' => $borrowing->book_id,
                        'book_title' => $borrowing->book->title,
                        'borrowed_at' => $borrowing->borrowed_at,
                        'returned_at' => $borrowing->returned_at
                    ];
                })
            ]


        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);

        if ($validator->fails()) {
            $error_messages = $validator->errors()->all();
            return response()->json(["status" => "failed", "error" => true, "message" => $error_messages],422);
        }

        $data = $request->only('name', 'email', 'password');

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            "status" => "success",
            "error" => false,
            'message' => 'User updated successfully.',
            "data" => $user
        ],201);
                
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        if (!$user || !$user->currentAccessToken()) {
            return response()->json([
                "status" => "failed",
                "error" => true,
                "message" => ["User is already logged out or token is invalid."]
            ], 401);
        }
    

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "status" => "success",
            "error" => false,
            "message" => "Logged out successfully."
        ]);
    }


}
