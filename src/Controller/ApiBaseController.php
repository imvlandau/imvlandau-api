<?php

namespace App\Controller;

use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\Pricing\Exception as PricingException;
use Aws\Pricing\PricingClient;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Intl\Currencies;

class ApiBaseController extends FOSRestController
{
    /**
     * Map of AWS region codes to location names used by \Aws\Pricing\PricingClient.
     * Full list at: https://docs.aws.amazon.com/general/latest/gr/rande.html
     */
    const REGION_LOCATIONS = [
        'us-east-1' => 'US East (N. Virginia)', // Price list API available
        'us-east-2' => 'US East (Ohio)',
        'us-west-1' => 'US West (N. California)',
        'us-west-2' => 'US West (Oregon)',
        'ap-east-1' => 'Asia Pacific (Hong Kong)', // EC2 API not available
        'ap-south-1' => 'Asia Pacific (Mumbai)', // Price list API available
        'ap-northeast-3' => 'Asia Pacific (Osaka-Local)', // EC2 API not available
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ca-central-1' => 'Canada (Central)',
        'eu-central-1' => 'EU (Frankfurt)',
        'eu-west-1' => 'EU (Ireland)',
        'eu-west-2' => 'EU (London)',
        'eu-west-3' => 'EU (Paris)',
        'eu-north-1' => 'EU (Stockholm)',
        'me-south-1' => 'Middle East (Bahrain)', // EC2 API not available
        'sa-east-1' => 'South America (São Paulo)',
    ];

    /**
     * OfferTermCodes
     * https://cloudbanshee.com/blog/decoding-theaec2-pricing-api
     */
    const OFFER_TERM_CODES = [
        'ON_DEMAND' => 'JRTCKXETXF',
        'ALL_UPFRONT' => '6QCMYABX3D',
        'PARTIAL_UPFRONT' => 'R5XV2EPZQZ',
        'NO_UPFRONT' => 'Z2E3P23VKM',
    ];

    /**
     * PriceDimension
     * https://cloudbanshee.com/blog/decoding-theaec2-pricing-api
     */
    const PRICE_DIMENSIONS = [
        'PRICE_PER_HOUR' => '6YS6EN2CT7',
        'UPFRONT_FEE' => '2TG2D8R56U',
    ];

    protected $products;

    public function __construct(
        $products = []
    ) {
        $this->products = $products;
    }

    protected function scandir(
        $pathname = "",
        $maxDepth = null,
        $regexExclude = "//"
    ) {
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        chdir($userDataDir);
        return $this->scandirFlattened($pathname, $maxDepth, $regexExclude);
    }

    private function scandirFlattened($dir, $maxDepth = null, $regexExclude = "//", &$filedata = [], $level = 0)
    {
        if (!$dir instanceof \FilesystemIterator) {
            $dir = new \FilesystemIterator((string) $dir,
                \FilesystemIterator::KEY_AS_PATHNAME |
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::SKIP_DOTS |
                \FilesystemIterator::UNIX_PATHS
            );
        }
        foreach ($dir as $node) {
            $name = $node->getFilename();
            if ($node->isDir()) {
                $pathname = $node->getPathname();
                if ($regexExclude === "//" || !preg_match($regexExclude, $name)) {
                    $filedata[] = [
                        "children" => [],
                        "name" => $name,
                        "path" => $node->getPath(),
                        "pathname" => $pathname,
                        'type' => $node->getType(),
                    ];
                    if (!isset($maxDepth) || $level < $maxDepth) {
                        $this->scandirFlattened($pathname, $maxDepth, $regexExclude, $filedata, $level + 1);
                    }
                }
            } elseif (
                $node->isFile() && ($regexExclude === "//" || !preg_match($regexExclude, $name))
            ) {
                $pathname = $node->getPathname();
                $filedata[] = [
                    "children" => [],
                    "name" => $name,
                    "path" => $node->getPath(),
                    "pathname" => $pathname,
                    'type' => $node->getType(),
                ];
            }
        }
        return $filedata;
    }

    public function touch($path, $name, $content = "")
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';
        $pathname = "$path/$name";

        $this->validateName($name);

        if ($filesystem->exists("$userDataDir/$pathname")) {
            throw new ApiProblemException(new ApiProblem(400, "Target file already exists"));
        }

        try {
            // create file
            $filesystem->touch("$userDataDir/$pathname");
            // change permission
            $filesystem->chmod("$userDataDir/$pathname", 0664);
            if (!empty($content)) {
                // set initial content if wanted
                $filesystem->dumpFile("$userDataDir/$pathname", $content);
            }
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function mkdir($path, $name)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';
        $pathname = "$path/$name";

        $this->validateName($name);

        if ($filesystem->exists("$userDataDir/$pathname")) {
            throw new ApiProblemException(new ApiProblem(400, "Target folder already exists"));
        }

        try {
            // create directory
            $filesystem->mkdir("$userDataDir/$pathname", 0775);
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    protected function validateName($name)
    {
        if (!$this->isNameValid($name)) {
            throw new ApiProblemException(new ApiProblem(400, "Invalid characters detected. Following charackters are not allowed: /, \\, ?, %, *, :, |, \", <, >, ., space, DOS device file names"));
        }
    }

    protected function isNameValid($name)
    {
        return preg_match('/^(?!(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\.[^.]*)?$)[^<>:\x22\/\\|?*\x00-\x1F]*[^<>:\x22\/\\|?*\x00-\x1F\ .]$/iD', $name);
    }

    protected function removeFile($pathname)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        try {
            // delete file
            $filesystem->remove("$userDataDir/$pathname");
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    protected function removeDirectory($pathname)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        try {
            // delete directory
            $filesystem->remove("$userDataDir/$pathname");
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    protected function removeChildren($pathname)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        try {
            $children = $this->scandir($pathname, 0);
            foreach ($children as $child) {
                // delete file or directory
                $filesystem->remove($userDataDir . "/" . $child['pathname']);
            }
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function copyFile($pathname, $path, $name)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        $this->validateName($name);

        if ($filesystem->exists("$userDataDir/$path/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target file already exists"));
        }

        try {
            // copy file
            $filesystem->copy("$userDataDir/$pathname", "$userDataDir/$path/$name");
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function copyDirectory($pathname, $path, $name)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        $this->validateName($name);

        if ($filesystem->exists("$userDataDir/$path/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target folder already exists"));
        }

        try {
            // copy directory
            $filesystem->mirror("$userDataDir/$pathname", "$userDataDir/$path/$name");
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function renameFile($pathname, $path, $name)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        $this->validateName($name);

        if ($filesystem->exists("$userDataDir/$path/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target file already exists"));
        }

        try {
            // rename file
            $filesystem->rename(
                "$userDataDir/$pathname",
                "$userDataDir/$path/$name"
            );
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function renameDirectory($pathname, $path, $name)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        $this->validateName($name);

        if ($filesystem->exists("$userDataDir/$path/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target folder already exists"));
        }

        try {
            // rename directory
            $filesystem->rename(
                "$userDataDir/$pathname",
                "$userDataDir/$path/$name"
            );
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function moveFile($pathname, $path, $name)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        $this->validateName($name);

        if (!$filesystem->exists("$userDataDir/$pathname")) {
            throw new ApiProblemException(new ApiProblem(400, "Target parent does not exist"));
        }
        if (!$filesystem->exists("$userDataDir/$path/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target file does not exist"));
        }
        if ($filesystem->exists("$userDataDir/$pathname/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target file already exists"));
        }

        try {
            // move file
            $filesystem->rename(
                "$userDataDir/$path/$name",
                "$userDataDir/$pathname/$name"
            );
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function moveDirectory($pathname, $path, $name)
    {
        $filesystem = new Filesystem();
        $projectDir = $this->get('kernel')->getProjectDir();
        $userDataDir = $projectDir . '/var/userData';

        $this->validateName($name);

        if (!$filesystem->exists("$userDataDir/$pathname")) {
            throw new ApiProblemException(new ApiProblem(400, "Target parent does not exist"));
        }
        if (!$filesystem->exists("$userDataDir/$path/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target directory does not exist"));
        }
        if ($filesystem->exists("$userDataDir/$pathname/$name")) {
            throw new ApiProblemException(new ApiProblem(400, "Target directory already exists"));
        }

        try {
            // move directory
            $filesystem->rename(
                "$userDataDir/$path/$name",
                "$userDataDir/$pathname/$name"
            );
        } catch (IOExceptionInterface $ex) {
            throw new ApiProblemException(new ApiProblem(500, $ex->getMessage()));
        }
    }

    public function getProducts($region = 'us-east-2', $platform = 'Linux', PricingClient $client)
    {
        $symbol = Currencies::getSymbol('USD');
        $offerTermCode = self::OFFER_TERM_CODES['ON_DEMAND'];
        $priceDimension = self::PRICE_DIMENSIONS['PRICE_PER_HOUR'];
        $result = [];

        try {
            foreach ($this->products as $instanceType => $attributes) {
                $matches = $client->getProducts([
                    'ServiceCode' => 'AmazonEC2',
                    'FormatVersion' => 'aws_v1',
                    'Filters' => [
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'instanceType',
                            'Value' => $instanceType,
                        ],
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'servicecode',
                            'Value' => 'AmazonEC2',
                        ],
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'location',
                            'Value' => self::REGION_LOCATIONS[$region],
                        ],
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'operatingSystem',
                            'Value' => $platform,
                        ],
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'termType',
                            'Value' => 'OnDemand',
                        ],
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'tenancy',
                            'Value' => 'Shared',
                        ],
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'capacitystatus',
                            'Value' => 'Used',
                        ],
                        [
                            'Type' => 'TERM_MATCH',
                            'Field' => 'preInstalledSw',
                            'Value' => 'NA',
                        ],
                    ],
                ]);
                foreach ($matches['PriceList'] as $product) {
                    $productArray = json_decode($product, true);
                    $sku = $productArray['product']['sku'];
                    $pricePerUnit = $productArray['terms']['OnDemand']["$sku.$offerTermCode"]['priceDimensions']["$sku.$offerTermCode.$priceDimension"]['pricePerUnit']['USD'];
                    $priceHourly = round($pricePerUnit, 4);
                    $priceMonthly = number_format((float) ceil($priceHourly * 24 * 31), 2);
                    $result[] = array_merge([
                        'instanceType' => $instanceType,
                        'memory' => $productArray['product']['attributes']['memory'],
                        'vcpu' => $productArray['product']['attributes']['vcpu'],
                        'priceHourly' => "$symbol$priceHourly",
                        'priceMonthly' => "$symbol$priceMonthly",
                    ], $attributes);
                }
            }
            return $result;
        } catch (CredentialsException $e) {
            throw new ApiProblemException(new ApiProblem(400, $e->getMessage()));
        } catch (PricingException $e) {
            throw new ApiProblemException(new ApiProblem(400, $e->getMessage()));
        } catch (AwsException $e) {
            throw new ApiProblemException(new ApiProblem(400, $e->getMessage()));
        }
    }
}
