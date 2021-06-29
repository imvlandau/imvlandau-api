<?php

namespace App\Controller;

use App\Entity\App;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\KeyPairRepository;
use App\Repository\StartupScriptRepository;
use App\Services\Blueimp\UploadHandler;
use Aws\Pricing\PricingClient;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Editor controller
 *
 * @Route("/api")
 */
class ApiEditorController extends ApiBaseController
{
    private $exclude;
    private $boxTreeMaxDepth;
    private $uploadMaxNumberOfFiles;
    private $uploadMaxFileSize;
    private $uploadAcceptFileTypes;

    public function __construct(
        TranslatorInterface $translator,
        $exclude = "",
        $boxTreeMaxDepth = false,
        $uploadMaxNumberOfFiles = 20,
        $uploadMaxFileSize = 8388608,
        $uploadAcceptFileTypes = "/\.(gif|jpe?g|png)$/i",
        $locations = [],
        $operatingSystems = [],
        $products = []
    ) {
        parent::__construct(
            $products
        );
        $this->translator = $translator;
        $this->exclude = $exclude;
        $this->boxTreeMaxDepth = $boxTreeMaxDepth;
        $this->uploadMaxNumberOfFiles = $uploadMaxNumberOfFiles;
        $this->uploadMaxFileSize = $uploadMaxFileSize;
        $this->uploadAcceptFileTypes = $uploadAcceptFileTypes;
        $this->locations = $locations;
        $this->operatingSystems = $operatingSystems;
        $this->products = $products;
    }

    /**
     * Fetch app, trees and initial state
     *
     * @Rest\Get("/{shortid}/app/fetch", name="api_app_fetch")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function fetchApp(Request $request, $shortid, App $app)
    {
        $exclude = $this->exclude;
        $boxTreeMaxDepth = $this->boxTreeMaxDepth;
        $boxTreeFlat[] = [
            "children" => [],
            "expanded" => true,
            "name" => $shortid,
            "path" => "..",
            "pathname" => $shortid,
            "type" => "dir",
        ];
        $children = [];

        try {
            $children = $this->scandir($shortid, $boxTreeMaxDepth, $exclude);
        } catch (\UnexpectedValueException $e) {
            throw new ApiProblemException(new ApiProblem(400, "Unable to open target path: " . $shortid));
        } catch (\RuntimeException $e) {
            throw new ApiProblemException(new ApiProblem(400, "Target path may not be empty"));
        }

        return [
            "boxTreeFlat" => array_merge($boxTreeFlat, $children),
            "boxTreeMaxDepth" => $boxTreeMaxDepth,
        ];
    }

    /**
     * Get specific child nodes
     *
     * @Rest\Post("/{shortid}/get/children", name="api_children_get")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function getChildren(Request $request, $shortid, App $app)
    {
        $pathname = $request->request->get('pathname');
        $depth = $request->request->get('depth', 0);
        $exclude = $this->exclude;
        $children = [];

        try {
            $children = $this->scandir($pathname, $depth, $exclude);
        } catch (\Throwable $e) {
            // could be caused by trying to scan non-existant directories
        }

        return $children;
    }

    /**
     * Delete children of directory
     *
     * @Rest\Delete("/{shortid}/children/delete",name="api_children_delete")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function deleteChildren(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }

        $operation = function ($pathname) {
            // delete children of directory in file system
            $this->removeChildren($pathname);
        };

        $operation($pathname);
    }

    /**
     * Import target directory into database as new app
     *
     * @Rest\Get("/import/{id}", name="api_import")
     * @Entity("app",
     *   options={
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function import(Request $request, $id, App $app = null)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $depth = $request->request->get('depth', 0);
        $exclude = $this->exclude;

        if (!empty($app)) {
            throw new ApiProblemException(new ApiProblem(400, "Target app already exists"));
        }

        // create array of file system
        $tree = $this->scandir($id, $depth, $exclude);

        // create new database entry
        $app = new App();
        $app->setShortid($id);
        $app->setTitle($id);
        $app->setTree($tree);
        $entityManager->persist($app);
        $entityManager->flush();

        return $tree;
    }

    /**
     * Create file
     *
     * @Rest\Post("/{shortid}/file/create",name="api_file_create")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function createFile(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $name = $request->request->get('name', 'new file');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(400, "Filename is required"));
        }

        $operation = function ($pathname, $name) {
            // create file in file system
            $this->touch($pathname, $name);
        };

        $operation($pathname, $name);
    }

    /**
     * Delete file
     *
     * @Rest\Delete("/{shortid}/file/delete",name="api_file_delete")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function deleteFile(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }

        $operation = function ($pathname) {
            // delete file in file system
            $this->removeFile($pathname);
        };

        $operation($pathname);
    }

    /**
     * Duplicate file
     *
     * @Rest\Post("/{shortid}/file/duplicate",name="api_file_duplicate")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function duplicateFile(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $path = $request->request->get('path');
        $name = $request->request->get('name', 'new file');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(400, "Filename is required"));
        }

        $operation = function ($pathname, $path, $name) {
            // duplicate file in file system
            $this->copyFile($pathname, $path, $name);
        };

        $operation($pathname, $path, $name);

        return [
            "children" => [],
            "name" => $name,
            "path" => $path,
            "pathname" => "$path/$name",
            "type" => "file",
        ];
    }

    /**
     * Rename file
     *
     * @Rest\Post("/{shortid}/file/rename",name="api_file_rename")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function renameFileAction(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $path = $request->request->get('path');
        $name = $request->request->get('name', 'new file');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(400, "Filename is required"));
        }

        $operation = function ($pathname, $path, $name) {
            // rename file in file system
            $this->renameFile($pathname, $path, $name);
        };

        $operation($pathname, $path, $name);

        return [
            "children" => [],
            "name" => $name,
            "path" => $path,
            "pathname" => "$path/$name",
            "type" => "file",
        ];
    }

    /**
     * Move file
     *
     * @Rest\Post("/{shortid}/file/move",name="api_file_move")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function moveFileAction(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $path = $request->request->get('path');
        $name = $request->request->get('name');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(400, "Parent pathname is required"));
        }
        if (empty($path)) {
            throw new ApiProblemException(new ApiProblem(500, "Path is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(500, "Name is required"));
        }

        $operation = function ($pathname, $path, $name) {
            // move file in file system
            $this->moveFile($pathname, $path, $name);
        };

        $operation($pathname, $path, $name);

        return [
            "children" => [],
            "expanded" => true,
            "name" => $name,
            "path" => $pathname,
            "pathname" => "$pathname/$name",
            "type" => "file",
        ];
    }

    /**
     * Upload file
     *
     * @Rest\Post("/{shortid}/file/upload", name="api_file_upload")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     */
    public function uploadFileAction(
        Request $request,
        $shortid,
        App $app
    ) {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';
        $pathname = $request->request->get('pathname');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(400, "Pathname is required"));
        }

        if (!$filesystem->exists("$userDataDir/$pathname")) {
            throw new ApiProblemException(new ApiProblem(400, "Target folder does not exist"));
        }

        if (!count($request->files->get('files'))) {
            throw new ApiProblemException(new ApiProblem(400, "No files uploaded"));
        }

        $boxTree = [];
        $pathnameSplitted = explode("/", $pathname);
        foreach ($request->files->get('files') as $file) {
            $p = "";
            $pn = "";
            for ($i = 0; $i < count($pathnameSplitted); $i++) {
                $name = $pathnameSplitted[$i];
                $pn = "$p/$name";
                $boxTree[] = [
                    "children" => [],
                    "name" => $name,
                    "path" => $i === 0 ? ".." : $p,
                    "pathname" => $i === 0 ? $name : $pn,
                    "type" => "dir",
                ];
                $p = $i === 0 ? $name : "$p/$name";
            }
            $name = $file->getClientOriginalName();
            $boxTree[] = [
                "children" => [],
                "name" => $name,
                "path" => $pathname,
                "pathname" => "$pathname/$name",
                "type" => "file",
            ];
        }

        $operation = function ($pathname) use ($userDataDir) {
            $targetDir = "$userDataDir/$pathname";
            $fileUploader = new UploadHandler([
                'upload_dir' => "$targetDir/",
                'upload_url' => "/",
                'max_number_of_files' => $this->uploadMaxNumberOfFiles,
                'max_file_size' => $this->uploadMaxFileSize,
                'accept_file_types' => $this->uploadAcceptFileTypes,
                'print_response' => false,
            ]);
            $fileUploader->head();
        };

        $operation($pathname);

        return $boxTree;
    }

    /**
     * Create directory
     *
     * @Rest\Post("/{shortid}/directory/create",name="api_directory_create")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function createDirectory(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $name = $request->request->get('name', 'new folder');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(400, "Folder name is required"));
        }

        $operation = function ($pathname, $name) {
            // create directory in file system
            $this->mkdir($pathname, $name);
        };

        $operation($pathname, $name);
    }

    /**
     * Delete directory
     *
     * @Rest\Delete("/{shortid}/directory/delete",name="api_directory_delete")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function deleteDirectory(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }

        $operation = function ($pathname) {
            // delete directory in file system
            $this->removeDirectory($pathname);
        };

        $operation($pathname);
    }

    /**
     * Duplicate directory
     *
     * @Rest\Post(
     *   "/{shortid}/directory/duplicate",
     *   name="api_directory_duplicate"
     * )
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function duplicateDirectory(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $path = $request->request->get('path');
        $name = $request->request->get('name', 'new folder');
        $depth = $request->request->get('depth', 0);
        $exclude = $this->exclude;

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(400, "Filename is required"));
        }

        $operation = function ($pathname, $path, $name) {
            // duplicate directory in file system
            $this->copyDirectory($pathname, $path, $name);
        };

        $operation($pathname, $path, $name);

        $boxTree[] = [
            "children" => [],
            "expanded" => true,
            "name" => $name,
            "path" => $path,
            "pathname" => "$path/$name",
            "type" => "dir",
        ];
        $boxTree = array_merge($boxTree, $this->scandir("$path/$name", $depth, $exclude));
        return $boxTree;
    }

    /**
     * Rename directory
     *
     * @Rest\Post("/{shortid}/directory/rename",name="api_directory_rename")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function renameDirectoryAction(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $path = $request->request->get('path');
        $name = $request->request->get('name', 'new folder');
        $depth = $request->request->get('depth', 0);
        $exclude = $this->exclude;

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(500, "Pathname is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(400, "Filename is required"));
        }

        $operation = function ($pathname, $path, $name) {
            // rename directory in file system
            $this->renameDirectory($pathname, $path, $name);
        };

        $operation($pathname, $path, $name);

        $boxTree[] = [
            "children" => [],
            "expanded" => true,
            "name" => $name,
            "path" => $path,
            "pathname" => "$path/$name",
            "type" => "dir",
        ];
        $boxTree = array_merge($boxTree, $this->scandir("$path/$name", $depth, $exclude));
        return $boxTree;
    }

    /**
     * Move directory
     *
     * @Rest\Post("/{shortid}/directory/move", name="api_directory_move")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function moveDirectoryAction(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $path = $request->request->get('path');
        $name = $request->request->get('name');
        $depth = $request->request->get('depth', 0);
        $exclude = $this->exclude;

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(400, "Parent pathname is required"));
        }
        if (empty($path)) {
            throw new ApiProblemException(new ApiProblem(500, "Path is required"));
        }
        if (empty($name)) {
            throw new ApiProblemException(new ApiProblem(500, "Name is required"));
        }

        $operation = function ($pathname, $path, $name) {
            // move directory in file system
            $this->moveDirectory($pathname, $path, $name);
        };

        $operation($pathname, $path, $name);

        $boxTree[] = [
            "children" => [],
            "expanded" => true,
            "name" => $name,
            "path" => $pathname,
            "pathname" => "$pathname/$name",
            "type" => "dir",
        ];
        $boxTree = array_merge($boxTree, $this->scandir("$pathname/$name", $depth, $exclude));
        return $boxTree;
    }

    /**
     * Fetch content of target file
     *
     * @Rest\Get("/{shortid}/fetch/content", name="api_content_fetch")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function fetchContent(Request $request, $shortid, App $app)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';
        $pathname = $request->query->get('pathname');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(400, "Pathname is required"));
        }

        if (!$filesystem->exists("$userDataDir/$pathname")) {
            throw new ApiProblemException(new ApiProblem(400, "File \"$pathname\" does not exist"));
        }

        return new BinaryFileResponse("$userDataDir/$pathname");
    }

    /**
     * Update content of target file
     *
     * @Rest\Post("/{shortid}/update/content", name="api_content_update")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function updateContent(Request $request, $shortid, App $app)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';
        $pathname = $request->request->get('pathname');
        $content = $request->request->get('content');

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(400, "Pathname is required"));
        }

        if (!isset($content)) {
            throw new ApiProblemException(new ApiProblem(400, "Content is required"));
        }

        $filesystem->dumpFile("$userDataDir/$pathname", $content);
    }

    /**
     * Refresh directory
     *
     * @Rest\Post("/{shortid}/refresh", name="api_refresh")
     * @Entity("app",
     *   options={
     *     "id" : "shortid",
     *     "repository_method" : "findOneByShortid"
     *   }
     * )
     *
     * @return Response
     */
    public function refresh(
        Request $request,
        $shortid,
        App $app
    ) {
        $pathname = $request->request->get('pathname');
        $depth = $request->request->get('depth', null);
        $exclude = $this->exclude;

        if (empty($pathname)) {
            throw new ApiProblemException(new ApiProblem(400, "Parent pathname is required"));
        }

        $operation = function ($pathname, $depth) use ($exclude) {
            // refresh directory
            return $this->scandir($pathname, $depth, $exclude);
        };

        $boxTree = $operation($pathname, $depth);

        return $boxTree;
    }

    /**
     * Get available options of the infrastructure
     *
     * @Rest\Get("/get/infrastructure/options", name="api_infrastructure_options_get")
     *
     * @return Response
     */
    public function getInfrastructureOptions(
        Request $request,
        PricingClient $client,
        KeyPairRepository $keyPairRepository,
        StartupScriptRepository $startupScriptRepository
    ) {
        set_time_limit(300);

        $region = $request->request->get('region', 'us-east-2');
        $platform = $request->request->get('platform', 'Linux'); // Linux, Windows, MacOSX, FreeBSD
        $products = $this->getProducts($region, $platform, $client);
        $startupScripts = $startupScriptRepository->findAll();
        $keyPairs = $keyPairRepository->findAll();

        return [
            "hardwarePriceList" => $products,
            "startupScripts" => $startupScripts,
            "keyPairs" => $keyPairs,
            "locations" => $this->locations,
            "operatingSystems" => $this->operatingSystems,
        ];
    }

    /**
     * Get price list of available hardware sizes
     *
     * @Rest\Post("/get/hardware/price/list", name="api_hardware_price_list_get")
     *
     * @return Response
     */
    public function getHardwarePriceList(Request $request, PricingClient $client)
    {
        set_time_limit(300);

        $region = $request->request->get('region', 'us-east-2');
        $platform = $request->request->get('platform', 'Linux'); // Linux, Windows, MacOSX, FreeBSD

        return $this->getProducts($region, $platform, $client);
    }
}
