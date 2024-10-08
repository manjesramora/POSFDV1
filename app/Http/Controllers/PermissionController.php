<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Obtener el usuario autenticado
            $user = Auth::user();

            if ($user) {
                // Obtener los roles del usuario autenticado
                $userRoles = $user->roles;

                // Compartir los roles del usuario con todas las vistas
                view()->share('userRoles', $userRoles);
            }

            return $next($request);
        });
    }

    // Método para mostrar la lista de permisos
    public function permissions(Request $request)
    {
        $query = Permission::query();

        // Filtro por nombre o descripción
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'id'); // Ajusta el valor predeterminado según sea necesario
        $sortOrder = $request->input('sort_order', 'asc');

        if ($sortBy == 'name') {
            $query->orderBy('name', $sortOrder);
        } elseif ($sortBy == 'description') {
            $query->orderBy('description', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $permissions = $query->paginate(10)->appends($request->all()); // Asegúrate de pasar todos los parámetros

        return view('permissions', compact('permissions'));
    }

    public function createPermissionForm()
    {
        $departments = Department::all();
        return view('permission.create', ['departments' => $departments]);
    }

    public function store(Request $request)
    {
        // Mensajes de error personalizados
        $messages = [
            'name.unique' => 'El nombre del permiso ya existe. Por favor, elige otro.'
        ];

        // Validar los datos del formulario con mensajes personalizados
        $validatedData = $request->validate([
            'name' => 'required|unique:permissions|max:100',
            'description' => 'required|string|max:255',
        ], $messages);

        // Crear un nuevo permiso
        $permission = new Permission();
        $permission->name = strtoupper($request->name);
        $permission->description = $request->description;

        // Guardar el permiso en la base de datos
        $permission->save();

        // Redireccionar con mensaje de éxito
        return redirect()->route('permissions')->with('success', 'Permiso creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        // Mensajes de error personalizados
        $messages = [
            'name.unique' => 'El nombre del permiso ya existe. Por favor, elige otro.'
        ];
    
        // Validar los campos del formulario
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'description' => 'required|string|max:255',
        ], $messages);
    
        // Encontrar el Permiso por su ID
        $permission = Permission::findOrFail($id);
    
        // Convertir el nombre a mayúsculas antes de actualizar
        $permission->update([
            'name' => strtoupper($request->name),
            'description' => $request->description,
        ]);
    
        // Redirigir con un mensaje de éxito
        return redirect()->route('permissions')->with('success', 'Permiso actualizado correctamente.');
    }    

    public function destroy($id)
    {
        // Encontrar el permiso por su ID
        $permission = Permission::findOrFail($id);

        // Eliminar el permiso
        $permission->delete();

        // Redirigir con un mensaje de éxito
        return redirect()->route('permissions')->with('success', 'Permiso eliminado correctamente.');
    }
}
