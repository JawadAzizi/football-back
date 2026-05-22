<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Http\Requests\SalonRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\ApplyFilter;
use App\Traits\ApplySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalonController extends Controller
{
    use ApiResponseTrait, ApplyFilter, ApplySearch;

    public function index(Request $request): JsonResponse
    {
        $query = Salon::query();

        // Apply Filters
        $query = $this->applyFilter($query, [
            // 'salons.name' => $request->input('name'),
        ], '=');

        // Apply Search
        $query = $this->applySearch($query, [
            // 'salons.name' => $request->input('search'),
        ], 'LIKE');

        $items = $query->paginate(15)->toArray();
        return $this->apiResponse(['data' => $items, 'action' => 'list']);
    }

    public function store(SalonRequest $request): JsonResponse
    {
        $salon = Salon::create($request->validated());
        return $this->apiResponse(['data' => $salon, 'action' => 'create']);
    }

    public function show(Salon $salon): JsonResponse
    {
        return $this->apiResponse(['data' => $salon, 'action' => 'show']);
    }

    public function update(SalonRequest $request, Salon $salon): JsonResponse
    {
        $salon->update($request->validated());
        return $this->apiResponse(['data' => $salon, 'action' => 'update']);
    }

    public function destroy(Salon $salon): JsonResponse
    {
        $salon->delete();
        return $this->apiResponse(['action' => 'destroy']);
    }
}