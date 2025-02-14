<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardIndexComponent extends Component
{

    public $icon;
    public $title;
    public $subtitle;
    public $descrption;
    public $href;
    public $lista;
    /**
     * Create a new component instance.
     */
    public function __construct($icon = 'ki-setting', $title = 'Matriz logistico', $subtitle = 'Default role', $descrption = null, $href = null, $lista = null)
    {
        $this->icon = $icon;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->descrption = $descrption;
        $this->href = $href;
        $this->lista = $lista;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.card-index-component');
    }
}
