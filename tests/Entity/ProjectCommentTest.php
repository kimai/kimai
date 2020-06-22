<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\CommentInterface;
use App\Entity\Project;
use App\Entity\ProjectComment;

/**
 * @covers \App\Entity\ProjectComment
 * @covers \App\Entity\CommentTableTypeTrait
 */
class ProjectCommentTest extends AbstractCommentEntityTest
{
    protected function getEntity(): CommentInterface
    {
        return new ProjectComment();
    }

    public function testEntitySpecificMethods()
    {
        $sut = new ProjectComment();
        self::assertNull($sut->getProject());

        $project = new Project();
        self::assertInstanceOf(ProjectComment::class, $sut->setProject($project));
        self::assertSame($project, $sut->getProject());
    }
}
