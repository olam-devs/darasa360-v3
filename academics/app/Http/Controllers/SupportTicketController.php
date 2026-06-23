<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportModule;
use App\Models\SupportTicketComment;
use App\Models\SchoolUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
  private function switchTenantDB()
  {
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      abort(404, 'Tenant DB not found in session.');
    }
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');
  }

  /**
   * Display support ticket page
   */
  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userId = session('user_id');
    $userRole = session('role');

    // Get all modules for filtering
    $modules = SupportModule::where('is_active', true)->get();

    // Base query for tickets
    $ticketsQuery = SupportTicket::with(['user', 'module', 'assignedTo', 'comments']);

    // Filter based on role
    if (in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin'])) {
      // Admins can see all tickets
      $ticketsQuery = $ticketsQuery;
    } else {
      // Regular users can only see their own tickets
      $ticketsQuery->where('user_id', $userId);
    }

    // Apply filters
    if ($request->has('status') && $request->status != '') {
      $ticketsQuery->where('status', $request->status);
    }

    if ($request->has('module') && $request->module != '') {
      $ticketsQuery->where('module_id', $request->module);
    }

    if ($request->has('priority') && $request->priority != '') {
      $ticketsQuery->where('priority', $request->priority);
    }

    // Get tickets
    $tickets = $ticketsQuery->orderBy('created_at', 'desc')->get();

    // Get my tickets
    $myTickets = SupportTicket::with(['module', 'comments'])
      ->where('user_id', $userId)
      ->orderBy('created_at', 'desc')
      ->get();

    // Statistics for admins
    $stats = null;
    if (in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin'])) {
      $stats = [
        'total' => SupportTicket::count(),
        'open' => SupportTicket::where('status', 'open')->count(),
        'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
        'resolved' => SupportTicket::where('status', 'resolved')->count(),
        'closed' => SupportTicket::where('status', 'closed')->count(),
      ];
    }

    return view('content.dashboard.support.index', compact('tickets', 'myTickets', 'modules', 'stats', 'userRole'));
  }

  /**
   * Store a new ticket
   */
  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $validated = $request->validate([
      'subject' => 'required|string|max:255',
      'module_id' => 'required|exists:tenant.support_modules,id',
      'sub_module' => 'nullable|string|max:255',
      'description' => 'required|string',
      'priority' => 'nullable|in:low,medium,high,critical',
    ]);

    $userId = session('user_id');

    // Create ticket
    $ticket = new SupportTicket();
    $ticket->ticket_number = SupportTicket::generateTicketNumber();
    $ticket->subject = $validated['subject'];
    $ticket->module_id = $validated['module_id'];
    $ticket->sub_module = $validated['sub_module'] ?? null;
    $ticket->description = $validated['description'];
    $ticket->user_id = $userId;
    $ticket->priority = $validated['priority'] ?? 'medium';
    $ticket->status = 'open';
    $ticket->save();

    // Update module ticket count
    $module = SupportModule::find($validated['module_id']);
    if ($module) {
      $module->increment('ticket_count');
    }

    // Queue notification to school system admin
    $this->queueTicketCreationNotification($ticket);

    return redirect()->route('support.index')
      ->with('success', 'Support ticket created successfully! Ticket #' . $ticket->ticket_number);
  }

  /**
   * Show ticket details
   */
  public function show(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userId = session('user_id');
    $userRole = session('role');

    $ticket = SupportTicket::with(['user', 'module', 'assignedTo', 'resolvedBy', 'comments.user'])
      ->findOrFail($id);

    // Check permission
    if (!in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']) && $ticket->user_id != $userId) {
      abort(403, 'Unauthorized access to this ticket.');
    }

    // Get support staff for assignment (only for admins)
    $supportStaff = null;
    if (in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin'])) {
      $supportStaff = SchoolUser::whereHas('role', function ($query) {
        $query->whereIn('name', ['Headmaster', 'Academic', 'Owner', 'Admin']);
      })->get();
    }

    return view('content.dashboard.support.show', compact('ticket', 'supportStaff', 'userRole'));
  }

  /**
   * Update ticket status
   */
  public function updateStatus(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userRole = session('role');

    // Only admins can update status
    if (!in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin'])) {
      abort(403, 'Unauthorized action.');
    }

    $validated = $request->validate([
      'status' => 'required|in:open,in_progress,pending,resolved,closed',
      'resolution_notes' => 'nullable|string',
    ]);

    $ticket = SupportTicket::findOrFail($id);
    $ticket->status = $validated['status'];

    if ($validated['status'] === 'resolved' || $validated['status'] === 'closed') {
      $ticket->resolved_at = now();
      $ticket->resolved_by = session('user_id');
      $ticket->resolution_notes = $validated['resolution_notes'] ?? null;
    }

    $ticket->save();

    return back()->with('success', 'Ticket status updated successfully.');
  }

  /**
   * Assign ticket to support staff
   */
  public function assign(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userRole = session('role');

    // Only admins can assign tickets
    if (!in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin'])) {
      abort(403, 'Unauthorized action.');
    }

    $validated = $request->validate([
      'assigned_to' => 'required|exists:tenant.schoolUsers,id',
    ]);

    $ticket = SupportTicket::findOrFail($id);
    $ticket->assigned_to = $validated['assigned_to'];

    // Auto-update status to in_progress if it's open
    if ($ticket->status === 'open') {
      $ticket->status = 'in_progress';
    }

    $ticket->save();

    return back()->with('success', 'Ticket assigned successfully.');
  }

  /**
   * Add comment to ticket
   */
  public function addComment(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $validated = $request->validate([
      'comment' => 'required|string',
      'is_internal' => 'nullable|boolean',
    ]);

    $userId = session('user_id');
    $userRole = session('role');

    $ticket = SupportTicket::findOrFail($id);

    // Check permission
    if (!in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']) && $ticket->user_id != $userId) {
      abort(403, 'Unauthorized access to this ticket.');
    }

    $comment = new SupportTicketComment();
    $comment->ticket_id = $id;
    $comment->user_id = $userId;
    $comment->comment = $validated['comment'];
    $comment->is_internal = $validated['is_internal'] ?? false;
    $comment->save();

    return back()->with('success', 'Comment added successfully.');
  }

  /**
   * Update ticket priority
   */
  public function updatePriority(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userRole = session('role');

    // Only admins can update priority
    if (!in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin'])) {
      abort(403, 'Unauthorized action.');
    }

    $validated = $request->validate([
      'priority' => 'required|in:low,medium,high,critical',
    ]);

    $ticket = SupportTicket::findOrFail($id);
    $ticket->priority = $validated['priority'];
    $ticket->save();

    return back()->with('success', 'Ticket priority updated successfully.');
  }

  /**
   * Delete ticket (admin only)
   */
  public function destroy(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userRole = session('role');

    // Only specific admins can delete tickets
    if (!in_array($userRole, ['Owner', 'Admin'])) {
      abort(403, 'Unauthorized action.');
    }

    $ticket = SupportTicket::findOrFail($id);
    $ticket->delete();

    return redirect()->route('support.index')
      ->with('success', 'Ticket deleted successfully.');
  }

  /**
   * Escalate a ticket to higher admin level
   */
  public function escalateTicket(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $validated = $request->validate([
      'escalation_reason' => 'required|string',
      'notify_sms' => 'boolean',
      'notify_email' => 'boolean',
    ]);

    $ticket = SupportTicket::findOrFail($id);
    $userId = session('user_id');

    // Determine next escalation level
    $currentLevel = $ticket->escalation_level;
    $nextLevel = $this->getNextEscalationLevel($currentLevel);

    if (!$nextLevel) {
      return back()->with('error', 'Ticket is already at highest escalation level.');
    }

    // Find admin to escalate to
    $escalatedTo = $this->findAdminForLevel($nextLevel);

    if (!$escalatedTo) {
      return back()->with('error', 'No admin available at next escalation level.');
    }

    // Update ticket
    $ticket->update([
      'escalation_level' => $nextLevel,
      'is_escalated' => true,
      'escalated_at' => now(),
      'escalated_to' => $escalatedTo->id,
      'escalated_by' => $userId,
      'escalation_reason' => $validated['escalation_reason'],
      'notify_sms' => $validated['notify_sms'] ?? true,
      'notify_email' => $validated['notify_email'] ?? true,
    ]);

    // Queue notification
    if ($ticket->notify_sms || $ticket->notify_email) {
      $this->queueEscalationNotification($ticket, $escalatedTo);
    }

    // Add comment about escalation
    SupportTicketComment::create([
      'ticket_id' => $ticket->id,
      'user_id' => $userId,
      'comment' => "Ticket escalated to {$nextLevel}: {$validated['escalation_reason']}",
    ]);

    return back()->with('success', "Ticket escalated to {$nextLevel} successfully.");
  }

  /**
   * Get next escalation level
   */
  private function getNextEscalationLevel($currentLevel)
  {
    $levels = [
      'school_system_admin' => 'system_admin',
      'system_admin' => 'super_admin',
      'super_admin' => null, // No further escalation
    ];

    return $levels[$currentLevel] ?? null;
  }

  /**
   * Find admin for escalation level
   */
  private function findAdminForLevel($level)
  {
    // For school_system_admin level, find from SchoolUsers
    if ($level === 'school_system_admin') {
      return SchoolUser::whereHas('role', function ($q) {
        $q->where('name', 'school_system_admin');
      })->first();
    }

    // For system_admin and super_admin, these are in main database
    // You might need to query the main database here
    // For now, return first available admin
    return SchoolUser::whereHas('role', function ($q) use ($level) {
      $q->where('name', $level);
    })->first();
  }

  /**
   * Queue escalation notification
   */
  private function queueEscalationNotification($ticket, $admin)
  {
    $message = "URGENT: Support Ticket #{$ticket->ticket_number} has been escalated to you.\n\n";
    $message .= "Subject: {$ticket->subject}\n";
    $message .= "Priority: {$ticket->priority}\n";
    $message .= "Reason: {$ticket->escalation_reason}\n\n";
    $message .= "Please log in to review and respond.";

    \App\Models\NotificationQueue::create([
      'type' => $admin->email ? 'both' : 'sms',
      'recipient_phone' => $admin->phone_number,
      'recipient_email' => $admin->email,
      'recipient_name' => $admin->username,
      'subject' => "Escalated Support Ticket #{$ticket->ticket_number}",
      'message' => $message,
      'status' => 'pending',
      'priority' => 'high',
      'notification_category' => 'ticket',
      'related_id' => $ticket->id,
      'related_type' => 'ticket',
      'scheduled_at' => now(),
    ]);
  }

  /**
   * View escalated tickets
   */
  public function escalatedTickets(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userRole = session('role');
    $userId = session('user_id');

    // Get escalated tickets based on role
    $tickets = SupportTicket::with(['user', 'module', 'assignedTo', 'comments'])
      ->where('is_escalated', true)
      ->orderBy('escalated_at', 'desc')
      ->get();

    return view('content.dashboard.support.escalated', compact('tickets', 'userRole'));
  }

  /**
   * Queue notification when ticket is created
   */
  private function queueTicketCreationNotification($ticket)
  {
    // Find school system admin(s)
    $admins = SchoolUser::whereHas('role', function ($q) {
      $q->where('name', 'school_system_admin');
    })->get();

    if ($admins->isEmpty()) {
      // If no school system admin, notify regular admins
      $admins = SchoolUser::whereHas('role', function ($q) {
        $q->whereIn('name', ['Admin', 'Headmaster', 'Owner']);
      })->get();
    }

    $creator = SchoolUser::find($ticket->user_id);
    $creatorName = $creator ? $creator->username : 'A user';

    foreach ($admins as $admin) {
      $message = "NEW SUPPORT TICKET #{$ticket->ticket_number}\n\n";
      $message .= "From: {$creatorName}\n";
      $message .= "Subject: {$ticket->subject}\n";
      $message .= "Priority: {$ticket->priority}\n";
      $message .= "Module: " . ($ticket->module ? $ticket->module->name : 'N/A') . "\n\n";
      $message .= "Description: {$ticket->description}\n\n";
      $message .= "Please log in to the system to review and respond.";

      \App\Models\NotificationQueue::create([
        'type' => $admin->email ? 'both' : 'sms',
        'recipient_phone' => $admin->phone_number,
        'recipient_email' => $admin->email,
        'recipient_name' => $admin->username,
        'subject' => "New Support Ticket #{$ticket->ticket_number}",
        'message' => $message,
        'status' => 'pending',
        'priority' => $ticket->priority === 'critical' ? 'urgent' : ($ticket->priority === 'high' ? 'high' : 'medium'),
        'notification_category' => 'ticket',
        'related_id' => $ticket->id,
        'related_type' => 'ticket',
        'scheduled_at' => now(),
      ]);
    }
  }
}
