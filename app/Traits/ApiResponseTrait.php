<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponseTrait
{
    /**
     * Smart API Response
     * @param array $props ['data' => mixed, 'action' => string, 'message' => string, 'code' => int]
     */
    public function apiResponse(array $props): JsonResponse
    {
        $action  = $props['action'] ?? 'list';
        $data    = $props['data'] ?? null;
        $message = $props['message'] ?? null;
        $code    = $props['code'] ?? null;

        $defaults = [
            'create'  => ['code' => Response::HTTP_CREATED, 'msg' => 'Resource created successfully'],
            'update'  => ['code' => Response::HTTP_OK, 'msg' => 'Resource updated successfully'],
            'destroy' => ['code' => Response::HTTP_OK, 'msg' => 'Resource deleted successfully'],
            'show'    => ['code' => Response::HTTP_OK, 'msg' => 'Resource retrieved successfully'],
            'list'    => ['code' => Response::HTTP_OK, 'msg' => 'Resources listed successfully'],
        ];

        $settings = $defaults[$action] ?? ['code' => Response::HTTP_OK, 'msg' => 'Success'];
        $finalCode = $code ?? $settings['code'];

        return response()->json([
            'success' => $finalCode < 400,
            'action'  => $action,
            'message' => $message ?? $settings['msg'],
            'data'    => isset($data['data']) ? $data['data'] : $data,
            'meta'    => isset($data['current_page']) ? array_diff_key($data, ['data' => []]) : null
        ], $finalCode);
    }
}