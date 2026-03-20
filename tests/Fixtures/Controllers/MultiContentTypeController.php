<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class MultiContentTypeController extends Controller
{
    /**
     * Export report in multiple formats.
     *
     * @Response({"code": 200, "description": "PDF export.", "content_type": "application/pdf"})
     * @Response({"code": 200, "content_type": "application/json"})
     * @Response({"code": 401, "description": "Unauthorized."})
     */
    public function export(): Response
    {
        return response('', 200);
    }
}
