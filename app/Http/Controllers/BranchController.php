<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branch::paginate(10); // Paginate results
        return response()->json([
            'status' => 'success',
            'data' => $branches
        ]);
    }

    public function getBranches()
    {
        $branches = Branch::all();
        return response()->json([
            'status' => 'success',
            'data' => $branches
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'branch_id' => 'required|unique:branches,branch_id',
            'branch_name' => 'required|unique:branches,branch_name',
            'location' => 'required',
            'contact_number' => 'required',
            'branch_manager' => 'required',
        ]);

        $branch = Branch::create($fields);

        return response()->json([
            'status' => 'success',
            'message' => 'Branch created successfully',
            'data' => $branch
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        return response()->json([
            'status' => 'success',
            'data' => $branch
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $fields = $request->validate([
            'branch_id' => 'sometimes|required|unique:branches,branch_id,' . $branch->branch_id,
            'branch_name' => 'sometimes|required|unique:branches,branch_name,' . $branch->branch_name,
            'location' => 'sometimes|required',
            'contact_number' => 'sometimes|required',
            'branch_manager' => 'sometimes|required',
        ]);

        $branch->update($fields);

        return response()->json([
            'status' => 'success',
            'message' => 'Branch updated successfully',
            'data' => $branch
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Branch deleted successfully'
        ]);
    }
}
