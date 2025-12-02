<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TrainingRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $program = $request->get('program');
        $search = $request->get('search');

        $query = TrainingRegistration::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($program) {
            $query->where('program', $program);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('program_name', 'like', "%{$search}%");
            });
        }

        $registrations = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $registrations->items(),
            'pagination' => [
                'page' => $registrations->currentPage(),
                'limit' => $registrations->perPage(),
                'total' => $registrations->total(),
                'totalPages' => $registrations->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'program' => 'required|string|max:255',
            'program_name' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = 'pending';
        $data['registration_date'] = now();

        // Mapper le nom du programme si nécessaire
        if (empty($data['program_name']) && !empty($data['program'])) {
            $programNames = [
                'training1' => 'Formation en Gestion Financière',
                'training2' => 'Formation en Marketing Digital',
                'training3' => 'Formation en Leadership',
                'training4' => 'Formation en Innovation',
            ];
            $data['program_name'] = $programNames[$data['program']] ?? $data['program'];
        }

        $registration = TrainingRegistration::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Inscription enregistrée avec succès',
            'data' => $registration,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $registration = TrainingRegistration::find($id);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $registration,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $registration = TrainingRegistration::find($id);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'program' => 'sometimes|required|string|max:255',
            'program_name' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Gérer les dates selon le statut
        if (isset($data['status'])) {
            if ($data['status'] === 'confirmed' && $registration->status !== 'confirmed') {
                $data['confirmed_at'] = now();
            }
            if ($data['status'] === 'cancelled' && $registration->status !== 'cancelled') {
                $data['cancelled_at'] = now();
            }
        }

        $registration->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Inscription mise à jour avec succès',
            'data' => $registration,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $registration = TrainingRegistration::find($id);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée',
            ], 404);
        }

        $registration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscription supprimée avec succès',
        ]);
    }
}
