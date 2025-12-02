<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actuality;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ActualityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $category = $request->get('category');
        $featured = $request->get('featured');

        $query = Actuality::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($featured !== null) {
            $query->where('featured', filter_var($featured, FILTER_VALIDATE_BOOLEAN));
        }

        $actualities = $query->orderBy('publish_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $actualities->items(),
            'pagination' => [
                'page' => $actualities->currentPage(),
                'limit' => $actualities->perPage(),
                'total' => $actualities->total(),
                'totalPages' => $actualities->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Decode JSON strings for array fields before validation
        $requestData = $request->all();
        
        // Handle tags - always ensure it's an array
        if (isset($requestData['tags'])) {
            if (is_string($requestData['tags'])) {
                $decoded = json_decode($requestData['tags'], true);
                $requestData['tags'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['tags'])) {
                $requestData['tags'] = [];
            }
        } else {
            $requestData['tags'] = [];
        }
        
        // Handle learning_points - always ensure it's an array
        if (isset($requestData['learning_points'])) {
            if (is_string($requestData['learning_points'])) {
                $decoded = json_decode($requestData['learning_points'], true);
                $requestData['learning_points'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['learning_points'])) {
                $requestData['learning_points'] = [];
            }
        } else {
            $requestData['learning_points'] = [];
        }
        
        // Handle key_points - always ensure it's an array
        if (isset($requestData['key_points'])) {
            if (is_string($requestData['key_points'])) {
                $decoded = json_decode($requestData['key_points'], true);
                $requestData['key_points'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['key_points'])) {
                $requestData['key_points'] = [];
            }
        } else {
            $requestData['key_points'] = [];
        }
        
        // Handle related_articles - always ensure it's an array
        if (isset($requestData['related_articles'])) {
            if (is_string($requestData['related_articles'])) {
                $decoded = json_decode($requestData['related_articles'], true);
                $requestData['related_articles'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['related_articles'])) {
                $requestData['related_articles'] = [];
            }
        } else {
            $requestData['related_articles'] = [];
        }

        $validator = Validator::make($requestData, [
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'content' => 'required|string',
            'learning_points' => 'nullable|array',
            'learning_points.*' => 'string|max:500',
            'key_points' => 'nullable|array',
            'key_points.*' => 'string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'nullable|string|max:255',
            'author' => 'required|string|max:255',
            'author_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'publish_date' => 'required|date',
            'read_time' => 'nullable|integer|min:1',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'featured' => 'nullable|boolean',
            'status' => 'nullable|in:draft,published,archived',
            'related_articles' => 'nullable|array',
            'related_articles.*' => 'integer|exists:actualities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Ensure array fields are always arrays (even if empty or not provided)
        $data['tags'] = $data['tags'] ?? [];
        $data['learning_points'] = $data['learning_points'] ?? [];
        $data['key_points'] = $data['key_points'] ?? [];
        $data['related_articles'] = $data['related_articles'] ?? [];

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('actualities', 'public');
        }

        // Handle author photo upload
        if ($request->hasFile('author_photo')) {
            $data['author_photo'] = $request->file('author_photo')->store('authors', 'public');
        }

        $actuality = Actuality::create($data);

        return response()->json([
            'success' => true,
            'data' => $actuality,
            'message' => 'Actualité créée avec succès',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $actuality = Actuality::find($id);

        if (!$actuality) {
            return response()->json([
                'success' => false,
                'message' => 'Actualité non trouvée',
            ], 404);
        }

        // Increment views
        $actuality->increment('views');

        return response()->json([
            'success' => true,
            'data' => $actuality,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $actuality = Actuality::find($id);

        if (!$actuality) {
            return response()->json([
                'success' => false,
                'message' => 'Actualité non trouvée',
            ], 404);
        }

        // Decode JSON strings for array fields before validation
        $requestData = $request->all();
        
        // Handle tags - decode if string, ensure array
        if (isset($requestData['tags'])) {
            if (is_string($requestData['tags'])) {
                $decoded = json_decode($requestData['tags'], true);
                $requestData['tags'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['tags'])) {
                $requestData['tags'] = [];
            }
        }
        // Note: for update, we don't set default empty array if not provided (to allow partial updates)
        
        // Handle learning_points
        if (isset($requestData['learning_points'])) {
            if (is_string($requestData['learning_points'])) {
                $decoded = json_decode($requestData['learning_points'], true);
                $requestData['learning_points'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['learning_points'])) {
                $requestData['learning_points'] = [];
            }
        }
        
        // Handle key_points
        if (isset($requestData['key_points'])) {
            if (is_string($requestData['key_points'])) {
                $decoded = json_decode($requestData['key_points'], true);
                $requestData['key_points'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['key_points'])) {
                $requestData['key_points'] = [];
            }
        }
        
        // Handle related_articles
        if (isset($requestData['related_articles'])) {
            if (is_string($requestData['related_articles'])) {
                $decoded = json_decode($requestData['related_articles'], true);
                $requestData['related_articles'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($requestData['related_articles'])) {
                $requestData['related_articles'] = [];
            }
        }

        $validator = Validator::make($requestData, [
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'sometimes|required|string',
            'content' => 'sometimes|required|string',
            'learning_points' => 'nullable|array',
            'learning_points.*' => 'string|max:500',
            'key_points' => 'nullable|array',
            'key_points.*' => 'string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'nullable|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'author_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'publish_date' => 'sometimes|required|date',
            'read_time' => 'nullable|integer|min:1',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'featured' => 'nullable|boolean',
            'status' => 'nullable|in:draft,published,archived',
            'related_articles' => 'nullable|array',
            'related_articles.*' => 'integer|exists:actualities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Ensure array fields are always arrays (even if empty or not provided)
        if (isset($data['tags'])) {
            $data['tags'] = is_array($data['tags']) ? $data['tags'] : [];
        }
        if (isset($data['learning_points'])) {
            $data['learning_points'] = is_array($data['learning_points']) ? $data['learning_points'] : [];
        }
        if (isset($data['key_points'])) {
            $data['key_points'] = is_array($data['key_points']) ? $data['key_points'] : [];
        }
        if (isset($data['related_articles'])) {
            $data['related_articles'] = is_array($data['related_articles']) ? $data['related_articles'] : [];
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($actuality->image) {
                Storage::disk('public')->delete($actuality->image);
            }
            $data['image'] = $request->file('image')->store('actualities', 'public');
        }

        // Handle author photo upload
        if ($request->hasFile('author_photo')) {
            // Delete old photo
            if ($actuality->author_photo) {
                Storage::disk('public')->delete($actuality->author_photo);
            }
            $data['author_photo'] = $request->file('author_photo')->store('authors', 'public');
        }

        $actuality->update($data);

        return response()->json([
            'success' => true,
            'data' => $actuality,
            'message' => 'Actualité mise à jour avec succès',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $actuality = Actuality::find($id);

        if (!$actuality) {
            return response()->json([
                'success' => false,
                'message' => 'Actualité non trouvée',
            ], 404);
        }

        // Delete associated images
        if ($actuality->image) {
            Storage::disk('public')->delete($actuality->image);
        }
        if ($actuality->author_photo) {
            Storage::disk('public')->delete($actuality->author_photo);
        }

        $actuality->delete();

        return response()->json([
            'success' => true,
            'message' => 'Actualité supprimée avec succès',
        ]);
    }
}
