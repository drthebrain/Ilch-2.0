<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Comment\Mappers;

use Modules\Comment\Models\Comment as CommentModel;

class Comment extends \Ilch\Mapper
{
    /**
     * @return CommentModel[]
     */
    public function getCommentsByKey($key)
    {
        $commentsArray = $this->db()->select('*')
            ->from('comments')
            ->where(['key' => $key])
            ->order(['id' => 'DESC'])
            ->execute()
            ->fetchRows();

        $comments = [];
        foreach ($commentsArray as $commentRow) {
            $commentModel = new CommentModel();
            $commentModel->setId($commentRow['id']);
            $commentModel->setFKId($commentRow['fk_id']);
            $commentModel->setKey($commentRow['key']);
            $commentModel->setText($commentRow['text']);
            $commentModel->setUserId($commentRow['user_id']);
            $commentModel->setDateCreated($commentRow['date_created']);
            $comments[] = $commentModel;
        }

        return $comments;
    }
	
    public function getCommentsByFKid($key)
    {
        $commentsArray = $this->db()->select('*')
            ->from('comments')
            ->where(['fk_id' => $key])
            ->order(['id' => 'DESC'])
            ->execute()
            ->fetchRows();

        $comments = [];
        foreach ($commentsArray as $commentRow) {
            $commentModel = new CommentModel();
            $commentModel->setId($commentRow['id']);
            $commentModel->setFKId($commentRow['fk_id']);
            $commentModel->setKey($commentRow['key']);
            $commentModel->setText($commentRow['text']);
            $commentModel->setUserId($commentRow['user_id']);
            $commentModel->setDateCreated($commentRow['date_created']);
            $comments[] = $commentModel;
        }

        return $comments;
    }

    /**
     * @return CommentModel[]
     */
    public function getComments()
    {
        $commentsArray = $this->db()->select('*')
            ->from('comments')
            ->order(['id' => 'DESC'])
            ->execute()
            ->fetchRows();

        $comments = [];
        foreach ($commentsArray as $commentRow) {
            $commentModel = new CommentModel();
            $commentModel->setId($commentRow['id']);
            $commentModel->setFKId($commentRow['fk_id']);
            $commentModel->setKey($commentRow['key']);
            $commentModel->setText($commentRow['text']);
            $commentModel->setUserId($commentRow['user_id']);
            $commentModel->setDateCreated($commentRow['date_created']);
            $comments[] = $commentModel;
        }

        return $comments;
    }
    
    /**
     * Gets the counter of all comments with given $key.
     *
     * @return integer
     */
    public function getCountComments($key)
    {
        $sql = 'SELECT COUNT(*)
                FROM `[prefix]_comments`
                WHERE `key` LIKE "'.$key.'%" ';

        $article = $this->db()->queryCell($sql);

        return $article;
    }

    /**
     * @param CommentModel $comment
     */
    public function save(CommentModel $comment)
    {
        $this->db()->insert('comments')
            ->values
            (
                [
                    'key' => $comment->getKey(),
                    'text' => $comment->getText(),
                    'date_created' => $comment->getDateCreated(),
                    'user_id' => $comment->getUserId(),
                    'fk_id' => $comment->getFKId(),
                ]
            )
            ->execute();
    }

    /**
     * @param integer $id
     */
    public function delete($id)
    {
        $this->db()->delete('comments')
            ->where(['id' => $id])
            ->execute();
    }
}
