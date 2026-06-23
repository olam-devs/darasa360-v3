<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\SchoolRole;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuggestionController extends Controller
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


  public function suggestionPage(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $userId = session('user_id');
    $userRoleName = session('role');

    $this->switchTenantDB();

    // Resolve user's role ID from role name
    $userRole = SchoolRole::where('name', $userRoleName)->first();
    $userRoleId = $userRole ? $userRole->id : 0;

    // Suggestions sent by the user with proper relationships
    $sentSuggestions = Suggestion::with([
      'sender:id,username,role_id',
      'receiver:id,username,role_id',
      'destinationRole:id,name',
      'class:id,name,class_teacher_id',
      'subject:id,name',
      'replier:id,username,role_id'
    ])
      ->where('sender_id', $userId)
      ->orderBy('created_at', 'desc')
      ->get();

    // Get all user IDs from sent suggestions to optimize queries
    $senderIds = $sentSuggestions->pluck('sender_id')->filter()->unique()->toArray();
    $receiverIds = $sentSuggestions->pluck('receiver_id')->filter()->unique()->toArray();
    $replierIds = $sentSuggestions->pluck('replied_by')->filter()->unique()->toArray();

    // Get class teacher IDs for suggestions with class_id but no receiver_id
    $classTeacherIds = [];
    foreach ($sentSuggestions as $suggestion) {
      if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
        $classTeacherIds[] = $suggestion->class->class_teacher_id;
      }
    }

    $allUserIds = array_merge($senderIds, $receiverIds, $replierIds, $classTeacherIds);
    $users = DB::connection('tenant')->table('schoolUsers')
      ->whereIn('id', $allUserIds)
      ->select('id', 'username', 'role_id')
      ->get()
      ->keyBy('id');

    // Get all role IDs to optimize queries
    $roleIds = $users->pluck('role_id')->merge($sentSuggestions->pluck('sender.role_id'))->filter()->unique()->toArray();
    $allRoles = SchoolRole::whereIn('id', $roleIds)
      ->get()
      ->keyBy('id');

    // Attach sender, receiver, and replier details to sent suggestions
    $sentSuggestions->each(function ($suggestion) use ($users, $allRoles) {
      // Attach sender details
      if ($suggestion->sender_id && $user = $users->get($suggestion->sender_id)) {
        $suggestion->sender = $user;
        if ($user->role_id) {
          $suggestion->sender->role_name = $allRoles->get($user->role_id);
        }
      }

      // Attach receiver details - handle class teacher case
      if ($suggestion->receiver_id && $user = $users->get($suggestion->receiver_id)) {
        $suggestion->receiver = $user;
        if ($user->role_id) {
          $suggestion->receiver->role_name = $allRoles->get($user->role_id);
        }
      } else {
        // Check if this is a class teacher suggestion
        if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
          // This is a class teacher suggestion - get the class teacher's info
          if ($classTeacher = $users->get($suggestion->class->class_teacher_id)) {
            $suggestion->receiver = $classTeacher;
            if ($classTeacher->role_id) {
              $suggestion->receiver->role_name = $allRoles->get($classTeacher->role_id);
            }
          } else {
            // Fallback to destination role if class teacher not found
            $suggestion->receiver = (object)[
              'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
              'role_name' => $suggestion->destinationRole
            ];
          }
        } else {
          // Generic role-based suggestion
          $suggestion->receiver = (object)[
            'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
            'role_name' => $suggestion->destinationRole
          ];
        }
      }

      // Attach replier details
      if ($suggestion->replied_by && $user = $users->get($suggestion->replied_by)) {
        $suggestion->replier = $user;
        if ($user->role_id) {
          $suggestion->replier->role_name = $allRoles->get($user->role_id);
        }
      }
    });

    // Get teacher information for both Teacher and ClassTeacher roles
    $teacherClasses = [];
    $teacherSubjects = [];
    $classTeacherClass = null;
    $teacherInfo = null;

    if (in_array($userRoleName, ['Teacher', 'ClassTeacher'])) {
      $teacher = DB::connection('tenant')->table('teachers')
        ->where('user_id', $userId)
        ->first();

      if ($teacher) {
        $teacherInfo = $teacher;

        // Get classes and subjects the teacher teaches with names
        $teacherClasses = DB::connection('tenant')->table('subject_teacher')
          ->join('classes', 'subject_teacher.class_id', '=', 'classes.id')
          ->where('subject_teacher.teacher_id', $teacher->id)
          ->select('classes.id', 'classes.name')
          ->distinct()
          ->get()
          ->toArray();

        $teacherSubjects = DB::connection('tenant')->table('subject_teacher')
          ->join('subjects', 'subject_teacher.subject_id', '=', 'subjects.id')
          ->where('subject_teacher.teacher_id', $teacher->id)
          ->select('subjects.id', 'subjects.name')
          ->distinct()
          ->get()
          ->toArray();

        // If user is ClassTeacher, get their assigned class with details
        if ($userRoleName === 'ClassTeacher') {
          $classTeacherClass = DB::connection('tenant')->table('classes')
            ->where('class_teacher_id', $userId)
            ->select('id', 'name')
            ->first();
        }
      }
    }

    // Suggestions assigned to the user based on role OR receiver
    $receivedSuggestions = Suggestion::with([
      'sender:id,username,role_id',
      'receiver:id,username,role_id',
      'destinationRole:id,name',
      'class:id,name,class_teacher_id',
      'subject:id,name',
      'replier:id,username,role_id'
    ])
      ->where(function ($query) use ($userRoleId, $userId, $userRoleName, $teacherClasses, $teacherSubjects, $classTeacherClass) {
        // For both Teacher and ClassTeacher roles
        if (in_array($userRoleName, ['Teacher', 'ClassTeacher'])) {
          // Check for suggestions assigned to Teacher role
          $teacherRoleId = SchoolRole::where('name', 'Teacher')->first()->id;

          $query->where(function ($q) use ($teacherRoleId, $userRoleId, $teacherClasses, $teacherSubjects) {
            // Suggestions assigned to Teacher role that match this teacher's classes/subjects
            $q->where('destination_role_id', $teacherRoleId)
              ->where(function ($q2) use ($teacherClasses, $teacherSubjects) {
                $teacherClassIds = array_column($teacherClasses, 'id');
                $teacherSubjectIds = array_column($teacherSubjects, 'id');

                $q2->where(function ($q3) use ($teacherClassIds, $teacherSubjectIds) {
                  $q3->whereIn('class_id', $teacherClassIds)
                    ->whereIn('subject_id', $teacherSubjectIds);
                })
                  ->orWhere(function ($q3) use ($teacherClassIds) {
                    $q3->whereIn('class_id', $teacherClassIds)
                      ->whereNull('subject_id');
                  })
                  ->orWhere(function ($q3) use ($teacherSubjectIds) {
                    $q3->whereIn('subject_id', $teacherSubjectIds)
                      ->whereNull('class_id');
                  })
                  ->orWhere(function ($q3) {
                    $q3->whereNull('class_id')
                      ->whereNull('subject_id');
                  });
              });
          });

          // If user is ClassTeacher, also check for suggestions assigned to ClassTeacher role
          if ($userRoleName === 'ClassTeacher') {
            $query->orWhere(function ($q) use ($userRoleId, $classTeacherClass) {
              $q->where('destination_role_id', $userRoleId)
                ->where(function ($q2) use ($classTeacherClass) {
                  if ($classTeacherClass) {
                    $q2->where('class_id', $classTeacherClass->id)
                      ->orWhereNull('class_id');
                  } else {
                    $q2->whereNull('class_id');
                  }
                });
            });
          }
        } else {
          // For other roles (Student, Parent, Headmaster, Academic)
          $query->where('destination_role_id', $userRoleId);
        }

        // Always include messages where user is the specific receiver
        $query->orWhere('receiver_id', $userId);
      })
      ->orderBy('created_at', 'desc')
      ->get();

    // Get all user IDs from received suggestions
    $receivedSenderIds = $receivedSuggestions->pluck('sender_id')->filter()->unique()->toArray();
    $receivedReceiverIds = $receivedSuggestions->pluck('receiver_id')->filter()->unique()->toArray();
    $receivedReplierIds = $receivedSuggestions->pluck('replied_by')->filter()->unique()->toArray();

    // Get class teacher IDs for received suggestions
    $receivedClassTeacherIds = [];
    foreach ($receivedSuggestions as $suggestion) {
      if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
        $receivedClassTeacherIds[] = $suggestion->class->class_teacher_id;
      }
    }

    $allReceivedUserIds = array_merge($receivedSenderIds, $receivedReceiverIds, $receivedReplierIds, $receivedClassTeacherIds);
    $receivedUsers = DB::connection('tenant')->table('schoolUsers')
      ->whereIn('id', $allReceivedUserIds)
      ->select('id', 'username', 'role_id')
      ->get()
      ->keyBy('id');

    // Get role IDs for received suggestions
    $receivedRoleIds = $receivedUsers->pluck('role_id')->merge($receivedSuggestions->pluck('sender.role_id'))->filter()->unique()->toArray();
    $allReceivedRoles = SchoolRole::whereIn('id', $receivedRoleIds)
      ->get()
      ->keyBy('id');

    // Attach sender, receiver, and replier details to received suggestions
    $receivedSuggestions->each(function ($suggestion) use ($receivedUsers, $allReceivedRoles) {
      // Attach sender details
      if ($suggestion->sender_id && $user = $receivedUsers->get($suggestion->sender_id)) {
        $suggestion->sender = $user;
        if ($user->role_id) {
          $suggestion->sender->role_name = $allReceivedRoles->get($user->role_id);
        }
      }

      // Attach receiver details - handle class teacher case
      if ($suggestion->receiver_id && $user = $receivedUsers->get($suggestion->receiver_id)) {
        $suggestion->receiver = $user;
        if ($user->role_id) {
          $suggestion->receiver->role_name = $allReceivedRoles->get($user->role_id);
        }
      } else {
        // Check if this is a class teacher suggestion
        if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
          // This is a class teacher suggestion - get the class teacher's info
          if ($classTeacher = $receivedUsers->get($suggestion->class->class_teacher_id)) {
            $suggestion->receiver = $classTeacher;
            if ($classTeacher->role_id) {
              $suggestion->receiver->role_name = $allReceivedRoles->get($classTeacher->role_id);
            }
          } else {
            // Fallback to destination role if class teacher not found
            $suggestion->receiver = (object)[
              'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
              'role_name' => $suggestion->destinationRole
            ];
          }
        } else {
          // Generic role-based suggestion
          $suggestion->receiver = (object)[
            'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
            'role_name' => $suggestion->destinationRole
          ];
        }
      }

      // Attach replier details
      if ($suggestion->replied_by && $user = $receivedUsers->get($suggestion->replied_by)) {
        $suggestion->replier = $user;
        if ($user->role_id) {
          $suggestion->replier->role_name = $allReceivedRoles->get($user->role_id);
        }
      }
    });

    $assignedSuggestions = $receivedSuggestions->filter(function ($suggestion) use ($userId) {
      return empty($suggestion->reply) || $suggestion->replied_by === $userId;
    });

    $replies = $receivedSuggestions->filter(function ($suggestion) use ($userId) {
      return !empty($suggestion->reply) && $suggestion->replied_by !== $userId;
    });

    // ✅ For students: merge in their own suggestions that have replies
    if ($userRoleName === 'Student') {
      $studentReplies = Suggestion::with([
        'sender:id,username,role_id',
        'receiver:id,username,role_id',
        'destinationRole:id,name',
        'class:id,name,class_teacher_id',
        'subject:id,name',
        'replier:id,username,role_id'
      ])
        ->where('sender_id', $userId)
        ->whereNotNull('reply')
        ->where('replied_by', '!=', $userId)
        ->orderBy('created_at', 'desc')
        ->get();

      // Get user IDs from student replies
      $studentReplySenderIds = $studentReplies->pluck('sender_id')->filter()->unique()->toArray();
      $studentReplyReceiverIds = $studentReplies->pluck('receiver_id')->filter()->unique()->toArray();
      $studentReplyReplierIds = $studentReplies->pluck('replied_by')->filter()->unique()->toArray();

      // Get class teacher IDs for student replies
      $studentReplyClassTeacherIds = [];
      foreach ($studentReplies as $suggestion) {
        if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
          $studentReplyClassTeacherIds[] = $suggestion->class->class_teacher_id;
        }
      }

      $studentReplyUserIds = array_merge($studentReplySenderIds, $studentReplyReceiverIds, $studentReplyReplierIds, $studentReplyClassTeacherIds);
      $studentReplyUsers = DB::connection('tenant')->table('schoolUsers')
        ->whereIn('id', $studentReplyUserIds)
        ->select('id', 'username', 'role_id')
        ->get()
        ->keyBy('id');

      // Get role IDs for student replies
      $studentReplyRoleIds = $studentReplyUsers->pluck('role_id')->filter()->unique()->toArray();
      $studentReplyRoles = SchoolRole::whereIn('id', $studentReplyRoleIds)
        ->get()
        ->keyBy('id');

      // Attach sender, receiver, and replier details to student replies
      $studentReplies->each(function ($suggestion) use ($studentReplyUsers, $studentReplyRoles) {
        // Attach sender details
        if ($suggestion->sender_id && $user = $studentReplyUsers->get($suggestion->sender_id)) {
          $suggestion->sender = $user;
          if ($user->role_id) {
            $suggestion->sender->role_name = $studentReplyRoles->get($user->role_id);
          }
        }

        // Attach receiver details - handle class teacher case
        if ($suggestion->receiver_id && $user = $studentReplyUsers->get($suggestion->receiver_id)) {
          $suggestion->receiver = $user;
          if ($user->role_id) {
            $suggestion->receiver->role_name = $studentReplyRoles->get($user->role_id);
          }
        } else {
          // Check if this is a class teacher suggestion
          if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
            // This is a class teacher suggestion - get the class teacher's info
            if ($classTeacher = $studentReplyUsers->get($suggestion->class->class_teacher_id)) {
              $suggestion->receiver = $classTeacher;
              if ($classTeacher->role_id) {
                $suggestion->receiver->role_name = $studentReplyRoles->get($classTeacher->role_id);
              }
            } else {
              // Fallback to destination role if class teacher not found
              $suggestion->receiver = (object)[
                'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
                'role_name' => $suggestion->destinationRole
              ];
            }
          } else {
            // Generic role-based suggestion
            $suggestion->receiver = (object)[
              'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
              'role_name' => $suggestion->destinationRole
            ];
          }
        }

        // Attach replier details
        if ($suggestion->replied_by && $user = $studentReplyUsers->get($suggestion->replied_by)) {
          $suggestion->replier = $user;
          if ($user->role_id) {
            $suggestion->replier->role_name = $studentReplyRoles->get($user->role_id);
          }
        }
      });

      $replies = $replies->merge($studentReplies)->unique('id');
    }

    // Get all suggestions for Headmaster and Academic roles with pagination
    $allSuggestions = null;
    if (in_array($userRoleName, ['Headmaster', 'Academic'])) {
      $allSuggestions = Suggestion::with([
        'sender:id,username,role_id',
        'receiver:id,username,role_id',
        'destinationRole:id,name',
        'class:id,name,class_teacher_id',
        'subject:id,name',
        'replier:id,username,role_id'
      ])
        ->orderBy('created_at', 'desc')
        ->paginate(5);

      // Get user IDs from all suggestions
      $allSuggestionSenderIds = collect($allSuggestions->items())->pluck('sender_id')->filter()->unique()->toArray();
      $allSuggestionReceiverIds = collect($allSuggestions->items())->pluck('receiver_id')->filter()->unique()->toArray();
      $allSuggestionReplierIds = collect($allSuggestions->items())->pluck('replied_by')->filter()->unique()->toArray();

      // Get class teacher IDs for all suggestions
      $allSuggestionClassTeacherIds = [];
      foreach ($allSuggestions->items() as $suggestion) {
        if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
          $allSuggestionClassTeacherIds[] = $suggestion->class->class_teacher_id;
        }
      }

      $allSuggestionUserIds = array_merge($allSuggestionSenderIds, $allSuggestionReceiverIds, $allSuggestionReplierIds, $allSuggestionClassTeacherIds);
      $allSuggestionUsers = DB::connection('tenant')->table('schoolUsers')
        ->whereIn('id', $allSuggestionUserIds)
        ->select('id', 'username', 'role_id')
        ->get()
        ->keyBy('id');

      // Get role IDs for all suggestions
      $allSuggestionRoleIds = $allSuggestionUsers->pluck('role_id')->filter()->unique()->toArray();
      $allSuggestionRoles = SchoolRole::whereIn('id', $allSuggestionRoleIds)
        ->get()
        ->keyBy('id');

      // Attach sender, receiver, and replier details to all suggestions
      foreach ($allSuggestions->items() as $suggestion) {
        // Attach sender details
        if ($suggestion->sender_id && $user = $allSuggestionUsers->get($suggestion->sender_id)) {
          $suggestion->sender = $user;
          if ($user->role_id) {
            $suggestion->sender->role_name = $allSuggestionRoles->get($user->role_id);
          }
        }

        // Attach receiver details - handle class teacher case
        if ($suggestion->receiver_id && $user = $allSuggestionUsers->get($suggestion->receiver_id)) {
          $suggestion->receiver = $user;
          if ($user->role_id) {
            $suggestion->receiver->role_name = $allSuggestionRoles->get($user->role_id);
          }
        } else {
          // Check if this is a class teacher suggestion
          if ($suggestion->receiver_id === null && $suggestion->class_id && $suggestion->class && $suggestion->class->class_teacher_id) {
            // This is a class teacher suggestion - get the class teacher's info
            if ($classTeacher = $allSuggestionUsers->get($suggestion->class->class_teacher_id)) {
              $suggestion->receiver = $classTeacher;
              if ($classTeacher->role_id) {
                $suggestion->receiver->role_name = $allSuggestionRoles->get($classTeacher->role_id);
              }
            } else {
              // Fallback to destination role if class teacher not found
              $suggestion->receiver = (object)[
                'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
                'role_name' => $suggestion->destinationRole
              ];
            }
          } else {
            // Generic role-based suggestion
            $suggestion->receiver = (object)[
              'username' => ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)),
              'role_name' => $suggestion->destinationRole
            ];
          }
        }

        // Attach replier details
        if ($suggestion->replied_by && $user = $allSuggestionUsers->get($suggestion->replied_by)) {
          $suggestion->replier = $user;
          if ($user->role_id) {
            $suggestion->replier->role_name = $allSuggestionRoles->get($user->role_id);
          }
        }
      }
    }

    // Build roles query - exclude certain roles
    $rolesQuery = SchoolRole::where('name', '!=', 'Admin')
      ->where('name', '!=', 'Student');

    if ($userRoleName !== 'Headmaster') {
      $rolesQuery->where('name', '!=', 'Owner');
    }

    $rolesQuery->where('id', '!=', $userRoleId);
    $roles = $rolesQuery->get();

    // All classes for dropdown
    $classes = Classroom::all();

    // Get subjects for teacher selection
    $subjects = [];
    if (in_array($userRoleName, ['Parent', 'Teacher', 'Academic'])) {
      $subjects = DB::connection('tenant')->table('subjects')->get();
    }

    return view('content.dashboard.suggestions.index', compact(
      'sentSuggestions',
      'assignedSuggestions',
      'replies',
      'roles',
      'classes',
      'subjects',
      'userRoleName',
      'allSuggestions',
      'teacherClasses',
      'teacherSubjects',
      'classTeacherClass',
      'teacherInfo'
    ));
  }

  public function getSubjectsByClass($classId)
  {
    $this->switchTenantDB();

    $subjects = DB::connection('tenant')->table('subjects')
      ->join('subject_teacher', 'subjects.id', '=', 'subject_teacher.subject_id')
      ->where('subject_teacher.class_id', $classId)
      ->select('subjects.id', 'subjects.name')
      ->distinct()
      ->get();

    return response()->json($subjects);
  }


  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $userId = session('user_id');
    $userRoleName = session('role');

    // Validate input
    $validated = $request->validate([
      'destination_role' => 'required|string|exists:tenant.school_roles,name',
      'class_id' => 'nullable|exists:tenant.classes,id',
      'subject_id' => 'nullable|exists:tenant.subjects,id',
      'receiver_id' => 'nullable|exists:tenant.teachers,id',
      'content' => 'required|string|max:1000',
    ]);

    // Prevent non-Head users from sending to Owner
    if ($validated['destination_role'] === 'Owner' && $userRoleName !== 'Headmaster') {
      return back()
        ->withErrors(['destination_role' => 'You are not authorized to send messages to Owner.'])
        ->withInput();
    }

    // Get role id from role name
    $role = DB::connection('tenant')->table('school_roles')->where('name', $validated['destination_role'])->first();
    if (!$role) {
      return back()
        ->withErrors(['destination_role' => 'Selected role is invalid.'])
        ->withInput();
    }

    // Additional validation for teacher/class teacher messages
    if (in_array($validated['destination_role'], ['Teacher', 'ClassTeacher'])) {
      // Class is required for ClassTeacher
      if ($validated['destination_role'] === 'ClassTeacher' && empty($validated['class_id'])) {
        return back()
          ->withErrors(['class_id' => 'Class is required when destination role is Class Teacher.'])
          ->withInput();
      }

      // For Teacher role, validate that the subject exists in the selected class (if class is provided)
      if ($validated['destination_role'] === 'Teacher' && !empty($validated['class_id']) && !empty($validated['subject_id'])) {
        $subjectExists = DB::connection('tenant')->table('subject_teacher')
          ->where('subject_id', $validated['subject_id'])
          ->where('class_id', $validated['class_id'])
          ->exists();

        if (!$subjectExists) {
          return back()
            ->withErrors(['subject_id' => 'This subject is not taught in the selected class.'])
            ->withInput();
        }
      }
    }

    // Remove automatic receiver_id assignment for teachers
    // receiver_id should only be set for direct personal messages, not role-based messages
    $receiverId = $validated['receiver_id'] ?? null;

    // Create suggestion
    $suggestion = new Suggestion();
    $suggestion->sender_id = $userId;
    $suggestion->destination_role_id = $role->id;
    $suggestion->class_id = $validated['class_id'] ?? null;
    $suggestion->subject_id = $validated['subject_id'] ?? null;
    $suggestion->receiver_id = $receiverId; // Only set for direct messages
    $suggestion->content = $validated['content'];
    $suggestion->status = 'unseen';
    $suggestion->save();

    return redirect()->route('suggestionPage')->with('success', 'Suggestion submitted successfully.');
  }


  public function reply(Request $request, $suggestionId)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $userId = session('user_id');
    $userRoleName = session('role');

    // Only staff can reply
    if (!in_array($userRoleName, ['Teacher', 'ClassTeacher', 'Headmaster', 'Academic'])) {
      abort(403, 'Unauthorized');
    }

    $suggestion = Suggestion::with('sender.role_name')->findOrFail($suggestionId);

    // Check if this is a student message
    // if ($suggestion->sender->role_name->name !== 'Student') {
    //   return redirect()->route('suggestionPage')
    //     ->with('error', 'You can only reply to student messages.');
    // }

    // Check if already replied
    if ($suggestion->reply) {
      return redirect()->route('suggestionPage')
        ->with('error', 'This message already has a reply.');
    }

    $validated = $request->validate([
      'reply_content' => 'required|string|max:1000',
    ]);

    // Update the existing suggestion with reply
    $suggestion->reply = $validated['reply_content'];
    $suggestion->replied_by = $userId;
    $suggestion->replied_at = now();
    $suggestion->status = 'replied'; // You might want to add this status
    $suggestion->save();

    return redirect()->route('suggestionPage')->with('success', 'Reply sent successfully.');
  }


  public function destroy(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $userId = session('user_id');
    $userRoleName = session('role');

    $suggestion = Suggestion::findOrFail($id);

    // Check if user can delete this message
    $canDelete = false;

    // Head and Academic can delete any teacher/class teacher messages
    if (in_array($userRoleName, ['Headmaster', 'Academic'])) {
      $destinationRole = SchoolRole::find($suggestion->destination_role_id);
      if ($destinationRole && in_array($destinationRole->name, ['Teacher', 'ClassTeacher'])) {
        $canDelete = true;
      }
    }

    // Users can delete their own messages (except teachers/class teachers)
    if ($suggestion->sender_id == $userId && !in_array($userRoleName, ['Teacher', 'ClassTeacher'])) {
      $canDelete = true;
    }

    if (!$canDelete) {
      return redirect()->route('suggestionPage')
        ->with('error', 'You are not authorized to delete this message.');
    }

    $suggestion->delete();

    return redirect()->route('suggestionPage')
      ->with('success', 'Message deleted successfully.');
  }

  public function markSeen(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userId = session('user_id');
    $userRoleName = session('role');
    $userRole = SchoolRole::where('name', $userRoleName)->first();

    $suggestion = Suggestion::findOrFail($id);

    // Check if user has permission to mark this suggestion as seen
    $canMarkSeen = false;

    // For Teacher and ClassTeacher roles, check if they teach the relevant class/subject
    if (in_array($userRoleName, ['Teacher', 'ClassTeacher'])) {
      $teacher = DB::connection('tenant')->table('teachers')
        ->where('user_id', $userId)
        ->first();

      if ($teacher) {
        // Get teacher's assigned classes and subjects
        $teacherClasses = DB::connection('tenant')->table('subject_teacher')
          ->where('teacher_id', $teacher->id)
          ->pluck('class_id')
          ->unique()
          ->toArray();

        $teacherSubjects = DB::connection('tenant')->table('subject_teacher')
          ->where('teacher_id', $teacher->id)
          ->pluck('subject_id')
          ->unique()
          ->toArray();

        // For ClassTeacher, get their assigned class
        $classTeacherClass = null;
        if ($userRoleName === 'ClassTeacher') {
          $classTeacherClass = DB::connection('tenant')->table('classes')
            ->where('class_teacher_id', $userId)
            ->pluck('id')
            ->first();
        }

        // Check if suggestion matches teacher's assignments
        $teacherRoleId = SchoolRole::where('name', 'Teacher')->first()->id;

        // For Teacher role suggestions
        if ($suggestion->destination_role_id === $teacherRoleId) {
          $classMatch = !$suggestion->class_id || in_array($suggestion->class_id, $teacherClasses);
          $subjectMatch = !$suggestion->subject_id || in_array($suggestion->subject_id, $teacherSubjects);

          if ($classMatch && $subjectMatch) {
            $canMarkSeen = true;
          }
        }

        // For ClassTeacher role suggestions
        if ($userRoleName === 'ClassTeacher' && $suggestion->destination_role_id === $userRole->id) {
          $classMatch = !$suggestion->class_id || $suggestion->class_id == $classTeacherClass;

          if ($classMatch) {
            $canMarkSeen = true;
          }
        }
      }
    } else {
      // For other roles, use the original simple check
      $canMarkSeen = $suggestion->destination_role_id === $userRole->id;
    }

    // Also allow if user is the specific receiver
    if ($suggestion->receiver_id === $userId) {
      $canMarkSeen = true;
    }

    if ($canMarkSeen) {
      $suggestion->status = 'seen';
      $suggestion->save();

      return back()->with('success', 'Suggestion marked as seen.');
    }

    return back()->with('error', 'Unauthorized action.');
  }
}
