<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Services\Learning\MethodLibrary;
use Illuminate\View\View;

/**
 * The reference desk — public, because "why should I trust this product's
 * pedagogy" is a question asked before signing up, not after.
 *
 * Thin by the usual rule: the registry decides what exists, the service decides
 * where the prose comes from, and this only turns a slug into a 404 or a page.
 */
class MethodController extends Controller
{
    public function __construct(private readonly MethodLibrary $methods) {}

    public function index(): View
    {
        return view('frontend.methods.index', ['methods' => $this->methods->all()]);
    }

    public function show(string $slug): View
    {
        $method = $this->methods->find($slug);

        abort_if($method === null, 404);

        return view('frontend.methods.show', [
            'slug' => $slug,
            'method' => $method,
            // The whole registry too: the entry ends in links to its siblings.
            'methods' => $this->methods->all(),
            'entry' => $this->methods->entry($slug, app()->getLocale()),
        ]);
    }
}
