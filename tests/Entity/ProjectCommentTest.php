<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\CommentTableTypeTrait;
use App\Entity\Project;
use App\Entity\ProjectComment;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CommentTableTypeTrait::class)]
#[CoversClass(ProjectComment::class)]
class ProjectCommentTest extends AbstractCommentEntityTestCase
{
    protected function getEntity(): ProjectComment
    {
        return new ProjectComment(new Project());
    }
}
