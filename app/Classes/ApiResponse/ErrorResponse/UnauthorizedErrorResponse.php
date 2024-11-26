<?php

namespace App\Classes\ApiResponse\ErrorResponse;

use App\Classes\ApiResponse\BaseErrorApiResponseClass;

class UnauthorizedErrorResponse extends BaseErrorApiResponseClass
{
    public function __construct($message = 'Unauthorized')
    {
        parent::__construct(401, 'UNAUTHORIZED', $message);
    }
}
