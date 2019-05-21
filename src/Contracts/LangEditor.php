<?php


namespace Eslym\LangEditor\Contracts;


interface LangEditor
{
    public function allLanguages(): array;

    public function allTranslations(): array;

    public function setTranslation(string $key, string $lang, ?string $value);
}