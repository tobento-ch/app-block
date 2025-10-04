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

namespace Tobento\App\Block\Console;

use Psr\Clock\ClockInterface;
use Tobento\App\Block\Controller\BlockEditorController;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Translation\TranslatorInterface;

/**
 * PurgeBlocksCommand
 */
class PurgeBlocksCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        blocks:purge | Deletes unused blocks.
    ';

    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param EditorsInterface $editors
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param RouterInterface $router
     * @param TranslatorInterface $translator
     * @param ActionProcessorInterface $actionProcessor
     * @param ClockInterface $clock
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     */
    public function handle(
        InteractorInterface $io,
        EditorsInterface $editors,
        RequesterInterface $requester,
        ResponserInterface $responser,
        RouterInterface $router,
        TranslatorInterface $translator,
        ActionProcessorInterface $actionProcessor,
        ClockInterface $clock,
    ): int {
        foreach($editors->names() as $editorName) {
            $editor = $editors->get($editorName);
            
            $request = $requester->request()->withMethod('POST')->withParsedBody([
                'editor' => $editor->name(),
            ]);
            
            $requester = new Requester($request);

            $blockEditorController = new BlockEditorController(
                editors: $editors,
                requester: $requester,
                translator: $translator,
                router: $router,
            );
            
            $blocks = $blockEditorController->repository()->findAll(where: [
                'editor' => $editor->name(),
                'status' => 'pending',
                'created_at' => ['<' => $clock->now()->modify('-1 days')->getTimestamp()],
            ]);

            foreach($blocks as $block) {
                $request = $requester->request()->withParsedBody([
                    'block' => $block->toArray(),
                ]);

                $requester = new Requester($request);            
                
                try {
                    $response = $blockEditorController->deleteBlock(
                        actionProcessor: $actionProcessor,
                        requester: $requester,
                        responser: $responser,
                    );
                    
                    if ($response->getStatusCode() !== 200) {
                        throw new \RuntimeException(
                            sprintf('Deleting block failed with status code %s', $response->getStatusCode())
                        );
                    }
                    
                    $io->success(sprintf('Deleted block with the id %s from editor %s', $block->id(), $editorName));
                } catch (\Throwable $e) {
                    $io->error(sprintf(
                        'Unable to delete block with the id %s from editor %s: %s',
                        $block->id(),
                        $editorName,
                        $e->getMessage(),
                    ));
                }
            }
        }
        
        return static::SUCCESS;
    }
}