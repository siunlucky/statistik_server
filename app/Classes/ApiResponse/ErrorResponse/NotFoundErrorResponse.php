<?php

namespace App\Classes\ApiResponse\ErrorResponse;

use App\Classes\ApiResponse\BaseErrorApiResponseClass;

class NotFoundErrorResponse extends BaseErrorApiResponseClass
{
    public function __construct($message = 'Resource not found')
    {
        parent::__construct(404, 'NOT_FOUND', $message);
    }
}
