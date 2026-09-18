<?php

namespace App\Services;

use App\BeyondUser;
use App\Http\Controllers\Controller;
use App\Task;
use App\TaskAssignment;
use App\TaskCc;
use App\User;
use App\Support\TaskPersonalization;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp notifications for task assignment, CC, accept, progress, complete, reminders.
 */
class TaskNotificationService extends Controller
{
    protected function sendPhone($phone, $message)
    {
        if (empty(trim((string) $phone))) {
            return false;
        }
        try {
            $this->sendWhatsAppToPhone($phone, $message);

            return true;
        } catch (\Exception $e) {
            Log::warning('Task WhatsApp failed: ' . $e->getMessage());

            return false;
        }
    }

    public function notifyAssignment(TaskAssignment $assignment)
    {
        $assignment->load(['task']);
        $task = $assignment->task;
        $user = BeyondUser::find($assignment->user_id);
        if (! $task || ! $user) {
            return false;
        }

        $link = url('/task-invite/' . $assignment->invite_token);
        $userVars = TaskPersonalization::userVars($user);
        $description = TaskPersonalization::personalize($task->description ?: '', $userVars);
        $template = $task->notification_template ?: TaskPersonalization::defaultAssignmentTemplate();
        $vars = array_merge($userVars, TaskPersonalization::taskVars($task, $link), [
            'description' => $description,
            'task_message' => $description,
        ]);
        $custom = '';
        $default = TaskPersonalization::defaultAssignmentTemplate();
        if ($template && trim($template) !== trim($default)) {
            $custom = trim(TaskPersonalization::personalize($template, $vars));
            $custom = preg_replace('/^📋\s*\*[^*]+\*\s*/u', '', $custom);
            $custom = preg_replace('/━+/u', '', $custom);
            $custom = preg_replace('/^(Hello|Bonjour)\s+\*[^*]+\*,\s*/iu', '', $custom);
            $custom = preg_replace('/_Welcome 2 Kigali Expats Club_\s*$/u', '', $custom);
            $custom = trim($custom);
        }
        $description = trim($custom !== '' ? $custom : $description);

        $message = \App\Support\WhatsAppMessage::taskAssigned(
            $user->name,
            $task->title,
            $task->priority,
            $vars['start_date'] ?? '—',
            $vars['deadline'] ?? '—',
            $description,
            $link
        );

        $phone = $this->resolveBeyondUserPhone($user);

        return $this->sendPhone($phone, $message);
    }

    /**
     * Prefer BeyondUser.phone; fall back to profile / customer directory phone.
     */
    protected function resolveBeyondUserPhone(BeyondUser $user)
    {
        $phone = trim((string) ($user->phone ?? ''));
        if ($phone !== '') {
            return $phone;
        }
        try {
            $profile = \App\BeyondProfile::find($user->id);
            if ($profile && trim((string) $profile->phone) !== '') {
                $phone = trim((string) $profile->phone);
                $user->phone = $phone;
                $user->save();

                return $phone;
            }
        } catch (\Throwable $e) {
        }
        try {
            if (! empty($user->email)) {
                $customer = \App\Customer::where('is_active', 1)
                    ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
                    ->whereNotNull('phone_number')
                    ->orderByDesc('id')
                    ->first();
                if ($customer && trim((string) $customer->phone_number) !== '') {
                    $phone = trim((string) $customer->phone_number);
                    $user->phone = $phone;
                    $user->save();

                    return $phone;
                }
            }
        } catch (\Throwable $e) {
        }

        return $phone;
    }

    public function notifyCcOnAssignment(Task $task)
    {
        $task->load(['assignments', 'ccRecipients']);
        $assigneeNames = BeyondUser::whereIn('id', $task->assignments->pluck('user_id'))
            ->pluck('name')->filter()->implode(', ') ?: 'the assignee(s)';

        $sent = 0;
        foreach ($task->ccRecipients as $cc) {
            $user = BeyondUser::find($cc->user_id);
            if (! $user || empty($user->phone)) {
                continue;
            }
            $start = $task->start_date
                ? $task->start_date->format('d M Y') . ($task->start_time ? ' ' . substr((string) $task->start_time, 0, 5) : '')
                : '—';
            $deadline = $task->deadline
                ? $task->deadline->format('d M Y') . ($task->deadline_time ? ' ' . substr((string) $task->deadline_time, 0, 5) : '')
                : '—';
            $desc = TaskPersonalization::personalize($task->description ?: '', TaskPersonalization::userVars($user));
            $msg = \App\Support\WhatsAppMessage::taskCcNotice(
                $user->name ?: 'Team Member',
                $assigneeNames,
                $task->title,
                $task->priority,
                $start,
                $deadline,
                $desc
            );

            if ($this->sendPhone($user->phone, $msg)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function notifyAccepted(TaskAssignment $assignment)
    {
        $assignment->load('task');
        $task = $assignment->task;
        $assignee = BeyondUser::find($assignment->user_id);
        if (! $task || ! $assignee) {
            return;
        }

        $assigneeName = $assignee->name ?: 'Assignee';

        // Admin / creator
        $admin = $task->created_by_admin_id ? User::find($task->created_by_admin_id) : null;
        if ($admin && ! empty($admin->phone)) {
            $this->sendPhone($admin->phone, \App\Support\WhatsAppMessage::taskStatusNotice(
                $admin->name,
                'TASK ACCEPTED',
                '📊',
                "*{$assigneeName}* has accepted the task.",
                ['Task' => $task->title]
            ));
        }

        // CC recipients
        foreach (TaskCc::where('task_id', $task->id)->get() as $cc) {
            $user = BeyondUser::find($cc->user_id);
            if (! $user || empty($user->phone)) {
                continue;
            }
            $this->sendPhone(
                $user->phone,
                \App\Support\WhatsAppMessage::taskStatusNotice(
                    $user->name ?: 'CC',
                    'TASK CC — ACCEPTED',
                    '📊',
                    "*{$assigneeName}* has accepted the task you are CC'd on.",
                    ['Task' => $task->title]
                )
            );
        }
    }

    public function notifyProgress(TaskAssignment $assignment, $progress, $status, $comment = null)
    {
        $assignment->load('task');
        $task = $assignment->task;
        $assignee = BeyondUser::find($assignment->user_id);
        if (! $task || ! $assignee) {
            return;
        }
        $assigneeName = $assignee->name ?: 'Assignee';

        if ($status === 'Completed') {
            $admin = $task->created_by_admin_id ? User::find($task->created_by_admin_id) : null;
            if ($admin && ! empty($admin->phone)) {
                $this->sendPhone($admin->phone, \App\Support\WhatsAppMessage::taskStatusNotice(
                    $admin->name,
                    'TASK COMPLETED',
                    '✅',
                    "*{$assigneeName}* completed the task.",
                    ['Task' => $task->title]
                ));
            }
            foreach (TaskCc::where('task_id', $task->id)->get() as $cc) {
                $user = BeyondUser::find($cc->user_id);
                if (! $user || empty($user->phone)) {
                    continue;
                }
                $this->sendPhone(
                    $user->phone,
                    \App\Support\WhatsAppMessage::taskStatusNotice(
                        $user->name ?: 'CC',
                        'TASK CC — COMPLETED',
                        '✅',
                        "*{$assigneeName}* completed the task you are CC'd on.",
                        ['Task' => $task->title]
                    )
                );
            }

            return;
        }

        foreach (TaskCc::where('task_id', $task->id)->get() as $cc) {
            $user = BeyondUser::find($cc->user_id);
            if (! $user || empty($user->phone)) {
                continue;
            }
            $this->sendPhone(
                $user->phone,
                \App\Support\WhatsAppMessage::taskStatusNotice(
                    $user->name ?: 'CC',
                    'TASK CC — PROGRESS UPDATE',
                    '📋',
                    "You are CC on a task assigned to *{$assigneeName}*.",
                    [
                        'Task' => $task->title,
                        'Realization' => $progress.'%',
                        'Status' => $status,
                        'Note' => $comment,
                    ]
                )
            );
        }
    }

    public function notifyReminder(Task $task)
    {
        $task->load('assignments');
        foreach ($task->assignments as $assignment) {
            if (in_array($assignment->status, ['Completed', 'Declined'], true)) {
                continue;
            }
            $user = BeyondUser::find($assignment->user_id);
            if (! $user || empty($user->phone)) {
                continue;
            }
            $deadline = $task->deadline
                ? $task->deadline->format('d M Y') . ($task->deadline_time ? ' ' . substr((string) $task->deadline_time, 0, 5) : '')
                : '—';
            $this->sendPhone(
                $user->phone,
                \App\Support\WhatsAppMessage::taskStatusNotice(
                    $user->name ?: 'Team Member',
                    'TASK REMINDER',
                    '⏰',
                    'Reminder for your assigned task.',
                    [
                        'Task' => $task->title,
                        'Deadline' => $deadline,
                    ],
                    url('/user/tasks')
                )
            );
        }
    }

    public function dispatchTaskNotifications(Task $task)
    {
        $task->load(['assignments', 'ccRecipients']);
        foreach ($task->assignments as $assignment) {
            $this->notifyAssignment($assignment);
        }
        $this->notifyCcOnAssignment($task);
        $task->notifications_sent = true;
        $task->save();
    }
}
