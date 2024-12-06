<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponse\ErrorResponse\BadRequestErrorResponse;
use Carbon\Carbon;
use App\Models\Otonometer\Member;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;
use App\Models\Otonometer\MemberAktifitas;

class MemberController extends Controller
{
    public function users(Request $request){
        $start_date = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null;
        $end_date = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now()->endOfDay();

        if ($start_date && $end_date) {
            if ($start_date->gt($end_date)) {
                return (new BadRequestErrorResponse('start_date must be before end_date'))->toResponse();
            }
        }

        $datas = Member::query();

        if ($start_date && $end_date) {
            $datas = $datas->whereBetween('created_at', [$start_date, $end_date]);
        } elseif ($start_date) {
            $datas = $datas->where('created_at', '>=', $start_date);
        } elseif ($end_date) {
            $datas = $datas->where('created_at', '<=', $end_date);
        }

        $registered_from = $request->get('registered_from', null);

        if ($registered_from != null) {
            $datas = $datas->where('website_register', $registered_from);
        }

        $datas = $datas->with(['wilayah.province', 'lastActivity'])->get();

        return (new OKResponse($datas, count($datas)))->toResponse();
    }

    public function new_users(Request $request){
        $type = $request->get('type', 'day');
        $validTypes = ['minute', 'hour', 'day'];

        if (!in_array($type, $validTypes)) {
            return (new BadRequestErrorResponse('Invalid Type'))->toResponse();
        }

        $end_date = Carbon::parse($request->get('end_date', Carbon::now()));
        $start_date = match ($type) {
            'day' => Carbon::parse($request->get('start_date', Carbon::now()->subMonth())),
            'hour' => Carbon::parse($request->get('start_date', Carbon::now()->subWeek())),
            'minute' => Carbon::parse($request->get('start_date', Carbon::now()))
        };

        if ($start_date->gt($end_date)) {
            return response()->json(['error' => 'start_date must be before end_date'], 400);
        } elseif ($type == "day" && $start_date->diffInDays($end_date) > 32) {
            return response()->json(['error' => 'Date range must not exceed 31 days for daily data'], 400);
        } elseif ($type == "hour" && $start_date->diffInDays($end_date) > 8) {
            return response()->json(['error' => 'Date range must not exceed 7 days for hourly data'], 400);
        } elseif ($type == "minute" && $start_date->diffInDays($end_date) > 1) {
            return response()->json(['error' => 'Date range must not exceed 1 day for minute-level data'], 400);
        }

        $registered_from = $request->get('registered_from', null);

        $datas = Member::query()
            ->whereBetween('created_at', [$start_date, $end_date]);

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

        $current = $start_date->copy();
        $filledData = collect();
        while ($current->lte($end_date)) {
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
        $start_date = $request->get('start_date') ? Carbon::parse($request->get('start_date'))->startOfDay() : null;
        $end_date = $request->get('end_date') ? Carbon::parse($request->get('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        if ($start_date && $end_date) {
            if ($start_date->gt($end_date)) {
                return (new BadRequestErrorResponse('start_date must be before end_date'))->toResponse();
            }
        }

        $total_verified_email = Member::query()
            ->whereNotNull('email_verified_at');

        $total_unverified_email = Member::query()
            ->whereNull('email_verified_at');

        $total_user = Member::query();

        if ($start_date && $end_date) {
            $total_verified_email = $total_verified_email->whereBetween('created_at', [$start_date, $end_date]);
            $total_unverified_email = $total_unverified_email->whereBetween('created_at', [$start_date, $end_date]);
            $total_user = $total_user->whereBetween('created_at', [$start_date, $end_date]);
        } elseif ($start_date) {
            $total_verified_email = $total_verified_email->where('created_at', '>=', $start_date);
            $total_unverified_email = $total_unverified_email->where('created_at', '>=', $start_date);
            $total_user = $total_user->where('created_at', '>=', $start_date);
        } elseif ($end_date) {
            $total_verified_email = $total_verified_email->where('created_at', '<=', $end_date);
            $total_unverified_email = $total_unverified_email->where('created_at', '<=', $end_date);
            $total_user = $total_user->where('created_at', '<=', $end_date);
        }

        $registered_from = $request->get('registered_from', null);

        if ($registered_from != null) {
            $total_verified_email = $total_verified_email->where('website_register', $registered_from);
            $total_unverified_email = $total_unverified_email->where('website_register', $registered_from);
            $total_user = $total_user->where('website_register', $registered_from);
        }

        $total_verified_email = $total_verified_email->count();
        $total_unverified_email = $total_unverified_email->count();
        $total_user = $total_user->count();

        $datas = [
            'total_verified_email' => $total_verified_email,
            'total_unverified_email' => $total_unverified_email,
            'total_user' => $total_user
        ];


        return (new OKResponse($datas, count($datas)))->toResponse();
    }

    public function user_growth(Request $request)
    {
        $start_date = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null;
        $end_date = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now()->endOfDay();

        if ($start_date && $end_date) {
            if ($start_date->gt($end_date)) {
                return (new BadRequestErrorResponse('start_date must be before end_date'))->toResponse();
            }
        }

        $datas = Member::query();

        if ($start_date && $end_date) {
            $datas = $datas->whereBetween('created_at', [$start_date, $end_date]);
        } elseif ($start_date) {
            $datas = $datas->where('created_at', '>=', $start_date);
        } elseif ($end_date) {
            $datas = $datas->where('created_at', '<=', $end_date);
        }

        $registered_from = $request->get('registered_from', null);

        if ($registered_from != null) {
            $datas = $datas->where('website_register', $registered_from);
        }

        $datas = $datas->get();

        $age_groups = [
            ["age_group" => "<18", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0],
            ["age_group" => "18-24", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0],
            ["age_group" => "25-34", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0],
            ["age_group" => "35-44", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0],
            ["age_group" => "45-54", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0],
            ["age_group" => "55-64", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0],
            ["age_group" => "65>", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0],
            ["age_group" => "Unknown", 'men' => 0, 'woman' => 0, 'unknown_gender' => 0, "total" => 0]
        ];

        foreach ($datas as $data) {
            if ($data->age < 18) {
                $age_groups[0]['total']++;
                if ($data->gender == 'L') {
                    $age_groups[0]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[0]['woman']++;
                } else {
                    $age_groups[0]['unknown_gender']++;
                }
            } elseif ($data->age >= 18 && $data->age <= 24) {
                $age_groups[1]['total']++;
                if ($data->gender == 'L'){
                    $age_groups[1]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[1]['woman']++;
                } else {
                    $age_groups[1]['unknown_gender']++;
                }
            } elseif ($data->age >= 25 && $data->age <= 34) {
                $age_groups[2]['total']++;
                if ($data->gender == 'L'){
                    $age_groups[2]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[2]['woman']++;
                } else {
                    $age_groups[2]['unknown_gender']++;
                }
            } elseif ($data->age >= 35 && $data->age <= 44) {
                $age_groups[3]['total']++;
                if ($data->gender == 'L'){
                    $age_groups[3]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[3]['woman']++;
                } else {
                    $age_groups[3]['unknown_gender']++;
                }
            } elseif ($data->age >= 45 && $data->age <= 54) {
                $age_groups[4]['total']++;
                if ($data->gender == 'L'){
                    $age_groups[4]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[4]['woman']++;
                } else {
                    $age_groups[4]['unknown_gender']++;
                }
            } elseif ($data->age >= 55 && $data->age <= 64) {
                $age_groups[5]['total']++;
                if ($data->gender == 'L'){
                    $age_groups[5]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[5]['woman']++;
                } else {
                    $age_groups[5]['unknown_gender']++;
                }
            } elseif ($data->age >= 65) {
                $age_groups[6]['total']++;
                if ($data->gender == 'L'){
                    $age_groups[6]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[6]['woman']++;
                } else {
                    $age_groups[6]['unknown_gender']++;
                }
            } else {
                $age_groups[7]['total']++;
                if ($data->gender == 'L'){
                    $age_groups[7]['men']++;
                } else if ($data->gender == 'P'){
                    $age_groups[7]['woman']++;
                } else {
                    $age_groups[7]['unknown_gender']++;
                }
            }
        }

        return (new OKResponse($age_groups, count($age_groups)))->toResponse();
    }

    public function returning_users(Request $request){
        $type = $request->get('type', 'day');
        $validTypes = ['minute', 'hour', 'day'];

        if (!in_array($type, $validTypes)) {
            return (new BadRequestErrorResponse('Invalid Type'))->toResponse();
        }

        $end_date = Carbon::parse($request->get('end_date', Carbon::now()));
        $start_date = match ($type) {
            'day' => Carbon::parse($request->get('start_date', Carbon::now()->subMonth())),
            'hour' => Carbon::parse($request->get('start_date', Carbon::now()->subWeek())),
            'minute' => Carbon::parse($request->get('start_date', Carbon::now()))
        };

        if ($start_date->gt($end_date)) {
            return (new BadRequestErrorResponse('start_date must be before end_date'))->toResponse();
        } elseif ($type === 'day' && $start_date->diffInDays($end_date) > 31) {
            return (new BadRequestErrorResponse('Date range must not exceed 31 days for daily data'))->toResponse();
        } elseif ($type === 'hour' && $start_date->diffInDays($end_date) > 7) {
            return (new BadRequestErrorResponse('Date range must not exceed 7 days for hourly data'))->toResponse();
        } elseif ($type === 'minute' && $start_date->diffInDays($end_date) > 1) {
            return (new BadRequestErrorResponse('Date range must not exceed 1 day for minute-level data'))->toResponse();
        }

        $datas = MemberAktifitas::query()
            ->whereBetween('created_at', [$start_date, $end_date])
            ->when($request->has('registered_from'), function ($query) use ($request) {
                $query->where('access_from', $request->get('registered_from'));
            })
            ->whereNotNull('id_member')
            ->with('member')
            ->get()
            ->groupBy(function ($item) use ($type) {
                return match ($type) {
                    'minute' => Carbon::parse($item->created_at)->format('Y-m-d H:i'),
                    'hour' => Carbon::parse($item->created_at)->format('Y-m-d H'),
                    'day' => Carbon::parse($item->created_at)->format('Y-m-d'),
                };
            });

        $current = $start_date->copy();
        $filledData = collect();

        while ($current->lte($end_date)) {
            $key = $current->format(match ($type) {
                'minute' => 'Y-m-d H:i',
                'hour' => 'Y-m-d H',
                'day' => 'Y-m-d',
            });

            $group = $datas->get($key, collect())
                ->unique('id_member');

            // $users = $group->values()->all();

            $users = $group->filter(function ($item) use ($key, $type) {
                $registrationTime = Carbon::parse($item->member->created_at);
                $keyTime = Carbon::createFromFormat(match ($type) {
                    'minute' => 'Y-m-d H:i',
                    'hour' => 'Y-m-d H',
                    'day' => 'Y-m-d',
                }, $key);

                return $registrationTime->lt($keyTime);
            })->values()->map(function ($item) {
                return $item->member;
            });



            $filledData->push([
                $type => $key,
                'data' => $users,
                'total_returning_users' => count($users),
            ]);

            $current->add(match ($type) {
                'minute' => 'minute',
                'hour' => 'hour',
                'day' => 'day',
            }, 1);
        }

        return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
    }

    public function visitors(Request $request)
{
    $type = $request->get('type', 'day');
    $validTypes = ['minute', 'hour', 'day'];

    if (!in_array($type, $validTypes)) {
        return (new BadRequestErrorResponse('Invalid Type'))->toResponse();
    }

    $end_date = Carbon::parse($request->get('end_date', Carbon::now()));
    $start_date = match ($type) {
        'day' => Carbon::parse($request->get('start_date', Carbon::now()->subMonth())),
        'hour' => Carbon::parse($request->get('start_date', Carbon::now()->subWeek())),
        'minute' => Carbon::parse($request->get('start_date', Carbon::now()->subDay()))
    };

    if ($start_date->gt($end_date)) {
        return (new BadRequestErrorResponse('start_date must be before end_date'))->toResponse();
    } elseif ($type === 'day' && $start_date->diffInDays($end_date) > 31) {
        return (new BadRequestErrorResponse('Date range must not exceed 31 days for daily data'))->toResponse();
    } elseif ($type === 'hour' && $start_date->diffInDays($end_date) > 7) {
        return (new BadRequestErrorResponse('Date range must not exceed 7 days for hourly data'))->toResponse();
    } elseif ($type === 'minute' && $start_date->diffInDays($end_date) > 1) {
        return (new BadRequestErrorResponse('Date range must not exceed 1 day for minute-level data'))->toResponse();
    }

    $access_from = $request->get('registered_from', null);

    $datas = MemberAktifitas::query()
        ->whereBetween('created_at', [$start_date, $end_date])
        ->when($access_from, function ($query) use ($access_from) {
            $query->where('access_from', $access_from);
        })
        ->with('member')
        ->get()
        ->groupBy(function ($item) use ($type) {
            return match ($type) {
                'minute' => Carbon::parse($item->created_at)->format('Y-m-d H:i'),
                'hour' => Carbon::parse($item->created_at)->format('Y-m-d H'),
                'day' => Carbon::parse($item->created_at)->format('Y-m-d'),
            };
        });

    $current = $start_date->copy();
    $filledData = collect();

    while ($current->lte($end_date)) {
        $key = $current->format(match ($type) {
            'minute' => 'Y-m-d H:i',
            'hour' => 'Y-m-d H',
            'day' => 'Y-m-d',
        });

        $group = $datas->get($key, collect());

        $members = $group->whereNotNull('id_member')->unique('id_member');
        $guests = $group->whereNull('id_member')->unique('guest');

        $filledData->push([
            $type => $key,
            'members' => $members->values()->map(function ($item) {
                return $item->member;
            }),
            'guests' => $guests->values()->all(),
            'total_members' => $members->count(),
            'total_guests' => $guests->count(),
            'total_visitors' => $members->count() + $guests->count(),
        ]);

        $current->add(match ($type) {
            'minute' => 'minute',
            'hour' => 'hour',
            'day' => 'day',
        }, 1);
    }

    return (new OKResponse($filledData->values(), $filledData->count()))->toResponse();
}




}
