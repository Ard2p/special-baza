<?php

namespace Modules\PartsWarehouse\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;

class WarehousePartSetController extends Controller
{
    /**
     * Update the specified resource in storage.
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' =>'required|string',
        ]);
        $data = $request->all();
        $data['edited'] = true;
        $wps = WarehousePartSet::query()->findOrFail($id);
        $wps->update($data);

        return response()->json($wps->fresh());
    }
}
