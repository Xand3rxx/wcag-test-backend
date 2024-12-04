<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Services\AccessibilityService;
use App\Http\Requests\HTMLFileUploadRequest;
use Symfony\Component\HttpFoundation\Response;

class AccessibilityController extends Controller
{
    /**
     * Handle the incoming request.
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(HTMLFileUploadRequest $request, AccessibilityService $service): JsonResponse
    {
        // Get the uploaded file
        $htmlFile = $request->file('file');

        // Read the content of the uploaded file
        $htmlContent = file_get_contents($htmlFile->getRealPath());

        // Check if the content is empty
        if (empty($htmlContent)) {
            return $this->responseFailed([], 'The uploaded HTML file is empty.', Response::HTTP_BAD_REQUEST);
        }

        return $this->responseSuccess($service->analyzeAccessibility($htmlContent));
    }
}
