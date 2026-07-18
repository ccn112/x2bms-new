<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves PRIVATE resident media (KYC images, legal documents) with per-record
 * authorization — the file is NEVER a public URL. Combine with a signed, short-lived
 * URL and the ResidentPolicy so only an authorized viewer can fetch it.
 */
class PrivateMediaController extends Controller
{
    private const IMAGE_FIELDS = ['id_front_path', 'id_back_path', 'portrait_path'];

    /** GET /media/residents/{resident}/{field}  (signed + auth) */
    public function resident(Request $request, Resident $resident, string $field): StreamedResponse
    {
        Gate::authorize('view', $resident);

        if (! in_array($field, self::IMAGE_FIELDS, true)) {
            throw new NotFoundHttpException;
        }

        $path = $resident->{$field};
        $disk = Storage::disk('local');
        if (! $path || ! $disk->exists($path)) {
            throw new NotFoundHttpException;
        }

        return $disk->response($path); // streams with correct mime, no public URL
    }

    /** GET /media/residents/{resident}/documents/{index}  (signed + auth) */
    public function residentDocument(Request $request, Resident $resident, int $index): StreamedResponse
    {
        Gate::authorize('view', $resident);

        $documents = (array) ($resident->documents ?? []);
        $path = $documents[$index] ?? null;
        $disk = Storage::disk('local');
        if (! $path || ! $disk->exists($path)) {
            throw new NotFoundHttpException;
        }

        return $disk->response($path);
    }
}
