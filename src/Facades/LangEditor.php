<?php


namespace Eslym\LangEditor\Facades;


use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;
use \Eslym\LangEditor\Contracts\LangEditor as LangEditorContract;

/**
 * Class LangEditor
 * @package Eslym\LangEditor\Facades
 *
 * @method static string[] allLanguages()
 * @method static array allTranslations()
 * @method static void setTranslation(string $key, string $lang, string $value)
 * @method static void routes(Router $router = null)
 */
class LangEditor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LangEditorContract::class;
    }
}