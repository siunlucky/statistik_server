<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Member;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;

class MemberController extends Controller
{
    public function new_users(Request $request)
    {
        $type = $request->get('type', 'day');
        $validTypes = ['minute', 'hour', 'day'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $endDate = Carbon::parse($request->get('end_date', Carbon::now()))->endOfDay();
        $startDate = match ($type) {
            'day' => Carbon::parse($request->get('start_date', Carbon::now()->subMonth()))->startOfDay(),
            'hour' => Carbon::parse($request->get('start_date', Carbon::now()->subWeek()))->startOfDay(),
            'minute' => Carbon::parse($request->get('start_date', Carbon::now()))->startOfDay()
        };

        if ($startDate->gt($endDate)) {
            return response()->json(['error' => 'start_date must be before end_date'], 400);
        } elseif ($type == "day" && $startDate->diffInDays($endDate) > 32) {
            return response()->json(['error' => 'Date range must not exceed 31 days for daily data'], 400);
        } elseif ($type == "hour" && $startDate->diffInDays($endDate) > 8) {
            return response()->json(['error' => 'Date range must not exceed 7 days for hourly data'], 400);
        } elseif ($type == "minute" && $startDate->diffInDays($endDate) > 1) {
            return response()->json(['error' => 'Date range must not exceed 1 day for minute-level data'], 400);
        }

        $registered_from = $request->get('registered_from', null);

        $datas = Member::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($registered_from != null) {
            $datas = $datas->where('website_register', $registered_from);
        }

        $datas = $datas->get()
            ->groupBy(function ($item) use ($type) {
                return match ($type) {
                    'minute' => Carbon::parse($item->created_at)->format('Y-m-d H:i'),
                    'hour' => Carbon::parse($item->created_at)->format('Y-m-d H'),
                    'day' => Carbon::parse($item->created_at)->format('Y-m-d')
                };
            });

        $current = $startDate->copy();
        $filledData = collect();
        while ($current->lte($endDate)) {
            $key = $current->format(match ($type) {
                'minute' => 'Y-m-d H:i',
                'hour' => 'Y-m-d H',
                'day' => 'Y-m-d'
            });

            $group = $datas->get($key, collect());
            $users = $group->values()->all();

            $filledData->push([
                $type => $key,
                'data' => $users,
                'total_new_users' => count($users)
            ]);

            $current->add(match ($type) {
                'minute' => 'minute',
                'hour' => 'hour',
                'day' => 'day'
            }, 1);
        }

        return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
    }

    public function total_verified_email(Request $request){
        $datas = [
            'total_verified_email' => Member::query()
                ->whereNotNull('email_verified_at')
                ->count(),
            'total_unverified_email' => Member::query()
                ->whereNull('email_verified_at')
                ->count(),
            'total_user' => Member::query()
                ->count()
        ];


        return (new OKResponse($datas, count($datas)))->toResponse();
    }

    public function new_users_based_on_age(Request $request)
    {
        $type = $request->get('type', 'day');
        $validTypes = ['minute', 'hour', 'day'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $endDate = Carbon::parse($request->get('end_date', Carbon::now()))->endOfDay();
        $startDate = match ($type) {
            'day' => Carbon::parse($request->get('start_date', Carbon::now()->subMonth()))->startOfDay(),
            'hour' => Carbon::parse($request->get('start_date', Carbon::now()->subWeek()))->startOfDay(),
            'minute' => Carbon::parse($request->get('start_date', Carbon::now()))->startOfDay()
        };

        if ($startDate->gt($endDate)) {
            return response()->json(['error' => 'start_date must be before end_date'], 400);
        } elseif ($type == "day" && $startDate->diffInDays($endDate) > 32) {
            return response()->json(['error' => 'Date range must not exceed 31 days for daily data'], 400);
        } elseif ($type == "hour" && $startDate->diffInDays($endDate) > 8) {
            return response()->json(['error' => 'Date range must not exceed 7 days for hourly data'], 400);
        } elseif ($type == "minute" && $startDate->diffInDays($endDate) > 1) {
            return response()->json(['error' => 'Date range must not exceed 1 day for minute-level data'], 400);
        }

        $registered_from = $request->get('registered_from', null);

        $ageGroupsTemplate = collect([
            '<18' => 0,
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55-64' => 0,
            '65>' => 0
        ]);

        $datas = Member::query()
            ->select('id', 'created_at', 'tanggal_lahir')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($registered_from, function ($query) use ($registered_from) {
                $query->where('website_register', $registered_from);
            })
            ->get()
            ->map(function ($item) {
                $umur = Carbon::parse($item->tanggal_lahir)->age;
                $item->age_group = match (true) {
                    $umur < 18 => '<18',
                    $umur >= 18 && $umur <= 24 => '18-24',
                    $umur >= 25 && $umur <= 34 => '25-34',
                    $umur >= 35 && $umur <= 44 => '35-44',
                    $umur >= 45 && $umur <= 54 => '45-54',
                    $umur >= 55 && $umur <= 64 => '55-64',
                    default => '65>'
                };
                return $item;
            })
            ->groupBy(function ($item) use ($type) {
                return match ($type) {
                    'minute' => Carbon::parse($item->created_at)->format('Y-m-d H:i'),
                    'hour' => Carbon::parse($item->created_at)->format('Y-m-d H'),
                    'day' => Carbon::parse($item->created_at)->format('Y-m-d')
                };
            });

        $current = $startDate->copy();
        $filledData = collect();
        while ($current->lte($endDate)) {
            $key = $current->format(match ($type) {
                'minute' => 'Y-m-d H:i',
                'hour' => 'Y-m-d H',
                'day' => 'Y-m-d'
            });

            $group = $datas->get($key, collect());
            $ageGroups = $group->groupBy('age_group')
                ->map(fn($users) => $users->count())
                ->union($ageGroupsTemplate)
                ->sortKeys();

            $filledData->push([
                $type => $key,
                'data' => $ageGroups->map(function ($total, $ageGroup) {
                    return [
                        'age_group' => $ageGroup,
                        'total' => $total
                    ];
                })->values(),
                'total_new_users' => $group->count()
            ]);

            $current->add(match ($type) {
                'minute' => 'minute',
                'hour' => 'hour',
                'day' => 'day'
            }, 1);
        }

        return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
    }
}
