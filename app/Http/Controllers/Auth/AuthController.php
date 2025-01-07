<?php

namespace App\Http\Controllers\Auth;

use App\Classes\ApiResponse\ErrorResponse\UnauthorizedErrorResponse;
use App\Classes\ApiResponse\SuccessResponse\CreatedResponse;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */

    public function register(RegisterRequest $request)
    {

        $credentials = $request->only(['name', 'email', 'password']);

        $credentials['password'] = bcrypt($credentials['password']);

        $user = User::create($credentials);

        return (new CreatedResponse($user, 1))->toResponse();
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return (new UnauthorizedErrorResponse('Unauthorized'))->toResponse();
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return (new OKResponse(auth()->user(), 1))->toResponse();
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout(true);

        return (new OKResponse(['message' => 'Successfully logged out'], 1))->toResponse();
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh(true, true));
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $data = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 10000 // 60 minutes
        ];

        return (new OKResponse($data, 1))->toResponse();
    }
}
