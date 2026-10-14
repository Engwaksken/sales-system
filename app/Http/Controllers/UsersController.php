<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\Branch;
use App\Models\User;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UsersController extends Controller
{
    use ExportsCsv;

    public function exportCsv(Request $request)
    {
        // Deliberately excludes password/remember_token regardless of what's requested —
        // those must never leave the system via an export, unlike every other module's
        // generic export which lets the user pick from all columns.
        $allColumns = ['name', 'email', 'role', 'branch_id'];
        $columns = array_values(array_intersect($allColumns, $request->query('columns', $allColumns)));
        if (empty($columns)) {
            $columns = $allColumns;
        }

        $rows = User::with('branch')->get()->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $col === 'branch_id' ? ($row->branch?->name ?? '') : $row->{$col};
            }
            return $out;
        });

        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);

        return $this->csvResponse('users-'.now()->format('Ymd').'.csv', $headers, $rows);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['name', 'email', 'role', 'branch_id'];
        $columns = array_values(array_intersect($allColumns, $request->query('columns', $allColumns)));
        if (empty($columns)) {
            $columns = $allColumns;
        }

        $rows = User::with('branch')->get()->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $col === 'branch_id' ? ($row->branch?->name ?? '') : $row->{$col};
            }
            return $out;
        });

        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Users', 'headers' => $headers, 'rows' => $rows, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->stream('users-'.now()->format('Ymd').'.pdf');
    }

    public function index(Request $request)
    {
        $query = User::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $users = $query->latest()->paginate($this->perPage($request));
        $branch_options = Branch::orderBy('id')->get();
        $periodStats = $this->periodStats(User::class, 'created_at');
        return view('users.index', ['items' => $users, 'branch_options' => $branch_options, 'periodStats' => $periodStats]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,cashier,customer,manager',
            'branch_id' => 'nullable|exists:branches,id',
            'avatar' => 'nullable|image|max:4096',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        if ($request->hasFile('avatar')) {
            $validated['profile_pic'] = $request->file('avatar')->store('avatars', 'public');
        }
        unset($validated['avatar']);
        $user = User::create($validated);

        try {
            Mail::to($user->email)->send(new WelcomeEmail($user, route('login')));
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function show(User $user)
    {
        return view('users.show', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,cashier,customer,manager',
            'branch_id' => 'nullable|exists:branches,id',
            'avatar' => 'nullable|image|max:4096',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        if ($request->hasFile('avatar')) {
            $validated['profile_pic'] = $request->file('avatar')->store('avatars', 'public');
        }
        unset($validated['avatar']);

        $user->update($validated);
        return redirect()->route('users.index')->with('status', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('status', 'User deleted.');
    }
}
