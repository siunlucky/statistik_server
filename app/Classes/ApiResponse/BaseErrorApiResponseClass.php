<?php

namespace App\Classes\ApiResponse;

use Illuminate\Http\JsonResponse;

class BaseErrorApiResponseClass
{
    /**
     * Create a new class instance.
     */
    protected $code;
    protected $status;
    protected $message;
    protected $validations;

    public function __construct($code, $status, $message, $validations = null)
    {
        $this->code = $code;
        $this->status = $status;
        $this->message = $message;
        $this->validations = $validations;
    }

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'code' => $this->code,
            'status' => $this->status,
            'recordsTotal' => 0,
            'data' => null,
            'error' => [
                'name' => $this->status,
                'message' => $this->message,
                'validation' => $this->validations
            ]
        ], $this->code);
    }
}












