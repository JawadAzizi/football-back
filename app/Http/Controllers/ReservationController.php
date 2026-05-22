<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Http\Requests\ReservationRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\ApplyFilter;
use App\Traits\ApplySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    use ApiResponseTrait, ApplyFilter, ApplySearch;

    public function index(Request $request): JsonResponse
    {
        $query = Reservation::query();

        // Apply Filters
        $query = $this->applyFilter($query, [
            // 'reservations.slot_id' => $request->input('slot_id'),
        ], '=');

        // Apply Search
        $query = $this->applySearch($query, [
            // 'reservations.column' => $request->input('search'),
        ], 'LIKE');

        $items = $query->paginate(15)->toArray();
        return $this->apiResponse(['data' => $items, 'action' => 'list']);
    }

    public function store(ReservationRequest $request): JsonResponse
    {
        $reservation = Reservation::create($request->validated());
        return $this->apiResponse(['data' => $reservation, 'action' => 'create']);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return $this->apiResponse(['data' => $reservation, 'action' => 'show']);
    }

    public function update(ReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $reservation->update($request->validated());
        return $this->apiResponse(['data' => $reservation, 'action' => 'update']);
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->delete();
        return $this->apiResponse(['action' => 'destroy']);
    }
}