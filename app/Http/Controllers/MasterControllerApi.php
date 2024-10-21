<?php

namespace App\Http\Controllers;

use App\Models\halaman;
use Illuminate\Http\Request;

class MasterControllerApi extends Controller
{
    //
    public function get(Request $request){
        $data = halaman::query();

        $search = $request->get('search', NULL);
        if($search != NULL && $search != ''){
            $data = $data->where('nama', 'LIKE', '%'.$search.'%');
        }

        $data = $data->get();
        return $this->responses(true, 'Berhasil mendapatkan data', $data);
    }

    function add_data(Request $request){
        $inserts = halaman::create([
            'nama' => $request->nama
        ]);

        return $this->responses(true, 'Berhasil menambahkan data');
    }

    function responses($status, $message, $data = array()){
        return json_encode(array(
            'status' => $status,
            'message' => $message,
            'data' => $data
        ), JSON_PRETTY_PRINT);
    }
}
