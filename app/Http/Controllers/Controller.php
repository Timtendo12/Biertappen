<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Gives controllers $this->authorize(). Laravel no longer includes it by
     * default, and every deck-scoped action here is policy-gated.
     */
    use AuthorizesRequests;
}
