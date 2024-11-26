<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Member;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;

class MemberController extends Controller
{
    public function new_users_daily(Request $request){
        $datas = Member::query()
            ->select('email', 'created_at')
            ->whereNotNull('email')
            ->where('email', '<>', 'Anonym')
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->created_at)->format('Y-m-d');
            })
            ->map(function ($group, $tanggal) {
                $users = $group->pluck('email')->unique()->values()->all();

                return [
                    'tanggal' => $tanggal,
                    'new_users' => $users,
                    'total_new_users' => count($users)
                ];
            });

        $firstDate = $datas->keys()->first() ?? Carbon::now()->format('Y-m-d');
        $currentDate = Carbon::now();

        $filledData = collect();
        for ($date = Carbon::parse($firstDate); $date->lte($currentDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $filledData->push(
                $datas->get($dateStr, [
                    'tanggal' => $dateStr,
                    'new_users' => [],
                    'total_new_users' => 0,
                ])
            );
        }

        return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
    }

    public function new_users_weekly(Request $request)
{
    // Ambil data dan kelompokkan berdasarkan tanggal, lalu berdasarkan minggu
    $datas = Member::query()
        ->select('email', 'created_at')
        ->whereNotNull('email')
        ->where('email', '<>', 'Anonym')
        ->get()
        ->groupBy(function ($item) {
            // Kelompokkan berdasarkan awal minggu (Senin)
            return Carbon::parse($item->created_at)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        })
        ->map(function ($group, $startOfWeek) {
            // Ambil pengguna unik dan tentukan akhir minggu (Minggu)
            $users = $group->pluck('email')->unique()->values()->all();
            $endOfWeek = Carbon::parse($startOfWeek)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

            return [
                'start_day' => $startOfWeek,
                'end_day' => $endOfWeek,
                'new_users' => $users,
                'total_new_users' => count($users),
            ];
        });

    // Tentukan minggu pertama dan minggu saat ini
    $firstWeekStart = Carbon::parse($datas->keys()->first() ?? Carbon::now()->startOfWeek(Carbon::MONDAY));
    $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);

    // Buat daftar minggu lengkap dari minggu pertama hingga minggu saat ini
    $filledData = collect();
    for ($date = $firstWeekStart; $date->lte($currentWeekStart); $date->addWeek()) {
        $startOfWeek = $date->format('Y-m-d');
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $filledData->push(
            $datas->get($startOfWeek, [
                'start_day' => $startOfWeek,
                'end_day' => $endOfWeek,
                'new_users' => [],
                'total_new_users' => 0,
            ])
        );
    }

    return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
}

    public function new_users_monthly(Request $request)
    {
        $datas = Member::query()
            ->select('email', 'created_at')
            ->whereNotNull('email')
            ->where('email', '<>', 'Anonym')
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->created_at)->format('Y-m');
            })
            ->map(function ($group, $tanggal) {
                $users = $group->pluck('email')->unique()->values()->all();

                return [
                    'tanggal' => $tanggal,
                    'new_users' => $users,
                    'total_new_users' => count($users)
                ];
            });

        $firstMonth = $datas->keys()->first() ?? Carbon::now()->format('Y-m');
        $currentMonth = Carbon::now();

        $filledData = collect();
        for ($date = Carbon::parse($firstMonth); $date->lte($currentMonth); $date->addMonth()) {
            $monthStr = $date->format('Y-m');
            $filledData->push(
                $datas->get($monthStr, [
                    'tanggal' => $monthStr,
                    'new_users' => [],
                    'total_new_users' => 0,
                ])
            );
        }

        return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
    }

    public function new_users_yearly(Request $request)
    {
        $datas = Member::query()
            ->select('email', 'created_at')
            ->whereNotNull('email')
            ->where('email', '<>', 'Anonym')
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->created_at)->format('Y');
            })
            ->map(function ($group, $tahun) {
                $users = $group->pluck('email')->unique()->values()->all();

                return [
                    'tanggal' => $tahun,
                    'new_users' => $users,
                    'total_new_users' => count($users),
                ];
            });

        $firstYear = $datas->keys()->first() ?? Carbon::now()->format('Y');
        $currentYear = Carbon::now()->year;

        $filledData = collect();
        for ($year = (int)$firstYear; $year <= $currentYear; $year++) {
            $yearStr = (string)$year;
            $filledData->push(
                $datas->get($yearStr, [
                    'tanggal' => $yearStr,
                    'new_users' => [],
                    'total_new_users' => 0,
                ])
            );
        }

        return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
    }
}
