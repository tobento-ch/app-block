<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Block\ResourceResolver;

use Tobento\App\Block\Resource;
use Tobento\App\Block\ResourceInterface;
use Tobento\App\Block\ResourceResolverInterface;
use Tobento\Service\Routing\RouterInterface;

/**
 * Slugs
 */
final class Slugs implements ResourceResolverInterface
{
    /**
     * Create a new Slugs instance.
     *
     * @param RouterInterface $router
     */
    public function __construct(
        private RouterInterface $router,
    ) {}
    
    /**
     * Returns the resolved resource or null.
     *
     * @return null|ResourceInterface
     */
    public function resolve(): null|ResourceInterface
    {
        $route = $this->router->getMatchedRoute();
        
        if (is_null($route)) {
            return null;
        }
        
        $resourceId = (string)$route->getParameter('slug.resourceId');
        $resourceKey = (string)$route->getParameter('slug.resourceKey');

        return new Resource(id: trim(sprintf('%s:%s', $resourceKey, $resourceId), ':'));
    }
}