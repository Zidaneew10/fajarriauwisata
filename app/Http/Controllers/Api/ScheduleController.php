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
            'busTrip.routeSegments.stop',  // ← ganti terminal → stop
        ])
        ->where('status', 'active')
        ->when($request->date, fn($q) =>
            $q->whereDate('departure_date', $request->date)
        )
        ->when($request->origin, fn($q) =>
            $q->whereHas('busTrip.routeSegments', fn($q2) =>
                $q2->where('sequence', 1)
                   ->whereHas('stop', fn($q3) =>   // ← ganti
                       $q3->where('city', 'like', "%{$request->origin}%")
                   )
            )
        )
        ->when($request->destination, fn($q) =>
            $q->whereHas('busTrip.routeSegments', fn($q2) =>
                $q2->where('sequence', '>', 1)
                   ->whereHas('stop', fn($q3) =>   // ← ganti
                       $q3->where('city', 'like', "%{$request->destination}%")
                   )
            )
        )
        ->orderBy('departure_date')
        ->orderBy('departure_time')
        ->paginate(20);

        $schedules->getCollection()->transform(function ($schedule) {
            $schedule->available_seats = $schedule->seats()
                ->where('is_available', true)->count();
            return $schedule;
        });

        return response()->json($schedules);
    }

    public function seats(Schedule $schedule)
    {
        $seats = $schedule->seats()
            ->orderBy('row')
            ->orderBy('column')
            ->get();

        $routeSegments = $schedule->busTrip->routeSegments()
            ->with('stop')
            ->orderBy('sequence')
            ->get()
            ->map(fn($seg) => [
                'sequence'    => $seg->sequence,
                'stop_id'     => $seg->stop_id,
                'city'        => $seg->stop->city,
                'name'        => $seg->stop->name,
                'type'        => $seg->stop->type,
                'address'     => $seg->stop->address,
                'time_offset' => $seg->time_offset,
            ]);

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
            ],
            'route_segments' => $routeSegments,
            'seats'          => $seats,
        ]);
    }
}
