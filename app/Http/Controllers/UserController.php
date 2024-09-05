<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Permission;
use App\Models\StoreCostCenter;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function showDashFDForm()
    {
        $users = User::all();
        $roles = Role::all();
        $permissions = Permission::all();
        $user = Auth::user();
        $employee = Employee::find($user->employee_id);
        $userRoles = $user->roles;
        $userCenters = $user->centers;

        return view('index', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'employee' => $employee,
            'userRoles' => $userRoles,
            'userCenters' => $userCenters,
        ]);
    }

    public function showUserIndexForm()
    {
        return view('indexes');
    }

    public function index(Request $request)
    {
        $query = User::query();

        // Filtro por usuario o empleado
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            })->orWhere('username', 'like', "%{$search}%");
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Filtro por centro de costo
        if ($request->filled('cost_center')) {
            $costCenter = $request->input('cost_center');
            
            $query->whereHas('costCenters', function ($q) use ($costCenter) {
                $q->where('cost_center_id', $costCenter); // Comparar como cadena directamente
            });
        }
        

        // Filtro por estado
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'id'); // Ajusta el valor predeterminado según sea necesario
        $sortOrder = $request->input('sort_order', 'asc');

        if ($sortBy == 'employee_name') {
            $query->join('employees', 'users.employee_id', '=', 'employees.id')
                ->select('users.*')
                ->orderBy('employees.first_name', $sortOrder)
                ->orderBy('employees.last_name', $sortOrder)
                ->orderBy('employees.middle_name', $sortOrder);
        } elseif ($sortBy == 'role') {
            $query->join('users_roles', 'users.id', '=', 'users_roles.user_id')
                ->join('roles', 'users_roles.role_id', '=', 'roles.id')
                ->select('users.*')
                ->groupBy('users.id', 'users.username', 'users.password', 'users.employee_id', 'users.status', 'users.failed_login_attempts', 'users.locked', 'users.created_at', 'users.updated_at')
                ->orderByRaw("MIN(roles.name) {$sortOrder}");
        } elseif ($sortBy == 'cost_center') {
            // No hacer join para evitar duplicados
            $query->with('costCenters')->orderBy('id', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->paginate(10)->appends($request->all()); // Asegúrate de pasar todos los parámetros

        $roles = Role::all();
        $centers = StoreCostCenter::all();

        return view('users', compact('users', 'roles', 'centers'));
    }


    public function createUserForm()
    {
        $employees = Employee::all();
        $roles = Role::all();
        $centers = StoreCostCenter::all(); // Obtener todos los centros de costo

        return view('user', ['employees' => $employees, 'roles' => $roles, 'centers' => $centers]);
    }

    public function checkUsername(Request $request)
    {
        $baseUsername = $request->get('base');
        $username = ($baseUsername);
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return response()->json(['username' => $username]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|max:100',
            'password' => 'required|min:6',
            'employee_id' => 'required|exists:employees,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'center' => 'nullable|array',
            'center.*' => 'exists:store_cost_centers,id',
        ]);
    
        // Verificar si el empleado ya tiene un usuario asociado
        $existingUser = User::where('employee_id', $request->employee_id)->first();
    
        if ($existingUser) {
            return redirect()->back()->withErrors(['employee_id' => 'Este empleado ya tiene un usuario asociado.']);
        }
    
        // Crear el nuevo usuario
        $user = new User();
        $user->username = $request->username;
        $user->password = Hash::make($request->password);
        $user->employee_id = $request->employee_id;
        $user->save();
    
        // Asignar el user_id al empleado
        $employee = Employee::find($request->employee_id);
        $employee->user_id = $user->id;  // Aquí se asigna el ID del usuario recién creado al empleado
        $employee->save();
    
        // Asignar roles si los hay
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }
    
        // Asignar centros de costo si los hay
        if ($request->has('center')) {
            $user->costCenters()->sync($request->center);
        }
    
        return redirect()->route('users')->with('success', 'Usuario creado correctamente.');
    }
    

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->username = $request->input('username');
        $user->employee_id = $request->input('employee_id');

        // Actualizar roles
        $roles = $request->input('roles', []);
        $user->roles()->sync($roles);

        // Actualizar centros de costo
        $centers = $request->input('centers', []);
        $user->costCenters()->sync($centers);

        $user->save();

        return redirect()->route('users')->with('success', 'Usuario actualizado exitosamente');
    }


    public function resetPassword(User $user)
    {
        // Actualizar contraseña y campos relacionados
        $user->password = bcrypt('Ferre01@');
        $user->failed_login_attempts = 0;
        $user->locked = 0;
        $user->save();

        return response()->json(['message' => 'Contraseña restablecida con éxito.']);
    }
    protected $employees;
    protected $centers;


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
                $this->employees = Employee::all();
                view()->share('employees', $this->employees);
                $this->centers = StoreCostCenter::all();
                view()->share('centers', $this->centers);
            }

            return $next($request);
        });
    }
}
