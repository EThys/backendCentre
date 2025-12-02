<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GalleryPhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $category = $request->get('category');
        $featured = $request->get('featured');

        $query = GalleryPhoto::query();

        if ($category) {
            $query->where('category', $category);
        }

        if ($featured !== null) {
            $query->where('featured', filter_var($featured, FILTER_VALIDATE_BOOLEAN));
        }

        // Si per_page est très grand (>= 1000), retourner toutes les photos sans pagination
        if ($perPage >= 1000) {
            $photos = $query->orderBy('order', 'asc')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $photos->toArray(),
                'pagination' => [
                    'page' => 1,
                    'limit' => $photos->count(),
                    'total' => $photos->count(),
                    'totalPages' => 1,
                ],
            ]);
        }
        
        $photos = $query->orderBy('order', 'asc')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $photos->items(),
            'pagination' => [
                'page' => $photos->currentPage(),
                'limit' => $photos->perPage(),
                'total' => $photos->total(),
                'totalPages' => $photos->lastPage(),
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
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'required|string|max:255',
            'date' => 'required|date',
            'author' => 'required|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'featured' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
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
            $imagePath = $request->file('image')->store('gallery', 'public');
            $data['image'] = $imagePath;

            // Generate thumbnail (you might want to use an image processing library like Intervention Image)
            // For now, we'll use the same image as thumbnail
            $data['thumbnail'] = $imagePath;
        }

        $photo = GalleryPhoto::create($data);

        return response()->json([
            'success' => true,
            'data' => $photo,
            'message' => 'Photo de galerie créée avec succès',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $photo = GalleryPhoto::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo de galerie non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $photo,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $photo = GalleryPhoto::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo de galerie non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'author' => 'sometimes|required|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'featured' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
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
            if ($photo->image) {
                Storage::disk('public')->delete($photo->image);
            }
            if ($photo->thumbnail) {
                Storage::disk('public')->delete($photo->thumbnail);
            }

            $imagePath = $request->file('image')->store('gallery', 'public');
            $data['image'] = $imagePath;
            $data['thumbnail'] = $imagePath; // You might want to generate a proper thumbnail
        }

        $photo->update($data);

        return response()->json([
            'success' => true,
            'data' => $photo,
            'message' => 'Photo de galerie mise à jour avec succès',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $photo = GalleryPhoto::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo de galerie non trouvée',
            ], 404);
        }

        // Delete associated images
        if ($photo->image) {
            Storage::disk('public')->delete($photo->image);
        }
        if ($photo->thumbnail) {
            Storage::disk('public')->delete($photo->thumbnail);
        }

        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo de galerie supprimée avec succès',
        ]);
    }

    /**
     * Get all categories
     */
    public function categories(): JsonResponse
    {
        $categories = GalleryPhoto::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(function ($category) {
                return [
                    'id' => $category,
                    'name' => $category,
                    'count' => GalleryPhoto::where('category', $category)->count(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
