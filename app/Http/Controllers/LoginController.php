<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAllocation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login()
    {
        return view('login.index');
    }
}
