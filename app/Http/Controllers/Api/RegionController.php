<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Otonometer\Member;
use App\Models\Otonometer\Wilayah;
use App\Http\Controllers\Controller;
use App\Models\Otonometer\MemberAktifitas;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;
use App\Classes\ApiResponse\ErrorResponse\BadRequestErrorResponse;

class RegionController extends Controller
{
    public function popular_regions(Request $request)
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

        $access_from = $request->get('registered_from', null);

        $provinsi = $request->get('provinsi', null);

        $sortBy = $request->get('sort_by', 'total');

        $defaultKeys = [
            'view' => 0,
            'download' => 0,
            'save' => 0,
            'share' => 0,
        ];

        // if ($domisili === 'true') {
        //     $data = MemberAktifitas::query()
        //     ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
        //     ->when($access_from, function ($query) use ($access_from) {
        //         return $query->where('access_from', $access_from);
        //     })
        //     ->with([
        //         'member' => function ($query) {
        //             $query->select('id', 'id_wilayah');
        //         },
        //         'member.wilayah' => function ($query) {
        //             $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        //         },
        //     ])
        //     ->whereBetween('created_at', [$start_date, $end_date])
        //     ->get()
        //     ->groupBy(function ($item) {
        //         if ($item->member->wilayah != null && $item->member->wilayah->tipe == 'propinsi') {
        //             return $item->member->wilayah->nama;
        //         } else if ($item->member->wilayah != null && ($item->member->wilayah->tipe == 'kabupaten' || $item->member->wilayah->tipe == 'kota')) {
        //             $wilayah = Wilayah::where('id', $item->member->wilayah->id_parent)->first();
        //             return $wilayah->nama;
        //         }

        //         return "Unknown";
        //     })
        //     ->map(function ($groupedItems) {
        //         return $groupedItems->groupBy('activity')->map(function ($activityGroup) {
        //             return $activityGroup->count();
        //         });
        //     });

        //     $data = $data->map(function ($group, $wilayah) use ($defaultKeys) {
        //         if ($wilayah === "Unknown") {
        //             return null;
        //         }

        //         $mergedData = collect($defaultKeys)->mapWithKeys(function ($defaultValue, $activity) use ($group) {
        //             return [$activity => $group[$activity] ?? 0];
        //         });

        //         $mergedData['total'] = $mergedData->sum();
        //         $mergedData['wilayah'] = $wilayah;

        //         return $mergedData;
        //     })->filter();

        //     $sortedData = $data->sortByDesc('total')->values();

        //     return (new OKResponse($sortedData, $sortedData->count()))->toResponse();
        // }

        $data_1 = MemberAktifitas::query()
        ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
        ->whereBetween('created_at', [$start_date, $end_date])
        ->when($access_from !== null, function ($query) use ($access_from) {
            return $query->where('access_from', $access_from);
        })
        ->with([
        'halaman' => function ($query) {
            $query->select('id','halaman_tipe', 'id_wilayah_1', 'id_wilayah_2', 'parent_1', 'parent_2');
        },
        'halaman.wilayahSatu' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        },
        'halaman.wilayahDua' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        }])
        ->get()
        ->groupBy(function ($item) use ($provinsi) {
            if ($provinsi){
                if ($item->halaman->wilayahSatu != null && ($item->halaman->wilayahSatu->tipe == 'kota' || $item->halaman->wilayahSatu->tipe == 'kabupaten')) {
                    $wilayah = Wilayah::where('id', $item->halaman->wilayahSatu->id_parent)->first();

                    if ($wilayah->nama == $provinsi) {
                        return $item->halaman->wilayahSatu->nama;
                    }
                }
            } else {
                if ($item->halaman->wilayahSatu != null && $item->halaman->wilayahSatu->tipe == 'propinsi') {
                    return $item->halaman->wilayahSatu->nama;
                } else if ($item->halaman->wilayahSatu != null && ($item->halaman->wilayahSatu->tipe == 'kabupaten' || $item->halaman->wilayahSatu->tipe == 'kota')) {
                    $wilayah = Wilayah::where('id', $item->halaman->wilayahSatu->id_parent)->first();
                    return $wilayah->nama;
                }
            }

            return "Unknown";
        })
        ->map(function ($groupedItems) {
            return $groupedItems->groupBy('activity')->map(function ($activityGroup) {
                return $activityGroup->count();
            });
        });

        $data_2 = MemberAktifitas::query()
        ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
        ->where('activity', 'view')
        ->whereBetween('created_at', [$start_date, $end_date])
        ->when($access_from !== null, function ($query) use ($access_from) {
            return $query->where('access_from', $access_from);
        })
        ->with([
        'halaman' => function ($query) {
            $query->select('id','halaman_tipe', 'id_wilayah_1', 'id_wilayah_2', 'parent_1', 'parent_2');
        },
        'halaman.wilayahSatu' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        },
        'halaman.wilayahDua' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        }])
        ->get()
        ->groupBy(function ($item) use ($provinsi) {
            if ($provinsi){
                if ($item->halaman->wilayahDua != null && ($item->halaman->wilayahDua->tipe == 'kota' || $item->halaman->wilayahDua->tipe == 'kabupaten')) {
                    $wilayah = Wilayah::where('id', $item->halaman->wilayahDua->id_parent)->first();

                    if ($wilayah->nama == $provinsi) {
                        return $item->halaman->wilayahDua->nama;
                    }
                }
            } else {
                if ($item->halaman->wilayahDua != null && $item->halaman->wilayahDua->tipe == 'propinsi') {
                    return $item->halaman->wilayahDua->nama;
                } else if ($item->halaman->wilayahDua != null && ($item->halaman->wilayahDua->tipe == 'kabupaten' || $item->halaman->wilayahDua->tipe == 'kota')) {
                    $wilayah = Wilayah::where('id', $item->halaman->wilayahDua->id_parent)->first();
                    return $wilayah->nama;
                }
            }



            return "Unknown";
        })
        ->map(function ($groupedItems) {
            return $groupedItems->groupBy('activity')->map(function ($activityGroup) {
                return $activityGroup->count();
            });
        });

        $data = $data_1->merge($data_2)->map(function ($group, $wilayah) use ($data_2, $defaultKeys) {
            if ($wilayah === "Unknown") {
                return null;
            }

            $mergedData = collect($defaultKeys)->mapWithKeys(function ($defaultValue, $activity) use ($group, $data_2, $wilayah) {
                $count1 = $group[$activity] ?? 0;
                $count2 = $data_2->get($wilayah)[$activity] ?? 0;
                return [$activity => $count1 + $count2];
            });

            $mergedData['total'] = $mergedData->sum();
            $mergedData['wilayah'] = $wilayah;

            return $mergedData;
        })->filter();

        $data_3 = Member::query()
        ->select('id', 'id_wilayah', 'created_at')
        ->whereBetween('created_at', [$start_date, $end_date])
        ->when($access_from !== null, function ($query) use ($access_from) {
            return $query->where('website_register', $access_from);
        })
        ->with([
            'wilayah' => function ($query) {
                $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
            }
        ])
        ->get()
        ->groupBy(function ($item) use ($provinsi) {
            if ($provinsi) {
                if ($item->wilayah != null && ($item->wilayah->id_parent != null)) {
                    $wilayah = Wilayah::where('id', $item->wilayah->id_parent)->first();

                    if ($wilayah->nama == $provinsi) {
                        return $item->wilayah->nama;
                    }
                }
            } else {
                if ($item->wilayah != null && $item->wilayah->tipe == 'propinsi') {
                    return $item->wilayah->nama;
                } else if ($item->wilayah != null && ($item->wilayah->tipe == 'kabupaten' || $item->wilayah->tipe == 'kota')) {
                    $wilayah = Wilayah::where('id', $item->wilayah->id_parent)->first();
                    return $wilayah->nama;
                }
            }



            return "Unknown";
        })
        ->map(function ($groupedItems) {
            return $groupedItems->count();
        });

        $data = $data->map(function ($group, $wilayah) use ($data_3, $defaultKeys) {
            if ($wilayah === "Unknown") {
                return null;
            }

            $mergedData = collect($defaultKeys)->mapWithKeys(function ($defaultValue, $activity) use ($group) {
                return [$activity => $group[$activity] ?? 0];
            });

            $mergedData['total'] = $mergedData->sum();
            $mergedData['wilayah'] = $wilayah;
            $mergedData['domisili'] = $data_3->get($wilayah, 0);

            return $mergedData;
        })->filter();

        $sortedData = $data->sortByDesc($sortBy)->values();

        return (new OKResponse($sortedData, $sortedData->count()))->toResponse();
    }

    public function regions(Request $request)
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

        $access_from = $request->get('registered_from', null);

        $provinsi = $request->get('provinsi', null);

        $sortBy = $request->get('sort_by', 'total');

        $defaultKeys = [
            'view' => 0,
            'download' => 0,
            'save' => 0,
            'share' => 0,
        ];

        // if ($domisili === 'true') {
        //     $data = MemberAktifitas::query()
        //     ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
        //     ->when($access_from, function ($query) use ($access_from) {
        //         return $query->where('access_from', $access_from);
        //     })
        //     ->with([
        //         'member' => function ($query) {
        //             $query->select('id', 'id_wilayah');
        //         },
        //         'member.wilayah' => function ($query) {
        //             $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        //         },
        //     ])
        //     ->whereBetween('created_at', [$start_date, $end_date])
        //     ->get()
        //     ->groupBy(function ($item) {
        //         if ($item->member->wilayah != null && $item->member->wilayah->tipe == 'propinsi') {
        //             return $item->member->wilayah->nama;
        //         } else if ($item->member->wilayah != null && ($item->member->wilayah->tipe == 'kabupaten' || $item->member->wilayah->tipe == 'kota')) {
        //             $wilayah = Wilayah::where('id', $item->member->wilayah->id_parent)->first();
        //             return $wilayah->nama;
        //         }

        //         return "Unknown";
        //     })
        //     ->map(function ($groupedItems) {
        //         return $groupedItems->groupBy('activity')->map(function ($activityGroup) {
        //             return $activityGroup->count();
        //         });
        //     });

        //     $data = $data->map(function ($group, $wilayah) use ($defaultKeys) {
        //         if ($wilayah === "Unknown") {
        //             return null;
        //         }

        //         $mergedData = collect($defaultKeys)->mapWithKeys(function ($defaultValue, $activity) use ($group) {
        //             return [$activity => $group[$activity] ?? 0];
        //         });

        //         $mergedData['total'] = $mergedData->sum();
        //         $mergedData['wilayah'] = $wilayah;

        //         return $mergedData;
        //     })->filter();

        //     $sortedData = $data->sortByDesc('total')->values();

        //     return (new OKResponse($sortedData, $sortedData->count()))->toResponse();
        // }

        $data_1 = MemberAktifitas::query()
        ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
        ->whereBetween('created_at', [$start_date, $end_date])
        ->when($access_from !== null, function ($query) use ($access_from) {
            return $query->where('access_from', $access_from);
        })
        ->with([
        'halaman' => function ($query) {
            $query->select('id','halaman_tipe', 'id_wilayah_1', 'id_wilayah_2', 'parent_1', 'parent_2');
        },
        'halaman.wilayahSatu' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        },
        'halaman.wilayahDua' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        }])
        ->get()
        ->groupBy(function ($item) use ($provinsi) {
            if ($item->halaman->wilayahSatu != null) {
                return $item->halaman->wilayahSatu->nama;
            }

            return "Unknown";
        })
        ->map(function ($groupedItems) {
            return $groupedItems->groupBy('activity')->map(function ($activityGroup) {
                return $activityGroup->count();
            });
        });

        $data_2 = MemberAktifitas::query()
        ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
        ->where('activity', 'view')
        ->whereBetween('created_at', [$start_date, $end_date])
        ->when($access_from !== null, function ($query) use ($access_from) {
            return $query->where('access_from', $access_from);
        })
        ->with([
        'halaman' => function ($query) {
            $query->select('id','halaman_tipe', 'id_wilayah_1', 'id_wilayah_2', 'parent_1', 'parent_2');
        },
        'halaman.wilayahSatu' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        },
        'halaman.wilayahDua' => function ($query) {
            $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
        }])
        ->get()
        ->groupBy(function ($item) use ($provinsi) {
            if ($item->halaman->wilayahDua != null) {
                return $item->halaman->wilayahDua->nama;
            }

            return "Unknown";
        })
        ->map(function ($groupedItems) {
            return $groupedItems->groupBy('activity')->map(function ($activityGroup) {
                return $activityGroup->count();
            });
        });

        $data = $data_1->merge($data_2)->map(function ($group, $wilayah) use ($data_2, $defaultKeys) {
            if ($wilayah === "Unknown") {
                return null;
            }

            $mergedData = collect($defaultKeys)->mapWithKeys(function ($defaultValue, $activity) use ($group, $data_2, $wilayah) {
                $count1 = $group[$activity] ?? 0;
                $count2 = $data_2->get($wilayah)[$activity] ?? 0;
                return [$activity => $count1 + $count2];
            });

            $mergedData['total'] = $mergedData->sum();
            $mergedData['wilayah'] = $wilayah;

            return $mergedData;
        })->filter();

        $data_3 = Member::query()
        ->select('id', 'id_wilayah', 'created_at')
        ->whereBetween('created_at', [$start_date, $end_date])
        ->when($access_from !== null, function ($query) use ($access_from) {
            return $query->where('website_register', $access_from);
        })
        ->with([
            'wilayah' => function ($query) {
                $query->select('id', 'tipe', 'nama', 'id_parent', 'id_increament');
            }
        ])
        ->get()
        ->groupBy(function ($item) use ($provinsi) {
            if ($item->wilayah != null) {
                return $item->wilayah->nama;
            }

            return "Unknown";
        })
        ->map(function ($groupedItems) {
            return $groupedItems->count();
        });

        $data = $data->map(function ($group, $wilayah) use ($data_3, $defaultKeys) {
            if ($wilayah === "Unknown") {
                return null;
            }

            $mergedData = collect($defaultKeys)->mapWithKeys(function ($defaultValue, $activity) use ($group) {
                return [$activity => $group[$activity] ?? 0];
            });

            $mergedData['total'] = $mergedData->sum();
            $mergedData['wilayah'] = $wilayah;
            $mergedData['domisili'] = $data_3->get($wilayah, 0);

            return $mergedData;
        })->filter();

        $sortedData = $data->sortByDesc($sortBy)->values();

        return (new OKResponse($sortedData, $sortedData->count()))->toResponse();
    }
}
