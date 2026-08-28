<?php

namespace App\Policies;

use App\Models\CarExpense;
use App\Models\User;

class CarExpensePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, CarExpense $expense): bool { return $expense->transaction?->user_id === $user->id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, CarExpense $expense): bool { return $this->view($user, $expense); }
    public function delete(User $user, CarExpense $expense): bool { return $this->view($user, $expense); }
}
