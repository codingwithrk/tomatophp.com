<?php

namespace Modules\Cms\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SocailIcon extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $icon = null,
        public ?string $url = null,
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('cms::components.social-icon');
    }
}