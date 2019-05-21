<?php


namespace Eslym\LangEditor\Providers;


use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Eslym\LangEditor\Contracts\LangEditor as LangEditorContract;
use Eslym\LangEditor\Tools\LangEditor;

class LangEditorServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->app->singleton(LangEditorContract::class, function(){
            return new LangEditor(
                app('path.lang'),
                app('translation.loader')
            );
        });

        $this->app->alias(
            LangEditorContract::class,
            class_basename(LangEditorContract::class)
        );

        $this->loadViewsFrom(
            realpath(__DIR__.'/../../res/views'),
            'lang-editor'
        );
    }
}