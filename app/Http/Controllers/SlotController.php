<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Http\Requests\SlotRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\ApplyFilter;
use App\Traits\ApplySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    use ApiResponseTrait, ApplyFilter, ApplySearch;

    public function index(Request $request): JsonResponse
    {
        $query = Slot::query();

        // Apply Filters
        $query = $this->applyFilter($query, [
            // 'slots.date' => $request->input('date'),
        ], '=');

        // Apply Search
        $query = $this->applySearch($query, [
            // 'slots.date' => $request->input('search'),
        ], 'LIKE');

        $items = $query->paginate(15)->toArray();
        return $this->apiResponse(['data' => $items, 'action' => 'list']);
    }

    public function store(SlotRequest $request): JsonResponse
    {
        $slot = Slot::create($request->validated());
        return $this->apiResponse(['data' => $slot, 'action' => 'create']);
    }

    public function show(Slot $slot): JsonResponse
    {
        return $this->apiResponse(['data' => $slot, 'action' => 'show']);
    }

    public function update(SlotRequest $request, Slot $slot): JsonResponse
    {
        $slot->update($request->validated());
        return $this->apiResponse(['data' => $slot, 'action' => 'update']);
    }

    public function destroy(Slot $slot): JsonResponse
    {
        $slot->delete();
        return $this->apiResponse(['action' => 'destroy']);
    }
}