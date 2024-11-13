<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\visitor;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;

class VisitorController extends Controller
{
    public function visitor(Request $request)
    {
        $daily_datas = visitor::query()
            ->distinct()
            ->get(['tanggal', 'guest', 'email', 'status'])->sortBy('tanggal')
            ->groupBy('tanggal')
            ->map(function ($group, $tanggal) {
                $guests = $group->filter(function ($item) {
                    return (is_null($item->email) || $item->email === 'Anonym') && $item->status === 0;
                });

                $users = $group->filter(function ($item) {
                    return (!is_null($item->email) && $item->email !== 'Anonym') || $item->status === 1;
                });

                $users = $users->map(function ($item) {
                    return $item->email ?: $item->guest;
                })->unique()->values()->all();

                return [
                    'tanggal' => $tanggal,
                    'guest' => $guests->pluck('guest')->all(),
                    'user' => $users,
                    'total_visitors' => count($users)+$guests->count(),
                    'total_guests' => $guests->count(),
                    'total_users' => count($users),
                ];
            })
            ->values();

        $datas = [
            'daily_datas' => $daily_datas,
        ];


        return (new OKResponse($datas, count($datas)))->toResponse();
    }

    public function new_users(Request $request){
        // get new user by created_at but only date on datetime
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
            })
            ->values();

        // $firstActionDates = visitor::query()
        //     ->select('email', DB::raw('MIN(tanggal) as first_date'))
        //     ->whereNotNull('email')
        //     ->where('email', '<>', 'Anonym')
        //     ->groupBy('email')
        //     ->pluck('first_date', 'email');

        // $datas = visitor::query()
        //     ->distinct()
        //     ->get(['email', 'tanggal'])
        //     ->groupBy('tanggal')
        //     ->map(function ($group, $tanggal) use ($firstActionDates) {
        //         $users = $group->filter(function ($item) use ($firstActionDates, $tanggal) {
        //             return isset($firstActionDates[$item->email]) && $firstActionDates[$item->email] === $tanggal;
        //         })->pluck('email')->unique()->values()->all();



        //         return [
        //             'tanggal' => $tanggal,
        //             'new_users' => $users,
        //             'total_new_users' => count($users)
        //         ];
        //     })
        //     ->values();

        return (new OKResponse($datas, count($datas)))->toResponse();
    }

    public function returning_users_weekly(Request $request){
        // KONSEP FAIZ
        $returning_user = visitor::query()
                ->distinct()
                ->get(['tanggal', 'email'])->sortBy('tanggal')
                ->groupBy('tanggal')
                ->map(function ($group, $tanggal) {
                    $users = $group->filter(function ($item) {
                        return (!is_null($item->email) && $item->email !== 'Anonym' && $item->email !== 'null') || $item->status === 1;
                    });

                    $users = $users->map(function ($item) {
                        return $item->email ?: '';
                    })->unique()->values()->all();

                    return [
                        'tanggal' => $tanggal,
                        'user' => $users,
                        'total_users' => count($users),
                    ];
                })
                ->unique()
                ->values()
                ->all();


        $returning_user = collect($returning_user)->map(function ($item) {
            // filtering untuk setiap users apakah dia tidak mengakses dalam waktu 7 hari terakhir, jika iya maka masukkan email user tersebut ke dalam array pada tanggal tersebut
            $item['user'] = collect($item['user'])->filter(function ($email) use ($item) {
                $last_7_days = Carbon::parse($item['tanggal'])->subDays(7); // ini kalo mau di ganti semisal di ganti 5 hari sebelum
                $user = visitor::query()
                    ->where('email', $email)
                    ->where('tanggal', '>=', $last_7_days)
                    ->where('tanggal', '<', $item['tanggal'])
                    ->first();

                return is_null($user);
            })->values()->all();
            return [
                'tanggal' => $item['tanggal'],
                'user' => $item['user'],
                'total_users' => count($item['user']),
            ];
        })->values()->all();

        return (new OKResponse($returning_user, count($returning_user)))->toResponse();
    }

    public function returning_users_daily(Request $request){
        $new_users = Member::query()
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
            })
            ->values();

        $returning_users = visitor::query()
            ->distinct()
            ->get(['tanggal', 'guest', 'email', 'status'])->sortBy('tanggal')
            ->groupBy('tanggal')
            ->map(function ($group, $tanggal) {
                $users = $group->filter(function ($item) {
                    return (!is_null($item->email) && $item->email !== 'Anonym') || $item->status === 1;
                });

                $users = $users->map(function ($item) {
                    return $item->email ?: $item->guest;
                })->unique()->values()->all();

                return [
                    'tanggal' => $tanggal,
                    'user' => $users,
                    'total_users' => count($users),
                ];
            })
            ->values();

        $returning_users = collect($returning_users)->map(function ($item) use ($new_users) {
            $new_user = collect($new_users)->where('tanggal', $item['tanggal'])->first();
            $new_user = $new_user ? $new_user['new_users'] : [];

            $item['user'] = collect($item['user'])->filter(function ($email) use ($new_user) {
                return !in_array($email, $new_user);
            })->values()->all();

            return [
                'tanggal' => $item['tanggal'],
                'user' => $item['user'],
                'total_users' => count($item['user']),
            ];
        })->values()->all();

        return (new OKResponse($returning_users, count($returning_users)))->toResponse();
    }

    public function returning_users_monthly(Request $request) {
        $returning_user = visitor::query()
            ->distinct()
            ->get(['tanggal', 'email'])
            ->sortBy('tanggal')
            ->groupBy(function ($item) {
                // Group by month and year (format YYYY-MM)
                return Carbon::parse($item->tanggal)->format('Y-m');
            })
            ->map(function ($group, $monthYear) {
                // Filter and get unique users as per your conditions
                $users = $group->filter(function ($item) {
                    return (!is_null($item->email) && $item->email !== 'Anonym') || $item->status === 1;
                })->map(function ($item) {
                    return $item->email ?: '';
                })->unique()->values()->all();

                return [
                    'month_year' => $monthYear,
                    'user' => $users,
                    'total_users' => count($users),
                ];
            })
            ->unique()
            ->values()
            ->all();

        $returning_user = collect($returning_user)->map(function ($item) {
            $item = collect($item);

            // Filter to check if users were not present in the previous month
            $item['user'] = collect($item['user'])->filter(function ($email) use ($item) {
                $currentMonth = Carbon::createFromFormat('Y-m', $item['month_year']);
                $lastMonthStart = $currentMonth->copy()->subMonthNoOverflow()->startOfMonth();
                $lastMonthEnd = $currentMonth->copy()->subMonthNoOverflow()->endOfMonth();

                $user = visitor::query()
                    ->where('email', $email)
                    ->whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
                    ->first();

                return is_null($user);
            })->values()->all();

            return [
                'month_year' => $item['month_year'],
                'user' => $item['user'],
                'total_users' => count($item['user']),
            ];
        })->values()->all();

        return (new OKResponse($returning_user, count($returning_user)))->toResponse();
    }

    public function returning_users_yearly(Request $request) {
        $returning_user = visitor::query()
            ->distinct()
            ->get(['tanggal', 'email'])
            ->sortBy('tanggal')
            ->groupBy(function ($item) {
                // Group by year only (format YYYY)
                return Carbon::parse($item->tanggal)->format('Y');
            })
            ->map(function ($group, $year) {
                // Filter and get unique users as per your conditions
                $users = $group->filter(function ($item) {
                    return (!is_null($item->email) && $item->email !== 'Anonym') || $item->status === 1;
                })->map(function ($item) {
                    return $item->email ?: '';
                })->unique()->values()->all();

                return [
                    'year' => $year,
                    'user' => $users,
                    'total_users' => count($users),
                ];
            })
            ->unique()
            ->values()
            ->all();

        $returning_user = collect($returning_user)->map(function ($item) {
            $item = collect($item);

            // Filter to check if users were not present in the previous year
            $item['user'] = collect($item['user'])->filter(function ($email) use ($item) {
                $currentYear = Carbon::createFromFormat('Y', $item['year']);
                $lastYearStart = $currentYear->copy()->subYear()->startOfYear();
                $lastYearEnd = $currentYear->copy()->subYear()->endOfYear();

                $user = visitor::query()
                    ->where('email', $email)
                    ->whereBetween('tanggal', [$lastYearStart, $lastYearEnd])
                    ->first();

                return is_null($user);
            })->values()->all();

            return [
                'year' => $item['year'],
                'user' => $item['user'],
                'total_users' => count($item['user']),
            ];
        })->values()->all();

        return (new OKResponse($returning_user, count($returning_user)))->toResponse();
    }
}
