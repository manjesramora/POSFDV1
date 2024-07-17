<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
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

    public function employees(Request $request)
    {
        $query = Employee::query();
    
        // Filtro por búsqueda de empleado
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }
    
        // Filtro por estado (activo/inactivo)
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }
    
        // Ordenamiento
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
    
        if ($sortBy == 'colony') {
            $query->orderBy('colony', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }
    
        $employees = $query->paginate(10)->appends($request->all());
    
        return view('employees', compact('employees'));
    }
    
    // Método para mostrar los detalles de un empleado
    public function show($id)
    {
        $employee = Employee::findOrFail($id); // Buscar al empleado por su ID
        return view('employees.show', compact('employee'));
    }

    // Método para guardar un nuevo empleado
    public function store(Request $request)
    {
        // Valida los datos del formulario
        $validatedData = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'curp' => 'required',
            'rfc' => 'required',
            'birth' => 'required',
            'status' => 'required|in:0,1',
        ]);

        // Crea un nuevo objeto Employee con los datos del formulario
        $employee = new Employee();
        $employee->first_name = strtoupper($request->first_name);
        $employee->last_name = strtoupper($request->last_name);
        $employee->middle_name = strtoupper($request->middle_name);
        $employee->curp = strtoupper($request->curp);
        $employee->rfc = strtoupper($request->rfc);
        $employee->colony = strtoupper($request->colony);
        $employee->street = strtoupper($request->street);
        $employee->external_number = $request->external_number;
        $employee->internal_number = $request->internal_number;
        $employee->postal_code = $request->postal_code;
        $employee->phone = $request->phone;
        $employee->phone2 = $request->phone2;
        $employee->status = $request->status;

        // Formatea la fecha al formato deseado (DD-MM-YYYY)
        $formattedBirth = date('d-m-Y', strtotime($request->birth));
        $employee->birth = $formattedBirth;

        // Guarda el empleado en la base de datos
        $employee->save();

        // Crear usuario asociado con el mismo estado del empleado
        $user = new User();
        $user->employee_id = $employee->id;
        $user->status = $employee->status;
        $user->save();

        // Redirecciona a una ruta adecuada después de guardar el empleado
        return redirect()->route('employees')->with('success', 'Empleado y usuario creados correctamente.');
    }


    // Método para actualizar un empleado
    public function update(Request $request, $id)
    {
        // Validar los campos del formulario
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'curp' => 'required|string|max:18',
            'rfc' => 'required|string|max:13',
            'colony' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'external_number' => 'nullable|string|max:255',
            'internal_number' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:15',
            'phone2' => 'nullable|string|max:15',
            'birth' => 'nullable|date',
            'status' => 'required|in:0,1'
        ]);

        // Encontrar el empleado por su ID
        $employee = Employee::findOrFail($id);

        // Formatear la fecha al formato d-m-Y
        if ($request->has('birth')) {
            $formattedBirth = date('d-m-Y', strtotime($request->birth));
        } else {
            $formattedBirth = $employee->birth; // Mantener la fecha actual si no se envió una nueva
        }

        // Actualizar los datos del empleado
        $employee->update([
            'first_name' => strtoupper($request->first_name),
            'last_name' => strtoupper($request->last_name),
            'middle_name' => strtoupper($request->middle_name),
            'curp' => strtoupper($request->curp),
            'rfc' => strtoupper($request->rfc),
            'colony' => strtoupper($request->colony),
            'street' => strtoupper($request->street),
            'external_number' => $request->external_number,
            'internal_number' => $request->internal_number,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'phone2' => $request->phone2,
            'birth' => $formattedBirth,
            'status' => $request->status,
        ]);

        // Actualizar el estado del usuario asociado
        $user = User::where('employee_id', $employee->id)->first();
        if ($user) {
            $user->status = $employee->status;
            $user->save();
        }

        // Redirigir con un mensaje de éxito
        return redirect()->route('employees')->with('success', 'Empleado y usuario actualizados correctamente.');
    }
}
