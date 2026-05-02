<?php

namespace App\Service;

use App\Entity\ContentNode;
use App\Entity\ContentPath;
use InvalidArgumentException;
use ReflectionClass;

class ContentNodeValidator
{
    public function validate(ContentNode $contentNode): bool
    {
        $title = $contentNode->getTitle();

        if (empty($title) || trim($title) === '') {
            throw new InvalidArgumentException('Title cannot be empty.');
        }

        $reflectionClass = new ReflectionClass($contentNode);
        $assignedUsersProperty = $reflectionClass->getProperty('assignedUsers');
        $assignedUsersProperty->setAccessible(true);

        $assignedUsers = $assignedUsersProperty->getValue($contentNode);
        $decodedAssignedUsers = json_decode($assignedUsers, true);

        if (!is_array($decodedAssignedUsers) || array_filter($decodedAssignedUsers, static fn ($value) => !is_int($value)) !== []) {
            throw new InvalidArgumentException('Assigned users must be a valid array of integers.');
        }

        return true;
    }

    public function validateContentPath(ContentPath $contentPath): bool
    {
        if ($contentPath->getAccessedAt() > new \DateTime()) {
            throw new InvalidArgumentException('Accessed at date cannot be in the future.');
        }

        if ($contentPath->getContentNode() === null) {
            throw new InvalidArgumentException('ContentPath must have a valid ContentNode assigned.');
        }

        if ($contentPath->getUser() === null) {
            throw new InvalidArgumentException('ContentPath must have a valid User assigned.');
        }

        return true;
    }
}
