<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\ApplyFilter;
use App\Traits\ApplySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponseTrait, ApplyFilter, ApplySearch;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Apply Filters
        $query = $this->applyFilter($query, [
            'users.name' => $request->input('name'),
        ], '=');

        // Apply Search
        $query = $this->applySearch($query, [
            'users.name' => $request->input('search'),
            'users.email' => $request->input('search'),
        ], 'LIKE');

        $items = $query->paginate(15)->toArray();
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