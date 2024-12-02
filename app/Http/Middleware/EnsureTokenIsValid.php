<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Classes\ApiResponse\ErrorResponse\UnauthorizedErrorResponse;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // try {
        //     // Parse the token from the request and extract the payload
        //     $token = JWTAuth::parseToken();
        //     $payload = $token->getPayload();

        //     $user = $payload->get('user');

        //     $request->merge(['user' => $user]);

        // } catch (TokenExpiredException $e) {
        //     return (new UnauthorizedErrorResponse('Token expired'))->toResponse();
        // } catch (TokenInvalidException $e) {
        //     return (new UnauthorizedErrorResponse('Token invalid'))->toResponse();
        // } catch (JWTException $e) {
        //     return (new UnauthorizedErrorResponse('Token Needed'))->toResponse();
        // }

        return $next($request);
    }
}
