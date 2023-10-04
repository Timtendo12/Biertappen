<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskReqeuest;
use App\Http\Requests\UpdateTaskReqeuest;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskReqeuest $request)
    {
        $request = $request->validated();
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskReqeuest $request, string $id)
    {
        $request = $request->validated();
        $task = Task::findOrFail($id);
        $request['players'] = intval($request['players']);
        if($task->update($request)) {
            return back()->with('success', 'Task pa successfully!');
        } else {
            return back()->with('error', 'Task failed to update!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
