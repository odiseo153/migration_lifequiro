<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


trait RequestHelper
{
    /**
     * Helper function to handle common request operations
     * 
     * @param mixed $request The request to process
     * @return mixed
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Errores de validación',
            'errors' => $validator->errors(),
        ], 422));
    }
}
