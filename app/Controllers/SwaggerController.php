<?php

namespace App\Controllers;

use OpenApi\Generator;
use OpenApi\Annotations as OA;
use OpenApi\Analysis;
use OpenApi\Context;
use OpenApi\Processors\BuildPaths;

/**
 * Swagger Documentation Controller
 * 
 * Generates and serves OpenAPI/Swagger documentation
 */
class SwaggerController extends BaseController
{
    /**
     * Generate and return Swagger JSON
     */
    public function index()
    {
        try {
            $apiPath = realpath(APPPATH . 'Controllers/Api');
            
            if (!$apiPath) {
                throw new \Exception('API controllers directory not found');
            }
            
            // Note: doctrine/annotations 2.0+ doesn't require AnnotationRegistry::registerLoader()
            // Composer's autoloader handles annotation class loading automatically
            
            // Create analysis first - we need it to get operations
            $generator = new Generator();
            $rootContext = new Context(['version' => '3.0.0']);
            $analysis = new Analysis([], $rootContext);
            
            // Generate OpenAPI spec with analysis to get all annotations including operations
            try {
                $openapi = $generator->generate([$apiPath], $analysis, false);
            } catch (\Exception $e) {
                // Try to continue with what we have
                if ($analysis->openapi) {
                    $openapi = $analysis->openapi;
                } else {
                    throw $e;
                }
            }
            
            if (!$openapi) {
                // If generate() returns null, use analysis->openapi or create a new one
                if ($analysis->openapi) {
                    $openapi = $analysis->openapi;
                } else {
                    $openapi = new OA\OpenApi([
                        'openapi' => '3.0.0',
                        '_context' => $rootContext
                    ]);
                    $analysis->openapi = $openapi;
                }
            }
            
            // Manually add Info if not found
            if (!$openapi->info || $openapi->info === Generator::UNDEFINED) {
                $openapi->info = new OA\Info([
                    'title' => 'RestApp API',
                    'version' => '1.0.0',
                    'description' => 'RESTful API for RestApp application',
                    '_context' => new Context(['generated' => true], $rootContext)
                ]);
            }
            
            // Manually add Server if not found
            if (!$openapi->servers || $openapi->servers === Generator::UNDEFINED || empty($openapi->servers)) {
                $openapi->servers = [
                    new OA\Server([
                        'url' => base_url(),
                        'description' => 'API Server',
                        '_context' => new Context(['generated' => true], $rootContext)
                    ])
                ];
            }
            
            // Get all operations from analysis (both merged and unmerged)
            $mergedOperations = $analysis->getAnnotationsOfType(OA\Operation::class);
            $unmergedOperations = $analysis->unmerged()->getAnnotationsOfType(OA\Operation::class);
            $allOperations = array_merge($mergedOperations, $unmergedOperations);
            
            // First, try to use BuildPaths processor if paths are empty
            if (empty($openapi->paths) || $openapi->paths === Generator::UNDEFINED || (is_array($openapi->paths) && count($openapi->paths) === 0)) {
                $buildPaths = new BuildPaths();
                $buildPaths($analysis);
                
                // Check if paths were created by the processor
                if ($analysis->openapi && isset($analysis->openapi->paths) && $analysis->openapi->paths !== Generator::UNDEFINED) {
                    if (is_array($analysis->openapi->paths) && !empty($analysis->openapi->paths)) {
                        $openapi->paths = $analysis->openapi->paths;
                    }
                }
            }
            
            // If still empty, manually build from operations
            if (empty($openapi->paths) || $openapi->paths === Generator::UNDEFINED || (is_array($openapi->paths) && count($openapi->paths) === 0)) {
                if (!empty($allOperations)) {
                    $paths = [];
                    
                    foreach ($allOperations as $operation) {
                        if (!empty($operation->path)) {
                            $pathKey = $operation->path;
                            
                            if (!isset($paths[$pathKey])) {
                                $paths[$pathKey] = new OA\PathItem([
                                    'path' => $pathKey,
                                    '_context' => $operation->_context ?? new Context(['generated' => true], $rootContext)
                                ]);
                            }
                            
                            // Merge operation into path item
                            $paths[$pathKey]->merge([$operation]);
                        }
                    }
                    
                    if (!empty($paths)) {
                        $openapi->paths = array_values($paths);
                    } else {
                        $openapi->paths = [];
                    }
                } else {
                    $openapi->paths = [];
                }
            }
            
            $json = $openapi->toJson(JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            
            return $this->response
                ->setContentType('application/json')
                ->setBody($json);
                
        } catch (\Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setBody(json_encode([
                    'error' => true,
                    'message' => 'Failed to generate Swagger documentation',
                    'details' => $e->getMessage()
                ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Serve Swagger UI
     */
    public function ui()
    {
        $swaggerJsonUrl = base_url('swagger.json');
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RestApp API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{$swaggerJsonUrl}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
HTML;

        return $this->response->setBody($html);
    }
}

