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

use Tobento\App\Block\ResourceInterface;
use Tobento\App\Block\ResourceResolverInterface;

/**
 * Composite
 */
final class Composite implements ResourceResolverInterface
{
    /**
     * @var array<array-key, ResourceResolverInterface>
     */
    private array $resolvers = [];
    
    /**
     * Create a new Composite.
     *
     * @param ResourceResolverInterface ...$resolvers
     */
    public function __construct(
        ResourceResolverInterface ...$resolvers,
    ) {
        $this->resolvers = $resolvers;
    }
    
    /**
     * Returns the resolved resource or null.
     *
     * @return null|ResourceInterface
     */
    public function resolve(): null|ResourceInterface
    {
        foreach($this->resolvers as $resolver) {
            if (!is_null($resource = $resolver->resolve())) {
                return $resource;
            }
        }

        return null;
    }
}