<?php

namespace Eslym\LangEditor\Controllers;

use Eslym\LangEditor\Facades\LangEditor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class LangEditorController extends BaseController
{
    public function index(){
        return response()->view('lang-editor::index', ['languages' => LangEditor::allLanguages()]);
    }

    public function trans(){
        return response()->json(['data' => array_values(LangEditor::allTranslations())]);
    }

    public function update(Request $request){
        $data = $request->only(['key', 'lang', 'value']);
        Validator::make($data, [
            'key' => 'required|regex:/^(?:[0-9a-z\-_]+::)?(?:[0-9a-z\-_]+(?:\.))*[0-9a-z\-_]+/i',
            'lang' => 'required|in:'.join(',', LangEditor::allLanguages()),
            'value' => 'nullable|string',
        ])->validate();
        LangEditor::setTranslation($data['key'], $data['lang'], $data['value']);
        return response()->json('success');
    }

    public function delete(Request $request){
        $data = $request->only(['keys']);
        Validator::make($data, [
            'keys' => 'requried|array',
            'keys.*' => 'required|regex:/^(?:[0-9a-z\-_]+::)?(?:[0-9a-z\-_]+(?:\.))*[0-9a-z\-_]+/i',
        ])->validate();
        LangEditor::deleteTranslation($data['keys']);
        return response()->json('success');
    }
}
