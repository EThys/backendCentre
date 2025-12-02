<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FinancingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $projectType = $request->get('project_type');
        $sector = $request->get('sector');
        $search = $request->get('search');

        $query = FinancingRequest::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($projectType) {
            $query->where('project_type', $projectType);
        }

        if ($sector) {
            $query->where('sector', 'like', "%{$sector}%");
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_first_name', 'like', "%{$search}%")
                  ->orWhere('contact_last_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('project_title', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'page' => $requests->currentPage(),
                'limit' => $requests->perPage(),
                'total' => $requests->total(),
                'totalPages' => $requests->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validation flexible : accepter soit les données complètes, soit les données du formulaire de contact
        $rules = [
            // Champs du formulaire de contact (simples)
            'name' => 'required_without:company_name|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required_without:project_title|string|max:255',
            'message' => 'required_without:project_description|string',
            
            // Champs complets (si envoyés)
            'company_name' => 'required_without:name|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'contact_first_name' => 'nullable|string|max:255',
            'contact_last_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_email' => 'required_without:email|email|max:255',
            'project_title' => 'required_without:subject|string|max:255',
            'project_description' => 'required_without:message|string',
            'requested_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'project_type' => 'nullable|in:startup,expansion,equipment,working-capital,other',
            'sector' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Séparer le nom en prénom et nom si nécessaire
        $name = $request->input('name', '');
        $nameParts = explode(' ', trim($name));
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
        
        // Récupérer les valeurs (priorité aux champs spécifiques, sinon utiliser le nom séparé)
        $contactFirstName = $request->input('contact_first_name', $firstName);
        $contactLastName = $request->input('contact_last_name', $lastName);
        
        // Si les champs sont vides, utiliser des valeurs par défaut
        if (empty($contactFirstName) && empty($contactLastName)) {
            $contactFirstName = $firstName ?: 'Non spécifié';
            $contactLastName = $lastName ?: 'Non spécifié';
        }
        
        // Créer la demande de financement
        $financingRequest = FinancingRequest::create([
            'company_name' => $request->input('company_name', $name ?: 'Non spécifié'),
            'legal_form' => $request->input('legal_form'),
            'registration_number' => $request->input('registration_number'),
            'tax_id' => $request->input('tax_id'),
            'address' => $request->input('address', 'Non spécifié'),
            'city' => $request->input('city', 'Non spécifié'),
            'country' => $request->input('country', 'RDC'),
            'phone' => $request->input('phone', ''),
            'email' => $request->input('email'),
            'website' => $request->input('website'),
            'contact_first_name' => $contactFirstName,
            'contact_last_name' => $contactLastName,
            'contact_position' => $request->input('contact_position'),
            'contact_phone' => $request->input('contact_phone', $request->input('phone', '')),
            'contact_email' => $request->input('contact_email', $request->input('email')),
            'project_title' => $request->input('project_title', $request->input('subject', 'Demande de financement')),
            'project_description' => $request->input('project_description', $request->input('message', '')),
            'project_type' => $request->input('project_type', $this->mapFinancingType($request->input('subject'))),
            'sector' => $request->input('sector'),
            'requested_amount' => $request->input('requested_amount', 0),
            'currency' => $request->input('currency', 'USD'),
            'project_duration' => $request->input('project_duration'),
            'expected_start_date' => $request->input('expected_start_date'),
            'status' => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de financement soumise avec succès',
            'data' => $financingRequest,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $financingRequest = FinancingRequest::find($id);

        if (!$financingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de financement non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $financingRequest,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $financingRequest = FinancingRequest::find($id);

        if (!$financingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de financement non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'company_name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:draft,submitted,under-review,approved,rejected,on-hold',
            'review_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $financingRequest->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Demande de financement mise à jour avec succès',
            'data' => $financingRequest,
        ]);
    }

    /**
     * Update the status of a financing request.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $financingRequest = FinancingRequest::find($id);

        if (!$financingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de financement non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,submitted,under-review,approved,rejected,on-hold',
            'review_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $financingRequest->update([
            'status' => $request->input('status'),
            'review_notes' => $request->input('review_notes'),
            'reviewed_by' => $request->user()?->id ?? 'admin',
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => $financingRequest,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $financingRequest = FinancingRequest::find($id);

        if (!$financingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de financement non trouvée',
            ], 404);
        }

        $financingRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demande de financement supprimée avec succès',
        ]);
    }

    /**
     * Map financing type from form subject to project_type enum
     */
    private function mapFinancingType(?string $subject): string
    {
        if (!$subject) {
            return 'other';
        }

        $subject = strtolower($subject);
        
        if (str_contains($subject, 'prêt') || str_contains($subject, 'pret')) {
            return 'working-capital';
        } elseif (str_contains($subject, 'subvention')) {
            return 'startup';
        } elseif (str_contains($subject, 'capital') || str_contains($subject, 'investissement')) {
            return 'expansion';
        } elseif (str_contains($subject, 'bail') || str_contains($subject, 'leasing')) {
            return 'equipment';
        }
        
        return 'other';
    }
}
