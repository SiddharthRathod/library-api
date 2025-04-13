<?php

namespace App\Http\Controllers\API;

use OpenApi\Annotations as OA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;


/**
 * @OA\Tag(name="Authentication", description="API Endpoints for user authentication")
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/user/show",
     *     tags={"Authentication"},
     *     summary="Get authenticated user details",
     *     security={"bearerAuth": {}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="borrowed_books", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/user/update",
     *     tags={"Authentication"},
     *     summary="Update authenticated user's profile",
     *     security={"bearerAuth": {}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="error", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/user/logout",
     *     tags={"Authentication"},
     *     summary="Logout authenticated user",
     *     security={"bearerAuth": {}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="error", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="User is already logged out or token is invalid")
     * )
     */
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
