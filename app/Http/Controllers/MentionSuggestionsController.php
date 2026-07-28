<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentionSuggestionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->string('query')->toString();
        $orgId = auth()->user()->organization_id;

        $users = User::query()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]);

        return response()->json($users);
    }
}
