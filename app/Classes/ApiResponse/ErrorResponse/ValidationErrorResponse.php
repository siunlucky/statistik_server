<?php

namespace App\Classes\ApiResponse\ErrorResponse;

use App\Classes\ApiResponse\BaseErrorApiResponseClass;

class ValidationErrorResponse extends BaseErrorApiResponseClass
{
    public function __construct($validations)
    {
        parent::__construct(422, 'VALIDATION_ERROR', 'Validation failed', $validations);
    }
}
