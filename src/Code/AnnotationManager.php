<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2014 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace Scabbia\Code;

use Scabbia\Code\AnnotationScanner;
use Scabbia\Framework\ApplicationBase;
use Scabbia\Framework\Core;
use Scabbia\Helpers\FileSystem;
use Scabbia\Yaml\Parser;

/**
 * AnnotationManager
 *
 * @package     Scabbia\Code
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class AnnotationManager
{
    /** @type ApplicationBase         $application        application */
    public $application;
    /** @type Parser|null             $parser             yaml parser */
    public $parser = null;
    /** @type AnnotationScanner|null  $annotationScanner  annotation scanner */
    public $annotationScanner = null;
    /** @type array                   $annotationMap      annotation map */
    public $annotationMap = null;


    /**
     * Initializes an annotation manager
     *
     * @param ApplicationBase  $uApplication   application
     *
     * @return AnnotationManager
     */
    public function __construct(ApplicationBase $uApplication)
    {
        $this->application = $uApplication;
    }

    /**
     * Loads saved annotations or start over scanning
     * 
     * @return void
     */
    public function load()
    {
        $tAnnotationMapPath = $this->application->writablePath . "/annotations.php";

        // TODO and not in development mode
        if (file_exists($tAnnotationMapPath)) {
            $this->annotationMap = require $tAnnotationMapPath;
        } else {
            $this->scan();
            // TODO if not in readonly mode
            FileSystem::writePhpFile($tAnnotationMapPath, $this->annotationMap);
        }
    }

    /**
     * Scans all files to find annotations
     *
     * @return void
     */
    public function scan()
    {
        // initialize annotation scanner
        $this->annotationScanner = new AnnotationScanner();

        // -- scan composer maps
        $tFolders = Core::$loader->getComposerFolders();

        foreach ($tFolders as $tPath) {
            if (file_exists($tPath[1])) {
                FileSystem::getFilesWalk(
                    $tPath[1],
                    "*.php",
                    true,
                    [$this, "scanFile"],
                    $tPath[0]
                );
            }
        }

        $this->annotationMap = $this->annotationScanner->result;
        unset($this->annotationScanner);
    }

    /**
     * Scans given file to search for classes
     *
     * @param string $uFile             file
     * @param string $uNamespacePrefix  namespace prefix
     *
     * @return void
     */
    public function scanFile($uFile, $uNamespacePrefix)
    {
        $tFileContents = FileSystem::read($uFile);
        $tTokenStream = TokenStream::fromString($tFileContents);

        $this->annotationScanner->process($tTokenStream, $uNamespacePrefix);
    }

    /**
     * Gets annotations
     *
     * @param string $uAnnotation tag of the annotation
     * @param bool   $uIsYaml     whether is in yaml format or not
     *
     * @return Iterator annotations in [class, level, member, value]
     */
    public function get($uAnnotation, $uIsYaml = false)
    {
        return Core::cachedRead(
            "annotation.{$uAnnotation}.{$uIsYaml}",
            function () use ($uAnnotation, $uIsYaml) {
                $tResult = [];

                if ($uIsYaml && $this->parser === null) {
                    $this->parser = new Parser();
                }

                foreach ($this->annotationMap as $tClass => $tAnnotationLevel) {
                    if ($tAnnotationLevel === null) {
                        continue;
                    }

                    foreach ($tAnnotationLevel as $tAnnotationLevelKey => $tMemberAnnotations) {
                        foreach ($tMemberAnnotations as $tAnnotationMemberKey => $tAnnotations) {
                            if (isset($tAnnotations[$uAnnotation])) {
                                $tValue = $tAnnotations[$uAnnotation];

                                if ($uIsYaml) {
                                    foreach ($tValue as &$tValueRef) {
                                        $tValueRef = $this->parser->parse($tValueRef);
                                    }
                                }

                                $tResult[] = [ $tClass, $tAnnotationLevelKey, $tAnnotationMemberKey, $tValue ];
                            }
                        }
                    }
                }

                return $tResult;
            },
            [
                "ttl" => 60 * 60
            ]
        );
    }
}
