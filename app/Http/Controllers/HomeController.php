<?php

namespace App\Http\Controllers;

use App\Enums\MealSlot;
use App\Models\MealPlanEntry;
use App\Support\Planning\WeekPlan;
use App\Support\Week;
use App\Support\WishList;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Startseite: Was gibt es heute und morgen? Gedacht vor allem fürs Handy.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $today = CarbonImmutable::today();
        $nextWeek = Week::current()->next();

        $upcoming = MealPlanEntry::query()
            ->betweenDates($today, $today->addDays(6))
            ->with('recipe:id,title,slug,summary,emoji,color,image_path,image_hash,prep_minutes,cook_minutes')
            ->get()
            ->keyBy(fn (MealPlanEntry $entry) => $entry->date->toDateString());

        $openWishes = WishList::upTo()->open();

        // Persönliches Dashboard „Mein Tag“ (eigener Plan, eigenes Budget)
        $myPlan = WeekPlan::for($request->user(), Week::current());

        return view('home', [
            'greeting' => $this->greeting(),
            'today' => $today,
            'upcoming' => $upcoming,
            'nextWeek' => $nextWeek,
            'nextWeekPlanned' => MealPlanEntry::query()->forWeek($nextWeek)->count(),
            'openWishes' => $openWishes->count(),
            'ownOpenWishes' => $openWishes->where('user_id', $request->user()->id)->count(),
            'myPlan' => $myPlan,
            'myDay' => $myPlan->days[$today->dayOfWeekIso],
            'slots' => MealSlot::cases(),
            // Training der heutigen Einheit (läuft oder erledigt)
            'todaySession' => ($workout = $myPlan->days[$today->dayOfWeekIso]->workout)
                ? $request->user()->workoutSessions()->where('workout_id', $workout->id)->whereDate('date', $today)->latest('id')->first()
                : null,
        ]);
    }

    private function greeting(): string
    {
        return match (true) {
            now()->hour < 11 => 'Guten Morgen',
            now()->hour < 17 => 'Hallo',
            default => 'Guten Abend',
        };
    }
}
