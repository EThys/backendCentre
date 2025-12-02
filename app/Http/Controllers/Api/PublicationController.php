<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $type = $request->get('type');
        $featured = $request->get('featured');

        $query = Publication::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($featured !== null) {
            $query->where('featured', filter_var($featured, FILTER_VALIDATE_BOOLEAN));
        }

        $publications = $query->orderBy('publication_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Récupérer aussi les demandes de publication validées (accepted ou published)
        $validatedRequests = \App\Models\PublicationRequest::whereIn('status', ['accepted', 'published'])
            ->orderBy('published_at', 'desc')
            ->orderBy('reviewed_at', 'desc')
            ->get();

        // Transformer les demandes validées en format Publication
        $publicationsFromRequests = $validatedRequests->map(function ($request) {
            // Parser les auteurs depuis la chaîne
            $authors = [];
            if ($request->authors) {
                $authorNames = explode(',', $request->authors);
                foreach ($authorNames as $index => $name) {
                    $authors[] = [
                        'id' => $index + 1,
                        'name' => trim($name),
                    ];
                }
            }

            // Construire les URLs des fichiers
            $documentFileUrl = null;
            if ($request->document_file) {
                $documentFileUrl = Storage::disk('public')->url($request->document_file);
            }

            $documentImageUrl = null;
            if ($request->document_image) {
                $documentImageUrl = Storage::disk('public')->url($request->document_image);
            }

            return [
                'id' => 'request_' . $request->id, // Préfixe pour distinguer des publications normales
                'title' => $request->title,
                'abstract' => $request->abstract,
                'content' => $request->abstract, // Utiliser l'abstract comme contenu si pas de contenu
                'image' => $documentImageUrl, // Image du document
                'type' => $request->type,
                'authors' => $authors,
                'journal' => null,
                'publisher' => null,
                'publication_date' => $request->published_at ? $request->published_at->format('Y-m-d') : ($request->reviewed_at ? $request->reviewed_at->format('Y-m-d') : $request->submission_date->format('Y-m-d')),
                'doi' => null,
                'isbn' => null,
                'citations' => 0,
                'downloads' => 0,
                'views' => 0,
                'pdf_url' => $documentFileUrl, // Fichier PDF/Word
                'domains' => $request->domains ?? [],
                'keywords' => $request->keywords ? explode(',', $request->keywords) : [],
                'references' => [],
                'status' => $request->status === 'published' ? 'published' : 'published',
                'featured' => false,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
                'is_from_request' => true, // Flag pour identifier les publications provenant de demandes
                'request_id' => $request->id,
                'document_file' => $documentFileUrl,
                'document_image' => $documentImageUrl,
            ];
        });

        // Fusionner les publications normales et celles provenant de demandes
        $allPublications = $publications->concat($publicationsFromRequests);

        // Appliquer les filtres après la fusion
        if ($type && $type !== 'all') {
            $allPublications = $allPublications->filter(function ($pub) use ($type) {
                return $pub['type'] === $type;
            });
        }

        // Trier par date de publication
        $allPublications = $allPublications->sortByDesc(function ($pub) {
            return $pub['publication_date'] ?? $pub['created_at'] ?? now();
        })->values();

        // Pagination manuelle
        $total = $allPublications->count();
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedPublications = $allPublications->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedPublications->toArray(),
            'pagination' => [
                'page' => (int) $page,
                'limit' => (int) $perPage,
                'total' => $total,
                'totalPages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'abstract' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'required|in:article,research-paper,book,report,other',
            'authors' => 'required|array|min:1',
            'authors.*.name' => 'required|string|max:255',
            'authors.*.affiliation' => 'nullable|string|max:255',
            'authors.*.email' => 'nullable|email|max:255',
            'authors.*.orcid' => 'nullable|string|max:255',
            'journal' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'required|date',
            'doi' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:255',
            'pdf_url' => 'nullable|url|max:255',
            'domains' => 'required|array|min:1',
            'domains.*' => 'string|max:255',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:255',
            'references' => 'nullable|array',
            'references.*' => 'string',
            'status' => 'nullable|in:draft,published,archived',
            'featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('publications', 'public');
        }

        // Handle PDF upload
        if ($request->hasFile('pdf')) {
            $data['pdf_url'] = $request->file('pdf')->store('publications/pdf', 'public');
        }

        $publication = Publication::create($data);

        return response()->json([
            'success' => true,
            'data' => $publication,
            'message' => 'Publication créée avec succès',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        // Vérifier si c'est une publication provenant d'une demande (format: request_123)
        if (str_starts_with($id, 'request_')) {
            $requestId = (int) str_replace('request_', '', $id);
            $request = \App\Models\PublicationRequest::find($requestId);

            if (!$request || !in_array($request->status, ['accepted', 'published'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Publication non trouvée',
                ], 404);
            }

            // Transformer la demande en format Publication
            $authors = [];
            if ($request->authors) {
                $authorNames = explode(',', $request->authors);
                foreach ($authorNames as $index => $name) {
                    $authors[] = [
                        'id' => $index + 1,
                        'name' => trim($name),
                    ];
                }
            }

            // Construire les URLs des fichiers
            $documentFileUrl = null;
            if ($request->document_file) {
                $documentFileUrl = Storage::disk('public')->url($request->document_file);
            }

            $documentImageUrl = null;
            if ($request->document_image) {
                $documentImageUrl = Storage::disk('public')->url($request->document_image);
            }

            $publication = [
                'id' => 'request_' . $request->id,
                'title' => $request->title,
                'abstract' => $request->abstract,
                'content' => $request->abstract,
                'image' => $documentImageUrl,
                'type' => $request->type,
                'authors' => $authors,
                'journal' => null,
                'publisher' => null,
                'publication_date' => $request->published_at ? $request->published_at->format('Y-m-d') : ($request->reviewed_at ? $request->reviewed_at->format('Y-m-d') : $request->submission_date->format('Y-m-d')),
                'doi' => null,
                'isbn' => null,
                'citations' => 0,
                'downloads' => 0,
                'views' => 0,
                'pdf_url' => $documentFileUrl,
                'domains' => $request->domains ?? [],
                'keywords' => $request->keywords ? explode(',', $request->keywords) : [],
                'references' => [],
                'status' => $request->status === 'published' ? 'published' : 'published',
                'featured' => false,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
                'is_from_request' => true,
                'request_id' => $request->id,
                'name' => $request->name,
                'email' => $request->email,
                'institution' => $request->institution,
                'position' => $request->position,
                'document_file' => $documentFileUrl,
                'document_image' => $documentImageUrl,
            ];

            return response()->json([
                'success' => true,
                'data' => $publication,
            ]);
        }

        // Publication normale
        $publication = Publication::find($id);

        if (!$publication) {
            return response()->json([
                'success' => false,
                'message' => 'Publication non trouvée',
            ], 404);
        }

        // Increment views
        $publication->increment('views');

        return response()->json([
            'success' => true,
            'data' => $publication,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $publication = Publication::find($id);

        if (!$publication) {
            return response()->json([
                'success' => false,
                'message' => 'Publication non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'abstract' => 'sometimes|required|string',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'sometimes|required|in:article,research-paper,book,report,other',
            'authors' => 'sometimes|required|array|min:1',
            'authors.*.name' => 'required|string|max:255',
            'authors.*.affiliation' => 'nullable|string|max:255',
            'authors.*.email' => 'nullable|email|max:255',
            'authors.*.orcid' => 'nullable|string|max:255',
            'journal' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'sometimes|required|date',
            'doi' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:255',
            'pdf_url' => 'nullable|url|max:255',
            'domains' => 'sometimes|required|array|min:1',
            'domains.*' => 'string|max:255',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:255',
            'references' => 'nullable|array',
            'references.*' => 'string',
            'status' => 'nullable|in:draft,published,archived',
            'featured' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($publication->image) {
                Storage::disk('public')->delete($publication->image);
            }
            $data['image'] = $request->file('image')->store('publications', 'public');
        }

        // Handle PDF upload
        if ($request->hasFile('pdf')) {
            // Delete old PDF
            if ($publication->pdf_url) {
                Storage::disk('public')->delete($publication->pdf_url);
            }
            $data['pdf_url'] = $request->file('pdf')->store('publications/pdf', 'public');
        }

        $publication->update($data);

        return response()->json([
            'success' => true,
            'data' => $publication,
            'message' => 'Publication mise à jour avec succès',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $publication = Publication::find($id);

        if (!$publication) {
            return response()->json([
                'success' => false,
                'message' => 'Publication non trouvée',
            ], 404);
        }

        // Delete associated files
        if ($publication->image) {
            Storage::disk('public')->delete($publication->image);
        }
        if ($publication->pdf_url) {
            Storage::disk('public')->delete($publication->pdf_url);
        }

        $publication->delete();

        return response()->json([
            'success' => true,
            'message' => 'Publication supprimée avec succès',
        ]);
    }
}
