<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    /**
     * List agents, paginated, with optional search.
     */
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
            ]);

            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            $query = Agent::query();

            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('phone_number', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('agent_number', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            $agents = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            return response()->json($agents);
        } catch (\Exception $e) {
            Log::error('Error listing agents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Create a new agent.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20|unique:agents,phone_number',
                'address' => 'nullable|string|max:255',
            ]);

            do {
                $uuid = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 8));
            } while (str_starts_with($uuid, '0'));
            $agentNumber = 'AG' . $uuid;

            $agent = Agent::create([
                'agent_number' => $agentNumber,
                'name' => ucwords($validated['name']),
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'] ?? null,
                'status' => 'ACTIVE',
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agent created successfully.',
                'data' => $agent,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating agent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the agent.',
            ], 500);
        }
    }

    /**
     * Show a single agent, with their consignment history.
     */
    public function show($id)
    {
        $agent = Agent::with(['consignments.items'])->find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $agent,
        ]);
    }

    /**
     * Update an agent.
     */
    public function update(Request $request, $id)
    {
        try {
            $agent = Agent::find($id);

            if (!$agent) {
                return response()->json(['success' => false, 'message' => 'Agent not found'], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20|unique:agents,phone_number,' . $agent->id,
                'address' => 'nullable|string|max:255',
                'status' => 'required|in:ACTIVE,INACTIVE',
            ]);

            $agent->update([
                'name' => ucwords($validated['name']),
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'],
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agent updated successfully.',
                'data' => $agent,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating agent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the agent.',
            ], 500);
        }
    }

    /**
     * Soft-delete an agent.
     * Blocked if they have any consignment that isn't fully reconciled/rejected,
     * since deleting them mid-consignment would orphan outstanding stock tracking.
     */
    public function destroy($id)
    {
        try {
            $agent = Agent::with('consignments')->find($id);

            if (!$agent) {
                return response()->json(['success' => false, 'message' => 'Agent not found'], 404);
            }

            $hasOpenConsignments = $agent->consignments
                ->whereIn('status', ['PENDING_APPROVAL', 'APPROVED', 'PARTIALLY_RECONCILED'])
                ->isNotEmpty();

            if ($hasOpenConsignments) {
                return response()->json([
                    'success' => false,
                    'message' => 'This agent has open consignments and cannot be deleted until they are fully reconciled or rejected.',
                ], 422);
            }

            $agent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Agent deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting agent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the agent.',
            ], 500);
        }
    }

    /**
     * Simple list for dropdowns (e.g. the "request consignment" form).
     */
    public function getAgents()
    {
        try {
            $agents = Agent::where('status', 'ACTIVE')
                ->select('id', 'agent_number', 'name', 'phone_number')
                ->orderBy('name')
                ->get();

            return response()->json($agents);
        } catch (\Exception $e) {
            Log::error('Error fetching agents: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch agents'], 500);
        }
    }
}
