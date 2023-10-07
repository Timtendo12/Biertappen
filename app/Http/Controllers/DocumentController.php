<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index() {
        $docs = Storage::files('/docs/');
        // for each file remove the parent directory and the file extension
        foreach($docs as $key => $doc) {
            $docs[$key] = substr($doc, 5, -3);
            // replace all _ with -
            $docs[$key] = str_replace('_', '-', $docs[$key]);
        }

        return view('admin.docs.index', ['docs' => $docs]);
    }

    public function show($page) {
        $page = str_replace('-', '_', $page);
        $doc = Storage::get('/docs/' . $page . '.md');
        if ($doc) {
            return view('admin.docs.show', ['doc' => $doc, 'page' => $page]);
        } else {
            return back()->with('error', 'Document not found!');
        }
    }
}
