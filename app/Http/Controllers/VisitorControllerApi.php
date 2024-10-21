<?php

namespace App\Http\Controllers;

use App\Models\guest;
use App\Models\halaman;
use App\Models\RecordRegistrasi;
use App\Models\visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VisitorControllerApi extends Controller
{
    //
    public function req_id_guest()
    {
        $id = Str::random(6);
        while (guest::where('id', $id)->count() > 0) {
            $id = Str::random(6);
        }

        $inserts = guest::create([
            'id' => $id
        ]);

        $data = guest::where('id', $id)->get();
        return $this->responses(true, 'Berhasil mendapatkan data', $data);
    }

    function add_visitor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest' => 'required',
            'status' => 'required',
            'halaman' => 'required',
            'daerah_1' => 'required',
            'parent_dataset_1' => 'required',
            'dataset_1' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responses(false, implode(",", $validator->messages()->all()));
        }

        $check_guest = guest::where('id', $request->guest)->count();
        if ($check_guest == 0) {
            return $this->responses(false, 'Gagal menambahkan data, guest tidak ditemukan');
        }

        $check_halaman = halaman::where('id', $request->halaman)->count();
        if ($check_halaman == 0) {
            return $this->responses(false, 'Gagal menambahkan data, halaman tidak ditemukan');
        }

        $waktu = time();

        $age = NULL;
        if ($request->post('usia', NULL) != NULL && $request->usia != '') {
            // $birthDate = new DateTime($request->usia);
            // $currentDate = new DateTime('now');
            // $birthDate = new DateTime($request->usia);
            // $currentDate = new DateTime('now');

            // Calculate the difference
            // $age = $currentDate->diff($birthDate);
            $age = date_diff(date_create($request->usia), date_create('now'));
        }

        $inserts = visitor::create([
            'guest' => $request->guest,
            'tanggal' => date("Y-m-d", $waktu),
            'jam' => date("H", $waktu),
            'menit' => date("i", $waktu),
            'detik' => date("s", $waktu),
            'status' => $request->status,
            'lat' => $request->lat,
            'long' => $request->long,
            'halaman' => $request->halaman,
            'daerah_1' => $request->daerah_1,
            'parent_dataset_1' => $request->parent_dataset_1,
            'dataset_1' => $request->dataset_1,
            'daerah_2' => $request->daerah_2,
            'parent_dataset_2' => $request->parent_dataset_2,
            'dataset_2' => $request->dataset_2,
            'email' => $request->email,
            'website_akses' => $request->website_akses
            // 'usia' => $age->y,
            // 'gender' => $request->gender
        ]);

        return $this->responses(true, 'Berhasil menambahkan data');
    }

    function get(Request $request)
    {
        $data = visitor::query();

        $tanggal = $request->get('tanggal', NULL);
        if ($tanggal != NULL && $tanggal != '') {
            $data = $data->where('tanggal', $tanggal);
        }

        $jam = $request->get('jam', NULL);
        if ($jam != NULL && $jam != '') {
            $data = $data->where('jam', $jam);
        }

        $status = $request->get('status', NULL);
        if ($status != NULL && $status != '') {
            $data = $data->where('status', $status);
        }

        $jam = $request->get('jam', NULL);
        if ($jam != NULL && $jam != '') {
            $data = $data->where('jam', $jam);
        }

        $halaman = $request->get('halaman', NULL);
        if ($halaman != NULL && $halaman != '') {
            $data = $data->where('halaman', $halaman);
        }

        $data = $data->get();
        return $this->responses(true, 'Berhasil mendapatkan data', $data);
    }

    function record_registrasi(Request $request){
        $validator = Validator::make($request->all(), [
            'guest' => 'required',
            'gender' => 'required',
            'age' => 'required',
            'province' => 'required',
            'city' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->responses(false, implode(",", $validator->messages()->all()));
        }

        $check_guest = guest::where('id', $request->guest)->count();
        if ($check_guest == 0) {
            return $this->responses(false, 'Gagal menambahkan data, guest tidak ditemukan');
        }

        $inserts = RecordRegistrasi::create([
            'guest' => $request->guest,
            'gender' => $request->gender,
            'age' => $request->age,
            'province' => $request->province,
            'city' => $request->city
        ]);

        return $this->responses(true, 'Berhasil menambahkan data');
    }

    function responses($status, $message, $data = array())
    {
        return json_encode(array(
            'status' => $status,
            'message' => $message,
            'data' => $data
        ), JSON_PRETTY_PRINT);
    }
}
