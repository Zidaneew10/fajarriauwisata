<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::with([
                'busTrip.routeSegments.stop',
                'busTrip.busClasses.facilities',
            ])
            ->withCount([
                'seats as available_seats' => function ($q) {
                    $q->where('is_available', true);
                }
            ])
            ->bookable()
            ->when($request->date, function ($q) use ($request) {
                $q->whereDate('departure_date', $request->date);
            })
            ->when($request->origin, function ($q) use ($request) {
                $q->whereHas('busTrip.routeSegments', function ($q2) use ($request) {
                    $q2->where('sequence', 1)
                        ->whereHas('stop', function ($q3) use ($request) {
                            $q3->where('city', 'like', "%{$request->origin}%");
                        });
                });
            })
            ->when($request->destination, function ($q) use ($request) {
                $q->whereHas('busTrip.routeSegments', function ($q2) use ($request) {
                    $q2->where('sequence', '>', 1)
                        ->whereHas('stop', function ($q3) use ($request) {
                            $q3->where('city', 'like', "%{$request->destination}%");
                        });
                });
            })
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->paginate(20);

        $schedules->getCollection()->transform(function ($schedule) {

            if ($schedule->busTrip) {

                $busTrip = clone $schedule->busTrip;

                $segments = $busTrip->routeSegments
                    ->sortBy('sequence')
                    ->values();

                $busTrip->setRelation(
                    'routeSegments',
                    $segments->map(function ($seg) {
                        return [
                            'sequence'    => $seg->sequence,
                            'stop_id'     => $seg->stop_id,
                            'city'        => $seg->stop?->city ?? '-',
                            'name'        => $seg->stop?->name ?? '-',
                            'type'        => $seg->stop?->type ?? '-',
                            'address'     => $seg->stop?->address,
                            'time_offset' => $seg->time_offset,
                        ];
                    })
                );

                $facilities = collect();
                if ($busTrip->relationLoaded('busClasses')) {
                    $busClass = $busTrip->busClasses->firstWhere('class_type', $busTrip->class_type);
                    if ($busClass && $busClass->relationLoaded('facilities')) {
                        $facilities = $busClass->facilities->map(function ($facility) {
                            return [
                                'id'          => $facility->id,
                                'name'        => $facility->name,
                                'image'       => $facility->image ? url('storage/' . $facility->image) : null,
                                'description' => $facility->description,
                            ];
                        })->values();
                    }
                }
                $busTrip->setAttribute('facilities', $facilities);

                $schedule->setRelation('busTrip', $busTrip);
            }

            return $schedule;
        });

        return response()->json($schedules);
    }

    public function seats(Schedule $schedule)
    {
        if (!$schedule->isBookable()) {
            return response()->json(['message' => 'Jadwal tidak tersedia atau sudah lewat.'], 422);
        }

        $schedule->load([
            'busTrip.routeSegments.stop'
        ]);

        $seats = $schedule->seats()
            ->orderBy('row')
            ->orderBy('column')
            ->get();

        $routeSegments = $schedule->busTrip
            ->routeSegments
            ->sortBy('sequence')
            ->values()
            ->map(function ($seg) {
                return [
                    'sequence'    => $seg->sequence,
                    'stop_id'     => $seg->stop_id,
                    'city'        => $seg->stop?->city ?? '-',
                    'name'        => $seg->stop?->name ?? '-',
                    'type'        => $seg->stop?->type ?? '-',
                    'address'     => $seg->stop?->address,
                    'time_offset' => $seg->time_offset,
                ];
            });

        return response()->json([
            'schedule' => [
                'id'             => $schedule->id,
                'departure_date' => $schedule->departure_date->format('Y-m-d'),
                'departure_time' => $schedule->departure_time,
                'class_type'     => $schedule->busTrip->class_type,
                'price'          => $schedule->busTrip->price,
                'seat_layout'    => $schedule->busTrip->seat_layout,
                'trip_number'    => $schedule->busTrip->trip_number,
                'description'    => $schedule->busTrip->description,
                'available_seats'=> $schedule->available_seats ?? null,
            ],
            'route_segments' => $routeSegments,
            'seats' => $seats,
        ]);
    }
}
