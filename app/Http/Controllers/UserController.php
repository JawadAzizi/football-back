<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $items = User::paginate(15)->toArray();
        return $this->apiResponse(['data' => $items, 'action' => 'list']);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        return $this->apiResponse(['data' => $user, 'action' => 'create']);
    }

    public function show(User $user): JsonResponse
    {
        return $this->apiResponse(['data' => $user, 'action' => 'show']);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());
        return $this->apiResponse(['data' => $user, 'action' => 'update']);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();
        return $this->apiResponse(['action' => 'destroy']);
    }
}
