<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Location;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MembershipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 10);
        $search = $request->input('search');
        $sports = $request->input('sports');
        $locations = $request->input('locations');

        $query = Membership::with(['locations', 'sports']);

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (!empty($sports)) {
            $query->whereHas('sports', function ($q) use ($sports) {
                $q->where('sportName', $sports);
            });
        }

        if (!empty($locations)) {
            $query->whereHas('locations', function ($q) use ($locations) {
                $q->where('locationName', $locations);
            });
        }

        $memberships = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'total' => $memberships->total(),
            'message' => 'Data Paket Langganan berhasil diambil',
            'data' => $memberships->map(function($membership) {
                return [
                    'membershipId' => $membership->membershipId,
                    'name' => $membership->name,
                    'description' => $membership->description,
                    'discount' => $membership->price,
                    'weeks' => $membership->weeks,
                    'locationName' => $membership->locations->locationName ?? null,
                    'sportName' => $membership->sports->sportName ?? null,
                ];
            })
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'locationId' => 'required|exists:locations,locationId',
            'sportId' => 'required|exists:sports,sportId',
            'name' => 'required|string|max:25',
            'description' => 'required|string',
            'price' => 'required|integer|min:0',
            'weeks' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $membership = Membership::create($request->all());

        $membership = Membership::with(['locations', 'sports'])->find($membership->membershipId);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create membership'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'time' => now()->toISOString(),
            'message' => 'Paket Langganan berhasil dibuat',
            'data' => [
                'membershipId' => $membership->membershipId,
                'name' => $membership->name,
                'description' => $membership->description,
                'price' => $membership->price,
                'weeks' => $membership->weeks,
                'locations' => [
                    'locationId' => $membership->locations->locationId,
                    'locationName' => $membership->locations->locationName
                ],
                'sports' => [
                    'sportId' => $membership->sports->sportId,
                    'sportName' => $membership->sports->sportName
                ],
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $membership = Membership::with(['locations', 'sports'])->find($id);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => "Paket Langganan dengan ID $id tidak ditemukan"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => "Data Paket Langganan dengan ID $id ditemukan",
            'data' => [
                'membershipId' => $membership->membershipId,
                'name' => $membership->name,
                'description' => $membership->description,
                'discount' => $membership->price,
                'weeks' => $membership->weeks,
                'locations' => [
                    'locationId' => $membership->locations->locationId,
                    'locationName' => $membership->locations->locationName
                ],
                'sports' => [
                    'sportId' => $membership->sports->sportId,
                    'sportName' => $membership->sports->sportName
                ],
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'time' => now()->toISOString(),
                'message' => "Paket Langganan dengan ID $id tidak ditemukan"
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'locationId' => 'exists:locations,locationId',
            'sportId' => 'exists:sports,sportId',
            'name' => 'string|max:25',
            'description' => 'string',
            'price' => 'integer|min:0',
            'weeks' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $membership->update($request->all());

        $membership = Membership::with(['locations', 'sports'])->find($membership->membershipId);

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Paket Langganan berhasil diperbarui',
            'data' => [
                'membershipId' => $membership->membershipId,
                'name' => $membership->name,
                'description' => $membership->description,
                'price' => $membership->price,
                'weeks' => $membership->weeks,
                'locations' => [
                    'locationId' => $membership->locations->locationId,
                    'locationName' => $membership->locations->locationName
                ],
                'sports' => [
                    'sportId' => $membership->sports->sportId,
                    'sportName' => $membership->sports->sportName
                ],
            ]
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => "Paket Langganan dengan ID $id tidak ditemukan"
            ], 404);
        }

        $membership->delete();

        return response()->json([
            'success' => true,
            'time' => now()->toISOString(),
            'message' => 'Paket Langganan berhasil dihapus'
        ], 200);
    }
}
