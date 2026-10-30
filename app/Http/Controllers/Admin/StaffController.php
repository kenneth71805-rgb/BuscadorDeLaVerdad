<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StaffRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $staff = User::query()->whereNotNull('role')->with('categories:id,name')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(20)->withQueryString()
            ->through(fn (User $u) => $this->present($u));

        return Inertia::render('admin/staff/index', ['staff' => $staff, 'filters' => ['q' => $q]]);
    }

    public function create()
    {
        return Inertia::render('admin/staff/create', $this->formOptions());
    }

    public function store(StaffRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->forceFill($this->privileged($data) + ['email_verified_at' => now()])->save();
            $user->categories()->sync($this->categoryIds($data));

            AuditLog::record('staff.created', $user, ['role' => $data['role']], $request->user());

            return $user;
        });

        return redirect()->route('admin.staff.index')->with('success', "Account created for {$user->name}.");
    }

    public function edit(User $user)
    {
        abort_if($user->role === null, 404);

        return Inertia::render('admin/staff/edit', ['member' => $this->present($user->load('categories:id,name'), true)]
            + $this->formOptions());
    }

    public function update(StaffRequest $request, User $user)
    {
        abort_if($user->role === null, 404);
        $data = $request->validated();
        $new = $this->privileged($data);

        $this->guardSelfAndLastAdmin($request->user(), $user, $new);

        DB::transaction(function () use ($data, $new, $user, $request) {
            $before = $user->only(['role', 'is_active', 'position', 'can_override', 'can_publish']);
            $beforeCats = $user->categories()->pluck('categories.id')->sort()->values()->all();

            $user->forceFill(['name' => $data['name'], 'email' => $data['email']] + $new);
            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->setRememberToken(\Illuminate\Support\Str::random(60)); // signs out "remember me" logins too
            }
            $user->save();
            $user->categories()->sync($this->categoryIds($data));

            $changes = [];
            foreach ($new as $key => $value) {
                $old = $before[$key] instanceof Role ? $before[$key]->value : $before[$key];
                $value = $value instanceof Role ? $value->value : $value;
                if ($old !== $value) {
                    $changes[$key] = ['from' => $old, 'to' => $value];
                }
            }
            if ($beforeCats !== collect($this->categoryIds($data))->sort()->values()->all()) {
                $changes['categories'] = true;
            }
            if (! empty($data['password'])) {
                $changes['password_reset'] = true;
            }

            AuditLog::record('staff.updated', $user, $changes, $request->user());
        });

        if ((! $user->is_active || ! empty($data['password'])) && config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete(); // revoke live sessions
        }

        return redirect()->route('admin.staff.index')->with('success', "{$user->name} updated.");
    }

    /** Fields that only an Admin may set; never mass-assignable. */
    private function privileged(array $data): array
    {
        $role = Role::from($data['role']);

        return [
            'role' => $role,
            'is_active' => (bool) $data['is_active'],
            'position' => $data['position'] ?? null,
            'can_override' => $role === Role::Admin && ! empty($data['can_override']),
            'can_publish' => $role === Role::CategoryHead && ! empty($data['can_publish']),
        ];
    }

    private function categoryIds(array $data): array
    {
        return Role::from($data['role']) === Role::CategoryHead ? array_values($data['category_ids'] ?? []) : [];
    }

    private function guardSelfAndLastAdmin(User $actor, User $target, array $new): void
    {
        if ($actor->is($target) && ($new['role'] !== $target->role || ! $new['is_active'])) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role or deactivate your own account.']);
        }

        $losesAdmin = $target->role === Role::Admin && $target->is_active
            && ($new['role'] !== Role::Admin || ! $new['is_active']);

        if ($losesAdmin && User::where('role', Role::Admin->value)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['role' => 'This is the last active Admin and cannot be demoted or deactivated.']);
        }
    }

    private function formOptions(): array
    {
        return [
            'roles' => collect(Role::cases())->map(fn (Role $r) => ['value' => $r->value, 'label' => $r->label()]),
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
        ];
    }

    private function present(User $u, bool $detail = false): array
    {
        $row = [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role?->value,
            'role_label' => $u->role?->label(),
            'position' => $u->position,
            'is_active' => $u->is_active,
            'categories' => $u->categories->pluck('name')->all(),
        ];

        return $detail ? $row + [
            'category_ids' => $u->categories->pluck('id')->all(),
            'can_override' => $u->can_override,
            'can_publish' => $u->can_publish,
        ] : $row;
    }
}
