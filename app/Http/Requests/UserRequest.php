<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    /** UserController authorizes through UserPolicy. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $actor = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target)],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => array_filter([
                'nullable',
                Rule::exists('branches', 'id')->whereNull('deleted_at'),
                // Branch-restricted managers can only place users in their own branch.
                $actor->canAccessAllBranches() ? null : Rule::in([$actor->branch_id]),
            ]),
            'is_active' => ['boolean'],
            'password' => [$target ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $roles = (array) $this->input('roles', []);
            $actor = $this->user();
            $target = $this->route('user');

            if (in_array(User::SUPER_ADMIN_ROLE, $roles, true) && ! $actor->isSuperAdmin()) {
                $validator->errors()->add('roles', 'Only a Super Admin can assign the Super Admin role.');
            }

            if ($target && $target->is($actor) && $actor->isSuperAdmin() && ! in_array(User::SUPER_ADMIN_ROLE, $roles, true)) {
                $validator->errors()->add('roles', 'You cannot remove the Super Admin role from your own account.');
            }

            if ($target && $target->is($actor) && ! $this->boolean('is_active', true)) {
                $validator->errors()->add('is_active', 'You cannot deactivate your own account.');
            }
        }];
    }

    /** Attributes safe to fill on the user; roles are synced separately. */
    public function userData(): array
    {
        $data = $this->safe()->except(['roles', 'password', 'password_confirmation']);

        if ($this->filled('password')) {
            $data['password'] = $this->validated('password');
        }

        return $data;
    }
}
