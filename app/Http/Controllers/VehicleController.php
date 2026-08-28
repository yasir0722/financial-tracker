<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::where('user_id', auth()->id())->withCount('carExpenses')->orderByDesc('is_default')->orderBy('name')->paginate(20);
        return view('vehicles.index', compact('vehicles'));
    }

    public function create(): View { return view('vehicles.form', ['vehicle' => new Vehicle()]); }

    public function store(VehicleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        if ($request->boolean('is_default')) Vehicle::where('user_id', auth()->id())->update(['is_default' => false]);
        Vehicle::create($data);
        return redirect()->route('vehicles.index')->with('success', 'Vehicle created successfully.');
    }

    public function edit(Vehicle $vehicle): View
    {
        $this->authorize('update', $vehicle);
        return view('vehicles.form', compact('vehicle'));
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        if ($request->boolean('is_default')) Vehicle::where('user_id', auth()->id())->where('id', '!=', $vehicle->id)->update(['is_default' => false]);
        $vehicle->update($request->validated());
        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);
        if ($vehicle->carExpenses()->exists()) return back()->with('error', 'Vehicles with maintenance records cannot be deleted.');
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted successfully.');
    }
}
