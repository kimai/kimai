<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\ProjectComment;

/**
 * @covers \App\Entity\ProjectComment
 * @covers \App\Entity\CommentTableTypeTrait
 */
class ProjectCommentTest extends AbstractCommentEntityTest
{
    protected function getEntity(): ProjectComment
    {
        return new ProjectComment(new Project());
    }

    public function testEntitySpecificMethods(): void
    {
        $sut = $this->getEntity();
        self::assertNotNull($sut->getProject());
    }
}
