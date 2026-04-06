<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Inicializamos la consulta
        $query = User::query();

        // Filtro por búsqueda (nombre o email)
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filtro por estado activo/inactivo
        if ($request->filled('active')) {
            $inactive = $request->active == '0' ? 1 : null;
            $query->where('inactive', $inactive);
        }

        // Ordenamiento
        $sort = $request->get('sort', 'name');
        $order = $request->get('order', 'asc');
        $query->orderBy($sort, $order);

        // Obtener los usuarios filtrados con paginación
        $users = $query->paginate(20);

        // Estadísticas para el dashboard
        $totalEmpleados = User::count();
        $empleadosActivos = User::where('inactive', null)->count();
        $empleadosInactivos = User::where('inactive', 1)->count();
        $rolesDisponibles = User::distinct('role')->pluck('role')->filter();

        return view('admin.users.index', compact(
            'users', 
            'totalEmpleados', 
            'empleadosActivos', 
            'empleadosInactivos', 
            'rolesDisponibles',
            'sort',
            'order'
        ));
    }

    public function create() {
        $roles = [
            'ADMIN' => 'Administrador',
            'USER' => 'Usuario',
            'LIMPIEZA' => 'Limpieza',
            'MANTENIMIENTO' => 'Mantenimiento',
            'RECEPCION' => 'Recepción',
            'SUPERVISOR' => 'Supervisor'
        ];
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request) {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:ADMIN,USER,LIMPIEZA,MANTENIMIENTO,RECEPCION,SUPERVISOR',
            'telefono' => 'nullable|string|max:20',
            'departamento' => 'nullable|string|max:100',
            'fecha_contratacion' => 'nullable|date',
            'salario' => 'nullable|numeric|min:0',
            'activo' => 'boolean'
        ];

        $messages = [
            'name.required' => 'El nombre del empleado es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'email.unique' => 'Este email ya está registrado en el sistema.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role.required' => 'Debes seleccionar un rol.',
            'role.in' => 'El rol seleccionado no es válido.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'departamento.max' => 'El departamento no puede tener más de 100 caracteres.',
            'fecha_contratacion.date' => 'La fecha de contratación no es válida.',
            'salario.numeric' => 'El salario debe ser un número.',
            'salario.min' => 'El salario no puede ser negativo.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['password'] = Hash::make($request->password);
            $validatedData['inactive'] = $request->has('activo') ? null : 1;
            $validatedData['email_verified_at'] = now(); // Verificar email automáticamente

            $user = User::create($validatedData);

            return redirect()->route('admin.empleados.index')
                ->with('swal_success', '¡Empleado creado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el empleado: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        
        // Estadísticas del empleado
        $totalFichajes = $user->fichajes()->count();
        $fichajesEsteMes = $user->fichajes()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $horasTrabajadas = $user->fichajes()
            ->whereNotNull('hora_fin')
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fin)')) / 60;
        
        return view('admin.users.show', compact(
            'user', 
            'totalFichajes', 
            'fichajesEsteMes',
            'horasTrabajadas'
        ));
    }

    public function edit($id) {
        $user = User::findOrFail($id);
        $roles = [
            'ADMIN' => 'Administrador',
            'USER' => 'Usuario',
            'LIMPIEZA' => 'Limpieza',
            'MANTENIMIENTO' => 'Mantenimiento',
            'RECEPCION' => 'Recepción',
            'SUPERVISOR' => 'Supervisor'
        ];
        
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id)
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:ADMIN,USER,LIMPIEZA,MANTENIMIENTO,RECEPCION,SUPERVISOR',
            'telefono' => 'nullable|string|max:20',
            'departamento' => 'nullable|string|max:100',
            'fecha_contratacion' => 'nullable|date',
            'salario' => 'nullable|numeric|min:0',
            'activo' => 'boolean'
        ];

        $messages = [
            'name.required' => 'El nombre del empleado es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato del email no es válido.',
            'email.unique' => 'Este email ya está registrado en el sistema.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role.required' => 'Debes seleccionar un rol.',
            'role.in' => 'El rol seleccionado no es válido.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'departamento.max' => 'El departamento no puede tener más de 100 caracteres.',
            'fecha_contratacion.date' => 'La fecha de contratación no es válida.',
            'salario.numeric' => 'El salario debe ser un número.',
            'salario.min' => 'El salario no puede ser negativo.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['inactive'] = $request->has('activo') ? null : 1;
            
            // Solo actualizar contraseña si se proporciona
            if ($request->filled('password')) {
                $validatedData['password'] = Hash::make($request->password);
            } else {
                unset($validatedData['password']);
            }

            $user->update($validatedData);

            return redirect()->route('admin.empleados.index')
                ->with('swal_success', '¡Empleado actualizado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el empleado: ' . $e->getMessage());
        }
    }

    public function destroy($id) {
        try {
            $user = User::findOrFail($id);
            
            // Verificar si es el último administrador
            if ($user->role === 'ADMIN' && User::where('role', 'ADMIN')->count() <= 1) {
                return redirect()->back()
                    ->with('swal_error', 'No se puede eliminar el último administrador del sistema.');
            }

            // Verificar si tiene registros asociados
            if ($user->fichajes()->count() > 0) {
                return redirect()->back()
                    ->with('swal_error', 'No se puede eliminar el empleado porque tiene fichajes asociados.');
            }

            $user->delete();

            return redirect()->route('admin.empleados.index')
                ->with('swal_success', '¡Empleado eliminado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el empleado: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->inactive = $user->inactive ? null : 1;
            $user->save();

            $status = $user->inactive ? 'desactivado' : 'activado';
            return redirect()->back()
                ->with('swal_success', "¡Empleado {$status} con éxito!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al cambiar el estado del empleado: ' . $e->getMessage());
        }
    }

    public function resetPassword($id)
    {
        try {
            $user = User::findOrFail($id);
            $newPassword = 'Empleado' . date('Y') . '!';
            $user->password = Hash::make($newPassword);
            $user->save();

            return redirect()->back()
                ->with('swal_success', "Contraseña restablecida. Nueva contraseña: {$newPassword}");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al restablecer la contraseña: ' . $e->getMessage());
        }
    }

    public function bulkAction(Request $request)
    {
        try {
            $action = $request->input('action');
            $userIds = $request->input('users', []);

            if (empty($userIds)) {
                return redirect()->back()
                    ->with('swal_error', 'Debes seleccionar al menos un empleado.');
            }

            switch ($action) {
                case 'activate':
                    User::whereIn('id', $userIds)->update(['inactive' => null]);
                    $message = 'Empleados activados con éxito';
                    break;
                case 'deactivate':
                    User::whereIn('id', $userIds)->update(['inactive' => 1]);
                    $message = 'Empleados desactivados con éxito';
                    break;
                case 'delete':
                    // Verificar que no se elimine el último admin
                    $adminsToDelete = User::whereIn('id', $userIds)->where('role', 'ADMIN')->count();
                    $totalAdmins = User::where('role', 'ADMIN')->count();
                    
                    if ($totalAdmins - $adminsToDelete <= 0) {
                        return redirect()->back()
                            ->with('swal_error', 'No se puede eliminar todos los administradores del sistema.');
                    }
                    
                    User::whereIn('id', $userIds)->delete();
                    $message = 'Empleados eliminados con éxito';
                    break;
                default:
                    return redirect()->back()
                        ->with('swal_error', 'Acción no válida.');
            }

            return redirect()->back()
                ->with('swal_success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al ejecutar la acción: ' . $e->getMessage());
        }
    }
}
