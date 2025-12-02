<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PublicationRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $type = $request->get('type');
        $search = $request->get('search');

        $query = PublicationRequest::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('authors', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Ajouter les URLs complètes des fichiers
        $requestsData = $requests->items();
        foreach ($requestsData as &$req) {
            if ($req->document_file) {
                $req->document_file_url = Storage::disk('public')->url($req->document_file);
            }
            if ($req->document_image) {
                $req->document_image_url = Storage::disk('public')->url($req->document_image);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $requestsData,
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
        // Préparer les données pour la validation
        $validationData = $request->all();
        
        // Si domains est une chaîne JSON, la décoder
        if (isset($validationData['domains']) && is_string($validationData['domains'])) {
            $decodedDomains = json_decode($validationData['domains'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedDomains)) {
                $validationData['domains'] = $decodedDomains;
            }
        }
        
        $validator = Validator::make($validationData, [
            'name' => 'required|string|max:500',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'title' => 'required|string|max:1000',
            'abstract' => 'required|string|min:200',
            'type' => 'required|in:article,research-paper,book,report,other',
            'domains' => 'required|array|min:1',
            'domains.*' => 'string',
            'authors' => 'required|string|max:500',
            'co_authors' => 'nullable|string',
            'keywords' => 'nullable|string',
            'message' => 'nullable|string',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            'document_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Parser les domaines si c'est une chaîne JSON
        $domains = $data['domains'];
        if (is_string($domains)) {
            $domains = json_decode($domains, true);
        }
        
        // Gérer l'upload du fichier document (PDF ou Word)
        $documentFilePath = null;
        if ($request->hasFile('document_file')) {
            $documentFile = $request->file('document_file');
            $documentFilePath = $documentFile->store('publication_requests/documents', 'public');
        }
        
        // Gérer l'upload de l'image du document
        $documentImagePath = null;
        if ($request->hasFile('document_image')) {
            $documentImage = $request->file('document_image');
            $documentImagePath = $documentImage->store('publication_requests/images', 'public');
        }
        
        $publicationRequest = PublicationRequest::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'institution' => $data['institution'] ?? null,
            'position' => $data['position'] ?? null,
            'title' => $data['title'],
            'abstract' => $data['abstract'],
            'type' => $data['type'],
            'domains' => $domains,
            'authors' => $data['authors'],
            'co_authors' => $data['co_authors'] ?? null,
            'keywords' => $data['keywords'] ?? null,
            'message' => $data['message'] ?? null,
            'document_file' => $documentFilePath,
            'document_image' => $documentImagePath,
            'status' => 'pending',
            'submission_date' => now(),
        ]);

        // Ajouter les URLs complètes des fichiers
        if ($publicationRequest->document_file) {
            $publicationRequest->document_file_url = Storage::disk('public')->url($publicationRequest->document_file);
        }
        if ($publicationRequest->document_image) {
            $publicationRequest->document_image_url = Storage::disk('public')->url($publicationRequest->document_image);
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande de publication soumise avec succès',
            'data' => $publicationRequest,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $publicationRequest = PublicationRequest::find($id);

        if (!$publicationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de publication non trouvée',
            ], 404);
        }

        // Ajouter les URLs complètes des fichiers
        if ($publicationRequest->document_file) {
            $publicationRequest->document_file_url = Storage::disk('public')->url($publicationRequest->document_file);
        }
        if ($publicationRequest->document_image) {
            $publicationRequest->document_image_url = Storage::disk('public')->url($publicationRequest->document_image);
        }

        return response()->json([
            'success' => true,
            'data' => $publicationRequest,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $publicationRequest = PublicationRequest::find($id);

        if (!$publicationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de publication non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:500',
            'email' => 'sometimes|email|max:255',
            'status' => 'sometimes|in:pending,under-review,accepted,rejected,published',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $publicationRequest->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Demande de publication mise à jour avec succès',
            'data' => $publicationRequest,
        ]);
    }

    /**
     * Update the status of a publication request.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $publicationRequest = PublicationRequest::find($id);

        if (!$publicationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de publication non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,under-review,accepted,rejected,published',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = $request->input('status');
        $updateData = ['status' => $status];
        
        if ($status === 'published') {
            $updateData['published_at'] = now();
        }
        
        if (in_array($status, ['accepted', 'rejected'])) {
            $updateData['reviewed_at'] = now();
        }

        $publicationRequest->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => $publicationRequest,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $publicationRequest = PublicationRequest::find($id);

        if (!$publicationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de publication non trouvée',
            ], 404);
        }

        $publicationRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demande de publication supprimée avec succès',
        ]);
    }
}
