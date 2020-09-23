<?php namespace Andesite\CliCommand;

use Andesite\CliCommand\Migrate\Module;
use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Core\Env\Env;
use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\Ghost\GhostManager;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use CaseHelper\CamelCaseHelper;
use PhpParser\Node\Expr\UnaryMinus;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Andesite\GhostGenerator\GhostGenerator;
use Symfony\Component\HttpFoundation\File\File;
use ZipArchive;


/**
 * @command-group ghost
 */
class Ghost extends CommandModule{
	/**
	 * @alias       ghost
	 * @description Generates ghosts
	 */
	public function generate(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){ GhostGenerator::Service()->setup($this->style)->generate($this->input->getArgument('name')); }
		} )->addArgument('name', InputArgument::OPTIONAL);
	}


	/**
	 * @alias       create
	 * @description Generates ghosts codex helpers
	 */
	public function create(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				GhostGenerator::Service()->setup($this->style)->create($this->input->getArgument('name'));
			}
		} )->addArgument('name', InputArgument::REQUIRED);
	}

	/**
	 * @alias       codex
	 * @description Generates ghosts codex helpers
	 */
	public function codex(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				CodexHelperGenerator::Service()->execute($this->style, $this->config['codexhelper']);
			}
		} )->addArgument('name', InputArgument::OPTIONAL);
	}

	/**
	 * @description Generates frontend ghosts
	 */
	public function js(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$this->style->note('Not implemented...');
			}
		} );
	}

	/**
	 * @description Exports ghost
	 */
	public function export(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$namespace = Env::Service()->get('modules.ghosts.config.namespace');
				$ghost = $this->input->getArgument('ghost');
				$where = $this->input->getOption('where');
				$class = $namespace . "\\" . $ghost;
				$path = Env::Service()->get('path.tmp');

				if (!class_exists($class)){
					$this->style->error('Ghost ' . $ghost . ' is not exists');
					return;
				}

				$ghosts = $class::search(Filter::where($where))->collect();
				$this->style->writeln(count($ghosts) . ' ghosts found');
				$this->style->writeln('path: ' . $path);
				foreach ($ghosts as $item){
					$filename = $ghost . '-' . $item->id;
					/** @var \Andesite\Ghost\Ghost $item */
					$this->style->writeln('exporting: ' . $item->id . ' as ' . $filename . '.json');
					$data = ['ghost' => $ghost, 'data' => $item->decompose(), 'attachments' => []];
					$collections = $item->getAttachmentCollections();
					foreach ($collections as $collection){
						foreach ($collection as $attachment){
							$data['attachments'][] = [
								'category'    => $collection->category->name,
								'meta'        => $attachment->meta->get(),
								'filename'    => $attachment->filename,
								'ordinal'     => $attachment->sequence
							];
						}
					}
					file_put_contents($path . '/' . $filename . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
					if (count($data['attachments'])){
						$rootPath = realpath($class::$model->getAttachmentStorage()->getPath() . $item->getPath());
						$this->style->writeln('... Creating attachment archive from ' . $rootPath);
						$zip = new ZipArchive();
						$zip->open($path . '/' . $filename . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
						/** @var SplFileInfo[] $files */
						$files = new RecursiveIteratorIterator(
							new RecursiveDirectoryIterator($rootPath),
							RecursiveIteratorIterator::LEAVES_ONLY
						);
						foreach ($files as $name => $file){
							if (!$file->isDir()){
								$this->style->writeln('... Compressing file: ' . $file->getFileName());
								$filePath = $file->getRealPath();
								$relativePath = substr($filePath, strlen($rootPath) + 1);
								$zip->addFile($filePath, $relativePath);
							}
						}
						$zip->close();
						$this->style->writeln('... Done');
					}
				}
			}
		} )
			->addArgument('ghost', InputArgument::REQUIRED)
			->addOption('where', 'w', InputOption::VALUE_REQUIRED);
	}

	/**
	 * @description Import ghost
	 */
	public function import(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$namespace = Env::Service()->get('modules.ghosts.config.namespace.ghost');

				$pattern = $this->input->getArgument('files');
				$files = glob(Env::Service()->get('path.tmp') . $pattern . '.json');
				if ($id = intval($this->input->getOption('id'))) $files = [$files[0]];

				$overwrite = $this->input->getOption('overwrite');

				foreach ($files as $file){
					$import = json_decode(file_get_contents($file), true);
					/** @var \Andesite\Ghost\Ghost $class */
					$class = $namespace . "\\" . $import['ghost'];
					$as = $id ? $id : ( $overwrite ? $import['data']['id'] : false );
					if ($id && !$overwrite && !is_null($class::pick($id))){
						$this->style->error($import['ghost'] . " already exists. You sholuld use the --overwrite option!");
						return;
					}
					$this->style->writeln("Starting import " . $import['ghost'] . ' (' . $import['data']['id'] . ') as ' . ( $as ? $as : 'new ' ));
					if (!$as || is_null($item = $class::pick($as))){
						$item = new $class();
					}

					if ($item->isExists()){
						$this->style->writeln("... opening existing " . $import['ghost'] . ' (' . $as . ')');
						if ($as) $import['data']['id'] = $as;
					}else{
						$this->style->writeln("... creating new " . $import['ghost']);
						unset($import['data']['id']);
					}

					$item->compose($import['data'], true);
					$item->save();
					$this->style->writeln("... " . $import['ghost'] . ' saved as ' . $item->id);

					if (count($import['attachments'])){
						$tmp = Env::Service()->get('path.tmp') . 'import-archives';
						$archive = substr($file, 0, -4) . 'zip';
						if (!file_exists($archive)) $this->style->warning("Attachment archive (" . $archive . ') is missing!');
						else{
							if (is_dir($tmp)){
								( function ($dir){
									$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
									foreach ($files as $fileinfo) ( $fileinfo->isDir() ? 'rmdir' : 'unlink' )($fileinfo->getRealPath());
									rmdir($dir);
								} )($tmp);
							}
							mkdir($tmp);
							$this->style->writeln("... opening archive " . $archive);
							$zip = new ZipArchive;
							if ($zip->open($archive) === true){
								$zip->extractTo($tmp);
								$zip->close();
								$this->style->write("... remove existing attachments ");

								foreach ($item->getAttachmentCollections() as $collection){
									foreach ($collection as $attachment){
										$attachment->delete();
									}
								}
								$this->style->writeln("... done");


								foreach ($import['attachments'] as $attachment){
									$collection = $item->getAttachmentCollection($attachment['category']);
									try{
										$this->style->write("... adding file \"".$attachment['filename']."\" to \"" . $attachment['category']."\" category");
										$collection->addFile(new File($tmp . '/' . $attachment['filename']));
										$this->style->writeln(" ... done ");
									}catch ( \Andesite\Attachment\Exception $exception){
										$this->style->warning($exception->getMessage());
									}
								}
								$item->save();
							}else{
								$this->style->warning("Could not open archive (" . $archive . ')!');
							}
						}
					}
					$this->style->writeln("... done ");

				}
				$this->style->writeln("DONE.");
			}
		} )
			->addArgument('files', InputArgument::REQUIRED, 'file or glob pattern of files')
			->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'insertion id (one import)')
			->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'inserting (or updating) on original id');
	}

}
