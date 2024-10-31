<?php
namespace Fpdo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FpdoMakeDatabase extends Command{
    protected $signature = 'fpdo:make-database {name} {path}';
    protected $description = 'Createa new database comprensie of example table (eg. fpdo:make-database mydatabasename App\My\Namespace psr4 compliant)';

    public function handle()
    {
        $name       = $this->argument('name');

        $path       = $this->argument('path');
        $targetFile = $this->normalizePath($path);
        $namespace  = $this->normalizeNamespace($path);
        

        $targetPath = dirname($targetFile);
        
        if(File::exists($targetFile)){
            $this->warn("{$targetFile} already exists");
            exit;
        }
        
        if (!File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        $stubContent = $this->getStubContent([
            'namespace'     => $namespace,
            'class'         => 'Database',
            'name'          => $name,
            'exampletable'  => '\\'.$namespace.'\\Tables\\ExampleTable::class'
        ]);

        File::put($targetFile, $stubContent);
       
       
        Artisan::call('fpdo:make-table',[
            'name'=>'example',
            'path'=>$namespace.'\\Tables\\ExampleTable'
        ]);


        $this->info("Database created in {$targetFile}");
    }


    protected function normalizePath(string $namespace)
    {
        $path = trim($namespace);
        $path = trim($path,'/\\');

        $path = str_replace(DIRECTORY_SEPARATOR,'/',$path);
        $path = preg_replace('#/+#','/',$path);
        $path = preg_replace('#^App/#','app/',$path);

        return base_path("{$path}/Database.php");
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

        $stubFile = realpath(__DIR__.'/../../../stubs/database.stub');
        $content  = file_get_contents($stubFile);

        return str_replace(
            array_keys($params),
            array_values($params),
            $content
        );

    }

}