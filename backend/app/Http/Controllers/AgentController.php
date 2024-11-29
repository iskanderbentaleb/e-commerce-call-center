<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentResource;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'name'); // Default sort by name
        $direction = $request->query('direction', 'asc'); // Default sort direction ascending

        // Validate sort parameter (optional but recommended)
        $validSortFields = ['name', 'email'];
        if (!in_array($sort, $validSortFields)) {
            return response()->json(['error' => 'Invalid sort field'], 400);
        }

        // Build the query for the Agent model
        $query = Agent::query();

        // Apply search condition only if a search term is provided
        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'LIKE', "%$search%")
                         ->orWhere('email', 'LIKE', "%$search%");
            });
        }

        // Apply sorting and pagination
        $agents = $query->orderBy($sort, $direction)->paginate(10);

        // Return paginated AgentResource collection
        return AgentResource::collection($agents);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Agent $agent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent)
    {
        //
    }
}
