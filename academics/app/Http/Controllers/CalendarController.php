<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolCalendar;
use App\Models\SchoolEvent;
use App\Models\NotificationQueue;
use App\Models\SchoolUser;
use App\Models\Student;

class CalendarController extends Controller
{
    /**
     * Display calendar with all events
     */
    public function index()
    {
        $activeCalendar = SchoolCalendar::where('is_active', true)->first();
        $upcomingEvents = SchoolEvent::where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->limit(10)
            ->get();

        return view('calendar.index', compact('activeCalendar', 'upcomingEvents'));
    }

    /**
     * Display modern calendar view with exams and events
     * Accessible by all roles
     */
    public function modernCalendar(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        // Get exams for the current month
        $exams = \App\Models\Exam::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->with('classes')
            ->orderBy('start_date', 'asc')
            ->get();

        // Get school events for the current month
        $schoolEvents = SchoolEvent::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->orderBy('start_date', 'asc')
            ->get();

        // Assign colors and format exams
        $colors = ['purple', 'blue', 'yellow', 'coral', 'green', 'pink'];
        $exams = $exams->map(function($exam, $index) use ($colors) {
            $exam->color = $colors[$index % count($colors)];
            $exam->date = $exam->start_date;
            $exam->time = $exam->start_time ?? '9:00 AM';
            $exam->students_count = 0; // You can calculate this if needed
            $exam->class = $exam->classes->first(); // Get first class for display
            return $exam;
        });

        // Format school events
        $schoolEvents = $schoolEvents->map(function($event, $index) use ($colors, $exams) {
            $offset = $exams->count();
            $event->color = $colors[($offset + $index) % count($colors)];
            $event->date = $event->start_date;
            $event->time = $event->start_time ? date('g:i A', strtotime($event->start_time)) : null;
            $event->students_count = 0;
            $event->class = null;
            return $event;
        });

        // Combine exams and events
        $allEvents = $exams->concat($schoolEvents)->sortBy('start_date');

        // Get upcoming events (next 30 days) - both exams and school events
        $upcomingExams = \App\Models\Exam::where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays(30))
            ->with('classes')
            ->orderBy('start_date', 'asc')
            ->limit(5)
            ->get();

        $upcomingSchoolEvents = SchoolEvent::where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays(30))
            ->orderBy('start_date', 'asc')
            ->limit(5)
            ->get();

        // Format upcoming exams
        $upcomingExams = $upcomingExams->map(function($exam, $index) use ($colors) {
            $exam->color = $colors[$index % count($colors)];
            $exam->date = $exam->start_date;
            $exam->time = $exam->start_time ?? '9:00 AM';
            $exam->class = $exam->classes->first();
            $exam->students_count = 0;
            return $exam;
        });

        // Format upcoming school events
        $upcomingSchoolEvents = $upcomingSchoolEvents->map(function($event, $index) use ($colors, $upcomingExams) {
            $offset = $upcomingExams->count();
            $event->color = $colors[($offset + $index) % count($colors)];
            $event->date = $event->start_date;
            $event->time = $event->start_time ? date('g:i A', strtotime($event->start_time)) : null;
            $event->students_count = 0;
            $event->class = null;
            return $event;
        });

        // Combine and sort upcoming events
        $upcomingEvents = $upcomingExams->concat($upcomingSchoolEvents)->sortBy('start_date')->take(10);

        // Calendar statistics - include both exams and events
        $totalExams = \App\Models\Exam::count();
        $totalEvents = SchoolEvent::count();

        $examsThisWeek = \App\Models\Exam::whereBetween('start_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        $eventsThisWeek = SchoolEvent::whereBetween('start_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        $examsCompleted = \App\Models\Exam::where('end_date', '<', now())->count();
        $eventsCompleted = SchoolEvent::where('start_date', '<', now())->count();

        $examsUpcoming = \App\Models\Exam::where('start_date', '>=', now())->count();
        $eventsUpcoming = SchoolEvent::where('start_date', '>=', now())->count();

        $stats = [
            'total_exams' => $totalExams + $totalEvents,
            'this_week' => $examsThisWeek + $eventsThisWeek,
            'completed' => $examsCompleted + $eventsCompleted,
            'upcoming' => $examsUpcoming + $eventsUpcoming,
        ];

        // Check if user can edit calendar (academic, owner/admin, headmaster)
        $userRole = session('role');
        $canEditCalendar = in_array($userRole, ['Academic', 'Owner', 'Admin', 'Headmaster']);

        return view('content.calendar.modern-index', compact(
            'allEvents',
            'upcomingEvents',
            'stats',
            'year',
            'month',
            'canEditCalendar'
        ))->with('exams', $allEvents)->with('upcomingExams', $upcomingEvents);
    }

    /**
     * Create a new academic calendar
     */
    public function createCalendar(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'term_count' => 'required|integer|min:1|max:4',
            'description' => 'nullable|string',
        ]);

        // Deactivate previous calendars
        SchoolCalendar::where('is_active', true)->update(['is_active' => false]);

        $calendar = SchoolCalendar::create([
            'academic_year' => $validated['academic_year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'term_count' => $validated['term_count'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Academic calendar created successfully!');
    }

    /**
     * Create a new event
     */
    public function createEvent(Request $request)
    {
        // Check if user has permission to create events
        $userRole = session('role');
        if (!in_array($userRole, ['Academic', 'Owner', 'Admin', 'Headmaster'])) {
            return back()->with('error', 'You do not have permission to create events.');
        }

        // Check if user is logged in
        $userId = session('user_id');
        if (!$userId) {
            return back()->with('error', 'User session expired. Please login again.');
        }

        // Check if user exists in schoolUsers table
        $userExists = \DB::connection('tenant')->table('schoolUsers')->where('id', $userId)->exists();
        if (!$userExists) {
            // User might be super admin or other type, use ID 1 (admin) as fallback
            $userId = \DB::connection('tenant')->table('schoolUsers')->first()->id ?? 1;
        }

        $validated = $request->validate([
            'calendar_id' => 'nullable|exists:school_calendar,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:exam,meeting,holiday,sport,cultural,academic,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'location' => 'nullable|string',
            'notify_staff' => 'boolean',
            'notify_students' => 'boolean',
            'notify_parents' => 'boolean',
            'reminder_days_before' => 'nullable|integer|min:1',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|string',
        ]);

        try {
            $event = SchoolEvent::create([
                'calendar_id' => $validated['calendar_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'event_type' => $validated['event_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'location' => $validated['location'] ?? null,
                'created_by' => $userId,
                'notify_staff' => $validated['notify_staff'] ?? false,
                'notify_students' => $validated['notify_students'] ?? false,
                'notify_parents' => $validated['notify_parents'] ?? false,
                'reminder_days_before' => $validated['reminder_days_before'] ?? null,
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurrence_pattern' => $validated['recurrence_pattern'] ?? null,
            ]);

            // Queue notifications if needed
            $this->queueEventNotifications($event);

            return back()->with('success', 'Event created successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error creating event: ' . $e->getMessage());

            // Check if it's a foreign key constraint error
            if ($e->getCode() == '23000') {
                return back()->with('error', 'Database error: Unable to create event. Please contact the administrator.')->withInput();
            }

            return back()->with('error', 'Failed to create event: ' . $e->getMessage())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error creating event: ' . $e->getMessage());
            return back()->with('error', 'An unexpected error occurred. Please try again.')->withInput();
        }
    }

    /**
     * Update an event
     */
    public function updateEvent(Request $request, $id)
    {
        // Check if user has permission to update events
        $userRole = session('role');
        if (!in_array($userRole, ['Academic', 'Owner', 'Admin', 'Headmaster'])) {
            return back()->with('error', 'You do not have permission to update events.');
        }

        $event = SchoolEvent::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:exam,meeting,holiday,sport,cultural,academic,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string',
        ]);

        $event->update($validated);

        return back()->with('success', 'Event updated successfully!');
    }

    /**
     * Delete an event
     */
    public function deleteEvent($id)
    {
        // Check if user has permission to delete events
        $userRole = session('role');
        if (!in_array($userRole, ['Academic', 'Owner', 'Admin', 'Headmaster'])) {
            return back()->with('error', 'You do not have permission to delete events.');
        }

        $event = SchoolEvent::findOrFail($id);
        $event->delete();

        return back()->with('success', 'Event deleted successfully!');
    }

    /**
     * Get events for calendar view (AJAX)
     */
    public function getEvents(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $events = SchoolEvent::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_date,
                    'end' => $event->end_date,
                    'type' => $event->event_type,
                    'location' => $event->location,
                    'description' => $event->description,
                ];
            });

        return response()->json($events);
    }

    /**
     * Queue notifications for event
     */
    private function queueEventNotifications($event)
    {
        $message = "Upcoming Event: {$event->title}\n";
        $message .= "Date: {$event->start_date->format('d/m/Y')}\n";
        if ($event->location) {
            $message .= "Location: {$event->location}\n";
        }
        if ($event->description) {
            $message .= "Details: {$event->description}";
        }

        // Notify staff if needed
        if ($event->notify_staff) {
            $staff = SchoolUser::whereHas('role', function ($q) {
                $q->whereIn('name', ['headmaster', 'academic', 'class_teacher', 'subject_teacher']);
            })->get();

            foreach ($staff as $user) {
                if ($user->phone_number) {
                    NotificationQueue::create([
                        'type' => 'sms',
                        'recipient_phone' => $user->phone_number,
                        'recipient_name' => $user->username,
                        'message' => $message,
                        'status' => 'pending',
                        'priority' => 'medium',
                        'notification_category' => 'event',
                        'related_id' => $event->id,
                        'related_type' => 'event',
                        'scheduled_at' => $event->reminder_days_before
                            ? now()->addDays($event->reminder_days_before * -1)
                            : now(),
                    ]);
                }
            }
        }

        // Notify students/parents if needed
        if ($event->notify_students || $event->notify_parents) {
            $students = Student::all();

            foreach ($students as $student) {
                if ($student->parent_phone) {
                    NotificationQueue::create([
                        'type' => 'sms',
                        'recipient_phone' => $student->parent_phone,
                        'recipient_name' => $student->parent_name ?? 'Parent',
                        'message' => $message,
                        'status' => 'pending',
                        'priority' => 'medium',
                        'notification_category' => 'event',
                        'related_id' => $event->id,
                        'related_type' => 'event',
                        'scheduled_at' => $event->reminder_days_before
                            ? now()->addDays($event->reminder_days_before * -1)
                            : now(),
                    ]);
                }
            }
        }
    }

    /**
     * View upcoming events
     */
    public function upcomingEvents()
    {
        $events = SchoolEvent::where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->paginate(20);

        return view('calendar.upcoming', compact('events'));
    }
}
