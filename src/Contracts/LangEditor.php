<?php


namespace Eslym\LangEditor\Contracts;


use Illuminate\Routing\Router;

interface LangEditor
{
    public function allLanguages(): array;

    public function allTranslations(): array;

    public function setTranslation(string $key, string $lang, ?string $value);

    public function routes(Router $router = null);
}