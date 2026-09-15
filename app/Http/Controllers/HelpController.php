<?php

namespace App\Http\Controllers;

use App\Support\HelpCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function content(Request $request, string $article): JsonResponse
    {
        $articles = HelpCatalog::articles(app()->getLocale(), $request->user()->role);
        $guide = collect($articles)->firstWhere('id', $article);
        abort_if($guide === null, 404);

        return response()->json($guide)->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request, ?string $article = null): Response
    {
        $articles = HelpCatalog::articles(app()->getLocale(), $request->user()->role);
        abort_if($article !== null && ! in_array($article, array_column($articles, 'id'), true), 404);

        return Inertia::render('help/Index', [
            'articles' => $articles,
            'selectedArticle' => $article,
        ]);
    }
}
