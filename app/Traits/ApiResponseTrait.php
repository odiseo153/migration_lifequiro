<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     */
    protected function success(
        string $message = 'Success',
        mixed $data = null,
        int $code = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     */
    protected function error(
        string $message = 'Error',
        mixed $errors = null,
        int $code = Response::HTTP_BAD_REQUEST,
        ?array $debug = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $code,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if (config('app.debug') && $debug !== null) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error JSON response.
     */
    protected function validationError(
        ValidationException $exception,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error(
            message: $message,
            errors: $exception->errors(),
            code: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}