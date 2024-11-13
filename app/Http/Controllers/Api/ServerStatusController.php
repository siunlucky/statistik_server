<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;
use App\Models\ServerStatus;

class ServerStatusController extends Controller
{
    public function index(Request $request){
        $datas = ServerStatus::query();

        $datas = $datas->orderByDesc('timestamp');

        if ($request->query('is_latest') == "true" || $request->query('is_latest') == "True") {
            $datas = $datas->first();

            return (new OKResponse($datas, 1))->toResponse();
        }

        $datas = $datas->get();

        if ($request->query('min_cpu_usage')) {
            $datas = $datas->where('cpu_usage', '>=', $request->query('min_cpu_usage'));
        }

        if ($request->query('max_cpu_usage')) {
            $datas = $datas->where('cpu_usage', '<=', $request->query('max_cpu_usage'));
        }

        if ($request->query('min_memory_usage')) {
            $datas = $datas->where('memory_usage', '>=', $request->query('min_memory_usage'));
        }

        if ($request->query('max_memory_usage')) {
            $datas = $datas->where('memory_usage', '<=', $request->query('max_memory_usage'));
        }

        if ($request->query('min_disk_usage')) {
            $datas = $datas->where('disk_usage', '>=', $request->query('min_disk_usage'));
        }

        if ($request->query('max_disk_usage')) {
            $datas = $datas->where('disk_usage', '<=', $request->query('max_disk_usage'));
        }

        return (new OKResponse($datas, count($datas)))->toResponse();
    }
}
