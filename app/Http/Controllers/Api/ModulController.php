<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Otonometer\Halaman;
use App\Http\Controllers\Controller;
use App\Models\Otonometer\MasterBidang;
use App\Models\Otonometer\MemberAktifitas;
use App\Classes\ApiResponse\SuccessResponse\OKResponse;
use App\Classes\ApiResponse\ErrorResponse\BadRequestErrorResponse;

class ModulController extends Controller
{
    public function module(Request $request)
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

        $tipe_halaman = $request->get('tipe_halaman', null);
        if ($tipe_halaman == 1) {
            $tipe_halaman = 'JELAJAH';
        } else if ($tipe_halaman == 2) {
            $tipe_halaman = 'UTAK-ATIK';
        } else if ($tipe_halaman == 3) {
            $tipe_halaman = 'BERKACA';
        } else {
            $tipe_halaman = null;
        }

        $tahun = $request->get('tahun', null);

        $dataset_1 = $request->get('dataset_1', null);

        if ($dataset_1 == 1) {
            $dataset_1 = 'Keuangan';
        } else if ($dataset_1 == 2) {
            $dataset_1 = 'Ekonomi';
        } else if ($dataset_1 == 3) {
            $dataset_1 = 'Statistik';
        } else {
            $dataset_1 = null;
        }

        $dataset_2 = $request->get('dataset_2', null);

        if ($tipe_halaman == null) {
            $datas = MemberAktifitas::query()
                ->where('activity', 'view')
                ->whereBetween('created_at', [$start_date, $end_date])
                ->when($access_from, function ($query) use ($access_from) {
                    return $query->where('access_from', $access_from);
                })
                ->with(['halaman' => function ($query) {
                    $query->select('id','halaman_tipe', 'dataset_1', 'dataset_2', 'parent_1', 'parent_2');
                }])
                ->when($tahun, function ($query) use ($tahun) {
                    return $query->whereHas('halaman', function ($subQuery) use ($tahun) {
                        $subQuery->where('tahun', $tahun);
                    });
                })
                ->get()
                ->groupBy(function ($item) {
                    return $item->halaman->halaman_tipe;
                })
                ->map(function ($group, $key) {
                    return [
                        'dataset' => $key,
                        'total_view' => $group->count(),
                    ];
                })
                ->values();

            $data_case = [
                [
                    'dataset' => 'JELAJAH',
                    'total_view' => 0,
                ],
                [
                    'dataset' => 'UTAK-ATIK',
                    'total_view' => 0,
                ],
                [
                    'dataset' => 'BERKACA',
                    'total_view' => 0,
                ],
            ];

            $datasArray = $datas->keyBy('dataset');

            $mergedResult = collect($data_case)->map(function ($case) use ($datasArray) {
                $dataset = $case['dataset'];
                if ($datasArray->has($dataset)) {
                    $case['total_view'] = $datasArray[$dataset]['total_view'];
                }
                return $case;
            });

            return (new OKResponse($mergedResult, $mergedResult->count()))->toResponse();
        }

        if ($dataset_1 == null) {
            $datas = MemberAktifitas::query()
                ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
                ->where('activity', 'view')
                ->whereBetween('created_at', [$start_date, $end_date])
                ->when($access_from, function ($query) use ($access_from) {
                    return $query->where('access_from', $access_from);
                })
                ->when($tipe_halaman, function ($query) use ($tipe_halaman) {
                    return $query->whereHas('halaman', function ($query) use ($tipe_halaman) {
                        $query->where('halaman_tipe', $tipe_halaman);
                    });
                })
                ->with(['halaman' => function ($query) {
                    $query->select('id','halaman_tipe', 'dataset_1', 'dataset_2', 'parent_1', 'parent_2');
                    }
                ])
                ->when($tahun, function ($query) use ($tahun) {
                    return $query->whereHas('halaman', function ($subQuery) use ($tahun) {
                        $subQuery->where('tahun', $tahun);
                    });
                })
                ->get();


                // return (new OKResponse($datas, 1))->toResponse();

                $groupedByDataset1 = $datas->groupBy(function ($item) {
                    if ($item->halaman->parent_1) {
                        $parent = MasterBidang::find($item->halaman->parent_1);
                        while ($parent->id_parent) {
                            $parent = MasterBidang::find($parent->id_parent);
                        }
                        return $parent->nama ?? "Unknown";
                    }

                    return isset($item->halaman->dataset_1_data[0]) ? $item->halaman->dataset_1_data[0]->nama : "Unknown";
                });

                $groupedByDataset2 = $datas->groupBy(function ($item) {
                    if ($item->halaman->parent_2) {
                        $parent = MasterBidang::find($item->halaman->parent_2);
                        while ($parent->id_parent) {
                            $parent = MasterBidang::find($parent->id_parent);
                        }
                        return $parent->nama ?? "Unknown";
                    }

                    return isset($item->halaman->dataset_2_data[0]) ? $item->halaman->dataset_2_data[0]->nama : "Unknown";
                });

                $processedDataset1 = $groupedByDataset1->map(function ($group, $key) {
                    return [
                        'dataset' => $key,
                        'total_view' => $group->count(),
                    ];
                });

                $processedDataset2 = $groupedByDataset2->map(function ($group, $key) {
                    return [
                        'dataset' => $key,
                        'total_view' => $group->count(),
                    ];
                });

                $mergedResult = $processedDataset1->merge($processedDataset2);

                $mergedResult = $mergedResult->filter(function ($item) {
                    return $item['dataset'] !== "Unknown";
                });

                $finalResult = $mergedResult->groupBy('dataset')->map(function ($group) {
                    return [
                        'dataset' => $group->first()['dataset'], // Use the first group's dataset name
                        'total_view' => $group->sum('total_view'), // Sum the total_view counts
                    ];
                })->values();

            $data_case = [
                [
                    'dataset' => 'Keuangan',
                    'total_view' => 0,
                ],
                [
                    'dataset' => 'Ekonomi',
                    'total_view' => 0,
                ],
                [
                    'dataset' => 'Statistik',
                    'total_view' => 0,
                ],
            ];

            $datasArray = $finalResult->keyBy('dataset');

            $mergedResult = collect($data_case)->map(function ($case) use ($datasArray) {
                $dataset = $case['dataset'];
                if ($datasArray->has(key: $dataset)) {
                    $case['total_view'] = $datasArray[$dataset]['total_view'];
                }
                return $case;
            });

            return (new OKResponse($mergedResult, $mergedResult->count()))->toResponse();
        }

        if ($dataset_2 == null) {
            $datas = MemberAktifitas::query()
                ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
                ->where('activity', 'view')
                ->whereBetween('created_at', [$start_date, $end_date])
                ->when($access_from, function ($query) use ($access_from) {
                    return $query->where('access_from', $access_from);
                })
                ->when($tipe_halaman, function ($query) use ($tipe_halaman) {
                    return $query->whereHas('halaman', function ($query) use ($tipe_halaman) {
                        $query->where('halaman_tipe', $tipe_halaman);
                    });
                })
                ->with(['halaman' => function ($query) {
                    $query->select('id','halaman_tipe', 'dataset_1', 'dataset_2', 'parent_1', 'parent_2');
                }])
                ->when($tahun, function ($query) use ($tahun) {
                    return $query->whereHas('halaman', function ($subQuery) use ($tahun) {
                        $subQuery->where('tahun', $tahun);
                    });
                })
                ->get();


                // return (new OKResponse($datas, 1))->toResponse();

                $groupedByDataset1 = $datas->groupBy(function ($item) use ($dataset_1) {
                    if ($item->halaman->parent_1) {
                        // dd("inside");
                        $parent = MasterBidang::find($item->halaman->parent_1);
                        while (MasterBidang::find($parent->id_parent)->id_parent) {
                            $parent = MasterBidang::find($parent->id_parent);
                        }

                        if (MasterBidang::find($parent->id_parent)->nama === $dataset_1){
                            return $parent->nama;
                        } else {
                            return "Unknown";
                        }
                    }

                    return (isset($item->halaman->dataset_1_data[1]) && $item->halaman->dataset_1_data[0]->nama == $dataset_1) ? $item->halaman->dataset_1_data[1]->nama : "Unknown";
                });

                $groupedByDataset2 = $datas->groupBy(function ($item) use ($dataset_1) {
                    if ($item->halaman->parent_2) {
                        $parent = MasterBidang::find($item->halaman->parent_2);
                        while (MasterBidang::find($parent->id_parent)->id_parent) {
                            $parent = MasterBidang::find($parent->id_parent);
                        }

                        if (MasterBidang::find($parent->id_parent)->nama == $dataset_1){
                            return $parent->nama;
                        } else {
                            return "Unknown";
                        }
                    }

                    return (isset($item->halaman->dataset_2_data[1]) && $item->halaman->dataset_2_data[0]->nama == $dataset_1) ? $item->halaman->dataset_2_data[1]->nama : "Unknown";
                });

                $processedDataset1 = $groupedByDataset1->map(function ($group, $key) {
                    return [
                        'dataset' => $key,
                        'total_view' => $group->count(),
                    ];
                });

                $processedDataset2 = $groupedByDataset2->map(function ($group, $key) {
                    return [
                        'dataset' => $key,
                        'total_view' => $group->count(),
                    ];
                });

                $mergedResult = $processedDataset1->merge($processedDataset2);

                $mergedResult = $mergedResult->filter(function ($item) {
                    return $item['dataset'] !== "Unknown";
                });

                $finalResult = $mergedResult->groupBy('dataset')->map(function ($group) {
                    return [
                        'dataset' => $group->first()['dataset'],
                        'total_view' => $group->sum('total_view'),
                    ];
                })->values();

            // $data_case =

            return (new OKResponse($finalResult, $finalResult->count()))->toResponse();
        }

        $datas = MemberAktifitas::query()
            ->select('id', 'id_member', 'activity', 'created_at', 'access_from', 'id_halaman')
            ->where('activity', 'view')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->when($access_from, function ($query) use ($access_from) {
                return $query->where('access_from', $access_from);
            })
            ->when($tipe_halaman, function ($query) use ($tipe_halaman) {
                return $query->whereHas('halaman', function ($query) use ($tipe_halaman) {
                    $query->where('halaman_tipe', $tipe_halaman);
                });
            })
            ->with(['halaman' => function ($query) {
                $query->select('id','halaman_tipe', 'dataset_1', 'dataset_2', 'parent_1', 'parent_2');
            }])
            ->when($tahun, function ($query) use ($tahun) {
                return $query->whereHas('halaman', function ($subQuery) use ($tahun) {
                    $subQuery->where('tahun', $tahun);
                });
            })
            ->get();


        // return (new OKResponse($datas, 1))->toResponse();

        $groupedByDataset1 = $datas->groupBy(function ($item) use ($dataset_1, $dataset_2) {
            if ($item->halaman->parent_1) {
                // dd("inside");
                $parent = MasterBidang::find($item->halaman->parent_1);
                while (MasterBidang::find($parent->id_parent)->id_parent) {
                    $parent = MasterBidang::find($parent->id_parent);
                }

                if (MasterBidang::find($parent->id_parent)->nama !== $dataset_1){
                    return "Unknown";
                }

                if ($parent->nama !== $dataset_2){
                    return "Unknown";
                }
                // dd($item->halaman->dataset_1_data);
                if (!isset($item->halaman->dataset_1_data[0])){
                    return "Unknown";
                }

                $dataset_1_temp = $item->halaman->dataset_1_data[0];

                while ($dataset_1_temp['level'] > 3){
                    $dataset_1_temp = MasterBidang::find($dataset_1_temp['id_parent']);
                };

                return $dataset_1_temp['nama'];
            }

            return (isset($item->halaman->dataset_1_data[2]) && $item->halaman->dataset_1_data[0]->nama == $dataset_1 && $item->halaman->dataset_1_data[1]->nama == $dataset_2) ? $item->halaman->dataset_1_data[2]->nama : "Unknown";
        });

        $groupedByDataset2 = $datas->groupBy(function ($item) use ($dataset_1, $dataset_2) {
            if ($item->halaman->parent_2) {
                $parent = MasterBidang::find($item->halaman->parent_2);
                while (MasterBidang::find($parent->id_parent)->id_parent) {
                    $parent = MasterBidang::find($parent->id_parent);
                }

                if (MasterBidang::find($parent->id_parent)->nama !== $dataset_1){
                    return "Unknown";
                }

                if ($parent->nama !== $dataset_2){
                    return "Unknown";
                }

                if (!isset($item->halaman->dataset_2_data[0])){
                    return "Unknown";
                }

                $dataset_2_temp = $item->halaman->dataset_2_data[0];

                while ($dataset_2_temp['level'] > 3){
                    $dataset_2_temp = MasterBidang::find($dataset_2_temp['id_parent']);
                };

                return $dataset_2_temp['nama'];
            }

            return (isset($item->halaman->dataset_2_data[2]) && $item->halaman->dataset_2_data[1]->nama == $dataset_1 && $item->halaman->dataset_1_data[1]->nama == $dataset_2) ? $item->halaman->dataset_2_data[2]->nama : "Unknown";
        });

        $processedDataset1 = $groupedByDataset1->map(function ($group, $key) {
            return [
                'dataset' => $key,
                'total_view' => $group->count(),
            ];
        });

        $processedDataset2 = $groupedByDataset2->map(function ($group, $key) {
            return [
                'dataset' => $key,
                'total_view' => $group->count(),
            ];
        });

        $mergedResult = $processedDataset1->merge($processedDataset2);

        $mergedResult = $mergedResult->filter(function ($item) {
            return $item['dataset'] !== "Unknown";
        });

        $finalResult = $mergedResult->groupBy('dataset')->map(function ($group) {
            return [
                'dataset' => $group->first()['dataset'],
                'total_view' => $group->sum('total_view'),
            ];
        })->values();

        return (new OKResponse($finalResult, $finalResult->count()))->toResponse();
    }
}
