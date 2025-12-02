<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NewsletterSubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $search = $request->get('search');

        $query = NewsletterSubscription::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $subscriptions->items(),
            'pagination' => [
                'page' => $subscriptions->currentPage(),
                'limit' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'totalPages' => $subscriptions->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage (Subscribe).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:newsletter_subscriptions,email',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'preferences' => 'nullable|array',
            'preferences.events' => 'nullable|boolean',
            'preferences.publications' => 'nullable|boolean',
            'preferences.actualities' => 'nullable|boolean',
            'preferences.general' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = 'active';
        $data['subscribed_at'] = now();

        // Préférences par défaut si non fournies
        if (empty($data['preferences'])) {
            $data['preferences'] = [
                'events' => true,
                'publications' => true,
                'actualities' => true,
                'general' => true,
            ];
        }

        $subscription = NewsletterSubscription::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Abonnement enregistré avec succès',
            'data' => $subscription,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $subscription = NewsletterSubscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $subscription = NewsletterSubscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|required|email|max:255|unique:newsletter_subscriptions,email,' . $id,
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,unsubscribed,pending',
            'preferences' => 'nullable|array',
            'preferences.events' => 'nullable|boolean',
            'preferences.publications' => 'nullable|boolean',
            'preferences.actualities' => 'nullable|boolean',
            'preferences.general' => 'nullable|boolean',
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
            if ($data['status'] === 'unsubscribed' && $subscription->status !== 'unsubscribed') {
                $data['unsubscribed_at'] = now();
            }
            if ($data['status'] === 'active' && $subscription->status === 'unsubscribed') {
                $data['subscribed_at'] = now();
                $data['unsubscribed_at'] = null;
            }
        }

        $subscription->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Abonnement mis à jour avec succès',
            'data' => $subscription,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $subscription = NewsletterSubscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé',
            ], 404);
        }

        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Abonnement supprimé avec succès',
        ]);
    }

    /**
     * Subscribe endpoint (public)
     */
    public function subscribe(Request $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Unsubscribe endpoint (public)
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subscription = NewsletterSubscription::where('email', $request->email)->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Email non trouvé dans nos abonnements',
            ], 404);
        }

        $subscription->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Désabonnement effectué avec succès',
            'data' => $subscription,
        ]);
    }

    /**
     * Get subscription status
     */
    public function status(Request $request): JsonResponse
    {
        $email = $request->get('email');

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email requis',
            ], 422);
        }

        $subscription = NewsletterSubscription::where('email', $email)->first();

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Email non trouvé',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }

    /**
     * Get all subscribers (admin)
     */
    public function subscribers(Request $request): JsonResponse
    {
        return $this->index($request);
    }
}
