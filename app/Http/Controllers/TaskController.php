<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskReqeuest;
use App\Http\Requests\UpdateTaskReqeuest;
use App\Models\Task;
use Ramsey\Uuid\Uuid;

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

        $request['uuid'] = Uuid::uuid4();

        $request['players'] = intval($request['players']);
        $request['duration'] = intval($request['duration']);
        $request['pack_id'] = intval($request['pack_id']);
        $request['min_sips'] = intval($request['min_sips']);
        $request['max_sips'] = intval($request['max_sips']);
        $request['chug'] = boolval($request['chug']);

        if(Task::create($request)) {
            return back()->with('success', 'Task created successfully!');
        } else {
            return back()->with('error', 'Task failed to create!');
        }
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
        $request['duration'] = intval($request['duration']);
        $request['min_sips'] = intval($request['min_sips']);
        $request['max_sips'] = intval($request['max_sips']);
        $ini = $request['chug'];
        $request['chug'] = intval($request['chug']);
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
