<?php

namespace App\Tests\Service;

use App\Entity\ContentNode;
use App\Service\ContentNodeValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ContentNodeValidatorTest extends TestCase
{
    public function testValidContentNode(): void
    {
        $contentNode = new ContentNode();
        $contentNode->setTitle('Anxiety Management');
        $contentNode->setAssignedUsers([7, 10]);

        $validator = new ContentNodeValidator();

        $this->assertTrue($validator->validate($contentNode));
    }

    public function testContentNodeWithoutTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $contentNode = new ContentNode();
        $contentNode->setTitle('');
        $contentNode->setAssignedUsers([7]);

        $validator = new ContentNodeValidator();
        $validator->validate($contentNode);
    }

    public function testContentNodeWithInvalidAssignedUsers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $contentNode = new ContentNode();
        $contentNode->setTitle('Valid Title');

        $reflectionClass = new ReflectionClass($contentNode);
        $assignedUsersProperty = $reflectionClass->getProperty('assignedUsers');
        $assignedUsersProperty->setAccessible(true);
        $assignedUsersProperty->setValue($contentNode, 'not-valid-json');

        $validator = new ContentNodeValidator();
        $validator->validate($contentNode);
    }
}