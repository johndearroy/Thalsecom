<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for user authentication"
 * )
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"admin","vendor","customer"}, example="customer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $role = Role::query()->where('name', $request['role'])->firstOrFail();

        $user = User::query()->create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'role_id' => $role->id,
        ]);

        $token = auth()->login($user);

        $data = [
            'user' => $user,
            'authorization' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ];

        return $this->jsonResponse(
            'User registered successfully',
            201,
            $data
        );
    }

    /**
     * Login user and return JWT token
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return $this->jsonErrorResponse('Invalid credentials', 401);
        }

        $user = auth()->user();
        $user->update(['last_login_at' => now()]);

        return $this->respondWithToken($token);
    }

    /**
     * Get authenticated user
     */
    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Authentication"},
     *     summary="Get authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User data retrieved successfully")
     * )
     */
    public function me(): JsonResponse
    {
        return $this->jsonResponse('User data retrieved successfully', 200, [auth()->user()->load('role')]);
    }

    /**
     * Refresh JWT token
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh access token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token refreshed successfully")
     * )
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = auth()->refresh();

            if (!$token) {
                return response()->json([
                    'message' => 'Token refresh failed. Please login again.'
                ], 401);
            }

            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed: ' . $e->getMessage()
            ], 401);
        }
    }

    /**
     * Logout user
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Successfully logged out")
     * )
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return $this->jsonResponse('Successfully logged out');
    }

    /**
     * Format token response
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()->load('role')
        ]);
    }
}
