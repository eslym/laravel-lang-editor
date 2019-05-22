<?php


namespace Eslym\LangEditor\Tools;

use Eslym\LangEditor\Contracts\LangEditor as LangEditorContract;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LangEditor implements LangEditorContract
{
    /**
     * @var string
     */
    protected $langPath;

    /**
     * @var Loader
     */
    protected $transLoader;

    /**
     * @var string[][]
     */
    protected $trans = null;

    /**
     * @var string[]
     */
    protected $languages = null;

    /**
     * LangEditor constructor.
     * @param string $langPath
     * @param Loader $transLoader
     */
    public function __construct(string $langPath, $transLoader)
    {
        $this->langPath = $langPath;
        $this->transLoader = $transLoader;
    }

    public function allLanguages(): array
    {
        if ($this->languages) {
            return $this->languages;
        }
        $langs = [];
        $langs[] = array_map([File::class, 'name'], File::glob($this->langPath . '/*/'));
        $langs[] = array_map([File::class, 'name'], File::glob($this->langPath . '/vendor/*/*/'));
        foreach ($this->transLoader->namespaces() as $dir) {
            $langs[] = array_map([File::class, 'name'], File::glob($dir . '/*/'));
        }
        $langs = array_unique(Arr::flatten($langs));
        if ($vendor = array_search('vendor', $langs)) {
            array_splice($langs, $vendor, 1);
        }
        return $this->languages = array_values($langs);
    }

    public function allTranslations(): array
    {
        $records = [];
        $groups = array_map([File::class, 'name'], File::glob($this->langPath . '/*/*.php'));
        $this->load($records, $groups);
        $namespaces = array_unique(array_merge(
            array_keys($this->transLoader->namespaces()),
            array_map([File::class, 'name'], File::glob($this->langPath . '/vendor/*/'))
        ));
        $paths = $this->transLoader->namespaces();
        foreach ($namespaces as $namespace) {
            $groups = [File::glob($this->langPath . "/vendor/$namespace/*/*.php")];
            if (isset($paths[$namespace])) {
                $groups [] = File::glob($paths[$namespace] . "/*/*.php");
            }
            $groups = array_unique(array_map([File::class, 'name'], Arr::flatten($groups)));
            $this->load($records, $groups, $namespace);
        }
        return $records;
    }

    public function setTranslation(string $key, string $lang, ?string $value)
    {
        $namespace = null;
        if (Str::contains($key, '::')) {
            [$namespace, $key] = explode('::', $key, 2);
        }
        $group = Str::before($key, '.');
        $data = [];
        $data[$group] = $this->transLoader->load($lang, $group, $namespace);
        if(empty($value)){
            Arr::forget($data, $key);
        } else {
            data_set($data, $key, $value);
        }
        $path = isset($namespace) ?
            $this->langPath . "/vendor/$namespace/$lang/$group.php" :
            $this->langPath . "/$lang/$group.php";
        if ((
                (!isset($data[$group])) ||
                (is_array($data[$group]) && empty($data[$group]))
            ) && File::exists($path)
        ) {
            File::delete($path);
            return;
        }
        if(!File::exists(File::dirname($path))){
            File::makeDirectory(File::dirname($path), 0755, true);
        }
        File::put($path, "<?php\n\nreturn ".$this->export($data[$group]).";");
    }

    public function deleteTranslations(array $keys){
        $data = [];
        foreach ($this->allLanguages() as $lang){
            foreach ($keys as $key){
                $namespace = null;
                if (Str::contains($key, '::')) {
                    [$namespace, $key] = explode('::', $key, 2);
                }
                $group = Str::before($key, '.');
                if(!isset($data[$lang][$group])){
                    $data[$lang][$group] = $this->transLoader->load($lang, $group, $namespace);
                }
                Arr::forget($data[$lang], $key);
            }
            foreach ($data[$lang] as $group => $trans){
                $path = isset($namespace) ?
                    $this->langPath . "/vendor/$namespace/$lang/$group.php" :
                    $this->langPath . "/$lang/$group.php";
                if ((
                        (!isset($trans[$group])) ||
                        (is_array($trans[$group]) && empty($trans[$group]))
                    ) && File::exists($path)
                ) {
                    File::delete($path);
                    return;
                }
                if(!File::exists(File::dirname($path))){
                    File::makeDirectory(File::dirname($path), 0755, true);
                }
                File::put($path, "<?php\n\nreturn ".$this->export($trans[$group]).";");
            }
        }
    }

    public function load(&$records, $groups, $namespace = null)
    {
        foreach ($groups as $group) {
            foreach ($this->allLanguages() as $lang) {
                $trans = $this->transLoader->load($lang, $group, $namespace);
                if (!is_array($trans)) {
                    $key = $namespace ? $namespace . '::' . $group : $group;
                    if (!isset($records[$key]['key'])) {
                        $records[$key]['key'] = $key;
                    }
                    $records[$key][$lang] = $trans;
                    continue;
                }
                foreach (Arr::dot($trans) as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    $lang_key = $namespace ? "$namespace::$group.$key" : "$group.$key";
                    if (!isset($records[$lang_key]['key'])) {
                        $records[$lang_key]['key'] = $lang_key;
                    }
                    $records[$lang_key][$lang] = $value;
                }
            }
        }
    }

    public function export($var, string $indent = '')
    {
        if(is_array($var)){
            if (empty($var)) {
                return '[]';
            }
            $result = "[\n";
            foreach ($var as $key => $value) {
                if(empty($value)) continue;
                $key = var_export($key, true);
                $value = $this->export($value, "$indent    ");
                $result .= "$indent    $key => $value,\n";
            }
            return $result . "$indent]";
        } else {
            return var_export($var, true);
        }
    }

    public function routes(Router $router = null){
        $router = $router ?? app('router');
        $router->name('lang-editor::')
            ->namespace("\\Eslym\\LangEditor\\Controllers")
            ->group(function (Router $router){
                $router->get('langs', 'LangEditorController@index')
                    ->name('index');
                $router->get('langs.json', 'LangEditorController@trans')
                    ->name('trans');
                $router->post('langs.json', 'LangEditorController@update')
                    ->name('update');
            });
    }
}