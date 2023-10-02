<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackRequest;
use App\Http\Requests\UpdatePackRequest;
use App\Models\Pack;
use Illuminate\Http\Request;
use App\Traits\PackTrait;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Ramsey\Uuid\Uuid;

class PackController extends Controller
{
    use PackTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $active_packs = Pack::where('is_active', true)->get();
        $inactive_packs = Pack::where('is_active', false)->get();
        return view('admin.packs.index', [
            'active_packs' => $active_packs,
            'inactive_packs' => $inactive_packs
        ]);
    }

    public function create() {
        return view('admin.packs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePackRequest $request)
    {
        $validated = $request->validated();
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = false;
        } else $validated['is_active'] = true;

        // generate uuid4
        $uuid = Uuid::uuid4()->toString();
        $image = $this->saveImage($validated['image'], $uuid);

        if (!$image) {
            return back()->with('error', 'Fout bij het opslaan van de afbeelding. Probeer het opnieuw.');
        }

        // save pack
        Pack::create([
            'uuid' => $uuid,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image' => $image,
            'is_active' => $validated['is_active']
        ]);

        return redirect(route('packs.index'))->with('success', 'Pack succesvol aangemaakt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pack $pack)
    {
        return view('admin.packs.show', [
            'pack' => $pack
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePackRequest $request, Pack $pack)
    {
        $validated = $request->validated();

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = false;
        } else $validated['is_active'] = true;

        if (!isset($validated['image'])) {
            $image = $pack->image;
        } else {
            $image = $this->saveImage($validated['image'], $pack->uuid);
            if (!$image) {
                return back()->with('error', 'Fout bij het opslaan van de afbeelding. Probeer het opnieuw.');
            }
        }

        $pack->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image' => $image,
            'is_active' => $validated['is_active']
        ]);
        return redirect(route('packs.index'))->with('success', 'Pack succesvol bijgewerkt.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pack $pack)
    {
        $pack->delete();
        return back();
    }
}
