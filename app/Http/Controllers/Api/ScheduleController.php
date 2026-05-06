<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleBus;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
 public function index(Request $request)
{
    $schedules = Schedule::with(['busTrip.routeSegments.terminal'])
        ->where('departure_time', '>=', now())
        ->when($request->date, fn($q) =>
            $q->whereDate('departure_time', $request->date)
        )
        ->when($request->origin, fn($q) =>
            $q->whereHas('busTrip.routeSegments.terminal', fn($q2) =>
                $q2->where('city', 'like', "%{$request->origin}%")
            )
        )
        ->when($request->destination, fn($q) =>
            $q->whereHas('busTrip.routeSegments.terminal', fn($q2) =>
                $q2->where('city', 'like', "%{$request->destination}%")
            )
        )
        ->orderBy('departure_time')
        ->paginate(20);

    return response()->json($schedules);
}

    public function buses(Schedule $schedule)
    {
        $buses = $schedule->scheduleBuses()
            ->with(['busClass.facilities'])
            ->withCount(['scheduleSeats as available_seats' => fn($q) => $q->where('is_available', true)])
            ->get()
            ->map(fn($sb) => [
                'id'              => $sb->id,
                'bus_code'        => $sb->bus_code,
                'class_type'      => $sb->busClass->class_type,
                'price'           => $sb->busClass->price,
                'capacity'        => $sb->busClass->capacity,
                'available_seats' => $sb->available_seats,
                'facilities'      => $sb->busClass->facilities,
            ]);

        return response()->json($buses);
    }

    public function seats(ScheduleBus $scheduleBus)
    {
        $seats = $scheduleBus->scheduleSeats()
            ->orderBy('row')->orderBy('column')
            ->get()
            ->map(fn($seat) => [
                'id'           => $seat->id,
                'label'        => $seat->label,
                'row'          => $seat->row,
                'column'       => $seat->column,
                'is_available' => $seat->is_available,
            ]);

        return response()->json([
            'bus' => [
                'id'         => $scheduleBus->id,
                'bus_code'   => $scheduleBus->bus_code,
                'class_type' => $scheduleBus->busClass->class_type,
                'price'      => $scheduleBus->busClass->price,
            ],
            'seats' => $seats,
        ]);
    }
}
