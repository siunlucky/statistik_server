<?php

namespace App\Classes\ApiResponse\ErrorResponse;

use App\Classes\ApiResponse\BaseErrorApiResponseClass;

class InternalServerErrorResponse extends BaseErrorApiResponseClass
{
    public function __construct($message = 'An internal server error occurred')
    {
        parent::__construct(500, 'INTERNAL_SERVER_ERROR', $message);
    }
}
