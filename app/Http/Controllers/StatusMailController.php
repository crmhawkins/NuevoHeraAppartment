<?php

namespace App\Http\Controllers;

use App\Models\StatusMail;
use Illuminate\Http\Request;

class StatusMailController extends Controller
{
    public function index()
    {
        $statuses = StatusMail::all();
        return view('admin.statusEmail.index', compact('statuses'));
    }

    public function create()
    {
        return view('admin.statusEmail.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'other' => 'nullable|string',
        ]);

        StatusMail::create($request->all());

        return redirect()->route('admin.statusMail.index')->with('status', 'Estado de email creado exitosamente.');
    }

    public function edit($id)
    {
        $status = StatusMail::findOrFail($id);
        return view('admin.statusEmail.edit', compact('status'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'other' => 'nullable|string',
        ]);

        $status = StatusMail::findOrFail($id);
        $status->update($request->all());

        return redirect()->route('admin.statusMail.index')->with('status', 'Estado de email actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $status = StatusMail::findOrFail($id);
        $status->delete();

        return redirect()->route('admin.statusMail.index')->with('status', 'Estado de email eliminado exitosamente.');
    }
}
