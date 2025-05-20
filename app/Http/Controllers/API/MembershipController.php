<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use App\Models\Location;
use App\Models\Sport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MembershipController extends Controller
{
    /**
     * Tampilkan daftar membership
     *
     * Mengambil daftar membership berdasarkan filter lokasi dan olahraga.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $membership = Membership::with(['location', 'sport']);

        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 9);

        $search = $request->input('search');
        $locationNames = $request->input('locations') ? explode('.', $request->input('locations')) : [];
        $sportNames = $request->input('sports') ? explode('.', $request->input('sports')) : [];

        if (!empty($locationNames)) {
            $membership->whereHas('location', function ($q) use ($locationNames) {
                $q->whereIn('locationName', $locationNames);
            });
        }

        if (!empty($sportNames)) {
            $membership->whereHas('sport', function ($q) use ($sportNames) {
                $q->whereIn('sportName', $sportNames);
            });
        }

        if (!empty($search)) {
            $membership->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('price', 'like', "%$search%");
            });
        }

        $totalMembership = $membership->count();
        $offset = ($page - 1) * $limit;
        $memberships = $membership->skip($offset)->take($limit)->get();

        $formattedMemberships = $memberships->map(function ($membership) {
            return [
                'id' => $membership->membershipId,
                'name' => $membership->name,
                'price' => $membership->price,
                'location' => $membership->location?->locationName ?? 'Tidak diketahui',
                'sport' => $membership->sport?->sportName ?? 'Tidak diketahui',
                'description' => $membership->description,
                'created_at' => $membership->created_at,
                'updated_at' => $membership->updated_at,
            ];
        });

        return response()->json([
            'total' => $totalMembership,
            'page' => $page,
            'limit' => $limit,
            'memberships' => $formattedMemberships,
        ]);
    }

    /**
     * Simpan membership baru
     *
     * Menyimpan data membership baru ke dalam database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'membershipName' => 'required|string|max:255',
            'membershipPrice' => 'required|numeric',
            'locationId' => 'required|exists:locations,locationId',
            'sportId' => 'required|exists:sports,sportId',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new membership
        $membership = Membership::create([
            'membershipName' => $request->input('membershipName'),
            'membershipPrice' => $request->input('membershipPrice'),
            'locationId' => $request->input('locationId'),
            'sportId' => $request->input('sportId'),
        ]);

        return response()->json($membership, 201);
    }
    /**
     * Update membership
     *
     * Mengupdate data membership berdasarkan ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'membershipName' => 'sometimes|required|string|max:255',
            'membershipPrice' => 'sometimes|required|numeric',
            'locationId' => 'sometimes|required|exists:locations,locationId',
            'sportId' => 'sometimes|required|exists:sports,sportId',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the membership by ID
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json(['message' => 'Membership tidak ditemukan'], 404);
        }

        // Update the membership
        $membership->update($request->all());

        return response()->json($membership);
    }
    /**
     * Hapus membership
     *
     * Menghapus membership berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the membership by ID
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json(['message' => 'Membership tidak detiemukan'], 404);
        }

        // Delete the membership
        $membership->delete();

        return response()->json(['message' => 'Membership berhasil dihapus']);
    }
    /**
     * Tampilkan membership berdasarkan ID
     *
     * Mengambil data membership berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMembershipById($id)
    {
        // Find the membership by ID
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json(['message' => 'Membership tidak ditemukan.'], 404);
        }

        // Format the response
        $formattedMembership = [
            'id' => $membership->membershipId,
            'name' => $membership->membershipName,
            'price' => $membership->membershipPrice,
            'location' => $membership->location->locationName,
            'sport' => $membership->sport->sportName,
            'created_at' => $membership->created_at,
            'updated_at' => $membership->updated_at,
        ];

        return response()->json($formattedMembership);
    }
    /**
     * Tampilkan membership berdasarkan lokasi dan olahraga
     *
     * Mengambil data membership berdasarkan lokasi dan olahraga.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMembershipByLocationAndSport(Request $request)
    {
        $locationId = $request->input('locationId');
        $sportId = $request->input('sportId');

        // Validate the request
        if (empty($locationId) || empty($sportId)) {
            return response()->json(['message' => 'Location ID and Sport ID are required'], 422);
        }

        // Find the membership by location and sport
        $membership = Membership::where('locationId', $locationId)
            ->where('sportId', $sportId)
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'Membership tidak ditemukan.'], 404);
        }

        return response()->json($membership);
    }
    /**
     * Tampilkan detail membership
     *
     * Mengambil detail membership berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Find the membership by ID
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json(['message' => 'Membership tidak ditemukan.'], 404);
        }

        return response()->json($membership);
    }
}
