<?php

namespace App\Http\Middleware;

use App\Enums\TermsDocument;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class EnsureTermsAccepted
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $document): Response
    {
        try {
            $terms = TermsDocument::from($document);
        } catch (ValueError) {
            abort(500, 'Invalid terms document middleware parameter.');
        }

        $user = $request->user();

        if (! $user || ! $user->hasAcceptedTerms($terms)) {
            return redirect()
                ->route($terms->redirectRoute())
                ->withErrors([
                    'terms' => 'Please accept the '.$terms->label().' before continuing.',
                ]);
        }

        return $next($request);
    }
}
