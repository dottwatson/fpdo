<?php
namespace Fpdo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FpdoMakeTable extends Command{
    protected $signature = 'fpdo:make-table {name} {path}';
    protected $description = 'Createa new Table (eg. fpdo:make-table mytablename App\My\Namespace psr4 compliant)';

    public function handle()
    {
        $name       = $this->argument('name');
        $path       = $this->argument('path');

        $namespace  = $this->normalizeNamespace($path);
        $targetFile = $this->normalizePath($path);

        $blocks             = explode('\\',$namespace);
        $tableClassName     = array_pop($blocks);
        $namespace          = implode('\\',$blocks);

        $targetPath         = dirname($targetFile);
        
        if(File::exists($targetFile)){
            $this->error("{$targetFile} already exists");
            exit;
        }
        
        if (!File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

       
        $stubContent = $this->getStubContent([
            'namespace' => $namespace,
            'class'     => $tableClassName,
            'name'      => $name
        ]);

        File::put($targetFile, $stubContent);

        $this->info("Table created in {$targetFile}");
    }


    protected function normalizePath(string $namespace)
    {
        $path = trim($namespace);
        $path = trim($path,'/\\');

        $path = str_replace(DIRECTORY_SEPARATOR,'/',$path);
        $path = preg_replace('#/+#','/',$path);
        $path = preg_replace('#^App/#','app/',$path);


        return base_path("{$path}.php");
    }

    protected function normalizeNamespace(string $namespace)
    {
        $namespace = trim($namespace);
        $namespace = trim($namespace,'/\\');
        $namespace = preg_replace('#\\+#','\\',$namespace);

        return $namespace;
    }

    protected function getStubContent(array $vars = [])
    {
        $params = [];
        foreach($vars as $k=>$v){
            $params['{{ '.$k.' }}'] = $v;
        }

        $stubFile = realpath(__DIR__.'/../../../stubs/table.stub');
        $content  = file_get_contents($stubFile);

        return str_replace(
            array_keys($params),
            array_values($params),
            $content
        );

    }

}