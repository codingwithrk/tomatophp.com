<?php

namespace Modules\Cms\View\Components;

use App\Models\AccountLog;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CommentLog extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public AccountLog $log)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('cms::components.comment-log');
    }
}