<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class BinaryResponseController extends Controller
{
    /**
     * Download PDF ticket.
     *
     * @Response({"code": 200, "description": "PDF file download.", "content_type": "application/pdf"})
     * @Response({"code": 401, "description": "(Unauthorized) Invalid or missing Access Token."})
     */
    public function download(): Response
    {
        return response('', 200);
    }
}
