<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
    private function apiResponse($data, string $message = 'Success', int $code = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => $code < 400,
            'message' => $message,
            'data'    => isset($data['data']) ? $data['data'] : $data,
            'meta'    => isset($data['current_page']) ? array_diff_key($data, ['data' => []]) : null
        ], $code);
    }

    public function index(): JsonResponse
    {
        $items = User::paginate(15)->toArray();
        return $this->apiResponse($items, 'Items retrieved successfully');
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        return $this->apiResponse($user, 'Created successfully', Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        return $this->apiResponse($user, 'Details retrieved');
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());
        return $this->apiResponse($user, 'Updated successfully');
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();
        return $this->apiResponse(null, 'Deleted successfully', Response::HTTP_NO_CONTENT);
    }
}