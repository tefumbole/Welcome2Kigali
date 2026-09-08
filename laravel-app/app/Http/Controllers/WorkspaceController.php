<?php

namespace App\Http\Controllers;

use App\Support\UserWorkspaces;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function choose(Request $request)
    {
        $user = Auth::guard('web')->user();
        $beyond = Auth::guard('beyond')->user();
        if (! $user && ! $beyond) {
            return redirect('/login');
        }

        $keys = UserWorkspaces::keys($user, $beyond);
        if (count($keys) === 1) {
            UserWorkspaces::set($keys[0]);

            return redirect(UserWorkspaces::urlFor($user, $keys[0], $beyond));
        }
        if (count($keys) === 0) {
            return redirect('/admin');
        }

        return view('beyond.auth.workspace', [
            'title' => __('site.workspace.title'),
            'header' => '<h1 class="text-2xl font-bold text-brand-blue">'.e(__('site.workspace.title')).'</h1>'
                .'<p class="text-brand-blue text-sm mt-1">'.e(__('site.workspace.subtitle')).'</p>',
            'workspaces' => $keys,
            'labels' => UserWorkspaces::labels(),
            'current' => UserWorkspaces::current($user),
        ]);
    }

    public function switchWorkspace(Request $request, $workspace)
    {
        $user = Auth::guard('web')->user();
        $beyond = Auth::guard('beyond')->user();
        if (! $user && ! $beyond) {
            return redirect('/login');
        }

        $allowed = UserWorkspaces::keys($user, $beyond);
        if (! in_array($workspace, $allowed, true)) {
            return redirect()->route('workspace.choose')
                ->withErrors(['workspace' => __('site.workspace.unavailable')]);
        }

        if ($workspace === UserWorkspaces::MEMBER && $user) {
            UserWorkspaces::ensureCustomer($user);
        }

        UserWorkspaces::set($workspace);

        return redirect(UserWorkspaces::urlFor($user, $workspace, $beyond));
    }
}
