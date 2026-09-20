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

namespace Tobento\App\Block\Test\Field;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Field\Image;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;
use Tobento\Service\Tag\Attributes;

class ImageTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg'
        );

        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testRenderReturnsPictureOnlyWhenNoFigcaption(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg'
        );

        $output = $field->render();

        $this->assertStringContainsString('<picture', $output);
        $this->assertStringNotContainsString('<figure', $output);
        $this->assertStringNotContainsString('<figcaption', $output);
    }

    public function testRenderAddsAltAttribute(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg',
            imgAlt: 'My Alt'
        );

        $output = $field->render();

        $this->assertStringContainsString('alt="My Alt"', $output);
    }

    public function testRenderResizesImageWhenWidthIsSet(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        // Factory generator produces width=100 height=50 for testing
        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg',
            imgWidth: 50
        );

        $output = $field->render();

        // width forced to 50
        $this->assertStringContainsString('width="50"', $output);

        // height recalculated: original 100x50 → ratio = 0.5 → new height = 25
        $this->assertStringContainsString('height="25"', $output);
    }

    public function testRenderWrapsInFigureWhenFigcaptionIsProvided(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg',
            figcaption: 'Hello'
        );

        $output = $field->render();

        $this->assertStringContainsString('<figure', $output);
        $this->assertStringContainsString('<figcaption', $output);
        $this->assertStringContainsString('Hello', $output);
    }

    public function testRenderIncludesFigureAttributes(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg',
            figcaption: 'Caption'
        );

        $field->withFigureAttributes(['class' => 'my-figure']);

        $output = $field->render();

        $this->assertStringContainsString('<figure class="my-figure"', $output);
    }

    public function testRenderIncludesFigcaptionAttributes(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg',
            figcaption: 'Caption'
        );

        $field->withFigcaptionAttributes(['class' => 'my-cap']);

        $output = $field->render();

        $this->assertStringContainsString('<figcaption class="my-cap"', $output);
    }

    public function testWithDefinitionOverridesDefinition(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg'
        );

        $field->withDefinition('new-def');

        $output = $field->render();

        $this->assertStringContainsString('data-definition="new-def"', $output);
    }

    public function testRenderEditableReturnsSameAsRender(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg'
        );

        $this->assertSame($field->render(), $field->renderEditable());
    }

    public function testValueMethodReturnsAllProperties(): void
    {
        $picture = Factory::createPictureGenerator();
        $view = Factory::createView();

        $field = new Image(
            pictureGenerator: $picture,
            view: $view,
            path: 'foo.jpg',
            resource: 'res',
            definition: 'def',
            imgAlt: 'Alt',
            imgWidth: 80,
            figcaption: 'Cap',
            generateImagesInBackground: false
        );

        $field->withFigureAttributes(['class' => 'fig']);
        $field->withFigcaptionAttributes(['class' => 'cap']);

        $value = $field->value();

        $this->assertSame('foo.jpg', $value['path']);
        $this->assertSame('res', $value['resource']);
        $this->assertSame('def', $value['definition']);
        $this->assertSame('Alt', $value['imgAlt']);
        $this->assertSame(80, $value['imgWidth']);
        $this->assertSame('Cap', $value['figcaption']);
        $this->assertFalse($value['generateImagesInBackground']);
        $this->assertInstanceOf(Attributes::class, $value['figureAttributes']);
        $this->assertInstanceOf(Attributes::class, $value['figcaptionAttributes']);
    }
}