<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Providers;

class ProviderController extends Controller
{
    public function autocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required',
            'field' => 'required',
        ]);

        $query = $request->get('query');
        $field = $request->get('field');

        $providers = Providers::where($field, 'LIKE', "%{$query}%")
            ->whereBetween('CNCDIRID', [30000000, 49999999])
            ->take(10)
            ->get();

        return response()->json($providers);
    }
}
