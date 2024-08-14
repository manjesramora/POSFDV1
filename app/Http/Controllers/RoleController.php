<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{

    // Método para mostrar la lista de roles
    public function roles(Request $request)
    {
        $query = Role::query();

        // Filtro por nombre de rol
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'id'); // Ajusta el valor predeterminado según sea necesario
        $sortOrder = $request->input('sort_order', 'asc');

        if ($sortBy == 'role_name') {
            $query->orderBy('name', $sortOrder);
        } elseif ($sortBy == 'description') {
            $query->orderBy('description', $sortOrder);
        } elseif ($sortBy == 'permissions') {
            $query->join('role_permission', 'roles.id', '=', 'role_permission.role_id')
                ->join('permissions', 'role_permission.permission_id', '=', 'permissions.id')
                ->select('roles.*')
                ->orderBy('permissions.name', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $roles = $query->paginate(10)->appends($request->all()); // Asegúrate de pasar todos los parámetros
        $permissions = Permission::all();

        return view('roles', compact('roles', 'permissions'));
    }

    // Método para mostrar los detalles de un rol
    public function show($id)
    {
        $role = Role::findOrFail($id); // Buscar al rol por su ID
        return view('roles.show', compact('role'));
    }

    public function createRoleForm()
    {
        $permissions = Permission::all(); // Obtener todos los permisos
        return view('role', ['permissions' => $permissions]);
    }

    public function store(Request $request)
    {
        // Mensajes de error personalizados
        $messages = [
            'name.unique' => 'El nombre del rol ya existe. Por favor, elige otro.'
        ];

        // Validar los datos del formulario con mensajes personalizados
        $validatedData = $request->validate([
            'name' => 'required|unique:roles|max:100',
            'description' => 'required|string|max:200',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], $messages);


        // Crear un nuevo rol
        $role = new Role();
        $role->name = strtoupper($request->name);
        $role->description = $request->description;

        // Guardar el rol en la base de datos
        $role->save();

        // Guardar permisos seleccionados
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        // Redireccionar con mensaje de éxito
        return redirect()->route('roles')->with('success', 'Rol creado con éxito.');
    }

    public function update(Request $request, $id)
    {
        // Mensajes de error personalizados
        $messages = [
            'name.unique' => 'El nombre del rol ya existe. Por favor, elige otro.'
        ];
    
        // Validar los campos del formulario con mensajes personalizados
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'description' => 'required|string|max:200',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], $messages);
    
        // Encontrar el rol por su ID
        $role = Role::findOrFail($id);
    
        // Actualizar los datos del rol
        $role->name = strtoupper($request->name);
        $role->description = $request->description;
        $role->save();
    
        // Actualizar los permisos seleccionados
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach(); // Eliminar todos los permisos si no se seleccionó ninguno
        }
    
        // Redirigir con un mensaje de éxito
        return redirect()->route('roles')->with('success', 'Rol actualizado correctamente.');
    }
    

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

            // Obtener todos los permisos y compartirlos con todas las vistas
            $permissions = Permission::all();
            view()->share('permissions', $permissions);

            return $next($request);
        });
    }


    public function destroy($id)
    {
        // Encontrar el rol por su ID
        $role = Role::findOrFail($id);

        // Desvincular todos los permisos asociados
        $role->permissions()->detach();

        // Desvincular todos los usuarios asociados
        $role->users()->detach();

        // Eliminar el rol
        $role->delete();

        // Redirigir con un mensaje de éxito
        return redirect()->route('roles')->with('success', 'Rol y sus relaciones con usuarios eliminados correctamente.');
    }
}
